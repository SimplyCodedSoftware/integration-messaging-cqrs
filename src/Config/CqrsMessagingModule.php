<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepositoryFactory;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class IntegrationMessagingCqrsModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation()
 */
class CqrsMessagingModule implements AnnotationModule, AggregateRepositoryFactory
{
    const INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL = "integration_messaging.cqrs.execute_message";
    const CQRS_MODULE                                          = "cqrsModule";

    /**
     * @var ParameterConverterAnnotationFactory
     */
    private $parameterConverterAnnotationFactory;
    /**
     * @var AggregateRepositoryConstructor[]
     */
    private $aggregateRepositoryConstructors;

    /**
     * @var AnnotationRegistration[]
     */
    private $serviceCommandHandlerRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $serviceQueryHandlerRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $aggregateCommandHandlerRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $aggregateQueryHandlerRegistrations;

    /**
     * CqrsMessagingModule constructor.
     *
     * @param ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory
     * @param AnnotationRegistration[]                               $serviceCommandHandlerRegistrations
     * @param AnnotationRegistration[]                               $serviceQueryHandlerRegistrations
     * @param AnnotationRegistration[]                               $aggregateCommandHandlerRegistrations
     * @param AnnotationRegistration[]                               $aggregateQueryHandlerRegistrations
     */
    private function __construct(
        ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory,
        array $serviceCommandHandlerRegistrations,
        array $serviceQueryHandlerRegistrations,
        array $aggregateCommandHandlerRegistrations,
        array $aggregateQueryHandlerRegistrations
    )
    {
        $this->parameterConverterAnnotationFactory = $parameterConverterAnnotationFactory;
        $this->serviceCommandHandlerRegistrations = $serviceCommandHandlerRegistrations;
        $this->serviceQueryHandlerRegistrations = $serviceQueryHandlerRegistrations;
        $this->aggregateCommandHandlerRegistrations = $aggregateCommandHandlerRegistrations;
        $this->aggregateQueryHandlerRegistrations = $aggregateQueryHandlerRegistrations;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new self(
            ParameterConverterAnnotationFactory::create(),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpointAnnotation::class, CommandHandlerAnnotation::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpointAnnotation::class, QueryHandlerAnnotation::class),
            $annotationRegistrationService->findRegistrationsFor(AggregateAnnotation::class, CommandHandlerAnnotation::class),
            $annotationRegistrationService->findRegistrationsFor(AggregateAnnotation::class, QueryHandlerAnnotation::class)
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::CQRS_MODULE;
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void
    {
        foreach ($this->serviceCommandHandlerRegistrations as $registration) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassWithAnnotation(), $registration->getMethodName());

            if ($interfaceToCall->hasReturnValue()) {
                throw ConfigurationException::create("Command handler {$interfaceToCall} must not return value. Change return type to `void`");
            }

            $inputMessageChannelName = $this->getInputMessageChannel($interfaceToCall, $registration);
            $handler        = ServiceActivatorBuilder::create($inputMessageChannelName, $registration->getReferenceName(), $registration->getMethodName());

            $this->registerChannelAndHandler($configuration, $interfaceToCall, $handler, $registration, $inputMessageChannelName);
        }

