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

namespace Sonata\PageBundle\Processor;

use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SiteInterface;

/**
 * Consumer service to cleanup snapshots.
 */
class CleanupSnapshotsProcessor
{
    protected CleanupSnapshotProcessor $cleanupSnapshotProcessor;
    protected PageManagerInterface $pageManager;

    public function __construct(CleanupSnapshotProcessor $cleanupSnapshotProcessor, PageManagerInterface $pageManager)
    {
        $this->cleanupSnapshotProcessor = $cleanupSnapshotProcessor;
        $this->pageManager = $pageManager;
    }

    public function process(SiteInterface $site, int $keepSnapshots): void
    {
        $pages = $this->pageManager->findBy([
            'site' => $site->getId(),
        ]);

        foreach ($pages as $page) {
            $this->cleanupSnapshotProcessor->process($page, $keepSnapshots);
        }
    }
}
