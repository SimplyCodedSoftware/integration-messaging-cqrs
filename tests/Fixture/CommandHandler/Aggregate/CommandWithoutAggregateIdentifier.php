<?php

namespace Fixture\CommandHandler\Aggregate;

/**
 * Class CommandWithoutAggregateIdentifier
 * @package Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CommandWithoutAggregateIdentifier
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * CommandWithoutAggregateIdentifier constructor.
     * @param string $orderId
     */
    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public static function create(string $orderId) : self
    {
        return new self($orderId);
    }
}