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

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Sonata\Doctrine\Model\ManagerInterface;
use Sonata\PageBundle\Model\BlockInteractorInterface;
use Sonata\PageBundle\Model\PageInterface;

/**
 * This class interacts with blocks.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * @final since sonata-project/page-bundle 3.26
 */
class BlockInteractor implements BlockInteractorInterface
{
    /**
     * @var bool[]
     */
    protected $pageBlocksLoaded = [];

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ManagerInterface
     */
    protected $blockManager;

    /**
     * @param ManagerRegistry  $registry     Doctrine registry
     * @param ManagerInterface $blockManager Block manager
     */
    public function __construct(ManagerRegistry $registry, ManagerInterface $blockManager)
    {
        $this->blockManager = $blockManager;
        $this->registry = $registry;
    }

    public function getBlock($id)
    {
        $blocks = $this->getEntityManager()->createQueryBuilder()
            ->select('b')
            ->from($this->blockManager->getClass(), 'b')
            ->where('b.id = :id')
            ->setParameters([
              'id' => $id,
            ])
            ->getQuery()
            ->execute();

        return \count($blocks) > 0 ? $blocks[0] : false;
    }

    public function getBlocksById(PageInterface $page)
    {
        $blocks = $this->getEntityManager()
            ->createQuery(sprintf('SELECT b FROM %s b INDEX BY b.id WHERE b.page = :page ORDER BY b.position ASC', $this->blockManager->getClass()))
            ->setParameters([
                 'page' => $page->getId(),
            ])
            ->execute();

        return $blocks;
    }

    public function saveBlocksPosition(array $data = [], $partial = true)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();

        try {
            foreach ($data as $block) {
                if (!$block['id'] || !\array_key_exists('position', $block) || !$block['parent_id'] || !$block['page_id']) {
                    continue;
                }

                $this->blockManager->updatePosition($block['id'], $block['position'], $block['parent_id'], $block['page_id'], $partial);
            }

            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            throw $e;
        }

        return true;
    }

    public function createNewContainer(array $values = [], ?\Closure $alter = null)
    {
        $container = $this->blockManager->create();
        $container->setEnabled($values['enabled'] ?? true);
        $container->setCreatedAt(new \DateTime());
        $container->setUpdatedAt(new \DateTime());
        $container->setType('sonata.page.block.container');

        if (isset($values['page'])) {
            $container->setPage($values['page']);
        }

        if (isset($values['name'])) {
            $container->setName($values['name']);
        } else {
            $container->setName($values['code'] ?? 'No name defined');
        }

        $container->setSettings(['code' => $values['code'] ?? 'no code defined']);
        $container->setPosition($values['position'] ?? 1);

        if (isset($values['parent'])) {
            $container->setParent($values['parent']);
        }

        if ($alter) {
            $alter($container);
        }

        $this->blockManager->save($container);

        return $container;
    }

    public function loadPageBlocks(PageInterface $page)
    {
        if (isset($this->pageBlocksLoaded[$page->getId()])) {
            return [];
        }

        $blocks = $this->getBlocksById($page);

        $page->disableBlockLazyLoading();

        foreach ($blocks as $block) {
            $parent = $block->getParent();

            $block->disableChildrenLazyLoading();
            if (!$parent) {
                $page->addBlocks($block);

                continue;
            }

            $blocks[$block->getParent()->getId()]->disableChildrenLazyLoading();
            $blocks[$block->getParent()->getId()]->addChildren($block);
        }

        $this->pageBlocksLoaded[$page->getId()] = true;

        return $blocks;
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->registry->getManagerForClass($this->blockManager->getClass());
    }
}
