<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface CallInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface CallInterceptor
{
    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;

    /**
     * @param ChannelResolver        $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return MessageHandlerBuilderWithOutputChannel
     */
    public function build(ChannelResolver $channelResolver,ReferenceSearchService $referenceSearchService) : MessageHandlerBuilderWithOutputChannel;
}