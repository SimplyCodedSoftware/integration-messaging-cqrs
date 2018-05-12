<?php

namespace Behat\Bootstrap;

require_once __DIR__ . "/../../phpunit/TestBootstrap.php";

use Behat\Behat\Context\Context;
use Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepositoryConstructor;
use Fixture\CommandHandler\Aggregate\Order;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\GatewayModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\CommandGateway;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\Config\CqrsMessagingModule;
use SimplyCodedSoftware\IntegrationMessaging\Cqrs\QueryGateway;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;

/**
 * Defines application features from the specific context.
 */
class DomainContext extends TestCase implements Context
{
    /**
     * @var QueryGateway
     */
    private $queryGateway;
    /**
     * @var CommandGateway
     */
    private $commandGateway;

    /**
     * @Given I have order with id :orderId for :productAmount products registered to shipping address :shippingAddress
     *
     * @param int    $orderId
     * @param int    $productAmount
     * @param string $shippAddress
     */
    public function iHaveOrderWithIdForProductsRegisteredToShippingAddress(int $orderId, int $productAmount, string $shippAddress)
    {
        $this->commandGateway->execute(CreateOrderCommand::create($orderId, $productAmount, $shippAddress));
    }

    /**
     * @When I change order with id of :orderId the shipping address to :shippingAddress
     *
     * @param int    $orderId
     * @param string $shippAddress
     */
    public function iChangeOrderWithIdOfTheShippingAddressTo(int $orderId, string $shippAddress)
    {
        $this->commandGateway->execute(ChangeShippingAddressCommand::create($orderId, 0, $shippAddress));
    }

    /**
     * @Then shipping address should be :shippingAddress for order with id :orderId
     *
     * @param string $shippingAddress
     * @param int    $orderId
     */
    public function shippingAddressShouldBeForOrderWithId(string $shippingAddress, int $orderId)
    {
        $this->assertEquals($shippingAddress, $this->queryGateway->execute(GetShippingAddressQuery::create($orderId)));
    }

    /**
     * @BeforeScenario
     */
    public function setUpMessaging()
    {
        $this->prepareConfiguration([Order::class]);
    }

    /**
     * @param array $annotationClassesToRegister
     *
     * @return void
     */
    private function prepareConfiguration(array $annotationClassesToRegister): void
    {
        $cqrsMessagingModule = CqrsMessagingModule::create(InMemoryAnnotationRegistrationService::createFrom($annotationClassesToRegister));
        $gatewayModule = GatewayModule::create(InMemoryAnnotationRegistrationService::createFrom([CommandGateway::class, QueryGateway::class]));

        $configuredMessagingSystem = MessagingSystemConfiguration::prepare(
            InMemoryModuleMessaging::createWith(
                [$cqrsMessagingModule, $gatewayModule],
                [InMemoryOrderAggregateRepositoryConstructor::createEmpty()]
            )
        )
            ->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty());

        $this->commandGateway = $configuredMessagingSystem->getGatewayByName(CommandGateway::class);
        $this->queryGateway   = $configuredMessagingSystem->getGatewayByName(QueryGateway::class);
    }
}
