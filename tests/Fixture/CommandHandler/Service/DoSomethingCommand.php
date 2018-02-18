<?php

namespace Fixture\CommandHandler\Service;

/**
 * Class DoSomethingCommand
 * @package Fixture\CommandHandler\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DoSomethingCommand
{
    public static function create() : self
    {
        return new self();
    }
}