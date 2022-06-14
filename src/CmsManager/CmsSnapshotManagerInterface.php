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

namespace Sonata\PageBundle\CmsManager;

/**
 * The CmsSnapshotManagerInterface class is in charge of retrieving the correct page (cms page or action page).
 *
 * An action page is linked to a symfony action and a cms page is a standalone page.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
interface CmsSnapshotManagerInterface extends CmsManagerInterface
{

}
