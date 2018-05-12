<?php

namespace Fixture\Annotation\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\ReferenceCallInterceptorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;

/**
 * Class AggregateCommandHandlerWithInterceptorExample
 * @package Fixture\Annotation\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @AggregateAnnotation()
 */
class AggregateCommandHandlerWithPreCallInterceptorExample
{
    /**
     * @CommandHandlerAnnotation(
     *     preCallInterceptors={
     *          @ReferenceCallInterceptorAnnotation(referenceName="some", methodName="action", parameterConverters={
     *              @MessageToPayloadParameterAnnotation(parameterName="command")
     *          })
     *    }
     * )
     * @param DoStuffCommand $stuffCommand
     */
    public function interceptedCommand(DoStuffCommand $stuffCommand) : void
    {

    }
}