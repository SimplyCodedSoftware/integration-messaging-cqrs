<?php

namespace Fixture\Annotation\CommandHandler\Aggregate;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;

/**
 * Class AggregateCommandHandlerExample
 * @package Fixture\Annotation\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @AggregateAnnotation()
 */
class AggregateCommandHandlerExample
{
    /**
     * @param DoStuffCommand $command
     * @CommandHandlerAnnotation()
     */
    public function doAction(DoStuffCommand $command) : void
    {

    }
}