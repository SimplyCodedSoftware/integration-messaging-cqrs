<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

/**
 * Class NoMessageNameDefinedForMessageFlowException
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NoMessageNameDefinedForMessageFlowException extends MessagingException
{
    public const NO_MESSAGE_NAME_DEFINED_EXCEPTION = 1005;

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::NO_MESSAGE_NAME_DEFINED_EXCEPTION;
    }
}