<?php

namespace Fixture\CommandHandler\Interceptor;

/**
 * Class ShopAggregateExample
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ShopAggregateExample
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $ownerId;
    /**
     * @var bool
     */
    private $isClosed = false;
    /**
     * @var int
     */
    private $products = 0;

    /**
     * ShopAggregateExample constructor.
     *
     * @param string $ownerId
     * @param string $name
     */
    private function __construct(string $ownerId, string $name)
    {
        $this->name = $name;
        $this->ownerId = $ownerId;
    }

    /**
     * @param CreateShopCommand $command
     *
     * @param string            $ownerId
     *
     * @return ShopAggregateExample
     */
    public static function create(CreateShopCommand $command, string $ownerId) : self
    {
        return new self($ownerId, $command->getName());
    }

    /**
     * @param string $name
     *
     * @return ShopAggregateExample
     */
    public static function createWithoutOwner(string $name) : self
    {
        return new self("", $name);
    }

    /**
     * @return int
     */
    public function getProductsAmount() : int
    {
        return $this->products;
    }

    /**
     * @param AddProductsCommand $command
     *
     */
    public function addProducts(AddProductsCommand $command) : void
    {
        $this->products += $command->getAmount();
    }

    public function close() : void
    {
        $this->isClosed = true;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->name;
    }

    public function isClosed() : bool
    {
        return $this->isClosed;
    }
}