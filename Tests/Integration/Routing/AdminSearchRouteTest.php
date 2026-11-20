<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Routing;

use Symfony\Component\Routing\RouterInterface;
use Thelia\Test\IntegrationTestCase;

final class AdminSearchRouteTest extends IntegrationTestCase
{
    public function testTheBackOfficeSearchIsAnsweredByTheThemeAndNotByTheModule(): void
    {
        $router = $this->getService('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        $route = $router->match('/admin/search');

        self::assertSame('admin.search', $route['_route'] ?? null);
        self::assertStringNotContainsString('TntSearch', (string) ($route['_controller'] ?? ''));
    }
}
