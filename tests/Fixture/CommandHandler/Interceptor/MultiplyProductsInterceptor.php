<?php

namespace Fixture\CommandHandler\Interceptor;

/**
 * Class AddProductInterceptor
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MultiplyProductsInterceptor
{
    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @param AddProductsCommand $command
     *
     * @return AddProductsCommand
     */
    public function multiply(AddProductsCommand $command) : AddProductsCommand
    {
        return AddProductsCommand::create($command->getShopName(),$command->getAmount() * 2);
    }
}