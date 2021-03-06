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
class QueryHandlerAnnotation extends WithInterceptors
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
    /**
     * Optional output channel for extra transformations of query handler result
     *
     * @var string
     */
    public $outputChannelName = "";
}