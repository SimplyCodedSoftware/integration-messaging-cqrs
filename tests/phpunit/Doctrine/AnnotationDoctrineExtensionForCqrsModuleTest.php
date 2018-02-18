<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine;

use Fixture\Doctrine\DoctrineExampleAggregate;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\AggregateRepositoryExtension;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine\AnnotationDoctrineExtensionForCqrsModule;

/**
 * Class AnnotationDoctrineExtensionForCqrsModuleTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationDoctrineExtensionForCqrsModuleTest extends TestCase
{
    public function test_creation_of_aggregate_repository()
    {
        /** @var AggregateRepositoryExtension $doctrineExtension */
        $doctrineExtension = AnnotationDoctrineExtensionForCqrsModule::create(
            InMemoryConfigurationVariableRetrievingService::create([
                "isProductionRun" => false,
                "pathsToEntityFiles" => [realpath(__DIR__ . "/../../Fixture/Doctrine")],
                "databaseDriver" => "pdo_pgsql",
                "databaseUser"  => "admin",
                "databasePassword" => "secret",
                "databaseName" => "cqrs"
            ])
        );

        $this->assertInstanceOf(AggregateRepository::class, $doctrineExtension->getRepositoryFor(DoctrineExampleAggregate::class));
    }
}