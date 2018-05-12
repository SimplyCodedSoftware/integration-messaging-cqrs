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
class ReferenceCallInterceptorAnnotation
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
    /**
     * List of names for excluded methods. Used only in context of ClassMethodInterceptor
     *
     * @var array
     */
    public $excludedMethods = [];
}