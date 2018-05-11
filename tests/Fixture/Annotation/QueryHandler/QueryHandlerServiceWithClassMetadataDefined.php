<?php

namespace Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;

/**
 * Class QueryHandlerServiceWithClassMetadataDefined
 * @package Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class QueryHandlerServiceWithClassMetadataDefined
{
    /**
     * @param string $personId
     *
     * @return SomeResult
     * @QueryHandlerAnnotation(
     *     messageClassName=SomeQuery::class,
     *     parameterConverters={@MessageToHeaderParameterAnnotation(parameterName="personId", headerName="currentUserId")}
     * )
     */
    public function searchFor(string $personId) : SomeResult
    {
        return new SomeResult();
    }
}