<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use Fixture\Annotation\MessageFlow\ExampleFlowCommand;
use Fixture\Annotation\MessageFlow\ExampleFlowCommandWithCustomChannel;
use Fixture\Annotation\MessageFlow\ExampleFlowWithSubscribableChannelCommand;
use Fixture\Annotation\MessageFlow\ExampleMessageFlowApplicationContextForExternalFlow;
use Fixture\Annotation\MessageFlow\ExampleMessageFlowWithRegex;
use Fixture\Handler\ReplyViaHeadersMessageHandler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ApplicationContextModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\MessageFlowModule;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class MessageFlowModuleTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFlowModuleTest extends TestCase
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_no_message_name_defined()
    {
        $messagingSystem = $this->createMessagingSystem([]);

        $this->expectException(MessageHandlingException::class);

        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, ExampleFlowCommand::MESSAGE_NAME)
                    ->build()
            );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_routing_message_by_auto_registered_direct_channel()
    {
        $annotationClassesToRegister = [ExampleFlowCommand::class];

        $messagingSystem = $this->createMessagingSystem($annotationClassesToRegister);

        /** @var DirectChannel $externalChannel */
        $externalChannel = $messagingSystem->getMessageChannelByName(ExampleFlowCommand::MESSAGE_NAME);
        $messageHandler = ReplyViaHeadersMessageHandler::create(null);
        $externalChannel->subscribe($messageHandler);

        $messageName = ExampleFlowWithSubscribableChannelCommand::MESSAGE_NAME;
        $payload = "test";
        $this->sendTestMessage($messagingSystem, $payload, $messageName);
        $this->assertEquals($payload, $messageHandler->getReceivedMessage()->getPayload());
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_routing_message_by_auto_registered_subscribable_channel()
    {
        $annotationClassesToRegister = [ExampleFlowWithSubscribableChannelCommand::class];

        $messagingSystem = $this->createMessagingSystem($annotationClassesToRegister);

        /** @var SubscribableChannel $externalChannel */
        $externalChannel = $messagingSystem->getMessageChannelByName(ExampleFlowWithSubscribableChannelCommand::MESSAGE_NAME);
        $messageHandler = ReplyViaHeadersMessageHandler::create(null);
        $externalChannel->subscribe($messageHandler);

        $messageName = ExampleFlowWithSubscribableChannelCommand::MESSAGE_NAME;
        $payload = "test";
        $this->sendTestMessage($messagingSystem, $payload, $messageName);
        $this->assertEquals($payload, $messageHandler->getReceivedMessage()->getPayload());
    }

    public function test_routing_to_multiple_flows()
    {
        $annotationClassesToRegister = [ExampleMessageFlowWithRegex::class, ExampleFlowCommandWithCustomChannel::class];

        $messagingSystem = $this->createMessagingSystemWithChannels($annotationClassesToRegister, [
            SimpleMessageChannelBuilder::createQueueChannel("externalChannelWithRegex"),
            SimpleMessageChannelBuilder::createQueueChannel("externalChannel")
        ]);
        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, ExampleFlowCommandWithCustomChannel::MESSAGE_NAME)
                    ->build()
            );

        /** @var PollableChannel $externalChannel */
        $externalChannel = $messagingSystem->getMessageChannelByName("externalChannel");
        $this->assertNotEmpty($externalChannel->receive());

        /** @var PollableChannel $externalChannel */
        $externalChannel = $messagingSystem->getMessageChannelByName("externalChannelWithRegex");
        $this->assertNotEmpty($externalChannel->receive());
    }

    public function test_routing_by_star()
    {
        $annotationClassesToRegister = [ExampleMessageFlowWithRegex::class];

        $messagingSystem = $this->createMessagingSystemWithChannels($annotationClassesToRegister, [
            SimpleMessageChannelBuilder::createQueueChannel("externalChannelWithRegex")
        ]);
        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, ExampleFlowCommandWithCustomChannel::MESSAGE_NAME)
                    ->build()
            );

        /** @var PollableChannel $externalChannel */
        $externalChannel = $messagingSystem->getMessageChannelByName("externalChannelWithRegex");

        $this->assertNotEmpty($externalChannel->receive());
    }

    public function test_not_finding_flow_when_no_star_defined_and_part_of_name_involved()
    {
        $this->expectException(MessageHandlingException::class);

        $annotationClassesToRegister = [ExampleFlowCommandWithCustomChannel::class];

        $messagingSystem = $this->createMessagingSystemWithChannels($annotationClassesToRegister, [
            SimpleMessageChannelBuilder::createQueueChannel("externalChannel")
        ]);
        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, "external")
                    ->build()
            );
    }

    public function test_not_finding_when_message_name_contains_flow_name_but_is_not_equal()
    {
        $this->expectException(MessageHandlingException::class);

        $annotationClassesToRegister = [ExampleFlowCommandWithCustomChannel::class];

        $messagingSystem = $this->createMessagingSystemWithChannels($annotationClassesToRegister, [
            SimpleMessageChannelBuilder::createQueueChannel("externalChannel")
        ]);
        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, "example.external.flow.not.equal")
                    ->build()
            );
    }

    /***
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_routing_message_by_external_flow_from_application_context()
    {
        $annotationClassesToRegister = [ExampleMessageFlowApplicationContextForExternalFlow::class];

        $messagingSystem = $this->createMessagingSystemWithChannels($annotationClassesToRegister, [
            SimpleMessageChannelBuilder::createQueueChannel("externalChannel")
        ]);
        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, ExampleFlowCommandWithCustomChannel::MESSAGE_NAME)
                    ->build()
            );

        /** @var PollableChannel $externalChannel */
        $externalChannel = $messagingSystem->getMessageChannelByName("externalChannel");

        $this->assertNotEmpty($externalChannel->receive());
    }

    /**
     * @param array $annotationClassesToRegister
     *
     * @return \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createMessagingSystem(array $annotationClassesToRegister): \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
    {
        return $this->createMessagingSystemWithChannels($annotationClassesToRegister, []);
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    protected function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @param array $annotationClassesToRegister
     * @param array $channelBuilders
     *
     * @return \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createMessagingSystemWithChannels(array $annotationClassesToRegister, array $channelBuilders)
    {
        $messageFlow = $this->prepareConfiguration($annotationClassesToRegister)
            ->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory())
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_DEFAULT_FLOW_CHANNEL));


        foreach ($channelBuilders as $channelBuilder) {
            $messageFlow->registerMessageChannel($channelBuilder);
        }

        $messagingSystem = $messageFlow->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createEmpty(),
            InMemoryConfigurationVariableRetrievingService::createEmpty()
        );

        return $messagingSystem;
    }

    /**
     * @param array $annotationClassesToRegister
     *
     * @return MessagingSystemConfiguration
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function prepareConfiguration(array $annotationClassesToRegister): MessagingSystemConfiguration
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom($annotationClassesToRegister);
        $cqrsMessagingModule = MessageFlowModule::create($annotationRegistrationService);
        $applicationContextModule = ApplicationContextModule::create($annotationRegistrationService);

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $applicationContextModule->prepare(
            $extendedConfiguration,
            [$cqrsMessagingModule],
            NullObserver::create()
        );
        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            [],
            NullObserver::create()
        );


        return $extendedConfiguration;
    }

    /**
     * @param ConfiguredMessagingSystem $messagingSystem
     * @param string $payload
     * @param string $messageName
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     */
    private function sendTestMessage(ConfiguredMessagingSystem $messagingSystem, string $payload, string $messageName): void
    {
        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload($payload)
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, $messageName)
                    ->build()
            );
    }
}