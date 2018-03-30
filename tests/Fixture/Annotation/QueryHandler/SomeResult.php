<?php

namespace Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateIdAnnotation;

/**
 * Class SomeResult
 * @package Fixture\Annotation\QueryHandler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SomeResult
{
    /**
     * @var string
     * @AggregateIdAnnotation()
     */
    private $aggregateId;
}