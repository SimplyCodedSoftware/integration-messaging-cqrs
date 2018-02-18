<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatingHandler;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class CommandHandlerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceCommandHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var string
     */
    private $inputChannelName;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|\SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder[]
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var string
     */
    private $consumerName;

    /**
     * ServiceActivatorBuilder constructor.
     * @param string $inputChannelName
     * @param string $objectToInvokeOnReferenceName
     * @param string $methodName
     */
    private function __construct(string $inputChannelName, string $objectToInvokeOnReferenceName, string $methodName)
    {
        $this->inputChannelName = $inputChannelName;
        $this->referenceName = $objectToInvokeOnReferenceName;
        $this->methodName = $methodName;

        $this->requiredReferences[] = $objectToInvokeOnReferenceName;
    }

    /**
     * @param string $inputChannelName
     * @param string $referenceName
     * @param string $methodName
     * @return ServiceCommandHandlerBuilder
     */
    public static function create(string $inputChannelName, string $referenceName, string $methodName): self
    {
        return new self($inputChannelName, $referenceName, $methodName);
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
    public function getConsumerName(): string
    {
        return $this->consumerName;
    }

    /**
     * @param string $consumerName
     * @return ServiceCommandHandlerBuilder
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): void
    {
        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $parameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $methodParameterConverterBuilder) {
            $parameterConverters[] = $methodParameterConverterBuilder->build($referenceSearchService);
        }
        $objectToInvoke = $referenceSearchService->findByReference($this->referenceName);
        $interfaceToCall = InterfaceToCall::createFromObject($objectToInvoke, $this->methodName);

        if (!$interfaceToCall->doesItNotReturnValue()) {
            throw InvalidArgumentException::create("Can't assign command handler {$this->referenceName}, because it has return value");
        }

        return new ServiceActivatingHandler(
            RequestReplyProducer::createRequestAndReply(
                "",
                MethodInvoker::createWith(
                    $objectToInvoke,
                    $this->methodName,
                    $parameterConverters
                ),
                $channelResolver,
                false
            )
        );
    }
}