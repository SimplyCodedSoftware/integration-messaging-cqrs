<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;

/**
 * Class WithInterceptors
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class WithInterceptors
{
    /**
     * @var array
     */
    public $preCallInterceptors = [];
    /**
     * @var array
     */
    public $postCallInterceptors = [];
}