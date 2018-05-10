<?php

namespace Fixture\CommandHandler\Interceptor;

/**
 * Class CreateShopCommand
 * @package Fixture\CommandHandler\Interceptor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CreateShopCommand
{
    /**
     * @var string
     */
    private $name;

    /**
     * CreateShopCommand constructor.
     *
     * @param string $name
     */
    private function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     *
     * @return CreateShopCommand
     */
    public static function create(string $name) : self
    {
        return new self($name);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}