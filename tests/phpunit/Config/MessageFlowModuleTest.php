<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use Fixture\Annotation\MessageFlow\ExampleFlowCommandWithCustomChannel;
use Fixture\Annotation\MessageFlow\ExampleFlowCommand;
use Fixture\Annotation\MessageFlow\ExampleMessageFlowApplicationContextForExternalFlow;
use Fixture\Annotation\MessageFlow\ExampleMessageFlowWithRegex;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\MessageFlowModule;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class MessageFlowModuleTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFlowModuleTest extends TestCase
{
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

    public function test_routing_message_by_default_flow()
    {
        $annotationClassesToRegister = [ExampleFlowCommand::class];

        $messagingSystem = $this->createMessagingSystem($annotationClassesToRegister);
        $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_FLOW_CHANNEL)
            ->send(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER, ExampleFlowCommand::MESSAGE_NAME)
                    ->build()
            );

        /** @var PollableChannel $defaultFlowChannel */
        $defaultFlowChannel = $messagingSystem->getMessageChannelByName(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_DEFAULT_FLOW_CHANNEL);
        $this->assertEquals(
            ExampleFlowCommand::class,
            $defaultFlowChannel->receive()->getHeaders()->get(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_CLASS_HEADER)
        );
    }

    public function test_routing_message_by_external_flow()
    {
        $annotationClassesToRegister = [ExampleFlowCommandWithCustomChannel::class];

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
        $this->assertEquals(
            ExampleFlowCommandWithCustomChannel::class,
            $externalChannel->receive()->getHeaders()->get(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_CLASS_HEADER)
        );
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
     * @param $annotationClassesToRegister
     *
     * @return \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
     */
    private function createMessagingSystem(array $annotationClassesToRegister): \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
    {
        return $this->createMessagingSystemWithChannels($annotationClassesToRegister, []);
    }

    /**
     * @param array $annotationClassesToRegister
     * @param array $channelBuilders
     *
     * @return \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
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
     */
    private function prepareConfiguration(array $annotationClassesToRegister): MessagingSystemConfiguration
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom($annotationClassesToRegister);
        $cqrsMessagingModule           = MessageFlowModule::create($annotationRegistrationService);

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            [],
            NullObserver::create()
        );

        return $extendedConfiguration;
    }

    /**
     * @return MessagingSystemConfiguration
     */
    protected function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }
}