<?php

namespace Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;

/**
 * Class AggregateQueryHandlerWithOutputChannelExample
 * @package Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @AggregateAnnotation()
 */
class AggregateQueryHandlerWithOutputChannelExample
{
    /**
     * @param SomeQuery $query
     *
     * @return SomeResult
     * @QueryHandlerAnnotation(
     *     outputChannelName="outputChannel"
     * )
     */
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }
}