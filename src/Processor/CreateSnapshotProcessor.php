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
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SnapshotManagerInterface;
use Sonata\PageBundle\Model\TransformerInterface;

/**
 * Consumer service to generate a snapshot.
 */
class CreateSnapshotProcessor
{
    protected SnapshotManagerInterface $snapshotManager;
    protected PageManagerInterface $pageManager;
    protected TransformerInterface $transformer;

    public function __construct(
        SnapshotManagerInterface $snapshotManager,
        PageManagerInterface $pageManager,
        TransformerInterface $transformer
    ) {
        $this->snapshotManager = $snapshotManager;
        $this->pageManager = $pageManager;
        $this->transformer = $transformer;
    }

    public function process(PageInterface $page): void
    {
        // start a transaction
        $this->snapshotManager->getConnection()->beginTransaction();

        // creating snapshot
        $snapshot = $this->transformer->create($page);

        // update the page status
        $page->setEdited(false);
        $this->pageManager->save($page);

        // save the snapshot
        $this->snapshotManager->save($snapshot);
        $this->snapshotManager->enableSnapshots([$snapshot]);

        // commit the changes
        $this->snapshotManager->getConnection()->commit();
    }
}
