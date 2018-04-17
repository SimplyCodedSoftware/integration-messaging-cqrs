<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariable;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter\SplitterBuilder;

/**
 * Class MessageFlowModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class MessageFlowModule implements AnnotationModule
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
        $messageFlowAnnotations = [];

        foreach ($annotationRegistrationService->getAllClassesWithAnnotation(MessageFlowAnnotation::class) as $messageFlowClass) {
            /** @var MessageFlowAnnotation $annotation */
            $annotation = $annotationRegistrationService->getAnnotationForClass($messageFlowClass, MessageFlowAnnotation::class);
            $messageFlowAnnotations[$annotation->externalName][] = MessageFlowRegistration::createLocalFlow(
                $annotation->externalName,
                $messageFlowClass,
                $annotation->channelName
            );
        }

        foreach ($annotationRegistrationService->findRegistrationsFor(ApplicationContextAnnotation::class, MessageFlowComponentAnnotation::class) as $registration) {
            $applicationContextClassName = $registration->getClassWithAnnotation();
            $applicationContext = new $applicationContextClassName();

            /** @var MessageFlowRegistration $messageFlowRegistration */
            $messageFlowRegistration = $applicationContext->{$registration->getMethodName()}();
            $messageFlowAnnotations[str_replace("*", ".*", $messageFlowRegistration->getMessageName())][] = $messageFlowRegistration;
        }

        return new self(MessageFlowMapper::createWith($messageFlowAnnotations));
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

        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::INTEGRATION_MESSAGING_CQRS_SPLITTER_TO_ROUTER_BRIDGE));
        $configuration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(self::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL));
        $messageFlowRouter = RouterBuilder::createRouterFromObject(self::INTEGRATION_MESSAGING_CQRS_SPLITTER_TO_ROUTER_BRIDGE, new MessageFlowRegistrationRouter(), "routeMessageByRegistration");
        $messageFlowRouter->withMethodParameterConverters([
            MessageToHeaderParameterConverterBuilder::create("messageFlowRegistration", self::INTEGRATION_MESSAGING_CQRS_MESSAGE_FLOW_REGISTRATION_HEADER)
        ]);

        $configuration->registerMessageHandler($messageFlowRouter);
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
        return "messageFlowModule";
    }

    /**
     * @inheritDoc
     */
    public function preConfigure(array $moduleExtensions, ConfigurationObserver $configurationObserver): void
    {
        return;
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