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

use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SnapshotManagerInterface;

/**
 * Processor service to cleanup snapshots by a given page.
 */
class CleanupSnapshotProcessor
{
    protected SnapshotManagerInterface $snapshotManager;

    public function __construct(SnapshotManagerInterface $snapshotManager)
    {
        $this->snapshotManager = $snapshotManager;
    }

    public function process(PageInterface $page, int $keepSnapshots): void
    {
        // start a transaction
        $this->snapshotManager->getConnection()->beginTransaction();

        // cleanup snapshots
        $this->snapshotManager->cleanup($page, $keepSnapshots);

        // commit the changes
        $this->snapshotManager->getConnection()->commit();
    }
}
