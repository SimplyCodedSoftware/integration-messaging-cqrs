<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

/**
 * Interface AggregateRepository
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateRepository
{
    /**
     * @param string $aggregateId
     * @return object|null
     * @throws AggregateNotFoundException
     */
    public function findBy(string $aggregateId);

    /**
     * @param string $aggregateId
     * @param int $expectedVersion
     * @return object|null
     * @throws AggregateVersionMismatchException|AggregateNotFoundException
     */
    public function findWithLockingBy(string $aggregateId, int $expectedVersion);

    /**
     * @param object $aggregate
     */
    public function save($aggregate) : void;
}