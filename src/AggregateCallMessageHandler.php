<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class AggregateRepository
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class AggregateCallMessageHandler implements MessageHandler
{
    /**
     * @var AggregateRepository
     */
    private $aggregateRepository;
    /**
     * @var array|MessageToParameterConverter[]
     */
    private $messageToParameterConverters;
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var bool
     */
    private $isFactoryMethod;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var string
     */
    private $outputChannelName;

    /**
     * ServiceCallToAggregateAdapter constructor.
     *
     * @param AggregateRepository                 $aggregateRepository
     * @param ChannelResolver                     $channelResolver
     * @param string                              $aggregateClassName
     * @param string                              $methodName
     * @param array|MessageToParameterConverter[] $messageToParameterConverters
     * @param bool                                $isFactoryMethod
     * @param string                              $outputChannelName
     */
    public function __construct(AggregateRepository $aggregateRepository, ChannelResolver $channelResolver, string $aggregateClassName, string $methodName, array $messageToParameterConverters, bool $isFactoryMethod, string $outputChannelName)
    {
        $this->aggregateRepository          = $aggregateRepository;
        $this->messageToParameterConverters = $messageToParameterConverters;
        $this->aggregateClassName           = $aggregateClassName;
        $this->methodName                   = $methodName;
        $this->isFactoryMethod              = $isFactoryMethod;
        $this->channelResolver = $channelResolver;
        $this->outputChannelName = $outputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $aggregate = $message->getHeaders()->containsKey(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER)
                        ? $message->getHeaders()->get(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER)
                        : $message->getHeaders()->get(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_CLASS_NAME_HEADER);

        $methodInvoker = MethodInvoker::createWith($aggregate, $this->methodName, $this->messageToParameterConverters);
        try {
            if ($this->isFactoryMethod) {
                $aggregate = $methodInvoker->processMessage($message);
            } else {
                $requestReply = RequestReplyProducer::createRequestAndReply(
                    $this->outputChannelName,
                    $methodInvoker,
                    $this->channelResolver,
                    false
                );

                $requestReply->handleWithReply($message);
            }
        } catch (\Throwable $e) {
            throw MessageHandlingException::fromOtherException($e, $message);
        }

        $this->aggregateRepository->save($aggregate);
    }
}