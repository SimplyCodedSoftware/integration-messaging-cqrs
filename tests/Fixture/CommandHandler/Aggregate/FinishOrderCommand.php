<?php

namespace Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;

/**
 * Class FinishOrderCommand
 * @package Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FinishOrderCommand
{
    /**
     * @var string
     * @AggregateIdAnnotation()
     */
    private $orderId;

    /**
     * FinishOrderCommand constructor.
     * @param string $orderId
     */
    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param string $orderId
     * @return FinishOrderCommand
     */
    public static function create(string $orderId) : self
    {
        return new self($orderId);
    }
}