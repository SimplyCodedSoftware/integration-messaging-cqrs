<?php

namespace Fixture\Annotation\MessageFlow;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowAnnotation;

/**
 * Class ExampleExternalFlowCommand
 * @package Fixture\Annotation\MessageFlow
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageFlowAnnotation(externalName=ExampleFlowCommandWithCustomChannel::MESSAGE_NAME, channelName="externalChannel")
 */
class ExampleFlowCommandWithCustomChannel
{
    public const MESSAGE_NAME = "example.external.flow";
}