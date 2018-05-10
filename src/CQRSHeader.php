<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

/**
 * Interface CQRS
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CQRSHeader
{
    const AGGREGATE                                                    = "integration_messaging.cqrs.aggregate";
    const CLASS_NAME                                                   = "integration_messaging.cqrs.aggregate.class_name";
    const METHOD_NAME       = "integration_messaging.cqrs.aggregate.method";
    const REPOSITORY        = "integration_messaging.cqrs.aggregate.repository";
    const IS_FACTORY_METHOD = "integration_messaging.cqrs.aggregate.is_factory_method";
    const AGGREGATE_ID      = "integration_messaging.cqrs.aggregate.id";
    const EXPECTED_VERSION  = "integration_messaging.cqrs.aggregate.expected_version";
    const CALLED_MESSAGE    = "integration_messaging.cqrs.aggregate.calling_message";
}