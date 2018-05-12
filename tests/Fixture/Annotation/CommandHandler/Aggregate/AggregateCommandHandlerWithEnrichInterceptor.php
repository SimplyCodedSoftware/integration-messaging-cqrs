<?php

namespace Fixture\Annotation\CommandHandler\Aggregate;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToMessage\MessageToMessageHeaderExpressionSetterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToMessage\MessageToMessagePayloadExpressionSetterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\AggregateAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\CommandHandlerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Annotation\EnrichCallInterceptorAnnotation;

/**
 * Class AggregateCommandHandlerWithEnrichInterceptor
 * @package Fixture\Annotation\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @AggregateAnnotation()
 */
class AggregateCommandHandlerWithEnrichInterceptor
{
    /**
     * @CommandHandlerAnnotation(
     *     postCallInterceptors={
     *          @EnrichCallInterceptorAnnotation(requestMessageChannel="requestChannel", requestPayloadExpression="payload", requestHeaders={"key": "value"},
     *          messageToMessageSetters={
     *              @MessageToMessagePayloadExpressionSetterAnnotation(propertyPathToSet="token", expression="123"),
     *              @MessageToMessageHeaderExpressionSetterAnnotation(propertyPathToSet="password", expression="abc")
     *          }
     *     )
     *    }
     * )
     * @param DoStuffCommand $stuffCommand
     */
    public function interceptedCommand(DoStuffCommand $stuffCommand) : void
    {

    }
}