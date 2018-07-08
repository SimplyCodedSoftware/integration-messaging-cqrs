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
    private $messageFlowRegistrations = [];

    /**
     * MessageFlowMapper constructor.
     *
     * @param MessageFlowRegistration[] $messageFlowRegistrations
     */
    private function __construct(array $messageFlowRegistrations)
    {
        foreach ($messageFlowRegistrations as $messageFlowRegistration) {
            $this->addRegistration($messageFlowRegistration);
        }
    }

    /**
     * @param MessageFlowRegistration[] $messageFlowRegistrations
     *
     * @return MessageFlowMapper
     */
    public static function createWith(array $messageFlowRegistrations) : self
    {
        return new self($messageFlowRegistrations);
    }

    /**
     * @param MessageFlowRegistration $messageFlowRegistration
     */
    public function addRegistration(MessageFlowRegistration $messageFlowRegistration) : void
    {
        $this->messageFlowRegistrations[str_replace("*", ".*", $messageFlowRegistration->getMessageName())][] = $messageFlowRegistration;
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
            if (preg_match("#^{$messageFlowName}$#", $messageName)) {
                $messageFlows = array_merge($messageFlows, $definedMessageFlows);
            }
        }

        if (!$messageFlows) {
            throw NoMessageNameDefinedForMessageFlowException::create("No message with name {$messageName} defined in message flow. Did you remember to add MessageFlowAnnotation or register Flow via ApplicationContext?");
        }

        return $messageFlows;
    }

    /**
     * @return MessageFlowRegistration[][]
     */
    public function getMessageFlows() : array
    {
        return $this->messageFlowRegistrations;
    }
}