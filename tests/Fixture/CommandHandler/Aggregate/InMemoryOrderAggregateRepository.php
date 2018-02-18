<?php

namespace Fixture\CommandHandler\Aggregate;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateNotFoundException;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateVersionMismatchException;

/**
 * Class InMemoryAggregateRepository
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryOrderAggregateRepository implements AggregateRepository
{
    /**
     * @var array
     */
    private $aggregates = [];

    /**
     * InMemoryAggregateRepository constructor.
     * @param array|Order[] $aggregates
     */
    private function __construct(array $aggregates)
    {
        foreach ($aggregates as $aggregate) {
            $this->save($aggregate);
        }
    }

    /**
     * @param array $aggregates
     * @return InMemoryOrderAggregateRepository
     */
    public static function createWith(array $aggregates) : self
    {
        return new self($aggregates);
    }

    /**
     * @return InMemoryOrderAggregateRepository
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateId)
    {
        if (!array_key_exists($aggregateId, $this->aggregates)) {
            throw AggregateNotFoundException::create("Aggregate with id {$aggregateId} was not found");
        }

        return $this->aggregates[$aggregateId];
    }

    /**
     * @inheritDoc
     */
    public function findWithLockingBy(string $aggregateId, int $expectedVersion)
    {
        /** @var VersionAggregate $aggregate */
        $aggregate =  $this->findBy($aggregateId);

        if ($expectedVersion != $aggregate->getVersion()) {
            throw AggregateVersionMismatchException::create("Expected aggregate version {$expectedVersion} got {$aggregate->getVersion()}");
        }

        return $aggregate;
    }

    /**
     * @inheritDoc
     */
    public function save($aggregate): void
    {
        /** @var Order $aggregate */

        $this->aggregates[$aggregate->getOrderId()] = $aggregate;
    }
}