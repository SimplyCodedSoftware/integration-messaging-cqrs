<?php

namespace Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;

/**
 * Class GetShippingAddressQuery
 * @package Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetShippingAddressQuery
{
    /**
     * @var int
     * @AggregateIdAnnotation()
     */
    private $orderId;

    /**
     * GetShippingAddressQuery constructor.
     *
     * @param int $orderId
     */
    private function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param int $orderId
     *
     * @return GetShippingAddressQuery
     */
    public static function create(int $orderId) : self
    {
        return new self($orderId);
    }
}