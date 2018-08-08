<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

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
     * @var ManagerRegistry|EntityRepository
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
    public function findBy(array $identifiers)
    {
        return $this->getRepository()->findOneBy($identifiers);
    }

    /**
     * @inheritDoc
     */
    public function findWithLockingBy(array $identifiers, int $expectedVersion)
    {
        if (count($identifiers) != 1) {
            throw new InvalidArgumentException("Doctrine does not support multiple identifiers for locking mode");
        }

        return $this->getRepository()->find($identifiers[0], LockMode::OPTIMISTIC, $expectedVersion);
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