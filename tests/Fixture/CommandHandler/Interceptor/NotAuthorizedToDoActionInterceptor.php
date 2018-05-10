<?php

namespace Fixture\CommandHandler\Interceptor;

/**
 * Class NotAuthorizedToDoActionInterceptor
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NotAuthorizedToDoActionInterceptor
{
    private function __construct()
    {
    }

    /**
     * @return NotAuthorizedToDoActionInterceptor
     */
    public static function create() : self
    {
        return new self();
    }

    public function isAuthorized() : void
    {
        throw new \InvalidArgumentException("Not Authorized!");
    }
}