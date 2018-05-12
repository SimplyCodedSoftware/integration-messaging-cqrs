<?php

namespace Fixture\Annotation\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\ClassMethodInterceptorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\ReferenceCallInterceptorAnnotation;

/**
 * Class AggregateCommandHandlerWithInterceptorExample
 * @package Fixture\Annotation\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @AggregateAnnotation()
 * @ClassMethodInterceptorAnnotation(
 *     preCallInterceptors={
 *          @ReferenceCallInterceptorAnnotation(referenceName="some", methodName="action", parameterConverters={
 *              @MessageToPayloadParameterAnnotation(parameterName="command")
 *          })
 *     },
 *     postCallInterceptors={
 *          @ReferenceCallInterceptorAnnotation(referenceName="some", methodName="action", parameterConverters={
 *              @MessageToPayloadParameterAnnotation(parameterName="command")
 *          })
 *    }
 * )
 */
class AggregateCommandHandlerWithClassLevelMethodsInterceptorExample
{
    /**
     * @CommandHandlerAnnotation()
     * @param DoStuffCommand $stuffCommand
     */
    public function interceptedCommand(DoStuffCommand $stuffCommand) : void
    {

    }
}