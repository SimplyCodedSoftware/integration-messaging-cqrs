<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\GatewayAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToPayloadAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage\ParameterToHeaderAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\MessageFlowModule;

/**
 * Interface MessageFlowGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
interface MessageFlowGateway
{
    /**
     * @param string $messageName
     * @param mixed $payload
     *
     * @return mixed For query handler it will return value, otherwise not
     *
     * @GatewayAnnotation(
     *      parameterConverters={
     *          @ParameterToHeaderAnnotation(headerName=MessageFlowModule::INTEGRATION_MESSAGING_CQRS_MESSAGE_CLASS_HEADER, parameterName="messageName"),
     *          @ParameterToPayloadAnnotation(parameterName="payload")
     *     }
     * )
     */
    public function start(string $messageName, $payload);
}