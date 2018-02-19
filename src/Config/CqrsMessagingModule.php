<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\AnnotationClassesWithMethodFinder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateCallingCommandHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class IntegrationMessagingCqrsModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="cqrsModule")
 */
class CqrsMessagingModule implements AnnotationConfiguration
{
    const MESSAGING_CQRS_MESSAGE_CHANNEL = "messaging.cqrs.message";
    const MESSAGING_CQRS_COMMAND_CHANNEL = "messaging.cqrs.command";
    const MESSAGING_CQRS_QUERY_CHANNEL = "messaging.cqrs.query";
    const MESSAGING_CQRS_MESSAGE_TYPE_HEADER = "messaging.cqrs.message.type";
    const MESSAGING_CQRS_MESSAGE_TYPE_HEADER_COMMAND = "command";
    const MESSAGING_CQRS_MESSAGE_TYPE_HEADER_QUERY = "query";
    /**
     * @var ConfigurationVariableRetrievingService
     */
    private $configurationVariableRetrievingService;

    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;
    /**
     * @var array|AggregateRepositoryExtension[]
     */
    private $moduleExtensions;

    /**
     * AnnotationGatewayConfiguration constructor.
     * @param array|AggregateRepositoryExtension[] $moduleExtensions
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     */
    private function __construct(array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader)
    {
        $this->configurationVariableRetrievingService = $configurationVariableRetrievingService;
        $this->classLocator = $classLocator;
        $this->classMetadataReader = $classMetadataReader;
        $this->moduleExtensions = $moduleExtensions;
    }

    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(array $moduleConfigurationExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        return new self($moduleConfigurationExtensions, $configurationVariableRetrievingService, $classLocator, $classMetadataReader);
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {
        $annotationMessageEndpointConfigurationFinder = new AnnotationClassesWithMethodFinder($this->classLocator, $this->classMetadataReader);
        $parameterConvertAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::MESSAGING_CQRS_MESSAGE_CHANNEL));
        $configuration->registerMessageHandler(RouterBuilder::createHeaderValueRouter(
            "messaging.cqrs.message.router",
            self::MESSAGING_CQRS_MESSAGE_CHANNEL,
            self::MESSAGING_CQRS_MESSAGE_TYPE_HEADER,
            [
                self::MESSAGING_CQRS_MESSAGE_TYPE_HEADER_COMMAND => self::MESSAGING_CQRS_COMMAND_CHANNEL,
                self::MESSAGING_CQRS_MESSAGE_TYPE_HEADER_QUERY => self::MESSAGING_CQRS_QUERY_CHANNEL
            ]
        ));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::MESSAGING_CQRS_COMMAND_CHANNEL));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::MESSAGING_CQRS_QUERY_CHANNEL));

        $payloadTypeRouter = PayloadTypeRouter::create();
        $configuration->registerMessageHandler(RouterBuilder::createRouterFromObject("messaging.cqrs.command.router", self::MESSAGING_CQRS_COMMAND_CHANNEL, $payloadTypeRouter, "route"));
        $configuration->registerMessageHandler(RouterBuilder::createRouterFromObject("messaging.cqrs.query.router", self::MESSAGING_CQRS_QUERY_CHANNEL, $payloadTypeRouter, "route"));

        foreach ($annotationMessageEndpointConfigurationFinder->findFor(MessageEndpointAnnotation::class, CommandHandlerAnnotation::class) as $annotationRegistration) {
            $this->createHandler($configuration, $annotationRegistration, $parameterConvertAnnotationFactory, false);
        }

        foreach ($annotationMessageEndpointConfigurationFinder->findFor(MessageEndpointAnnotation::class, QueryHandlerAnnotation::class) as $annotationRegistration) {
            $this->createHandler($configuration, $annotationRegistration, $parameterConvertAnnotationFactory, true);
        }

        foreach ($annotationMessageEndpointConfigurationFinder->findFor(AggregateAnnotation::class, CommandHandlerAnnotation::class) as $annotationRegistration) {
            $messageHandlerBuilder = AggregateCallingCommandHandlerBuilder::createWith(
                $this->moduleExtensions[0]->getRepositoryFor($annotationRegistration->getMessageEndpointClass()),
                $annotationRegistration->getMessageEndpointClass(),
                $annotationRegistration->getMethodName()
            )
                ->setConsumerName($annotationRegistration->getMessageEndpointClass() . '-' . $annotationRegistration->getMethodName());


            $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($messageHandlerBuilder->getInputMessageChannelName()));
            $configuration->registerMessageHandler($messageHandlerBuilder);
        }
    }

    /**
     * @inheritDoc
     */
    public function configure(ReferenceSearchService $referenceSearchService): void
    {
        foreach ($this->moduleExtensions as $moduleExtension) {
            $moduleExtension->configure($referenceSearchService);
        }
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }

    /**
     * @param Configuration $configuration
     * @param AnnotationRegistration $annotationRegistration
     * @param ParameterConverterAnnotationFactory $parameterConvertAnnotationFactory
     * @param bool $requiredReply
     */
    private function createHandler(Configuration $configuration, AnnotationRegistration $annotationRegistration, ParameterConverterAnnotationFactory $parameterConvertAnnotationFactory, bool $requiredReply): void
    {
        $interfaceToCall = InterfaceToCall::create($annotationRegistration->getMessageEndpointClass(), $annotationRegistration->getMethodName());
        $inputMessageChannelName = $interfaceToCall->getFirstParameterTypeHint();
        $annotation = $annotationRegistration->getAnnotation();

        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($inputMessageChannelName));
        $messageHandlerBuilder   = ServiceActivatorBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getMethodName())
            ->withRequiredReply($requiredReply)
            ->withInputMessageChannel($inputMessageChannelName)
            ->withConsumerName($annotationRegistration->getReferenceName() . '-' . $annotationRegistration->getMethodName());

        $parameterConvertAnnotationFactory->configureParameterConverters($messageHandlerBuilder, $annotationRegistration->getMessageEndpointClass(), $annotationRegistration->getMethodName(), $annotation->parameterConverters);

        $configuration->registerMessageHandler($messageHandlerBuilder);
    }
}