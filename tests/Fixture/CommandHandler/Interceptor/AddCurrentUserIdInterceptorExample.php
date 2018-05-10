<?php

namespace Fixture\CommandHandler\Interceptor;

/**
 * Class AddHeaderInterceptorExample
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AddCurrentUserIdInterceptorExample
{
    private function __construct()
    {
    }

    /**
     * @return AddCurrentUserIdInterceptorExample
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @return array
     */
    public function addCurrentUserId() : array
    {
        return [
            "userId" => 1
        ];
    }
}