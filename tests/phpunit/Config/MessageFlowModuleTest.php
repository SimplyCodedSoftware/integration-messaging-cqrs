<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use Fixture\Annotation\MessageFlow\ExampleFlowCommand;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
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

    /**
     * @param $annotationClassesToRegister
     *
     * @return \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
     */
    private function createMessagingSystem($annotationClassesToRegister): \SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem
    {
        $messageFlow = $this->prepareConfiguration($annotationClassesToRegister)
            ->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory())
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_DEFAULT_FLOW_CHANNEL));

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