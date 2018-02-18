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
     * @param string $aggregateClassName
     */
    public function __construct(string $aggregateClassName)
    {
        $this->aggregateClassName = $aggregateClassName;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function withEntityManager(EntityManager $entityManager) : void
    {
        $this->entityManager = $entityManager;
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