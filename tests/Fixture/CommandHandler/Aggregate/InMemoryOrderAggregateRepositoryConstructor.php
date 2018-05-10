<?php

namespace Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepositoryFactory;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\AggregateRepositoryConstructor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class InMemoryAggregateRepositoryBuilder
 * @package Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryOrderAggregateRepositoryConstructor implements AggregateRepositoryConstructor, AggregateRepositoryFactory
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
        $this->orderAggregateRepository = InMemoryAggregateRepository::createWith($orders);
    }

    /**
     * @param array $orders
     *
     * @return InMemoryOrderAggregateRepositoryConstructor
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
    public function canHandle(ReferenceSearchService $referenceSearchService, string $aggregateClassName): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryFor(ReferenceSearchService $referenceSearchService, string $aggregateClassName): AggregateRepository
    {
        return $this->build($referenceSearchService, $aggregateClassName);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, string $aggregateClassName): AggregateRepository
    {
        return $this->orderAggregateRepository;
    }

    public function findBy(int $id) : ?Order
    {
        return $this->orderAggregateRepository->findBy($id);
    }
}