<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerExample;
use Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithClassLevelMethodsInterceptorAndOnlyFactoryMethodExample;
use Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithClassLevelMethodAndExclusionInterceptorExample;
use Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithPostCallInterceptorExample;
use Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithClassLevelMethodsInterceptorExample;
use Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithPreCallInterceptorExample;
use Fixture\Annotation\CommandHandler\Aggregate\DoStuffCommand;
use Fixture\Annotation\CommandHandler\Service\CommandHandlerServiceExample;
use Fixture\Annotation\CommandHandler\Service\CommandHandlerServiceWithCommandDefinedInAnnotation;
use Fixture\Annotation\CommandHandler\Service\CommandHandlerServiceWithParametersExample;
use Fixture\Annotation\CommandHandler\Service\CommandHandlerWithNoCommandInformationConfiguration;
use Fixture\Annotation\CommandHandler\Service\CommandHandlerWithReturnValue;
use Fixture\Annotation\CommandHandler\Service\HelloWorldCommand;
use Fixture\Annotation\CommandHandler\Service\SomeCommand;
use Fixture\Annotation\QueryHandler\AggregateQueryHandlerExample;
use Fixture\Annotation\QueryHandler\AggregateQueryHandlerWithOutputChannelExample;
use Fixture\Annotation\QueryHandler\QueryHandlerServiceExample;
use Fixture\Annotation\QueryHandler\QueryHandlerServiceWithClassMetadataDefined;
use Fixture\Annotation\QueryHandler\QueryHandlerWithNoReturnValue;
use Fixture\Annotation\QueryHandler\SomeQuery;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateCallMessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\CallInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\CqrsMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToReferenceServiceParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class IntegrationMessagingCqrsModule
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CqrsMessagingModuleTest extends TestCase
{
    public function test_building_cqrs_messaging_configuration()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration();

        $this->createModuleAndAssertConfiguration([], $expectedConfiguration);
    }

    public function test_registering_service_command_handler()
    {
        $serviceActivator = CqrsMessageHandlerBuilder::createServiceCommandHandlerWith(SomeCommand::class, "serviceCommandHandlerExample", "doAction");
        $serviceActivator->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("command")]);
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(SomeCommand::class))
            ->registerMessageHandler($serviceActivator);

        $this->createModuleAndAssertConfiguration([
            CommandHandlerServiceExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_service_command_handler_with_message_parameters()
    {
        $serviceActivator = CqrsMessageHandlerBuilder::createServiceCommandHandlerWith(HelloWorldCommand::class, CommandHandlerServiceWithParametersExample::class, "sayHello");
        $serviceActivator->registerRequiredReference("calculator");
        $serviceActivator->withMethodParameterConverters(
            [
                MessageToPayloadParameterConverterBuilder::create("command"),
                MessageToHeaderParameterConverterBuilder::create("name", "userName"),
                MessageToReferenceServiceParameterConverterBuilder::create("object", "calculator", $serviceActivator)
            ]
        );

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(HelloWorldCommand::class))
            ->registerMessageHandler($serviceActivator);

        $this->createModuleAndAssertConfiguration([
            CommandHandlerServiceWithParametersExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_service_command_handler_with_message_name_defined_in_annotation()
    {
        $serviceActivator = CqrsMessageHandlerBuilder::createServiceCommandHandlerWith(SomeCommand::class, CommandHandlerServiceWithCommandDefinedInAnnotation::class, "execute");
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(SomeCommand::class))
            ->registerMessageHandler($serviceActivator);

        $this->createModuleAndAssertConfiguration([
            CommandHandlerServiceWithCommandDefinedInAnnotation::class
        ], $expectedConfiguration);
    }

    public function test_throwing_configuration_exception_if_command_handler_has_no_information_about_command()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->prepareConfiguration([
            CommandHandlerWithNoCommandInformationConfiguration::class
        ]);
    }

    public function test_throwing_exception_if_command_handler_has_return_value()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->prepareConfiguration([
            CommandHandlerWithReturnValue::class
        ]);
    }

    public function test_registering_service_query_handler()
    {
        $serviceActivator = CqrsMessageHandlerBuilder::createServiceQueryHandlerWith(SomeQuery::class, QueryHandlerServiceExample::class, "searchFor")
                                ->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("query")]);
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(SomeQuery::class))
            ->registerMessageHandler($serviceActivator);

        $this->createModuleAndAssertConfiguration([
            QueryHandlerServiceExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_service_query_handler_with_class_metadata_defined()
    {
        $serviceActivator = CqrsMessageHandlerBuilder::createServiceQueryHandlerWith(SomeQuery::class, QueryHandlerServiceWithClassMetadataDefined::class, "searchFor")
                                ->withMethodParameterConverters([MessageToHeaderParameterConverterBuilder::create("personId", "currentUserId")]);
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(SomeQuery::class))
            ->registerMessageHandler($serviceActivator);

        $this->createModuleAndAssertConfiguration([
            QueryHandlerServiceWithClassMetadataDefined::class
        ], $expectedConfiguration);
    }

    public function test_throwing_exception_if_query_handler_has_no_return_value()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->prepareConfiguration([
            QueryHandlerWithNoReturnValue::class
        ]);
    }

    public function test_registering_aggregate_command_handler()
    {
        $commandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(DoStuffCommand::class, AggregateCommandHandlerExample::class, "doAction");
        $commandHandler->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("command")]);

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(DoStuffCommand::class))
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration([
            AggregateCommandHandlerExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_aggregate_query_handler()
    {
        $commandHandler = CqrsMessageHandlerBuilder::createAggregateQueryHandlerWith(SomeQuery::class, AggregateQueryHandlerExample::class, "doStuff");
        $commandHandler->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("query")]);

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(SomeQuery::class))
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration([
            AggregateQueryHandlerExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_aggregate_query_handler_with_output_channel()
    {
        $commandHandler = CqrsMessageHandlerBuilder::createAggregateQueryHandlerWith(SomeQuery::class, AggregateQueryHandlerWithOutputChannelExample::class, "doStuff")
                            ->withOutputMessageChannel("outputChannel");
        $commandHandler->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("query")]);

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(SomeQuery::class))
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration([
            AggregateQueryHandlerWithOutputChannelExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_aggregate_command_handler_with_pre_call_interceptors()
    {
        $commandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(DoStuffCommand::class, AggregateCommandHandlerWithPreCallInterceptorExample::class, "interceptedCommand")
            ->withOutputMessageChannel("nullChannel")
            ->withPreCallInterceptors([
                CallInterceptor::create("some", "action", [
                    MessageToPayloadParameterConverterBuilder::create("command")
                ])
            ]);
        $commandHandler->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("stuffCommand")]);

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(DoStuffCommand::class))
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration([
            AggregateCommandHandlerWithPreCallInterceptorExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_aggregate_command_handler_with_post_call_interceptors()
    {
        $commandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(DoStuffCommand::class, AggregateCommandHandlerWithPostCallInterceptorExample::class, "interceptedCommand")
            ->withOutputMessageChannel("nullChannel")
            ->withPostCallInterceptors([
                CallInterceptor::create("some", "action", [
                    MessageToPayloadParameterConverterBuilder::create("command")
                ])
            ]);
        $commandHandler->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("stuffCommand")]);

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(DoStuffCommand::class))
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration([
            AggregateCommandHandlerWithPostCallInterceptorExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_aggregate_command_handler_with_pre_and_post_class_call_interceptors()
    {
        $commandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(DoStuffCommand::class, AggregateCommandHandlerWithClassLevelMethodsInterceptorExample::class, "interceptedCommand")
            ->withOutputMessageChannel("nullChannel")
            ->withPreCallInterceptors([
                CallInterceptor::create("some", "action", [
                    MessageToPayloadParameterConverterBuilder::create("command")
                ])
            ])
            ->withPostCallInterceptors([
                CallInterceptor::create("some", "action", [
                    MessageToPayloadParameterConverterBuilder::create("command")
                ])
            ]);
        $commandHandler->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("stuffCommand")]);

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(DoStuffCommand::class))
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration([
            AggregateCommandHandlerWithClassLevelMethodsInterceptorExample::class
        ], $expectedConfiguration);
    }

    public function test_registering_aggregate_command_handler_with_pre_and_post_class_factory_call_interceptors()
    {
        $commandHandler = CqrsMessageHandlerBuilder::createAggregateCommandHandlerWith(DoStuffCommand::class, AggregateCommandHandlerWithClassLevelMethodAndExclusionInterceptorExample::class, "interceptedCommand")
            ->withOutputMessageChannel("nullChannel");
        $commandHandler->withMethodParameterConverters([MessageToPayloadParameterConverterBuilder::create("stuffCommand")]);

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(DoStuffCommand::class))
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration([
            AggregateCommandHandlerWithClassLevelMethodAndExclusionInterceptorExample::class
        ], $expectedConfiguration);
    }

    /**
     * @return MessagingSystemConfiguration
     */
    protected function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @param array      $annotationClassesToRegister
     * @param Configuration $expectedConfiguration
     */
    private function createModuleAndAssertConfiguration(array $annotationClassesToRegister, Configuration $expectedConfiguration): void
    {
        $expectedConfiguration = $expectedConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL))
            ->registerMessageHandler(RouterBuilder::createPayloadTypeRouterByClassName(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL));

        $this->assertEquals(
            $expectedConfiguration,
            $this->prepareConfiguration($annotationClassesToRegister)
        );
    }

    /**
     * @param array $annotationClassesToRegister
     *
     * @return MessagingSystemConfiguration
     */
    private function prepareConfiguration(array $annotationClassesToRegister): MessagingSystemConfiguration
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom($annotationClassesToRegister);
        $cqrsMessagingModule   = CqrsMessagingModule::create($annotationRegistrationService);

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            [],
            NullObserver::create()
        );

        return $extendedConfiguration;
    }
}