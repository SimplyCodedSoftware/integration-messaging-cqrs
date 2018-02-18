<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use Fixture\CommandHandler\Service\DoSomethingCommand;
use Fixture\CommandHandler\Service\ServiceCommandHandlerExample;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\ServiceCommandHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class CommandHandlerBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CommandHandlerBuilderTest extends TestCase
{
    public function test_configuring_command_handler_builder()
    {
        $referenceName = "reference-name";
        $consumerName = "consumer-a";
        $inputChannelName = "input-channel";
        $commandHandlerBuilder = ServiceCommandHandlerBuilder::create($inputChannelName, $referenceName, "doSomething")
            ->setConsumerName($consumerName);

        $this->assertEquals([$referenceName], $commandHandlerBuilder->getRequiredReferenceNames());
        $referenceNameTwo = "reference2";
        $commandHandlerBuilder->registerRequiredReference($referenceNameTwo);
        $this->assertEquals([$referenceName, $referenceNameTwo], $commandHandlerBuilder->getRequiredReferenceNames());

        $this->assertEquals($consumerName, $commandHandlerBuilder->getConsumerName());
        $this->assertEquals($inputChannelName, $commandHandlerBuilder->getInputMessageChannelName());
    }

    public function test_calling_command_handler_reference()
    {
        $inputChannelName = "input-channel";
        $referenceName = "command-handler";
        $commandHandlerBuilder = ServiceCommandHandlerBuilder::create($inputChannelName, $referenceName, "doSomething");

        $commandHandlerService = ServiceCommandHandlerExample::create();
        $commandHandler = $commandHandlerBuilder->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => DirectChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                $referenceName => $commandHandlerService
            ])
        );

        $payload = DoSomethingCommand::create();
        $commandHandler->handle(MessageBuilder::withPayload($payload)->build());

        $this->assertEquals($payload, $commandHandlerService->getCalledCommand());
    }

    public function test_throwing_exception_if_command_handler_reference_return_value()
    {
        $inputChannelName = "input-channel";
        $referenceName = "command-handler";
        $commandHandlerBuilder = ServiceCommandHandlerBuilder::create($inputChannelName, $referenceName, "doSomethingWithReturn");

        $this->expectException(InvalidArgumentException::class);

        $commandHandlerBuilder->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => DirectChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                $referenceName => ServiceCommandHandlerExample::create()
            ])
        );
    }
}