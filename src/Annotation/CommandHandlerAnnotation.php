<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class CommandHandlerAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class CommandHandlerAnnotation
{
    /**
     * @var array
     */
    public $parameterConverters = [];
}