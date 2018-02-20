<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Fixture\CommandHandler\Aggregate\CommandWithoutAggregateIdentifier;
use Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Fixture\CommandHandler\Aggregate\FinishOrderCommand;
use Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepository;
use Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepositoryBuilder;
use Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Fixture\CommandHandler\Aggregate\Order;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateCallingCommandHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateNotFoundException;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateVersionMismatchException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ServiceCallToAggregateAdapterTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateCallingCommandHandlerBuilderTest extends TestCase
{
    public function test_calling_existing_aggregate_method_with_only_command_as_parameter()
    {
        $order = Order::createWith(CreateOrderCommand::create(1, 1, "Poland"));
        $aggregateCallingCommandHandler = AggregateCallingCommandHandlerBuilder::createWith(
            InMemoryOrderAggregateRepositoryBuilder::createWith([
                $order
            ]),
            Order::class,
            "changeShippingAddress"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $newShippingAddress = "Germany";
        $aggregateCommandHandler->handle(MessageBuilder::withPayload(ChangeShippingAddressCommand::create(1, 1, $newShippingAddress))->build());

        $this->assertEquals($newShippingAddress, $order->getShippingAddress());
    }

    public function test_configuring_command_handler()
    {
        $aggregateCallingCommandHandler = AggregateCallingCommandHandlerBuilder::createWith(
            InMemoryOrderAggregateRepositoryBuilder::createEmpty(),
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

    public function test_throwing_exception_if_configuring_command_handler_when_no_parameters_for_method_exists()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateCallingCommandHandlerBuilder::createWith(
            InMemoryOrderAggregateRepositoryBuilder::createEmpty(),
            Order::class,
            "increaseAmount"
        );
    }

    public function test_throwing_exception_if_aggregate_method_can_return_value()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateCallingCommandHandlerBuilder::createWith(
            InMemoryOrderAggregateRepositoryBuilder::createEmpty(),
            Order::class,
            "hasVersion"
        );
    }

    public function test_creating_new_aggregate_from_factory_method()
    {
        $aggregateRepository = InMemoryOrderAggregateRepositoryBuilder::createEmpty();
        $aggregateCallingCommandHandler = AggregateCallingCommandHandlerBuilder::createWith(
            $aggregateRepository,
            Order::class,
            "createWith"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CreateOrderCommand::create(1, 1, "Poland"))->build());

        $this->assertNotNull($aggregateRepository->findBy(1));
    }

    public function test_calling_aggregate_with_version_locking()
    {
        $order = Order::createWith(CreateOrderCommand::create(1, 1, "Poland"));
        $aggregateCallingCommandHandler = AggregateCallingCommandHandlerBuilder::createWith(
            InMemoryOrderAggregateRepositoryBuilder::createWith([
                $order
            ]),
            Order::class,
            "multiplyOrder"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->build());

        $this->expectException(AggregateVersionMismatchException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->build());
    }

    public function test_calling_with_multiple_argument_converters()
    {
        $order = Order::createWith(CreateOrderCommand::create(1, 1, "Poland"));
        $aggregateCallingCommandHandler = AggregateCallingCommandHandlerBuilder::createWith(
            InMemoryOrderAggregateRepositoryBuilder::createWith([
                $order
            ]),
            Order::class,
            "finish"
        );
        $aggregateCallingCommandHandler->withMethodParameterConverters([
            MessageToHeaderParameterConverterBuilder::create("customerId", "client")
        ]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $clientId = 1001;
        $aggregateCommandHandler->handle(MessageBuilder::withPayload(FinishOrderCommand::create(1))->setHeader("client", $clientId)->build());

        $this->assertEquals(
            $clientId,
            $order->getCustomerId()
        );
    }

    public function test_throwing_exception_when_trying_to_handle_command_without_aggregate_id()
    {
        $aggregateCallingCommandHandler = AggregateCallingCommandHandlerBuilder::createWith(
            InMemoryOrderAggregateRepositoryBuilder::createWith([
                Order::createWith(CreateOrderCommand::create(1, 1, "Poland"))
            ]),
            Order::class,
            "finish"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $this->expectException(AggregateNotFoundException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CommandWithoutAggregateIdentifier::create(1))->build());
    }
}