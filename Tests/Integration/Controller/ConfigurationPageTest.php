<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Controller;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\ModuleQuery;
use Thelia\Test\FixtureFactory;
use Thelia\Test\WebIntegrationTestCase;
use Thelia\Tests\Support\BackOffice\AdminSessionInjector;

final class ConfigurationPageTest extends WebIntegrationTestCase
{
    private ?AdminSessionInjector $injector = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (1 !== (int) ModuleQuery::create()->findOneByCode('TntSearch')?->getActivate()) {
            self::markTestSkipped('TntSearch is not active in the test shop: its configuration page is not rendered.');
        }

        $this->injector = new AdminSessionInjector();
        $this->getService(EventDispatcherInterface::class)->addSubscriber($this->injector);

        $admin = (new FixtureFactory($this->getPropelConnection()))->admin();
        $admin->eraseCredentials();
        $this->injector->setAdmin($admin);
    }

    protected function tearDown(): void
    {
        if (null !== $this->injector) {
            $this->getService(EventDispatcherInterface::class)->removeSubscriber($this->injector);
            $this->injector->clear();
        }

        parent::tearDown();
    }

    public function testTheRealTimeOptionSaysItOnlyConcernsTheCustomerIndex(): void
    {
        $this->client->request('GET', '/admin/module/TntSearch');

        self::assertResponseIsSuccessful();
        $page = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Update the customer index each time a customer account is saved', $page);
        self::assertStringNotContainsString('each time a product, category, folder, content, brand', $page);
    }

    public function testARebuildTellsWhetherItSucceededWithoutShowingTextFromTheAddress(): void
    {
        $this->client->request('GET', '/admin/module/TntSearch', ['success' => '1']);
        self::assertStringContainsString('The indexes were rebuilt.', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/module/TntSearch', ['error' => 'Call the support on +33 1 23 45 67 89']);
        $page = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('The indexes could not be rebuilt. The cause is written in the Thelia log.', $page);
        self::assertStringNotContainsString('Call the support', $page);
    }
}
