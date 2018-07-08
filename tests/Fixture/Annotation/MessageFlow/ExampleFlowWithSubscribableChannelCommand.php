<?php

namespace Fixture\Annotation\MessageFlow;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowAnnotation;

/**
 * Class ExampleFlowCommand
 * @package Fixture\Annotation\MessageFlow
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageFlowAnnotation(externalName=ExampleFlowCommand::MESSAGE_NAME, channelName=ExampleFlowCommand::MESSAGE_NAME, autoCreate=true, isSubscriable=true)
 */
class ExampleFlowWithSubscribableChannelCommand
{
    public const MESSAGE_NAME = "example.flow";
}