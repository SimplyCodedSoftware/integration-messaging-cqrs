<?php

namespace Fixture\CommandHandler\Aggregate;

/**
 * Interface VersionAggregate
 * @package Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface VersionAggregate
{
    /**
     * @return int
     */
    public function getVersion() : int;
}