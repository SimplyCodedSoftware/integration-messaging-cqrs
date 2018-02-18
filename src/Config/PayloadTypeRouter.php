<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class PayloadTypeRouter
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadTypeRouter
{
    /**
     * @return PayloadTypeRouter
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @param Message $message
     * @return string
     */
    public function route(Message $message) : string
    {
        return get_class($message->getPayload());
    }
}