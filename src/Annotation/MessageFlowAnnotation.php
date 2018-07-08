<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MessageAnnotation
 *
 * If custom flow is not defined, then it will go integration_messaging.cqrs.start_default_flow channel, otherwise it go to channel after messageName
 *
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class MessageFlowAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $externalName;
    /**
     * @var string
     */
    public $channelName = "";
    /**
     * Auto create when custom channel name passed
     *
     * @var bool
     */
    public $autoCreate = false;
    /**
     * Should autocreated channel be publish subscribe
     *
     * @var bool
     */
    public $isSubscriable = false;
}