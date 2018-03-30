<?php

namespace Fixture\Annotation\MessageFlow;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\MessageFlowAnnotation;

/**
 * Class ExampleFlowCommand
 * @package Fixture\Annotation\MessageFlow
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageFlowAnnotation(externalName=ExampleFlowCommand::MESSAGE_NAME)
 */
class ExampleFlowCommand
{
    public const MESSAGE_NAME = "example.flow";
}