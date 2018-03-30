<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\LockMode;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;

/**
 * Class EntityManagerAggregateRepository
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class DoctrineAggregateRepositoryAdapter implements AggregateRepository
{
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * EntityManagerAggregateRepository constructor.
     *
     * @param ManagerRegistry $managerRegistry
     * @param string          $aggregateClassName
     */
    public function __construct(ManagerRegistry $managerRegistry, string $aggregateClassName)
    {
        $this->managerRegistry    = $managerRegistry;
        $this->aggregateClassName = $aggregateClassName;
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateId)
    {
        return $this->getRepository()->find($aggregateId);
    }

    /**
     * @inheritDoc
     */
    public function findWithLockingBy(string $aggregateId, int $expectedVersion)
    {
        return $this->getRepository()->find($aggregateId, LockMode::OPTIMISTIC, $expectedVersion);
    }

    /**
     * @inheritDoc
     */
    public function save($aggregate): void
    {
        $this->getManager()->persist($aggregate);
    }

    /**
     * @return ObjectRepository
     */
    private function getRepository() : ObjectRepository
    {
        return $this->managerRegistry->getManagerForClass($this->aggregateClassName)->getRepository($this->aggregateClassName);
    }

    /**
     * @return ObjectManager
     */
    private function getManager() : ObjectManager
    {
        return $this->managerRegistry->getManagerForClass($this->aggregateClassName);
    }
}