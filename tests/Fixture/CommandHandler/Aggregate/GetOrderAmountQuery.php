<?php

namespace Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;

/**
 * Class GetAmountQuery
 * @package Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetOrderAmountQuery
{
    /**
     * @var int
     * @AggregateIdAnnotation()
     */
    private $orderId;

    /**
     * GetOrderAmountQuery constructor.
     *
     * @param int $orderId
     */
    private function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param int $orderId
     *
     * @return GetOrderAmountQuery
     */
    public static function createWith(int $orderId) : self
    {
        return new self($orderId);
    }
}