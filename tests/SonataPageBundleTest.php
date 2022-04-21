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

namespace Sonata\PageBundle\Tests;

use Cocur\Slugify\Slugify;
use PHPUnit\Framework\TestCase;
use Sonata\PageBundle\DependencyInjection\Compiler\CacheCompilerPass;
use Sonata\PageBundle\DependencyInjection\Compiler\CmfRouterCompilerPass;
use Sonata\PageBundle\DependencyInjection\Compiler\GlobalVariablesCompilerPass;
use Sonata\PageBundle\DependencyInjection\Compiler\PageServiceCompilerPass;
use Sonata\PageBundle\DependencyInjection\Compiler\TwigStringExtensionCompilerPass;
use Sonata\PageBundle\SonataPageBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Page extends \Sonata\PageBundle\Model\Page
{
    /**
     * Returns the id.
     */
    public function getId()
    {
        // TODO: Implement getId() method.
    }
}

final class SonataPageBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects(static::exactly(5))
            ->method('addCompilerPass')
            ->withConsecutive(
                [new CacheCompilerPass()],
                [new GlobalVariablesCompilerPass()],
                [new PageServiceCompilerPass()],
                [new CmfRouterCompilerPass()],
                [new TwigStringExtensionCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1],

        );

        $bundle = new SonataPageBundle();
        $bundle->build($containerBuilder);
    }

    /**
     * @dataProvider getSlug
     */
    public function testBoot($text, $expected): void
    {
        $bundle = new SonataPageBundle();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(static::once())->method('hasParameter')->willReturn(true);
        $container->expects(static::exactly(2))->method('getParameter')->willReturnCallback(static function ($value) {
            if ('sonata.page.page.class' === $value) {
                return Page::class;
            }

            if ('sonata.page.slugify_service' === $value) {
                return 'slug_service';
            }
        });
        $container->expects(static::once())->method('get')->willReturn(Slugify::create());

        $bundle->setContainer($container);
        $bundle->boot();

        $page = new Page();
        $page->setSlug($text);
        static::assertSame($page->getSlug(), $expected);
    }

    public function getSlug(): array
    {
        return [
            ['Salut comment ca va ?',  'salut-comment-ca-va'],
            ['òüì',  'ouei'],
        ];
    }
}
