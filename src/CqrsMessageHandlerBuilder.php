<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\AggregateRepositoryConstructor;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToHeaderConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\NullableMessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CqrsMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    private $referenceClassName;
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
     * @var CallInterceptor[]
     */
    private $preSendInterceptors = [];
    /**
     * @var CallInterceptor[]
     */
    private $postSendInterceptors = [];
    /**
     * @var bool
     */
    private $isAggregateBased;

    /**
     * AggregateCallingCommandHandlerBuilder constructor.
     *
     * @param null|string $inputChannelName
     * @param string      $aggregateClassName
     * @param string      $methodName
     * @param bool        $isCommandHandler
     * @param string      $outChannelName
     * @param bool        $isAggregateBased
     */
    private function __construct(string $inputChannelName, string $aggregateClassName, string $methodName, bool $isCommandHandler, string $outChannelName, bool $isAggregateBased)
    {
        $this->referenceClassName = $aggregateClassName;
        $this->methodName         = $methodName;
        $this->outputChannelName  = $isCommandHandler ? NullableMessageChannel::CHANNEL_NAME : $outChannelName;
        $this->inputChannelName   = $inputChannelName;
        $this->isAggregateBased   = $isAggregateBased;

        $this->initialize($this->referenceClassName, $methodName, $isCommandHandler, $isAggregateBased);
    }

    /**
     * @param string $inputChannelName
     * @param string $aggregateClassName
     * @param string $methodName
     *
     * @return CqrsMessageHandlerBuilder
     */
    public static function createAggregateCommandHandlerWith(string $inputChannelName, string $aggregateClassName, string $methodName): self
    {
        return new self($inputChannelName, $aggregateClassName, $methodName, true, "", true);
    }

    /**
     * @param string $inputChannelName
     * @param string $aggregateClassName
     * @param string $methodName
     *
     * @return CqrsMessageHandlerBuilder
     */
    public static function createServiceCommandHandlerWith(string $inputChannelName, string $aggregateClassName, string $methodName): self
    {
        return new self($inputChannelName, $aggregateClassName, $methodName, true, "", false);
    }

    /**
     * @param string                    $inputChannelName
     * @param string                         $aggregateClassName
     * @param string                         $methodName
     *
     * @return CqrsMessageHandlerBuilder
     */
    public static function createAggregateQueryHandlerWith(string $inputChannelName, string $aggregateClassName, string $methodName): self
    {
        return new self($inputChannelName, $aggregateClassName, $methodName, false, "", true);
    }

    /**
     * @param string $inputChannelName
     * @param string $aggregateClassName
     * @param string $methodName
     *
     * @return CqrsMessageHandlerBuilder
     */
    public static function createServiceQueryHandlerWith(string $inputChannelName, string $aggregateClassName, string $methodName): self
    {
        return new self($inputChannelName, $aggregateClassName, $methodName, false, "", false);
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
     * @return CqrsMessageHandlerBuilder
     */
    public function withOutputMessageChannel(string $outputChannelName) : self
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, MessageToParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @param CallInterceptor[] $interceptors
     *
     * @return CqrsMessageHandlerBuilder
     */
    public function withPreCallInterceptors(array $interceptors) : self
    {
        Assert::allInstanceOfType($interceptors, CallInterceptor::class);

        $this->preSendInterceptors = $interceptors;

        foreach ($interceptors as $interceptor) {
            $this->registerRequiredReference($interceptor->getReferenceName());
        }

        return $this;
    }

    /**
     * @param CallInterceptor[] $interceptors
     *
     * @return CqrsMessageHandlerBuilder
     */
    public function withPostCallInterceptors(array $interceptors) : self
    {
        Assert::allInstanceOfType($interceptors, CallInterceptor::class);

        $this->postSendInterceptors = $interceptors;

        foreach ($interceptors as $interceptor) {
            foreach ($interceptor->getRequiredReferences() as $requiredReference) {
                $this->registerRequiredReference($requiredReference);
            }
        }

        return $this;
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
        $chainCqrsMessageHandler = ChainMessageHandlerBuilder::createWith("");

        /** @var AggregateRepositoryFactory $aggregateRepositoryFactory */
        $aggregateRepositoryFactory = $referenceSearchService->findByReference(CqrsMessagingModule::CQRS_MODULE);
        $interfaceToCall     = InterfaceToCall::create($this->referenceClassName, $this->methodName);

        if ($this->isAggregateBased) {
            $chainCqrsMessageHandler
                ->chain(
                    ServiceActivatorBuilder::createWithDirectReference(
                        "",
                        new LoadAggregateService(
                            $aggregateRepositoryFactory->getRepositoryFor($referenceSearchService, $this->referenceClassName),
                            $this->referenceClassName,
                            $this->methodName,
                            $interfaceToCall->isStaticallyCalled()
                        ),
                        "load"
                    )
                );
        }

        $this->registerInterceptors($channelResolver, $referenceSearchService, $chainCqrsMessageHandler, $this->preSendInterceptors);

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $parameterConverterBuilder) {
            $methodParameterConverters[] = $parameterConverterBuilder->build($referenceSearchService);
        }

        if ($this->isAggregateBased) {
            $chainCqrsMessageHandler
                ->chain(
                    ServiceActivatorBuilder::createWithDirectReference(
                        "",
                        new CallAggregateService($channelResolver, $methodParameterConverters),
                        "call"
                    )
                );
        }else {
            $chainCqrsMessageHandler->chain(ServiceActivatorBuilder::create("", $this->referenceClassName, $this->methodName));
        }

        $this->registerInterceptors($channelResolver, $referenceSearchService, $chainCqrsMessageHandler, $this->postSendInterceptors);

        if ($this->isAggregateBased) {
            $chainCqrsMessageHandler
                ->chain(
                    ServiceActivatorBuilder::createWithDirectReference(
                        "",
                        new SaveAggregateService(),
                        "save"
                    )
                );
        }

        return $chainCqrsMessageHandler
                    ->withOutputMessageChannel($this->outputChannelName)
                    ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @param ChannelResolver            $channelResolver
     * @param ReferenceSearchService     $referenceSearchService
     * @param ChainMessageHandlerBuilder $chainAggregateMessageHandler
     * @param CallInterceptor[] $interceptors
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function registerInterceptors(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, ChainMessageHandlerBuilder $chainAggregateMessageHandler, array $interceptors): void
    {
        foreach ($interceptors as $interceptorToRegister) {
            $chainAggregateMessageHandler->chain($interceptorToRegister->build($channelResolver, $referenceSearchService));
        }
    }

    /**
     * @param string $aggregateClassName
     * @param string $methodName
     * @param bool   $isCommandHandler
     * @param bool   $isAggregateBased
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $aggregateClassName, string $methodName, bool $isCommandHandler, bool $isAggregateBased): void
    {
        if (!$isAggregateBased) {
            $this->registerRequiredReference($aggregateClassName);

            return;
        }

        $interfaceToCall = InterfaceToCall::create($aggregateClassName, $methodName);

        if ($interfaceToCall->hasReturnValue() && !$interfaceToCall->isStaticallyCalled() && $isCommandHandler) {
            throw InvalidArgumentException::create("{$aggregateClassName} with method {$methodName} must not return value for command handling");
        }

        if (!$interfaceToCall->hasReturnValue() && !$isCommandHandler) {
            throw InvalidArgumentException::create("{$aggregateClassName} with method {$methodName} must return value for query handling");
        }
    }
}