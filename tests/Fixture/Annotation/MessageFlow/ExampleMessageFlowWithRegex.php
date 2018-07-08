<?php

namespace Fixture\Annotation\MessageFlow;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponentAnnotation;
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
     * @MessagingComponentAnnotation()
     */
    public function createExternalFlow(): MessageFlowRegistration
    {
        return MessageFlowRegistration::createExternalFlow("example*", "externalChannelWithRegex", false, false);
    }
}