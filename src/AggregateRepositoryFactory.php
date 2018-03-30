<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface AggregateRepositoryFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateRepositoryFactory
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param string                 $aggregateClassName
     *
     * @return AggregateRepository
     */
    public function getRepositoryFor(ReferenceSearchService $referenceSearchService, string $aggregateClassName) : AggregateRepository;
}