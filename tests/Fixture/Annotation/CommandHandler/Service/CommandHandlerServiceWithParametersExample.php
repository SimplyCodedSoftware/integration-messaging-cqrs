<?php

namespace Fixture\Annotation\CommandHandler\Service;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToReferenceServiceAnnotation;

/**
 * Class CommandHandlerServiceWithParametersExample
 * @package Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpointAnnotation()
 */
class CommandHandlerServiceWithParametersExample
{
    /**
     * @param HelloWorldCommand $command
     * @param string            $name
     * @param \stdClass         $object
     * @CommandHandlerAnnotation(
     *     parameterConverters={
     *          @MessageToHeaderParameterAnnotation(parameterName="name", headerName="userName"),
     *          @MessageToReferenceServiceAnnotation(parameterName="object", referenceName="calculator")
     *     }
     * )
     */
    public function sayHello(HelloWorldCommand $command, string $name, \stdClass $object) : void
    {

    }
}