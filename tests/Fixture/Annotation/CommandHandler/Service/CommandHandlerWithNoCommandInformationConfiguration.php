<?php

namespace Fixture\Annotation\CommandHandler\Service;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;

/**
 * Class CommandHandlerWithIncorrectConfiguration
 * @package Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class CommandHandlerWithNoCommandInformationConfiguration
{
    /**
     * @CommandHandlerAnnotation()
     */
    public function noAction() : void
    {

    }
}