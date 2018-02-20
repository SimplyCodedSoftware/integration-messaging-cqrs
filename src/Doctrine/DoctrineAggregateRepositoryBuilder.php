<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepositoryBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class DoctrineAggregateRepositoryBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DoctrineAggregateRepositoryBuilder implements AggregateRepositoryBuilder
{
    private function __construct()
    {
    }

    /**
     * @return DoctrineAggregateRepositoryBuilder
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function build(string $aggregateClassName, ReferenceSearchService $referenceSearchService): AggregateRepository
    {
        return new EntityManagerAggregateRepository($referenceSearchService->findByReference('doctrineEntityManager'), $aggregateClassName);
    }
}