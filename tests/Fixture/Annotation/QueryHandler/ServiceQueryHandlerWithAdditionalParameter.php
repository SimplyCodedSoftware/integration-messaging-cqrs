<?php

namespace Fixture\Annotation\QueryHandler;

use Ramsey\Uuid\UuidInterface;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;

/**
 * Class ServiceQueryHandlerWithAdditionalParameter
 * @package Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class ServiceQueryHandlerWithAdditionalParameter
{
    /**
     * @param SomeQuery     $query
     * @param UuidInterface $currentUserId
     *
     * @return SomeResult
     *
     * @QueryHandlerAnnotation(
     *     parameterConverters={
     *         @MessageToHeaderParameterAnnotation(parameterName="currentUserId", headerName="currentUserId")
     *     }
     * )
     */
    public function searchFor(SomeQuery $query, UuidInterface $currentUserId) : SomeResult
    {
        return new SomeResult();
    }
}