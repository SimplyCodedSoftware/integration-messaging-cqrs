<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class ClassInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class ClassMethodInterceptorAnnotation extends WithInterceptors
{
    /**
     * List of names for excluded methods
     *
     * @var array
     */
    public $excludedMethods = [];
}