<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Fixture\CommandHandler\Aggregate\CommandWithoutAggregateIdentifier;
use Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Fixture\CommandHandler\Aggregate\FinishOrderCommand;
use Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepositoryConstructor;
use Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Fixture\CommandHandler\Aggregate\Order;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateNotFoundException;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateVersionMismatchException;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ServiceCallToAggregateAdapterTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateCommandHandlerBuilderTest extends TestCase
{
    public function test_calling_existing_aggregate_method_with_only_command_as_parameter()
    {
        $order                          = Order::createWith(CreateOrderCommand::create(1, 1, "Poland"));
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createCommandHandlerWith(
            "",
            Order::class,
            "changeShippingAddress"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceFor([$order])
        );

        $newShippingAddress = "Germany";
        $aggregateCommandHandler->handle(MessageBuilder::withPayload(ChangeShippingAddressCommand::create(1, 1, $newShippingAddress))->build());

        $this->assertEquals($newShippingAddress, $order->getShippingAddress());
    }

    public function test_configuring_command_handler()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createCommandHandlerWith(
            ChangeShippingAddressCommand::class,
            Order::class,
            "changeShippingAddress"
        );

        $this->assertEquals(ChangeShippingAddressCommand::class, $aggregateCallingCommandHandler->getInputMessageChannelName());
        $this->assertEquals([], $aggregateCallingCommandHandler->getRequiredReferenceNames());

        $aggregateCallingCommandHandler->registerRequiredReference("some-ref");
        $this->assertEquals(["some-ref"], $aggregateCallingCommandHandler->getRequiredReferenceNames());

        $consumerName = "consumer-name";
        $aggregateCallingCommandHandler->setConsumerName($consumerName);
        $this->assertEquals($consumerName, $aggregateCallingCommandHandler->getConsumerName());
    }

    public function test_throwing_exception_if_aggregate_method_can_return_value_for_command_handler()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateMessageHandlerBuilder::createCommandHandlerWith(
            "",
            Order::class,
            "hasVersion"
        );
    }

    public function test_calling_aggregate_for_query_handler_with_return_value()
    {
        $orderAmount                    = 5;
        $order                          = Order::createWith(CreateOrderCommand::create(1, $orderAmount, "Poland"));
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createQueryHandlerWith(
            "",
            Order::class,
            "getAmountWithQuery"
        );

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceFor([$order])
        );

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $orderAmount,
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_calling_aggregate_for_query_handler_with_output_channel()
    {
        $outputChannelName              = "output";
        $orderAmount                    = 5;
        $order                          = Order::createWith(CreateOrderCommand::create(1, $orderAmount, "Poland"));
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createQueryHandlerWith(
            "inputChannel",
            Order::class,
            "getAmountWithQuery"
        )->withOutputChannelName($outputChannelName);

        $outputChannel         = QueueChannel::create();
        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $outputChannelName => $outputChannel
                ]
            ),
            $this->createReferenceSearchServiceFor([$order])
        );

        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->build()
        );

        $this->assertEquals(
            $orderAmount,
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_calling_aggregate_for_query_without_parameters()
    {
        $orderAmount                    = 5;
        $order                          = Order::createWith(CreateOrderCommand::create(1, $orderAmount, "Poland"));
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createQueryHandlerWith(
            "inputChannel",
            Order::class,
            "getAmount"
        );

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceFor([$order])
        );

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $orderAmount,
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_creating_new_aggregate_from_factory_method()
    {
        $aggregateRepository            = InMemoryOrderAggregateRepositoryConstructor::createEmpty();
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createCommandHandlerWith(
            "inputChannel",
            Order::class,
            "createWith"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith(
                [
                    CqrsMessagingModule::CQRS_MODULE => $aggregateRepository
                ]
            )
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CreateOrderCommand::create(1, 1, "Poland"))->build());

        $this->assertNotNull($aggregateRepository->findBy(1));
    }

    public function test_calling_aggregate_with_version_locking()
    {
        $order                          = Order::createWith(CreateOrderCommand::create(1, 1, "Poland"));
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createCommandHandlerWith(
            "inputChannel",
            Order::class,
            "multiplyOrder"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceFor([$order])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->build());

        $this->expectException(AggregateVersionMismatchException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->build());
    }

    public function test_throwing_exception_when_trying_to_handle_command_without_aggregate_id()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createCommandHandlerWith(
            "inputChannel",
            Order::class,
            "finish"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceFor([Order::createWith(CreateOrderCommand::create(1, 1, "Poland"))])
        );

        $this->expectException(AggregateNotFoundException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CommandWithoutAggregateIdentifier::create(1))->build());
    }

    /**
     * @param $orders
     *
     * @return InMemoryReferenceSearchService
     */
    private function createReferenceSearchServiceFor($orders): InMemoryReferenceSearchService
    {
        return InMemoryReferenceSearchService::createWith(
            [
                CqrsMessagingModule::CQRS_MODULE => InMemoryOrderAggregateRepositoryConstructor::createWith(
                    $orders
                )
            ]
        );
    }


}