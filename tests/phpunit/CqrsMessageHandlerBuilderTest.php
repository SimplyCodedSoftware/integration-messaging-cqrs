<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use Fixture\Annotation\QueryHandler\ServiceQueryHandlerWithAdditionalParameter;
use Fixture\Annotation\QueryHandler\SomeQuery;
use Fixture\CommandHandler\Aggregate\ChangeAmountInterceptor;
use Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Fixture\CommandHandler\Aggregate\CommandWithoutAggregateIdentifier;
use Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Fixture\CommandHandler\Aggregate\FinishOrderCommand;
use Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepositoryConstructor;
use Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Fixture\CommandHandler\Aggregate\Order;
use Fixture\CommandHandler\Interceptor\AddCurrentUserIdInterceptorExample;
use Fixture\CommandHandler\Interceptor\AddProductsCommand;
use Fixture\CommandHandler\Interceptor\CloseShopCommand;
use Fixture\CommandHandler\Interceptor\CreateShopCommand;
use Fixture\CommandHandler\Interceptor\DoNotCloseShopTwiceInterceptor;
use Fixture\CommandHandler\Interceptor\MultiplyProductsInterceptor;
use Fixture\CommandHandler\Interceptor\NotAuthorizedToDoActionInterceptor;
use Fixture\CommandHandler\Interceptor\ShopAggregateExample;
use Fixture\CommandHandler\Interceptor\WrongNoReturnValueInterceptor;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\ReferenceCallInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\CqrsMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateNotFoundException;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateVersionMismatchException;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\MessageFlowModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\MessagingException;
use SimplyCodedSoftware\IntegrationMessaging\NullableMessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ServiceCallToAggregateAdapterTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CqrsMessageHandlerBuilderTest extends TestCase
{
    public function test_calling_existing_aggregate_method_with_only_command_as_parameter()
    {
        $order                          = Order::createWith(CreateOrderCommand::create(1, 1, "Poland"));
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "",
            Order::class,
            "changeShippingAddress"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContaining([$order])
        );

        $newShippingAddress = "Germany";
        $aggregateCommandHandler->handle(MessageBuilder::withPayload(ChangeShippingAddressCommand::create(1, 1, $newShippingAddress))->build());

        $this->assertEquals($newShippingAddress, $order->getShippingAddress());
    }

    public function test_configuring_command_handler()
    {
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            ChangeShippingAddressCommand::class,
            Order::class,
            "changeShippingAddress"
        );

        $this->assertEquals(ChangeShippingAddressCommand::class, $aggregateCallingCommandHandler->getInputMessageChannelName());
        $this->assertEquals([], $aggregateCallingCommandHandler->getRequiredReferenceNames());

        $aggregateCallingCommandHandler->registerRequiredReference("some-ref");
        $this->assertEquals(["some-ref"], $aggregateCallingCommandHandler->getRequiredReferenceNames());
    }

    public function test_registering_service_query_handler_with_additional_parameter()
    {
        $serviceActivator      = CqrsMessageHandlerBuilder::createServiceQueryHandlerWith(SomeQuery::class, ServiceQueryHandlerWithAdditionalParameter::class, "searchFor")
            ->withMethodParameterConverters(
                [
                    MessageToPayloadParameterConverterBuilder::create("query"),
                    MessageToHeaderParameterConverterBuilder::create("currentUserId", "currentUserId")
                ]
            )
            ->build(InMemoryChannelResolver::createEmpty(), $this->createReferenceSearchServiceWithRepositoryContainingOrdersAndServices([], [
                ServiceQueryHandlerWithAdditionalParameter::class => new ServiceQueryHandlerWithAdditionalParameter()
            ]));

        $replyChannel = QueueChannel::create();
        $serviceActivator->handle(
            MessageBuilder::withPayload(new SomeQuery())
                ->setHeader("currentUserId", Uuid::uuid4())
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertNotNull($replyChannel->receive());
    }

    public function test_throwing_exception_if_aggregate_method_can_return_value_for_command_handler()
    {
        $this->expectException(InvalidArgumentException::class);

        CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "",
            Order::class,
            "hasVersion"
        );
    }

    public function test_calling_aggregate_for_query_handler_with_return_value()
    {
        $orderAmount                    = 5;
        $order                          = Order::createWith(CreateOrderCommand::create(1, $orderAmount, "Poland"));
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateQueryHandlerWith(
            "",
            Order::class,
            "getAmountWithQuery"
        );

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContaining([$order])
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
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateQueryHandlerWith(
            "inputChannel",
            Order::class,
            "getAmountWithQuery"
        )->withOutputMessageChannel($outputChannelName);

        $outputChannel         = QueueChannel::create();
        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $outputChannelName => $outputChannel
                ]
            ),
            $this->createReferenceSearchServiceWithRepositoryContaining([$order])
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
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateQueryHandlerWith(
            "inputChannel",
            Order::class,
            "getAmount"
        );

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContaining([$order])
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
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
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
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "inputChannel",
            Order::class,
            "multiplyOrder"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContaining([$order])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->build());

        $this->expectException(MessageHandlingException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->build());
    }

    public function test_throwing_exception_when_trying_to_handle_command_without_aggregate_id()
    {
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "inputChannel",
            Order::class,
            "finish"
        );

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContaining([Order::createWith(CreateOrderCommand::create(1, 1, "Poland"))])
        );

        $this->expectException(AggregateNotFoundException::class);

        try {
            $aggregateCommandHandler->handle(MessageBuilder::withPayload(CommandWithoutAggregateIdentifier::create(1))->build());
        }catch (MessagingException $messagingException) {
            throw $messagingException->getCause();
        }
    }

    public function test_calling_aggregate_with_void_interceptor()
    {
        $order                          = Order::createWith(CreateOrderCommand::create(1, 1, "Poland"));
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "",
            ShopAggregateExample::class,
            "create"
        )
            ->withPreCallInterceptors([
                ReferenceCallInterceptor::create(NotAuthorizedToDoActionInterceptor::class, "isAuthorized", [])
            ]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContainingOrdersAndServices(
                [$order],
                [NotAuthorizedToDoActionInterceptor::class => NotAuthorizedToDoActionInterceptor::create()]
            )
        );

        $this->expectException(MessageHandlingException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CreateShopCommand::create("some"))->build());
    }

    public function test_throwing_exception_when_register_interceptor_without_return_value()
    {
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "",
            ShopAggregateExample::class,
            "create"
        )
            ->withPreCallInterceptors([
                ReferenceCallInterceptor::create(WrongNoReturnValueInterceptor::class, "wrongCall", [])
            ]);

        $this->expectException(InvalidArgumentException::class);

        $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContainingOrdersAndServices(
                [],
                [WrongNoReturnValueInterceptor::class => WrongNoReturnValueInterceptor::create()]
            )
        );
    }

    public function test_calling_aggregate_with_header_changing_interceptor()
    {
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "",
            ShopAggregateExample::class,
            "create"
        )
            ->withMethodParameterConverters([
                MessageToPayloadParameterConverterBuilder::create("command"),
                MessageToHeaderParameterConverterBuilder::create("ownerId", "userId")
            ])
            ->withPreCallInterceptors([
                ReferenceCallInterceptor::create(AddCurrentUserIdInterceptorExample::class, "addCurrentUserId", [])
            ]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContainingOrdersAndServices(
                [],
                [AddCurrentUserIdInterceptorExample::class => AddCurrentUserIdInterceptorExample::create()]
            )
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CreateShopCommand::create("some"))->build());

        $this->assertTrue(true);
    }

    public function test_calling_aggregate_with_interceptor_containing_parameter_converter()
    {
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "",
            ShopAggregateExample::class,
            "close"
        )
            ->withPreCallInterceptors([
                ReferenceCallInterceptor::create(DoNotCloseShopTwiceInterceptor::class, "doNotCloseTwice", [
                    MessageToHeaderParameterConverterBuilder::create("aggregate", CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER)
                ])
            ]);

        $name                    = "superSam";
        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContainingOrdersAndServices(
                [ShopAggregateExample::createWithoutOwner($name)],
                [DoNotCloseShopTwiceInterceptor::class => DoNotCloseShopTwiceInterceptor::create()]
            )
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CloseShopCommand::create($name))->build());

        $this->expectException(MessageHandlingException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CloseShopCommand::create($name))->build());
    }

    public function test_calling_multiple_interceptors_with_command_modification()
    {
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(
            "",
            ShopAggregateExample::class,
            "addProducts"
        )
            ->withPreCallInterceptors([
                ReferenceCallInterceptor::create(MultiplyProductsInterceptor::class, "multiply", [
                    MessageToPayloadParameterConverterBuilder::create("command")
                ]),
                ReferenceCallInterceptor::create(MultiplyProductsInterceptor::class, "multiply", [
                    MessageToPayloadParameterConverterBuilder::create("command")
                ])
            ]);

        $name                    = "superSam";
        $shop                    = ShopAggregateExample::createWithoutOwner($name);
        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContainingOrdersAndServices(
                [$shop],
                [MultiplyProductsInterceptor::class => MultiplyProductsInterceptor::create()]
            )
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(AddProductsCommand::create($name, 2))->build());

        $this->assertEquals(8, $shop->getProductsAmount());
    }

    public function test_calling_post_interceptor_with_aggregate_query_handler()
    {
        $newAmount             = 100;
        $order                          = Order::createWith(CreateOrderCommand::create(1, 5, "Poland"));
        $aggregateCallingCommandHandler = CqrsMessageHandlerBuilder::createAggregateQueryHandlerWith(
            "",
            Order::class,
            "getAmountWithQuery"
        )->withPostCallInterceptors([
            ReferenceCallInterceptor::create(ChangeAmountInterceptor::class, "change", [])
        ]);

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            $this->createReferenceSearchServiceWithRepositoryContainingOrdersAndServices([$order], [ChangeAmountInterceptor::class => ChangeAmountInterceptor::create($newAmount)])
        );

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $newAmount,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @param array $orders
     *
     * @return InMemoryReferenceSearchService
     */
    private function createReferenceSearchServiceWithRepositoryContaining(array $orders): InMemoryReferenceSearchService
    {
        return InMemoryReferenceSearchService::createWith(
            [
                CqrsMessagingModule::CQRS_MODULE => InMemoryOrderAggregateRepositoryConstructor::createWith(
                    $orders
                )
            ]
        );
    }

    /**
     * @param array $orders
     * @param array $referenceServices
     *
     * @return InMemoryReferenceSearchService
     */
    private function createReferenceSearchServiceWithRepositoryContainingOrdersAndServices(array $orders, array $referenceServices): InMemoryReferenceSearchService
    {
        return InMemoryReferenceSearchService::createWith(
            array_merge([
                CqrsMessagingModule::CQRS_MODULE => InMemoryOrderAggregateRepositoryConstructor::createWith(
                    $orders
                )
            ], $referenceServices)
        );
    }
}