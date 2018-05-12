<?php

namespace Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;

/**
 * Class Order
 * @package Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @AggregateAnnotation()
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

    /**
     * Order constructor.
     *
     * @param CreateOrderCommand $createOrderCommand
     */
    private function __construct(CreateOrderCommand $createOrderCommand)
    {
        $this->orderId = $createOrderCommand->getOrderId();
        $this->amount = $createOrderCommand->getAmount();
        $this->shippingAddress = $createOrderCommand->getShippingAddress();

        $this->increaseAggregateVersion();
    }

    /**
     * @param CreateOrderCommand $command
     *
     * @return Order
     * @CommandHandlerAnnotation()
     */
    public static function createWith(CreateOrderCommand $command) : self
    {
        return new self($command);
    }

    public function increaseAmount() : void
    {
        $this->amount += 1;
        $this->increaseAggregateVersion();
    }

    /**
     * @param ChangeShippingAddressCommand $command
     * @CommandHandlerAnnotation()
     */
    public function changeShippingAddress(ChangeShippingAddressCommand $command) : void
    {
        $this->shippingAddress = $command->getShippingAddress();
        $this->increaseAggregateVersion();
    }


    /**
     * @param MultiplyAmountCommand $command
     * @CommandHandlerAnnotation()
     */
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
    public function getId() : int
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

    /**
     * @param GetOrderAmountQuery $query
     *
     * @return int
     * @QueryHandlerAnnotation()
     */
    public function getAmountWithQuery(GetOrderAmountQuery $query) : int
    {
        return $this->amount;
    }

    public function hasVersion(int $version) : bool
    {
        return $this->version == $version;
    }

    /**
     * @return string
     * @QueryHandlerAnnotation(messageClassName=GetShippingAddressQuery::class)
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