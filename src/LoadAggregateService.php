<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Cqrs;

use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class LoadAggregateService
 * @package SimplyCodedSoftware\IntegrationMessaging\Cqrs
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoadAggregateService
{
    /**
     * @var AggregateRepository
     */
    private $aggregateRepository;
    /**
     * @var bool
     */
    private $isFactoryMethod;
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var string
     */
    private $aggregateMethod;

    /**
     * ServiceCallToAggregateAdapter constructor.
     *
     * @param AggregateRepository $aggregateRepository
     * @param string              $aggregateClassName
     * @param string              $aggregateMethod
     * @param bool                $isFactoryMethod
     */
    public function __construct(AggregateRepository $aggregateRepository, string $aggregateClassName, string $aggregateMethod, bool $isFactoryMethod)
    {
        $this->aggregateRepository          = $aggregateRepository;
        $this->isFactoryMethod = $isFactoryMethod;
        $this->aggregateClassName = $aggregateClassName;
        $this->aggregateMethod = $aggregateMethod;
    }

    /**
     * @param Message $message
     *
     * @return Message
     * @throws AggregateNotFoundException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function load(Message $message) : Message
    {
        $commandReflection = new \ReflectionClass($message->getPayload());
        $aggregateId       = "";
        $expectedVersion   = null;
        foreach ($commandReflection->getProperties() as $property) {
            if (preg_match("*AggregateIdAnnotation*", $property->getDocComment())) {
                $property->setAccessible(true);
                $aggregateId = (string)$property->getValue($message->getPayload());
            }
            if (preg_match("*AggregateExpectedVersionAnnotation*", $property->getDocComment())) {
                $property->setAccessible(true);
                $expectedVersion = $property->getValue($message->getPayload());
            }
        }

        $aggregate = null;
        if (!$this->isFactoryMethod) {
            if (!$aggregateId) {
                throw AggregateNotFoundException::create("There is no aggregate id to search for found. Are you sure you defined AggregateId Annotation or isn't aggregateId null?");
            }

            $aggregate = is_null($expectedVersion)
                ? $this->aggregateRepository->findBy($aggregateId)
                : $this->aggregateRepository->findWithLockingBy($aggregateId, $expectedVersion);
        }

        $messageBuilder = MessageBuilder::fromMessage($message);
        if ($aggregate) {
            $messageBuilder = $messageBuilder->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_HEADER, $aggregate);
        }
        if ($expectedVersion) {
            $messageBuilder = $messageBuilder->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_EXPECTED_VERSION_HEADER, $expectedVersion);
        }

        return $messageBuilder
            ->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_CLASS_NAME_HEADER, $this->aggregateClassName)
            ->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_METHOD_HEADER, $this->aggregateMethod)
            ->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_REPOSITORY_HEADER, $this->aggregateRepository)
            ->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_ID_HEADER, $aggregateId)
            ->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_IS_FACTORY_METHOD_HEADER, $this->isFactoryMethod)
            ->setHeader(CqrsMessagingModule::INTEGRATION_MESSAGING_CQRS_AGGREGATE_MESSAGE_HEADER, $message->getPayload())
            ->build();
    }
}