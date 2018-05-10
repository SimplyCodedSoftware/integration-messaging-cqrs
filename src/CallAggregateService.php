<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class CallAggregateService
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallAggregateService
{
    /**
     * @var array|MessageToParameterConverter[]
     */
    private $messageToParameterConverters;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;

    /**
     * ServiceCallToAggregateAdapter constructor.
     *
     * @param ChannelResolver                     $channelResolver
     * @param array|MessageToParameterConverter[] $messageToParameterConverters
     */
    public function __construct(ChannelResolver $channelResolver, array $messageToParameterConverters)
    {
        Assert::allInstanceOfType($messageToParameterConverters, MessageToParameterConverter::class);

        $this->messageToParameterConverters = $messageToParameterConverters;
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param Message $message
     *
     * @return Message
     * @throws MessageHandlingException
     */
    public function call(Message $message) : Message
    {
        $aggregate = $message->getHeaders()->containsKey(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER)
                            ? $message->getHeaders()->get(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER)
                            : null;
        $methodInvoker = MethodInvoker::createWith(
            $aggregate ? $aggregate : $message->getHeaders()->get(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_CLASS_NAME_HEADER),
            $message->getHeaders()->get(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_METHOD_HEADER),
            $this->messageToParameterConverters
        );

        $resultMessage = MessageBuilder::fromMessage($message);
        try {
            $result = $methodInvoker->processMessage($message);

            if (!$aggregate) {
                $resultMessage = $resultMessage
                    ->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER, $result);
            }
        } catch (\Throwable $e) {
            throw MessageHandlingException::fromOtherException($e, $message);
        }

        if (!is_null($result)) {
            $resultMessage = $resultMessage
                ->setPayload($result);
        }

        return $resultMessage
                ->build();
    }
}