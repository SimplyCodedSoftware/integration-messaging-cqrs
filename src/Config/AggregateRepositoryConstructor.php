<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface FutureAggregateBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateRepositoryConstructor
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param string                 $aggregateClassName
     *
     * @return bool
     */
    public function canHandle(ReferenceSearchService $referenceSearchService, string $aggregateClassName) : bool;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param string                 $aggregateClassName
     *
     * @return AggregateRepository
     */
    public function build(ReferenceSearchService $referenceSearchService, string $aggregateClassName) : AggregateRepository;
}