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

namespace Sonata\PageBundle\Entity;

use Sonata\Doctrine\Entity\BaseEntityManager;
use Sonata\PageBundle\Model\SiteManagerInterface;

/**
 * This class manages SiteInterface persistency with the Doctrine ORM.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * @final since sonata-project/page-bundle 3.26
 */
class SiteManager extends BaseEntityManager implements SiteManagerInterface
{
    public function save($entity, $andFlush = true)
    {
        parent::save($entity, $andFlush);

        return $entity;
    }
}
