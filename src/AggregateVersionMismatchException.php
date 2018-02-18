<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

/**
 * Class AggregateVersionMismatchException
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateVersionMismatchException extends MessagingException
{
    public const AGGREGATE_VERSION_MISMATCH = 1001;

    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::AGGREGATE_VERSION_MISMATCH;
    }
}