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

namespace Sonata\PageBundle\Command;

use Psr\Container\ContainerInterface;
use Sonata\BlockBundle\Block\BlockContextManagerInterface;
use Sonata\BlockBundle\Block\BlockRendererInterface;
use Sonata\PageBundle\CmsManager\CmsManagerInterface;
use Sonata\PageBundle\CmsManager\CmsSnapshotManagerInterface;
use Sonata\PageBundle\Model\BlockInteractorInterface;
use Sonata\PageBundle\Model\BlockManagerInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SiteManagerInterface;
use Sonata\PageBundle\Model\SnapshotManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * BaseCommand.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
abstract class BaseCommand extends Command implements ServiceSubscriberInterface
{
    protected ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        parent::__construct();

        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'sonata.page.manager.site' => SiteManagerInterface::class,
            'sonata.page.manager.page' => PageManagerInterface::class,
            'sonata.page.manager.snapshot' => SnapshotManagerInterface::class,
            'sonata.page.manager.block' => BlockManagerInterface::class,
            'sonata.page.cms.page' => CmsManagerInterface::class,
            'sonata.page.cms.snapshot' => CmsSnapshotManagerInterface::class,
            'sonata.page.block_interactor' => BlockInteractorInterface::class,
            'sonata.block.context_manager' => BlockContextManagerInterface::class,
            'sonata.block.renderer' => BlockRendererInterface::class,
        ];
    }

    protected function getSiteManager(): SiteManagerInterface
    {
        return $this->locator->get('sonata.page.manager.site');
    }

    protected function getPageManager(): PageManagerInterface
    {
        return $this->locator->get('sonata.page.manager.page');
    }

    protected function getSnapshotManager(): PageManagerInterface
    {
        return $this->locator->get('sonata.page.manager.snapshot');
    }

    protected function getCmsPageManager(): CmsManagerInterface
    {
        return $this->locator->get('sonata.page.cms.page');
    }

    protected function getBlockManager(): BlockManagerInterface
    {
        return $this->locator->get('sonata.page.manager.block');
    }

    protected function getCmsSnapshotManager(): CmsManagerInterface
    {
        return $this->locator->get('sonata.page.cms.snapshot');
    }

    protected function getBlockInteractor(): BlockInteractorInterface
    {
        return $this->locator->get('sonata.page.block_interactor');
    }

    protected function getBlockContextManager(): BlockContextManagerInterface
    {
        return $this->locator->get('sonata.block.context_manager');
    }

    protected function getBlockRenderer(): BlockRendererInterface
    {
        return $this->locator->get('sonata.block.renderer');
    }

    protected function getSites(InputInterface $input): array
    {
        $parameters = [];
        $identifiers = $input->getOption('site');

        if ('all' !== current($identifiers)) {
            $parameters['id'] = 1 === \count($identifiers) ? current($identifiers) : $identifiers;
        }

        return $this->locator->get('sonata.page.manager.site')->findBy($parameters);
    }
}
