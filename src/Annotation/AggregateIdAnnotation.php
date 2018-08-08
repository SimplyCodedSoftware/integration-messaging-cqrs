<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class AggregateIdAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"PROPERTY"})
 */
class AggregateIdAnnotation
{
    /**
     * @var string
     */
    public $name = "";
}