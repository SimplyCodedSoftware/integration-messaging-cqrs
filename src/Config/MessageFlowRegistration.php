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
     * @var bool
     */
    private $isSubscribable;
    /**
     * @var bool
     */
    private $isAutoRegistered;

    /**
     * MessageFlowRegistration constructor.
     *
     * @param string $messageName
     * @param string $messageClassName
     * @param string $channelName
     * @param bool $isAutoRegistered
     * @param bool $isSubscribable
     */
    private function __construct(string $messageName, string $messageClassName, string $channelName, bool $isAutoRegistered, bool $isSubscribable)
    {
        $this->messageName      = $messageName;
        $this->messageClassName = $messageClassName;
        $this->channelName      = $channelName;
        $this->isSubscribable = $isSubscribable;
        $this->isAutoRegistered = $isAutoRegistered;
    }

    /**
     * @param string $messageName
     * @param string $messageClassName
     * @param string $channelName
     * @param bool $isChannelAutoRegisterd
     * @param bool $isSubscribable
     *
     * @return MessageFlowRegistration
     */
    public static function createLocalFlow(string $messageName, string $messageClassName, string $channelName, bool $isChannelAutoRegisterd, bool $isSubscribable) : self
    {
        return new self($messageName, $messageClassName, $channelName, $isChannelAutoRegisterd, $isSubscribable);
    }

    /**
     * @param string $messageName
     * @param string $channelName
     *
     * @param bool $isAutoRegistered
     * @param bool $isSubscribable
     * @return MessageFlowRegistration
     */
    public static function createExternalFlow(string $messageName, string $channelName, bool $isAutoRegistered, bool $isSubscribable) : self
    {
        return new self($messageName, "", $channelName, $isAutoRegistered, $isSubscribable);
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
     * @return bool
     */
    public function isSubscribable(): bool
    {
        return $this->isSubscribable;
    }

    /**
     * @return string
     */
    public function getMessageChannel() : string
    {
        return $this->channelName ? $this->channelName : MessageFlowModule::INTEGRATION_MESSAGING_CQRS_START_DEFAULT_FLOW_CHANNEL;
    }

    /**
     * @return bool
     */
    public function shouldChannelBeRegistered() : bool
    {
        return $this->isAutoRegistered && $this->channelName;
    }
}