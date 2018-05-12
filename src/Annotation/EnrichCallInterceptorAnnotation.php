<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;

/**
 * Class EnrichingCallInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class EnrichCallInterceptorAnnotation
{
    /**
     * @var array
     */
    public $messageToMessageSetters = [];
    /**
     * @var string
     */
    public $requestMessageChannel = "";
    /**
     * @var string
     */
    public $requestPayloadExpression = "";
    /**
     * @var array
     */
    public $requestHeaders = [];
}