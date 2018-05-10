<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class SaveAggregateService
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateService
{
    /**
     * @param Message $message
     *
     * @return Message
     */
    public function save(Message $message) : ?Message
    {
        /** @var AggregateRepository $repository */
        $repository = $message->getHeaders()->get(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_REPOSITORY_HEADER);

        $aggregate = $message->getHeaders()->get(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER);

        $repository->save($aggregate);

        return $message;
    }
}