<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class AggregatorInterceptorAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class CallInterceptorAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $referenceName;
    /**
     * @var string
     * @Required()
     */
    public $methodName;
    /**
     * @var array
     */
    public $parameterConverters = [];
}