<?php

namespace Fixture\Annotation\CommandHandler\Service;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;

/**
 * Class CommandHandlerServiceExample
 * @package Fixture\Annotation\CommandHandler\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation(referenceName="some")
 */
class CommandHandlerServiceExample
{
    /**
     * @param SomeCommand $command
     * @CommandHandlerAnnotation()
     */
    public function doAction(SomeCommand $command) : void
    {

    }
}