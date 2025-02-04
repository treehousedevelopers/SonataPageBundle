<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Tests\Admin;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteGeneratorInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\Site;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PageAdminTest extends TestCase
{
    public function testTabMenuHasLinksWithSubSite(): void
    {
        $request = new Request(['id' => 42]);
        $admin = $this->createMock(AdminInterface::class);
        $admin->setMenuFactory(new MenuFactory());
        $admin->setRequest($request);

        $site = $this->createStub(Site::class);
        $site->method('getRelativePath')->willReturn('/my-subsite');

        $page = $this->createStub(PageInterface::class);
        $page->method('getRouteName')->willReturn(PageInterface::PAGE_ROUTE_CMS_NAME);
        $page->method('getUrl')->willReturn('/my-page');
        $page->method('isHybrid')->willReturn(false);
        $page->method('isInternal')->willReturn(false);
        $page->method('getSite')->willReturn($site);
        $admin->expects(self::once())->method('setSubject')->with($page);

        $routeGenerator = $this->createMock(RouteGeneratorInterface::class);
        $routeGenerator->method('generateMenuUrl')->with(
            $admin,
            static::anything(),
            ['id' => 42],
            UrlGeneratorInterface::ABSOLUTE_PATH
        )->willReturn([
            'route' => 'page_edit',
            'routeParameters' => ['id' => 42],
            'routeAbsolute' => true,
        ]);

        $admin->setRouteGenerator($routeGenerator);
        $admin->setSubject($page);
    }
}
