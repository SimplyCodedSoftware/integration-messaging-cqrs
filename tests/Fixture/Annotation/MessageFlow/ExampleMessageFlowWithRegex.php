<?php

namespace Fixture\Annotation\MessageFlow;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\MessageFlowRegistration;

/**
 * Class ExampleMessageFlowWithRegex
 * @package Fixture\Annotation\MessageFlow
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContextAnnotation()
 */
class ExampleMessageFlowWithRegex
{
    /**
     * @return MessageFlowRegistration
     * @MessageFlowComponentAnnotation()
     */
    public function createExternalFlow() : MessageFlowRegistration
    {
        return MessageFlowRegistration::createExternalFlow("example*", "externalChannelWithRegex");
    }
}