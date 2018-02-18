<?php

namespace Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;

/**
 * Class QueryHandlerServiceExample
 * @package Fixture\Annotation\QueryHandler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation(referenceName="some")
 */
class QueryHandlerServiceExample
{
    /**
     * @param SomeQuery $query
     * @return SomeResult
     * @QueryHandlerAnnotation()
     */
    public function searchFor(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }
}