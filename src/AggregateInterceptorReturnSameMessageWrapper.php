<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class NoReturningAggregateInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateInterceptorReturnSameMessageWrapper
{
    /**
     * @var MessageHandler
     */
    private $messageHandler;

    /**
     * AggregateInterceptorReturnSameMessageWrapper constructor.
     *
     * @param MessageHandler $messageHandler
     */
    public function __construct(MessageHandler $messageHandler)
    {
        $this->messageHandler = $messageHandler;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): Message
    {
        $this->messageHandler->handle($message);

        return $message;
    }
}