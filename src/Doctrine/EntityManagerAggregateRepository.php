<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateNotFoundException;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateVersionMismatchException;

/**
 * Class EntityManagerAggregateRepository
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EntityManagerAggregateRepository implements AggregateRepository
{
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * EntityManagerAggregateRepository constructor.
     *
     * @param EntityManager $entityManager
     * @param string        $aggregateClassName
     */
    public function __construct(EntityManager $entityManager, string $aggregateClassName)
    {
        $this->entityManager = $entityManager;
        $this->aggregateClassName = $aggregateClassName;
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateId)
    {
        return $this->entityManager->getRepository($this->aggregateClassName)->find($aggregateId);
    }

    /**
     * @inheritDoc
     */
    public function findWithLockingBy(string $aggregateId, int $expectedVersion)
    {
        return $this->entityManager->getRepository($this->aggregateClassName)->find($aggregateId, LockMode::OPTIMISTIC, $expectedVersion);
    }

    /**
     * @inheritDoc
     */
    public function save($aggregate): void
    {
        $this->entityManager->persist($aggregate);
    }
}