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
 *          @ReferenceCallInterceptorAnnotation(
 *                  referenceName="some",
 *                  methodName="action",
 *                  parameterConverters={@MessageToPayloadParameterAnnotation(parameterName="command")},
 *                  excludedMethods={"interceptedCommand"}
 *          )
 *     },
 *     postCallInterceptors={
 *          @ReferenceCallInterceptorAnnotation(referenceName="some", methodName="action", parameterConverters={
 *              @MessageToPayloadParameterAnnotation(parameterName="command")
 *          }, excludedMethods={"interceptedCommand"})
 *    }
 * )
 */
class AggregateCommandHandlerWithClassLevelMethodAndExclusionInterceptorExample
{
    /**
     * @CommandHandlerAnnotation()
     * @param DoStuffCommand $stuffCommand
     */
    public static function interceptedCommand(DoStuffCommand $stuffCommand) : void
    {

    }
}