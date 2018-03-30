<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;

/**
 * Interface QueryGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
interface QueryGateway
{
    /**
     * Entrypoint for queries, when you have instance of query
     *
     * @param object $query instance of query
     *
     * @return mixed whatever query handler returns
     * @GatewayAnnotation(
     *     requestChannel=CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL
     * )
     */
    public function execute($query);
}