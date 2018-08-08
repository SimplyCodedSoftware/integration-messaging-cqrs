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
     * @param array $identifiers
     *
     * @return object|null
     */
    public function findBy(array $identifiers);

    /**
     * @param array $identifiers
     * @param int   $expectedVersion
     *
     * @return object|null
     */
    public function findWithLockingBy(array $identifiers, int $expectedVersion);

    /**
     * @param object $aggregate
     */
    public function save($aggregate) : void;
}