<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

/**
 * Class MessageFlowRegistration
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFlowRegistration
{
    /**
     * @var string
     */
    private $messageName;
    /**
     * @var string
     */
    private $messageClassName;
    /**
     * @var string
     */
    private $channelName;

    /**
     * MessageFlowRegistration constructor.
     *
     * @param string $messageName
     * @param string $messageClassName
     * @param string $channelName
     */
    private function __construct(string $messageName, string $messageClassName, string $channelName)
    {
        $this->messageName      = $messageName;
        $this->messageClassName = $messageClassName;
        $this->channelName      = $channelName;
    }

    /**
     * @param string $messageName
     * @param string $messageClassName
     * @param string $channelName
     *
     * @return MessageFlowRegistration
     */
    public static function createLocalFlow(string $messageName, string $messageClassName, string $channelName) : self
    {
        return new self($messageName, $messageClassName, $channelName);
    }

    /**
     * @param string $messageName
     * @param string $channelName
     *
     * @return MessageFlowRegistration
     */
    public static function createExternalFlow(string $messageName, string $channelName) : self
    {
        return new self($messageName, "", $channelName);
    }

    /**
     * @return string
     */
    public function getMessageName(): string
    {
        return $this->messageName;
    }

    /**
     * @return bool
     */
    public function isExternalFlow() : bool
    {
        return !(bool)$this->messageClassName;
    }

    /**
     * @return string
     */
    public function getMessageClassName(): string
    {
        return $this->messageClassName;
    }

    /**
     * @return string
     */
    public function getMessageChannel() : string
    {
        return $this->channelName ? $this->channelName : MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_DEFAULT_FLOW_CHANNEL;
    }
}