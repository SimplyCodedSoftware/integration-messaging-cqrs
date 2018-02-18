<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine;

use Doctrine\ORM\EntityManager;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfigurationExtension;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\AggregateRepositoryExtension;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\RequiredReferenceAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class DoctrineCqrsModuleExtension
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationExtensionAnnotation(moduleName="cqrsModule", requiredReferences={
 *      @RequiredReferenceAnnotation(requiredReferenceName="doctrineEntityManager")
 * })
 */
class AnnotationDoctrineExtensionForCqrsModule implements AggregateRepositoryExtension
{
    /**
     * @var EntityManagerAggregateRepository
     */
    private $entityManagerAggregateRepository;

    /**
     * @inheritDoc
     */
    public function configure(ReferenceSearchService $referenceSearchService): void
    {
        $entityManager = $referenceSearchService->findByReference("doctrineEntityManager");
        Assert::isSubclassOf($entityManager, EntityManager::class, "Reference to service doctrineEntityManager should resolve to " . EntityManager::class);

        $this->entityManagerAggregateRepository->withEntityManager($entityManager);
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryFor(string $aggregateClassName): AggregateRepository
    {
        $this->entityManagerAggregateRepository = new EntityManagerAggregateRepository($aggregateClassName);

        return $this->entityManagerAggregateRepository;
    }

    /**
     * @inheritDoc
     */
    public static function create(ConfigurationVariableRetrievingService $configurationVariableRetrievingService): ModuleConfigurationExtension
    {
        return new self();
    }
}