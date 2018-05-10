<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

/**
 * Interface CQRS
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CQRSHeader
{
    const AGGREGATE                                                    = "cqrs.aggregate";
    const CLASS_NAME                                                   = "cqrs.aggregate.class_name";
    const METHOD_NAME       = "cqrs.aggregate.method";
    const REPOSITORY        = "cqrs.aggregate.repository";
    const IS_FACTORY_METHOD = "cqrs.aggregate.is_factory_method";
    const AGGREGATE_ID      = "cqrs.aggregate.id";
    const EXPECTED_VERSION  = "cqrs.aggregate.expected_version";
    const CALLED_MESSAGE    = "cqrs.aggregate.calling_message";
}