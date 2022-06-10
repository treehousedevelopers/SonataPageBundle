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

namespace Sonata\PageBundle\Admin\Extension;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Processor\CreateSnapshotProcessor;

/**
 * @final since sonata-project/page-bundle 3.26
 */
class CreateSnapshotAdminExtension extends AbstractAdminExtension
{
    protected CreateSnapshotProcessor $createSnapshotProcessor;

    public function __construct(CreateSnapshotProcessor $createSnapshotProcessor)
    {
        $this->createSnapshotProcessor = $createSnapshotProcessor;
    }

    public function postUpdate(AdminInterface $admin, $object): void
    {
        $this->createSnapshot($object);
    }

    public function postPersist(AdminInterface $admin, $object): void
    {
        $this->createSnapshot($object);
    }

    public function postRemove(AdminInterface $admin, object $object): void
    {
        $this->createSnapshot($object);
    }

    /**
     * @param PageInterface $object
     */
    protected function createSnapshot($object): void
    {
        if ($object instanceof BlockInterface && method_exists($object, 'getPage')) {
            $pageId = $object->getPage()->getId();
        } elseif ($object instanceof PageInterface) {
            $pageId = $object->getId();
        } else {
            return;
        }

        $this->createSnapshotProcessor->process($pageId);
    }
}
