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
class CommandHandlerAnnotation extends WithInterceptors
{
    /**
     * @var array
     */
    public $parameterConverters = [];
    /**
     * If handler has no need in message payload, you can add name of the class name in annotation
     *
     * @var string
     */
    public $messageClassName;
}