        foreach ($this->serviceQueryHandlerRegistrations as $registration) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassWithAnnotation(), $registration->getMethodName());

            if (!$interfaceToCall->hasReturnValue()) {
                throw ConfigurationException::create("Query handler {$interfaceToCall} must return value. Change return value from `void` to result type");
            }

            $inputMessageChannelName = $this->getInputMessageChannel($interfaceToCall, $registration);
            $handler        = ServiceActivatorBuilder::create($inputMessageChannelName, $registration->getReferenceName(), $registration->getMethodName());
            $handler->withOutputChannel($registration->getAnnotationForMethod()->outputChannelName);
            $handler->withRequiredReply(true);


            $this->registerChannelAndHandler($configuration, $interfaceToCall, $handler, $registration, $inputMessageChannelName);

        }

        foreach ($this->aggregateCommandHandlerRegistrations as $registration) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassWithAnnotation(), $registration->getMethodName());
            $inputMessageChannelName = $this->getInputMessageChannel($interfaceToCall, $registration);

            $handler = AggregateMessageHandlerBuilder::createCommandHandlerWith($inputMessageChannelName, $registration->getClassWithAnnotation(), $registration->getMethodName());

            $this->registerChannelAndHandler($configuration, $interfaceToCall, $handler, $registration, $inputMessageChannelName);
        }

        foreach ($this->aggregateQueryHandlerRegistrations as $registration) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassWithAnnotation(), $registration->getMethodName());
            $inputMessageChannelName = $this->getInputMessageChannel($interfaceToCall, $registration);

            $handler = AggregateMessageHandlerBuilder::createQueryHandlerWith($inputMessageChannelName, $registration->getClassWithAnnotation(), $registration->getMethodName());
            $handler->withOutputChannelName($registration->getAnnotationForMethod()->outputChannelName);

            $this->registerChannelAndHandler($configuration, $interfaceToCall, $handler, $registration, $inputMessageChannelName);
        }

        $configuration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL))
            ->registerMessageHandler(RouterBuilder::createPayloadTypeRouterByClassName(self::INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL));

        $this->aggregateRepositoryConstructors = $moduleExtensions;
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }

    /**
     * @param InterfaceToCall        $interfaceToCall
     * @param AnnotationRegistration $annotation
     *
     * @return string
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function getInputMessageChannel(InterfaceToCall $interfaceToCall, AnnotationRegistration $annotation): string
    {
        if ($interfaceToCall->hasNoParameters()) {
            $inputChannel = $annotation->getAnnotationForMethod()->messageClassName;

            if (!$inputChannel) {
                throw ConfigurationException::create("{$interfaceToCall} has no information about command. Did you forget to typehint or add annotation?");
            }

            return $inputChannel;
        }

        return $interfaceToCall->getFirstParameterTypeHint();
    }

    /**
     * @param InterfaceToCall                              $interfaceToCall
     * @param MessageHandlerBuilderWithParameterConverters $messageHandlerBuilderWithParameterConverters
     * @param AnnotationRegistration                       $annotationRegistration
     *
     * @return void
     */
    private function configureMessageParametersFor(InterfaceToCall $interfaceToCall, MessageHandlerBuilderWithParameterConverters $messageHandlerBuilderWithParameterConverters, AnnotationRegistration $annotationRegistration): void
    {
        if ($interfaceToCall->hasNoParameters()) {
            return;
        }

        $methodAnnotation                = $annotationRegistration->getAnnotationForMethod();
        $parameterConverterAnnotations   = isset($methodAnnotation->parameterConverters) ? $methodAnnotation->parameterConverters : [];
        $messageParameter                = new MessageToPayloadParameterAnnotation();
        $messageParameter->parameterName = $interfaceToCall->getFirstParameterName();
        array_unshift($parameterConverterAnnotations, $messageParameter);

        $this->parameterConverterAnnotationFactory->configureParameterConverters(
            $messageHandlerBuilderWithParameterConverters,
            $annotationRegistration->getClassWithAnnotation(),
            $annotationRegistration->getMethodName(),
            $parameterConverterAnnotations
        );
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryFor(ReferenceSearchService $referenceSearchService, string $aggregateClassName): AggregateRepository
    {
        foreach ($this->aggregateRepositoryConstructors as $aggregateRepositoryConstructor) {
            if ($aggregateRepositoryConstructor->canHandle($referenceSearchService, $aggregateClassName)) {
                return $aggregateRepositoryConstructor->build($referenceSearchService, $aggregateClassName);
            }
        }

        throw new InvalidArgumentException("No suitable aggregate repository for {$aggregateClassName}. Have you registered your aggregate?");
    }

    /**
     * @param Configuration $configuration
     * @param               $interfaceToCall
     * @param               $handler
     * @param               $registration
     * @param               $inputMessageChannelName
     */
    private function registerChannelAndHandler(Configuration $configuration, InterfaceToCall $interfaceToCall, MessageHandlerBuilderWithParameterConverters $handler, AnnotationRegistration $registration, string $inputMessageChannelName): void
    {
        $this->configureMessageParametersFor($interfaceToCall, $handler, $registration);
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($inputMessageChannelName));
        $configuration->registerMessageHandler($handler);
    }
}