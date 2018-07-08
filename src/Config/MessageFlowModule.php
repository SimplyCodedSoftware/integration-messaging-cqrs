<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter\SplitterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class MessageFlowModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class MessageFlowModule implements AnnotationModule, ApplicationContextModuleExtension
{
    const INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL = "integration_messaging.cqrs.start_flow";

    const INTEGRATION_MESSAGING_CQRS_START_DEFAULT_FLOW_CHANNEL = "integration_messaging.cqrs.start_default_flow";

    private const INTEGRATION_MESSAGING_CQRS_SPLITTER_TO_ROUTER_BRIDGE = "integration_messaging.cqrs.splitter_to_router_bridge";

    const INTEGRATION_MESSAGING_CQRS_MESSAGE_CLASS_HEADER = "integration_messaging.cqrs.message_class";

    const INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER = "integration_messaging.cqrs.message_name";

    const INTEGRATION_MESSAGING_CQRS_MESSAGE_FLOW_REGISTRATION_HEADER = "integration_messaging.cqrs.message_flow_registration";

    /**
     * @var MessageFlowMapper
     */
    private $messageFlowMapper;

    /**
     * MessageFlowModule constructor.
     *
     * @param MessageFlowMapper $messageFlowMapper
     */
    private function __construct(MessageFlowMapper $messageFlowMapper)
    {
        $this->messageFlowMapper = $messageFlowMapper;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        $messageFlowAnnotations = MessageFlowMapper::createWith([]);

        foreach ($annotationRegistrationService->getAllClassesWithAnnotation(MessageFlowAnnotation::class) as $messageFlowClass) {
            /** @var MessageFlowAnnotation $annotation */
            $annotation = $annotationRegistrationService->getAnnotationForClass($messageFlowClass, MessageFlowAnnotation::class);
            $messageFlowAnnotations->addRegistration(MessageFlowRegistration::createLocalFlow(
                $annotation->externalName,
                $messageFlowClass,
                $annotation->channelName,
                $annotation->autoCreate,
                $annotation->isSubscriable
            ));
        }

        return new self($messageFlowAnnotations);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ConfigurationObserver $configurationObserver): void
    {
        $configuration->registerMessageHandler(
            SplitterBuilder::createWithDirectObject(
                MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL,
                new MessageFlowRegistrationSplitter($this->messageFlowMapper),
                "split"
            )
                ->withOutputMessageChannel(self::INTEGRATION_MESSAGING_CQRS_SPLITTER_TO_ROUTER_BRIDGE)
        );

        foreach ($this->messageFlowMapper->getMessageFlows() as $messageFlowRegistrations) {
            /** @var MessageFlowRegistration $messageFlowRegistration */
            foreach ($messageFlowRegistrations as $messageFlowRegistration) {
                $this->registerChannelIfNeeded($configuration, $messageFlowRegistration);
            }
        }

        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::INTEGRATION_MESSAGING_CQRS_SPLITTER_TO_ROUTER_BRIDGE));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL));
        $messageFlowRouter = RouterBuilder::createRouterFromObject(self::INTEGRATION_MESSAGING_CQRS_SPLITTER_TO_ROUTER_BRIDGE, new MessageFlowRegistrationRouter(), "routeMessageByRegistration");
        $messageFlowRouter->withMethodParameterConverters([
            MessageToHeaderParameterConverterBuilder::create("messageFlowRegistration", self::INTEGRATION_MESSAGING_CQRS_MESSAGE_FLOW_REGISTRATION_HEADER)
        ]);

        $configuration->registerMessageHandler($messageFlowRouter);
    }

    /**
     * @param Configuration $configuration
     * @param MessageFlowRegistration $messageFlowRegistration
     * @return void
     */
    private function registerChannelIfNeeded(Configuration $configuration, MessageFlowRegistration $messageFlowRegistration): void
    {
        if ($messageFlowRegistration->shouldChannelBeRegistered()) {
            $configuration->registerMessageChannel(
                $messageFlowRegistration->isSubscribable()
                    ? SimpleMessageChannelBuilder::createDirectMessageChannel($messageFlowRegistration->getMessageName())
                    : SimpleMessageChannelBuilder::createPublishSubscribeChannel($messageFlowRegistration->getMessageName())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($messagingComponent): bool
    {
        return $messagingComponent instanceof MessageFlowRegistration;
    }

    /**
     * @inheritDoc
     */
    public function registerMessagingComponent(Configuration $configuration, $messageFlowRegistration): void
    {
        Assert::isTrue(\assert($messageFlowRegistration instanceof MessageFlowRegistration), "Registering wrong type in " . self::class);

        $this->registerChannelIfNeeded($configuration, $messageFlowRegistration);
        $this->messageFlowMapper->addRegistration($messageFlowRegistration);
    }

    /**
     * @inheritDoc
     */
    public function configure(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return ApplicationContextModule::MODULE_NAME;
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
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }

}