<?php

namespace Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepositoryBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class InMemoryAggregateRepositoryBuilder
 * @package Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryOrderAggregateRepositoryBuilder implements AggregateRepositoryBuilder
{
    /**
     * @var AggregateRepository
     */
    private $orderAggregateRepository;

    /**
     * InMemoryOrderAggregateRepositoryBuilder constructor.
     *
     * @param array $orders
     */
    private function __construct(array $orders)
    {
        $this->orderAggregateRepository = InMemoryOrderAggregateRepository::createWith($orders);
    }

    /**
     * @param array $orders
     *
     * @return InMemoryOrderAggregateRepositoryBuilder
     */
    public static function createWith(array $orders) : self
    {
        return new self($orders);
    }

    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function build(string $aggregateClassName, ReferenceSearchService $referenceSearchService): AggregateRepository
    {
        return $this->orderAggregateRepository;
    }

    public function findBy(int $id) : ?Order
    {
        return $this->orderAggregateRepository->findBy($id);
    }
}