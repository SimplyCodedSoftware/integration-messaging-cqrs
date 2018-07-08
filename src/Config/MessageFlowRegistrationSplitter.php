<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class MessageFlowRegistrationSplitter
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class MessageFlowRegistrationSplitter
{
    /**
     * @var MessageFlowMapper
     */
    private $messageFlowMapper;

    /**
     * MessageFlowRegistrationSplitter constructor.
     *
     * @param MessageFlowMapper $messageFlowMapper
     */
    public function __construct(MessageFlowMapper $messageFlowMapper)
    {
        $this->messageFlowMapper = $messageFlowMapper;
    }

    /**
     * @param Message $message
     *
     * @return Message[]
     * @throws NoMessageNameDefinedForMessageFlowException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function split(Message $message) : array
    {
        $splittedMessages = [];
        $messageName      = $message->getHeaders()->get(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_NAME_HEADER);

        foreach($this->messageFlowMapper->getFlowRegistrationsFor($messageName) as $messageFlowRegistration) {
            $registrationMessage     = MessageBuilder::fromMessage($message)
                ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_FLOW_REGISTRATION_HEADER, $messageFlowRegistration);

            if (!$messageFlowRegistration->isExternalFlow()) {
                $registrationMessage = $registrationMessage
                    ->setHeader(MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_CLASS_HEADER, $messageFlowRegistration->getMessageClassName());
            }

            $splittedMessages[] = $registrationMessage
                                    ->build();
        }

        return $splittedMessages;
    }
}