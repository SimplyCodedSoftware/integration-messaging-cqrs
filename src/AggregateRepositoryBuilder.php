<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface FutureAggregateBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateRepositoryBuilder
{
    /**
     * @param string                 $aggregateClassName
     * @param ReferenceSearchService $referenceSearchService
     *
     * @return AggregateRepository
     */
    public function build(string $aggregateClassName, ReferenceSearchService $referenceSearchService) : AggregateRepository;
}