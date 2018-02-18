<?php

namespace Fixture\CommandHandler\Aggregate;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateExpectedVersionAnnotation;

/**
 * Class MultiplyAmountCommand
 * @package Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MultiplyAmountCommand
{
    /**
     * @var string
     * @AggregateIdAnnotation()
     */
    private $orderId;
    /**
     * @var int
     * @AggregateExpectedVersionAnnotation()
     */
    private $version;
    /**
     * @var int
     */
    private $amount;

    /**
     * MultiplyAmountCommand constructor.
     * @param string $orderId
     * @param int $version
     * @param int $amount
     */
    private function __construct(string $orderId, int $version, int $amount)
    {
        $this->orderId = $orderId;
        $this->version = $version;
        $this->amount = $amount;
    }

    /**
     * @param string $orderId
     * @param int $version
     * @param int $amount
     * @return MultiplyAmountCommand
     */
    public static function create(string $orderId, int $version, int $amount) : self
    {
        return new self($orderId, $version, $amount);
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
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }
}