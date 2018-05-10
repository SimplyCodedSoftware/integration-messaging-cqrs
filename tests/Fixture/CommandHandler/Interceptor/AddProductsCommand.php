<?php

namespace Fixture\CommandHandler\Interceptor;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;

/**
 * Class AddProductsCommand
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AddProductsCommand
{
    /**
     * @var string
     * @AggregateIdAnnotation()
     */
    private $shopName;

    private $amount = 0;

    /**
     * AddProductsCommand constructor.
     *
     * @param string $shopName
     * @param int    $amount
     */
    private function __construct(string $shopName, $amount)
    {
        $this->amount = $amount;
        $this->shopName = $shopName;
    }

    /**
     * @param string $shopName
     * @param int    $amount
     *
     * @return AddProductsCommand
     */
    public static function create(string $shopName, int $amount) : self
    {
        return new self($shopName, $amount);
    }

    /**
     * @return string
     */
    public function getShopName(): string
    {
        return $this->shopName;
    }

    /**
     * @return int
     */
    public function getAmount() : int
    {
        return $this->amount;
    }
}