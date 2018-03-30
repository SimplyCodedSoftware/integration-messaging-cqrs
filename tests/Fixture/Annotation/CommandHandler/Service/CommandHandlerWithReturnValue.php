<?php

namespace Fixture\Annotation\CommandHandler\Service;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;

/**
 * Class CommandHandlerWithReturnValue
 * @package Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class CommandHandlerWithReturnValue
{
    /**
     * @param SomeCommand $command
     *
     * @return int
     * @CommandHandlerAnnotation()
     */
    public function execute(SomeCommand $command) : int
    {
        return 1;
    }
}