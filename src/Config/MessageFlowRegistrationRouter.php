<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

/**
 * Class MessageFlowRegistrationRouter
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class MessageFlowRegistrationRouter
{
    /**
     * @param MessageFlowRegistration $messageFlowRegistration
     *
     * @return string
     */
    public function routeMessageByRegistration(MessageFlowRegistration $messageFlowRegistration) : string
    {
        return $messageFlowRegistration->getMessageChannel();
    }
}