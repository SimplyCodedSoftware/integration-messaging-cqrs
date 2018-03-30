<?php

namespace Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;

/**
 * Class QueryHandlerWithNoReturnValue
 * @package Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class QueryHandlerWithNoReturnValue
{
    /**
     * @param SomeQuery $query
     * @return void
     * @QueryHandlerAnnotation()
     */
    public function searchFor(SomeQuery $query) : void
    {
        return;
    }
}