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
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateCallingCommandHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var AggregateRepository
     */
    private $aggregateRepository;
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
     * AggregateCallingCommandHandlerBuilder constructor.
     * @param AggregateRepository $aggregateRepository
     * @param string $aggregateClassName
     * @param string $methodName
     */
    private function __construct(AggregateRepository $aggregateRepository, string $aggregateClassName, string $methodName)
    {
        $this->aggregateRepository = $aggregateRepository;
        $this->aggregateClassName = $aggregateClassName;
        $this->methodName = $methodName;

        $this->initialize($this->aggregateClassName, $methodName);
    }

    /**
     * @param AggregateRepository $aggregateRepository
     * @param string $aggregateClassName
     * @param string $methodName
     * @return AggregateCallingCommandHandlerBuilder
     */
    public static function createWith(AggregateRepository $aggregateRepository, string $aggregateClassName, string $methodName) : self
    {
        return new self($aggregateRepository, $aggregateClassName, $methodName);
    }

    /**
     * @param string $consumerName
     * @return AggregateCallingCommandHandlerBuilder
     */
    public function setConsumerName(string $consumerName) : self
    {
        $this->consumerName = $consumerName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->consumerName;
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
        $commandParameter = $interfaceToCall->parameters()[0];

        $parameterConverters = [MessageToPayloadParameterConverter::create($interfaceToCall->getFirstParameterName())];
        foreach ($this->methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            $messageToParameterConverter = $methodParameterConverterBuilder->build($referenceSearchService);
            if ($messageToParameterConverter->isHandling($commandParameter)) {
                continue;
            }

            $parameterConverters[] = $messageToParameterConverter;
        }

        return new AggregateCallingCommandHandler(
            $this->aggregateRepository,
            $this->aggregateClassName,
            $this->methodName,
            $parameterConverters,
            $this->isFactoryMethod
        );
    }

    /**
     * @param string $aggregateClassName
     * @param string $methodName
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $aggregateClassName, string $methodName) : void
    {
        $interfaceToCall = InterfaceToCall::create($aggregateClassName, $methodName);

        if (!$interfaceToCall->doesItNotReturnValue() && !$interfaceToCall->isStaticallyCalled()) {
            throw InvalidArgumentException::create("{$aggregateClassName} with method {$methodName} must not return value for command handling");
        }
        if ($interfaceToCall->isStaticallyCalled()) {
            $this->isFactoryMethod = true;
        }

        $this->inputChannelName = $interfaceToCall->getFirstParameterTypeHint();
    }
}