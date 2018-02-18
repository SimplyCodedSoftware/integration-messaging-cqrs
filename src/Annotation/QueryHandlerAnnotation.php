<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class QueryHandlerAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class QueryHandlerAnnotation
{
    /**
     * @var array
     */
    public $parameterConverters = [];
}