<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;
use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerExample;
use Fixture\Annotation\CommandHandler\Service\SomeCommand;
use Fixture\Annotation\QueryHandler\SomeQuery;
use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Configuration\DumbModuleConfigurationRetrievingService;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\DoctrineClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\FileSystemClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine\AnnotationDoctrineExtensionForCqrsModule;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine\EntityManagerAggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class IntegrationMessagingCqrsModule
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CqrsMessagingModuleTest extends TestCase
{
//    @TODO && behat tests

//    public function test_creating_command_handler_service()
//    {
//        $configuration = $this->createMessagingSystemConfiguration();
//        $annotationConfiguration = $this->createAnnotationConfiguration("CommandHandler\Service");
//
//        $annotationConfiguration->registerWithin($configuration, InMemoryConfigurationVariableRetrievingService::createEmpty());
//
//        $this->assertEquals(
//            $this->createMessagingSystemConfiguration()
//                ->registerMessageHandler(
//                    ServiceActivatorBuilder::create("some", "doAction")
//                        ->withConsumerName("some-doAction")
//                        ->withInputMessageChannel(SomeCommand::class)
//                        ->withRequiredReply(false)
//                ),
//            $configuration
//        );
//    }
//
//    public function test_creating_query_handler_service()
//    {
//        $configuration = $this->createMessagingSystemConfiguration();
//        $annotationConfiguration = $this->createAnnotationConfiguration("QueryHandler");
//
//        $annotationConfiguration->registerWithin($configuration, InMemoryConfigurationVariableRetrievingService::createEmpty());
//
//        $this->assertEquals(
//            $this->createMessagingSystemConfiguration()
//                ->registerMessageHandler(
//                    ServiceActivatorBuilder::create("some", "searchFor")
//                        ->withConsumerName("some-searchFor")
//                        ->withInputMessageChannel(SomeQuery::class)
//                        ->withRequiredReply(true)
//                ),
//            $configuration
//        );
//    }
//
//    public function test_creating_aggregate_command_handler()
//    {
//        $configuration = $this->createMessagingSystemConfiguration();
//        $annotationConfiguration = $this->createAnnotationConfigurationWithExtension(
//            [AnnotationDoctrineExtensionForCqrsModule::create(InMemoryConfigurationVariableRetrievingService::createEmpty())],
//            "CommandHandler\Aggregate"
//        );
//
//        $annotationConfiguration->registerWithin($configuration, InMemoryConfigurationVariableRetrievingService::createEmpty());
//
//        $this->assertEquals(
//            $this->createMessagingSystemConfiguration()
//                ->registerMessageHandler(
//                    AggregateCallingCommandHandlerBuilder::createWith(
//                        new EntityManagerAggregateRepository(AggregateCommandHandlerExample::class),
//                        AggregateCommandHandlerExample::class,
//                        "doAction"
//                    )
//                    ->setConsumerName(AggregateCommandHandlerExample::class . "-doAction")
//                ),
//            $configuration
//        );
//    }

    /**
     * @param string $namespacePart
     * @return AnnotationConfiguration
     */
    private function createAnnotationConfiguration(string $namespacePart) : AnnotationConfiguration
    {
        return $this->createAnnotationConfigurationWithExtension([], $namespacePart);
    }

    /**
     * @param array $extensions
     * @param string $namespacePart
     * @return AnnotationConfiguration
     */
    private function createAnnotationConfigurationWithExtension(array $extensions, string $namespacePart) : AnnotationConfiguration
    {
        $annotationReader = new AnnotationReader();

        return CqrsMessagingModule::createAnnotationConfiguration(
            $extensions,
            InMemoryConfigurationVariableRetrievingService::createEmpty(),
            new FileSystemClassLocator(
                $annotationReader,
                [
                    __DIR__ . "/../../Fixture/Annotation"
                ],
                [
                    "Fixture\Annotation\\" . $namespacePart
                ]
            ),
            new DoctrineClassMetadataReader(
                $annotationReader
            )
        );
    }

    /**
     * @return MessagingSystemConfiguration
     */
    private function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(
            DumbModuleConfigurationRetrievingService::createEmpty(),
            InMemoryConfigurationVariableRetrievingService::createEmpty(),
            DumbConfigurationObserver::create()
        );
    }
}