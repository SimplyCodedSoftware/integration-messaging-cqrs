<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config;

use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfigurationExtension;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepository;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\AggregateRepositoryBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Interface AggregateRepositoryExtension
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateRepositoryExtension extends ModuleConfigurationExtension
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     */
    public function configure(ReferenceSearchService $referenceSearchService) : void;

    /**
     * @return AggregateRepositoryBuilder
     */
    public function prepareBuilder() : AggregateRepositoryBuilder;
}