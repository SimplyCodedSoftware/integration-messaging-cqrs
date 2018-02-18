<?php

namespace Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;

/**
 * Class ChangeShippingAddressCommand
 * @package Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChangeShippingAddressCommand
{
    /**
     * @var string
     * @AggregateIdAnnotation()
     */
    private $orderId;
    /**
     * @var int
     */
    private $version;
    /**
     * @var string
     */
    private $shippingAddress;

    /**
     * ChangeShippingAddressCommand constructor.
     * @param string $orderId
     * @param int $version
     * @param string $shippingAddress
     */
    private function __construct(string $orderId, int $version, string $shippingAddress)
    {
        $this->orderId = $orderId;
        $this->version = $version;
        $this->shippingAddress = $shippingAddress;
    }

    public static function create(string $orderId, int $version, string $shippingAddress) : self
    {
        return new self($orderId, $version, $shippingAddress);
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }
}