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

namespace Sonata\PageBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Site Admin controller.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * @final since sonata-project/page-bundle 3.26
 */
final class SiteAdminController extends CRUDController
{
    /**
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     *
     * @return RedirectResponse|Response
     */
    public function snapshotsAction(Request $request)
    {
        if (false === $this->get('sonata.page.admin.snapshot')->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        $id = $request->get($this->admin->getIdParameter());

        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        $this->admin->setSubject($object);

        if ('POST' === $request->getMethod()) {
            $this->get('sonata.notification.backend')
                ->createAndPublish('sonata.page.create_snapshots', [
                    'siteId' => $object->getId(),
                    'mode' => 'async',
                ]);

            $this->addFlash('sonata_flash_success', $this->admin->trans('flash_snapshots_created_success'));

            return new RedirectResponse($this->admin->generateUrl('edit', ['id' => $object->getId()]));
        }

        return $this->render('@SonataPage/SiteAdmin/create_snapshots.html.twig', [
            'action' => 'snapshots',
            'object' => $object,
        ]);
    }
}
