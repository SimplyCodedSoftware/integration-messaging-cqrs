<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 30.03.18
 * Time: 09:28
 */

namespace Fixture\Annotation\QueryHandler;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\QueryHandlerAnnotation;

/**
 * Class AggregateQueryHandlerExample
 * @package Fixture\Annotation\QueryHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @AggregateAnnotation()
 */
class AggregateQueryHandlerExample
{
    /**
     * @param SomeQuery $query
     *
     * @return SomeResult
     * @QueryHandlerAnnotation()
     */
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }
}