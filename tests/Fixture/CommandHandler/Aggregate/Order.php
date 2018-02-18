<?php

namespace Fixture\CommandHandler\Aggregate;

/**
 * Class Order
 * @package Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Order implements VersionAggregate
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var int
     */
    private $amount = 0;
    /**
     * @var string
     */
    private $shippingAddress;
    /**
     * @var int
     */
    private $version = 0;
    /**
     * @var string
     */
    private $customerId;

    private function __construct(CreateOrderCommand $createOrderCommand)
    {
        $this->orderId = $createOrderCommand->getOrderId();
        $this->amount = $createOrderCommand->getAmount();
        $this->shippingAddress = $createOrderCommand->getShippingAddress();

        $this->increaseAggregateVersion();
    }

    public static function createWith(CreateOrderCommand $command) : self
    {
        return new self($command);
    }

    public function increaseAmount() : void
    {
        $this->amount += 1;
        $this->increaseAggregateVersion();
    }

    public function changeShippingAddress(ChangeShippingAddressCommand $command) : void
    {
        $this->shippingAddress = $command->getShippingAddress();
        $this->increaseAggregateVersion();
    }


    public function multiplyOrder(MultiplyAmountCommand $command) : void
    {
        $this->amount *= $command->getAmount();
        $this->increaseAggregateVersion();
    }

    /**
     * @param FinishOrderCommand $command
     * @param string $customerId
     */
    public function finish(FinishOrderCommand $command, string $customerId) : void
    {
        $this->customerId = $customerId;
    }

    /**
     * @param CommandWithoutAggregateIdentifier $command
     */
    public function wrongCommand(CommandWithoutAggregateIdentifier $command) : void
    {

    }

    /**
     * @return string
     */
    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    /**
     * @return int
     */
    public function getOrderId() : int
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    public function hasVersion(int $version) : bool
    {
        return $this->version == $version;
    }

    /**
     * @return string
     */
    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    private function increaseAggregateVersion() : void
    {
        $this->version += 1;
    }
}