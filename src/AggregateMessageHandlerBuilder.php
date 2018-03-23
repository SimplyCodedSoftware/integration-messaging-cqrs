<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var AggregateRepositoryBuilder
     */
    private $aggregateRepositoryBuilder;
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|\SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder[]
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string
     */
    private $consumerName;
    /**
     * @var string
     */
    private $inputChannelName;
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var bool
     */
    private $isFactoryMethod = false;
    /**
     * @var bool
     */
    private $isCommandHandler;
    /**
     * @var string
     */
    private $outChannelName;

    /**
     * AggregateCallingCommandHandlerBuilder constructor.
     *
     * @param null|string                $inputChannelName
     * @param AggregateRepositoryBuilder $aggregateRepositoryBuilder
     * @param string                     $aggregateClassName
     * @param string                     $methodName
     * @param bool                       $isCommandHandler
     * @param string                     $outChannelName
     */
    private function __construct(?string $inputChannelName, AggregateRepositoryBuilder $aggregateRepositoryBuilder, string $aggregateClassName, string $methodName, bool $isCommandHandler, string $outChannelName)
    {
        $this->aggregateRepositoryBuilder = $aggregateRepositoryBuilder;
        $this->aggregateClassName         = $aggregateClassName;
        $this->methodName                 = $methodName;
        $this->isCommandHandler           = $isCommandHandler;
        $this->outChannelName             = $outChannelName;

        $this->initialize($inputChannelName, $this->aggregateClassName, $methodName);
    }

    /**
     * @param AggregateRepositoryBuilder $aggregateRepository
     * @param string                     $aggregateClassName
     * @param string                     $methodName
     *
     * @return AggregateMessageHandlerBuilder
     */
    public static function createCommandHandlerWith(AggregateRepositoryBuilder $aggregateRepository, string $aggregateClassName, string $methodName): self
    {
        return new self(null, $aggregateRepository, $aggregateClassName, $methodName, true, "");
    }

    /**
     * @param null|string                $inputChannelName
     * @param AggregateRepositoryBuilder $aggregateRepository
     * @param string                     $aggregateClassName
     * @param string                     $methodName
     *
     * @return AggregateMessageHandlerBuilder
     */
    public static function createQueryHandlerWith(?string $inputChannelName, AggregateRepositoryBuilder $aggregateRepository, string $aggregateClassName, string $methodName): self
    {
        return new self($inputChannelName, $aggregateRepository, $aggregateClassName, $methodName, false, "");
    }

    /**
     * @param null|string                $inputChannelName
     * @param AggregateRepositoryBuilder $aggregateRepository
     * @param string                     $aggregateClassName
     * @param string                     $methodName
     * @param string                     $outputChannelName
     *
     * @return AggregateMessageHandlerBuilder
     */
    public static function createQueryHandlerWithOutputChannel(?string $inputChannelName, AggregateRepositoryBuilder $aggregateRepository, string $aggregateClassName, string $methodName, string $outputChannelName): self
    {
        return new self($inputChannelName, $aggregateRepository, $aggregateClassName, $methodName, false, $outputChannelName);
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->consumerName;
    }

    /**
     * @param string $consumerName
     *
     * @return AggregateMessageHandlerBuilder
     */
    public function setConsumerName(string $consumerName): self
    {
        $this->consumerName = $consumerName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferences[] = $referenceName;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): void
    {
        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $interfaceToCall = InterfaceToCall::create($this->aggregateClassName, $this->methodName);
        $hasArguments    = count($interfaceToCall->parameters()) > 0;

        $parameterConverters = $hasArguments ? [MessageToPayloadParameterConverter::create($interfaceToCall->getFirstParameterName())] : [];
        foreach ($this->methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            $messageToParameterConverter = $methodParameterConverterBuilder->build($referenceSearchService);
            if ($hasArguments && $messageToParameterConverter->isHandling($interfaceToCall->parameters()[0])) {
                continue;
            }

            $parameterConverters[] = $messageToParameterConverter;
        }

        return new AggregateMessageHandler(
            $this->aggregateRepositoryBuilder->build($this->aggregateClassName, $referenceSearchService),
            $channelResolver,
            $this->aggregateClassName,
            $this->methodName,
            $parameterConverters,
            $this->isFactoryMethod,
            $this->outChannelName
        );
    }

    /**
     * @param null|string $inputChannelName
     * @param string      $aggregateClassName
     * @param string      $methodName
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(?string $inputChannelName, string $aggregateClassName, string $methodName): void
    {
        $interfaceToCall = InterfaceToCall::create($aggregateClassName, $methodName);

        if (!$interfaceToCall->doesItNotReturnValue() && !$interfaceToCall->isStaticallyCalled() && $this->isCommandHandler) {
            throw InvalidArgumentException::create("{$aggregateClassName} with method {$methodName} must not return value for command handling");
        }
        if ($interfaceToCall->isStaticallyCalled()) {
            $this->isFactoryMethod = true;
        }

        $this->inputChannelName = $inputChannelName ? $inputChannelName : $interfaceToCall->getFirstParameterTypeHint();
    }
}