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
        $this->outputChannelName  = $isCommandHandler ? NullableMessageChannel::CHANNEL_NAME : $outChannelName;
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, MessageToParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @param CallInterceptor[] $interceptors
     *
     * @return AggregateMessageHandlerBuilder
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
        $chainAggregateMessageHandler = ChainMessageHandlerBuilder::createWith("");

        /** @var AggregateRepositoryFactory $aggregateRepositoryFactory */
        $aggregateRepositoryFactory = $referenceSearchService->findByReference(CqrsMessagingModule::CQRS_MODULE);
        $interfaceToCall     = InterfaceToCall::create($this->aggregateClassName, $this->methodName);

        $chainAggregateMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    "",
                    new LoadAggregateService(
                        $aggregateRepositoryFactory->getRepositoryFor($referenceSearchService, $this->aggregateClassName),
                        $this->aggregateClassName,
                        $this->methodName,
                        $interfaceToCall->isStaticallyCalled()
                    ),
                    "load"
                )
            );

        $this->registerPreSendInterceptors($channelResolver, $referenceSearchService, $chainAggregateMessageHandler);

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $parameterConverterBuilder) {
            $methodParameterConverters[] = $parameterConverterBuilder->build($referenceSearchService);
        }

        $chainAggregateMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    "",
                    new CallAggregateService($channelResolver, $methodParameterConverters),
                    "call"
                )
            );

        $chainAggregateMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    "",
                    new SaveAggregateService(),
                    "save"
                )
            );

        return $chainAggregateMessageHandler
                    ->withOutputMessageChannel($this->outputChannelName)
                    ->build($channelResolver, $referenceSearchService);
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

    /**
     * @param ChannelResolver            $channelResolver
     * @param ReferenceSearchService     $referenceSearchService
     * @param ChainMessageHandlerBuilder $chainAggregateMessageHandler
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function registerPreSendInterceptors(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, ChainMessageHandlerBuilder $chainAggregateMessageHandler): void
    {
        foreach ($this->preSendInterceptors as $preSendInterceptor) {
            $interceptor = InterfaceToCall::createFromObject(
                $referenceSearchService->findByReference($preSendInterceptor->getReferenceName()),
                $preSendInterceptor->getMethodName()
            );

            if (!$interceptor->hasReturnTypeVoid() && $interceptor->isReturnTypeUnknown()) {
                throw InvalidArgumentException::create("{$preSendInterceptor} must have return value or be void");
            }

            if ($interceptor->hasReturnTypeVoid()) {
                $chainAggregateMessageHandler->chain(
                    ServiceActivatorBuilder::createWithDirectReference(
                        "",
                            new AggregateInterceptorReturnSameMessageWrapper(
                                ServiceActivatorBuilder::create("", $preSendInterceptor->getReferenceName(), $preSendInterceptor->getMethodName())
                                    ->withMethodParameterConverters($preSendInterceptor->getParameterConverters())
                                    ->build($channelResolver, $referenceSearchService)
                            ),
                        "handle"
                        )
                );
            } else {
                $chainAggregateMessageHandler->chain(
                    TransformerBuilder::create("", $preSendInterceptor->getReferenceName(), $preSendInterceptor->getMethodName())
                        ->withMethodParameterConverters($preSendInterceptor->getParameterConverters())
                );
            }
        }
    }
}