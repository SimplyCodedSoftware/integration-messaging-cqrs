<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\AggregateRepositoryConstructor;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
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
     * @var string
     */
    private $outputChannelName;

    /**
     * AggregateCallingCommandHandlerBuilder constructor.
     *
     * @param null|string                $inputChannelName
     * @param string                     $aggregateClassName
     * @param string                     $methodName
     * @param bool                       $isCommandHandler
     * @param string                     $outChannelName
     */
    private function __construct(string $inputChannelName, string $aggregateClassName, string $methodName, bool $isCommandHandler, string $outChannelName)
    {
        $this->aggregateClassName = $aggregateClassName;
        $this->methodName         = $methodName;
        $this->outputChannelName  = $outChannelName;
        $this->inputChannelName = $inputChannelName;

        $this->initialize($this->aggregateClassName, $methodName, $isCommandHandler);
    }

    /**
     * @param string $inputChannelName
     * @param string $aggregateClassName
     * @param string $methodName
     *
     * @return AggregateMessageHandlerBuilder
     */
    public static function createCommandHandlerWith(string $inputChannelName, string $aggregateClassName, string $methodName): self
    {
        return new self($inputChannelName, $aggregateClassName, $methodName, true, "");
    }

    /**
     * @param string                    $inputChannelName
     * @param string                         $aggregateClassName
     * @param string                         $methodName
     *
     * @return AggregateMessageHandlerBuilder
     */
    public static function createQueryHandlerWith(string $inputChannelName, string $aggregateClassName, string $methodName): self
    {
        return new self($inputChannelName, $aggregateClassName, $methodName, false, "");
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
     * @param string $outputChannelName
     *
     * @return AggregateMessageHandlerBuilder
     */
    public function withOutputChannelName(string $outputChannelName) : self
    {
        $this->outputChannelName = $outputChannelName;

        return $this;
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
    public function withInputChannelName(string $inputChannelName) : self
    {
        $this->inputChannelName = $inputChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $interfaceToCall = InterfaceToCall::create($this->aggregateClassName, $this->methodName);
        $parameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            $parameterConverters[] = $methodParameterConverterBuilder->build($referenceSearchService);
        }

        /** @var AggregateRepositoryFactory $aggregateRepositoryFactory */
        $aggregateRepositoryFactory = $referenceSearchService->findByReference(CqrsMessagingModule::CQRS_MODULE);


        return new AggregateMessageHandler(
            $aggregateRepositoryFactory->getRepositoryFor($referenceSearchService, $this->aggregateClassName),
            $channelResolver,
            $this->aggregateClassName,
            $this->methodName,
            $parameterConverters,
            $interfaceToCall->isStaticallyCalled(),
            $this->outputChannelName
        );
    }

    /**
     * @param string $aggregateClassName
     * @param string $methodName
     * @param bool   $isCommandHandler
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $aggregateClassName, string $methodName, bool $isCommandHandler): void
    {
        $interfaceToCall = InterfaceToCall::create($aggregateClassName, $methodName);

        if ($interfaceToCall->hasReturnValue() && !$interfaceToCall->isStaticallyCalled() && $isCommandHandler) {
            throw InvalidArgumentException::create("{$aggregateClassName} with method {$methodName} must not return value for command handling");
        }
    }
}