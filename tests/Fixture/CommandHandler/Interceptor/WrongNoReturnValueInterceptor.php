<?php

namespace Fixture\CommandHandler\Interceptor;

/**
 * Class WrongNoReturnValueInterceptor
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class WrongNoReturnValueInterceptor
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

    public function wrongCall()
    {
        return new \stdClass();
    }
}