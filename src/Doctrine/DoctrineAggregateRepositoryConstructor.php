<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\AggregateRepositoryConstructor;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class DoctrineAggregateRepositoryBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DoctrineAggregateRepositoryConstructor implements AggregateRepositoryConstructor, ModuleExtension
{
    private const REQUIRED_DOCTRINE_REFERENCE = "doctrine";

    private function __construct()
    {
    }

    /**
     * @return DoctrineAggregateRepositoryConstructor
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, string $aggregateClassName): AggregateRepository
    {
        return new DoctrineAggregateRepositoryAdapter($this->getObjectManager($referenceSearchService), $aggregateClassName);
    }

    /**
     * @inheritDoc
     */
    public function canHandle(ReferenceSearchService $referenceSearchService, string $aggregateClassName): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return CqrsMessagingModule::CQRS_MODULE;
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [
            RequiredReference::create(self::REQUIRED_DOCTRINE_REFERENCE, ObjectManager::class, "Unity of work for doctrine")
        ];
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return ManagerRegistry
     */
    private function getObjectManager(ReferenceSearchService $referenceSearchService) : ManagerRegistry
    {
        return $referenceSearchService->findByReference(self::REQUIRED_DOCTRINE_REFERENCE);
    }
}