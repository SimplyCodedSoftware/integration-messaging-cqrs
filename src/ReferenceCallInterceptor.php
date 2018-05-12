<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class AggregateInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceCallInterceptor implements CallInterceptor
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
     * @return ReferenceCallInterceptor
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
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [$this->referenceName];
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandlerBuilderWithOutputChannel
    {
        $interceptorInterface = InterfaceToCall::createFromObject(
            $referenceSearchService->findByReference($this->referenceName),
            $this->methodName
        );

        if (!$interceptorInterface->hasReturnTypeVoid() && $interceptorInterface->isReturnTypeUnknown()) {
            throw InvalidArgumentException::create("{$this} must have return value or be void");
        }

        if ($interceptorInterface->hasReturnTypeVoid()) {
                return ServiceActivatorBuilder::createWithDirectReference(
                    "",
                    new AggregateInterceptorReturnSameMessageWrapper(
                        ServiceActivatorBuilder::create("", $this->referenceName, $this->methodName)
                            ->withMethodParameterConverters($this->parameterConverters)
                            ->build($channelResolver, $referenceSearchService)
                    ),
                    "handle"
                );
        } else {
            return TransformerBuilder::create("", $this->referenceName, $this->methodName)
                    ->withMethodParameterConverters($this->parameterConverters);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Interceptor {$this->referenceName}:{$this->methodName}";
    }
}