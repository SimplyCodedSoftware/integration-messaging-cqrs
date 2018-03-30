<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class AggregateRepository
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class AggregateMessageHandler implements MessageHandler
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
        $commandReflection = new \ReflectionClass($message->getPayload());
        $aggregateId       = "";
        $expectedVersion   = null;
        foreach ($commandReflection->getProperties() as $property) {
            if (preg_match("*AggregateIdAnnotation*", $property->getDocComment())) {
                $property->setAccessible(true);
                $aggregateId = (string)$property->getValue($message->getPayload());
            }
            if (preg_match("*AggregateExpectedVersionAnnotation*", $property->getDocComment())) {
                $property->setAccessible(true);
                $expectedVersion = $property->getValue($message->getPayload());
            }
        }

        $aggregate = $this->aggregateClassName;
        if (!$this->isFactoryMethod) {
            if (!$aggregateId) {
                throw AggregateNotFoundException::create("There is no aggregate id to search for found. Are you sure you defined AggregateId Annotation?");
            }

            $aggregate = is_null($expectedVersion)
                ? $this->aggregateRepository->findBy($aggregateId)
                : $this->aggregateRepository->findWithLockingBy($aggregateId, $expectedVersion);
        }

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