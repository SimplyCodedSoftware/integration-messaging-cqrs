<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;

/**
 * Interface CommandGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
interface CommandGateway
{
    /**
     * Entrypoint for commands, when you access to instance of the command
     *
     * @param object $command instance of command
     * @return void commands do not return data
     *
     * @GatewayAnnotation(
     *     requestChannel=CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL
     * )
     */
    public function execute($command) : void;
}