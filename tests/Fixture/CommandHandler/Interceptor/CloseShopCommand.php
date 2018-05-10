<?php

namespace Fixture\CommandHandler\Interceptor;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;

/**
 * Class CloseShopCommand
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CloseShopCommand
{
    /**
     * @var string
     * @AggregateIdAnnotation()
     */
    private $shopName;

    /**
     * CloseShopCommand constructor.
     *
     * @param string $shopName
     */
    private function __construct($shopName)
    {
        $this->shopName = $shopName;
    }

    /**
     * @param string $shopName
     *
     * @return CloseShopCommand
     */
    public static function create(string $shopName) : self
    {
        return new self($shopName);
    }
}