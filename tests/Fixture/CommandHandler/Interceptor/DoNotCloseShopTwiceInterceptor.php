<?php

namespace Fixture\CommandHandler\Interceptor;

/**
 * Class DoNotCloseShopTwiceInterceptor
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DoNotCloseShopTwiceInterceptor
{
    private function __construct()
    {
    }

    /**
     * @return DoNotCloseShopTwiceInterceptor
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @param ShopAggregateExample $aggregate
     */
    public function doNotCloseTwice(ShopAggregateExample $aggregate) : void
    {
        if ($aggregate->isClosed()) {
            throw new \InvalidArgumentException("Do not close shop twice");
        }
    }
}