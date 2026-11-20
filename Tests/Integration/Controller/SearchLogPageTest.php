<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Controller;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Model\Admin;
use Thelia\Model\ModuleQuery;
use Thelia\Test\FixtureFactory;
use Thelia\Test\WebIntegrationTestCase;
use Thelia\Tests\Support\BackOffice\AdminSessionInjector;

final class SearchLogPageTest extends WebIntegrationTestCase
{
    private ?AdminSessionInjector $injector = null;

    private FixtureFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        if (1 !== (int) ModuleQuery::create()->findOneByCode('TntSearch')?->getActivate()) {
            self::markTestSkipped('TntSearch is not active in the test shop: its search log page does not answer.');
        }

        $this->injector = new AdminSessionInjector();
        $this->getService(EventDispatcherInterface::class)->addSubscriber($this->injector);
        $this->factory = new FixtureFactory($this->getPropelConnection());
    }

    protected function tearDown(): void
    {
        if (null !== $this->injector) {
            $this->getService(EventDispatcherInterface::class)->removeSubscriber($this->injector);
            $this->injector->clear();
        }

        parent::tearDown();
    }

    public function testTheSearchLogIsRefusedToAnAdministratorWhoCannotSeeTheOrders(): void
    {
        $this->loginAs($this->factory->restrictedAdmin(['admin.search' => [AccessManager::VIEW]]));

        $this->client->request('GET', '/admin/search_log');

        self::assertResponseStatusCodeSame(403);
    }

    public function testTheSearchLogIsShownToAnAdministratorWhoCanSeeTheOrders(): void
    {
        $this->loginAs($this->factory->restrictedAdmin([AdminResources::ORDER => [AccessManager::VIEW]]));

        $this->client->request('GET', '/admin/search_log');

        self::assertResponseIsSuccessful();
    }

    private function loginAs(Admin $admin): void
    {
        $admin->eraseCredentials();
        $this->injector?->setAdmin($admin);
    }
}
