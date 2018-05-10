<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class AggregateInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallInterceptor
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var MessageToParameterConverterBuilder[]
     */
    private $parameterConverters;

    /**
     * AggregateInterceptor constructor.
     *
     * @param string                               $referenceName
     * @param string                               $methodName
     * @param MessageToParameterConverterBuilder[] $parameterConverters
     */
    private function __construct(string $referenceName, string $methodName, array $parameterConverters)
    {
        Assert::allInstanceOfType($parameterConverters, MessageToParameterConverterBuilder::class);

        $this->referenceName       = $referenceName;
        $this->methodName          = $methodName;
        $this->parameterConverters = $parameterConverters;
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @param MessageToParameterConverterBuilder[]  $parameterConverters
     *
     * @return CallInterceptor
     */
    public static function create(string $referenceName, string $methodName, array $parameterConverters) : self
    {
        return new self($referenceName, $methodName, $parameterConverters);
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return MessageToParameterConverterBuilder[]
     */
    public function getParameterConverters(): array
    {
        return $this->parameterConverters;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Interceptor {$this->referenceName}:{$this->methodName}";
    }
}