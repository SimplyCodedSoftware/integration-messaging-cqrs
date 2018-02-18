<?php

namespace Fixture\CommandHandler\Service;

/**
 * Class ServiceCommandHandlerExample
 * @package Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceCommandHandlerExample
{
    /**
     * @var DoSomethingCommand|null
     */
    private $calledCommand;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @param DoSomethingCommand $doSomethingCommand
     */
    public function doSomething(DoSomethingCommand $doSomethingCommand) : void
    {
        $this->calledCommand = $doSomethingCommand;
    }

    public function doSomethingWithReturn(DoSomethingCommand $doSomethingCommand) : string
    {
        return "some";
    }

    /**
     * @return DoSomethingCommand|null
     */
    public function getCalledCommand() : ?DoSomethingCommand
    {
        return $this->calledCommand;
    }
}