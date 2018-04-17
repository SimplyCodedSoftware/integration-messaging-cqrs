<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

/**
 * Class MessageFlowMapper
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFlowMapper
{
    /**
     * @var MessageFlowRegistration[][]
     */
    private $messageFlowRegistrations;

    /**
     * MessageFlowMapper constructor.
     *
     * @param MessageFlowRegistration[][] $messageFlowRegistrations
     */
    private function __construct(array $messageFlowRegistrations)
    {
        $this->messageFlowRegistrations = $messageFlowRegistrations;
    }

    /**
     * @param MessageFlowRegistration[][] $messageFlowRegistrations
     *
     * @return MessageFlowMapper
     */
    public static function createWith(array $messageFlowRegistrations) : self
    {
        return new self($messageFlowRegistrations);
    }

    /**
     * @param string $messageName
     *
     * @return MessageFlowRegistration[]
     * @throws NoMessageNameDefinedForMessageFlowException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getFlowRegistrationsFor(string $messageName) : array
    {
        $messageFlows = [];
        foreach ($this->messageFlowRegistrations as $messageFlowName => $definedMessageFlows) {
            if (preg_match("#{$messageFlowName}#", $messageName)) {
                $messageFlows = array_merge($messageFlows, $definedMessageFlows);
            }
        }

        if (!$messageFlows) {
            throw NoMessageNameDefinedForMessageFlowException::create("No message with name {$messageName} defined in message flow. Did you remember to add MessageFlowAnnotation?");
        }

        return $messageFlows;
    }
}