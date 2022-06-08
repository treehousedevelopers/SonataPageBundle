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

namespace Sonata\PageBundle\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Sonata\BlockBundle\Model\BlockManagerInterface;
use Sonata\NotificationBundle\Backend\MessageManagerBackend;
use Sonata\NotificationBundle\Backend\RuntimeBackend;
use Sonata\PageBundle\CmsManager\CmsPageManager;
use Sonata\PageBundle\Command\CloneSiteCommand;
use Sonata\PageBundle\Entity\BlockInteractor;
use Sonata\PageBundle\Entity\SnapshotManager;
use Sonata\PageBundle\Listener\ExceptionListener;
use Sonata\PageBundle\Model\PageBlockInterface;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\SiteInterface;
use Sonata\PageBundle\Model\SiteManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Christian Gripp <mail@core23.de>
 */
final class CloneSiteCommandTest extends TestCase
{
    /** @var Stub&BlockInteractor */
    protected $blockInteractor;

    /** @var Stub&SnapshotManager */
    protected $snapshotManager;

    /** @var Stub&CmsPageManager */
    protected $cmsPageManager;

    /** @var Stub&ExceptionListener */
    protected $exceptionListener;

    /** @var Stub&MessageManagerBackend */
    protected $backend;

    /** @var Stub&RuntimeBackend */
    protected $backendRuntime;
    /**
     * @var Application
     */
    private $application;

    /**
     * @var Stub&SiteManagerInterface
     */
    private $siteManager;

    /**
     * @var MockObject&PageManagerInterface
     */
    private $pageManager;

    /**
     * @var MockObject&BlockManagerInterface
     */
    private $blockManager;

    protected function setUp(): void
    {
        $this->siteManager = $this->createStub(SiteManagerInterface::class);
        $this->pageManager = $this->createMock(PageManagerInterface::class);
        $this->blockManager = $this->createMock(BlockManagerInterface::class);
        $this->blockInteractor = $this->createStub(BlockInteractor::class);
        $this->snapshotManager = $this->createStub(SnapshotManager::class);
        $this->cmsPageManager = $this->createStub(CmsPageManager::class);
        $this->exceptionListener = $this->createStub(ExceptionListener::class);
        $this->backend = $this->createStub(MessageManagerBackend::class);
        $this->backendRuntime = $this->createStub(RuntimeBackend::class);

        $command = new CloneSiteCommand(
            $this->siteManager,
            $this->pageManager,
            $this->snapshotManager,
            $this->blockManager,
            $this->cmsPageManager,
            $this->exceptionListener,
            $this->backend,
            $this->backendRuntime
        );

        $this->application = new Application();
        $this->application->add($command);
    }

    public function testExecute(): void
    {
        $sourceSite = $this->createStub(SiteInterface::class);
        $destSite = $this->createStub(SiteInterface::class);

        $this->siteManager->method('find')->willReturnMap([
            [23, $sourceSite],
            [42, $destSite],
        ]);

        $page1 = $this->createMock(PageInterface::class);
        $page1->method('getId')->willReturn(1);
        $page1->method('getTitle')->willReturn('Page 1');
        $page1->method('getParent')->willReturn(null);
        $page1->method('isHybrid')->willReturn(true);

        $page2 = $this->createMock(PageInterface::class);
        $page2->method('getId')->willReturn(2);
        $page2->method('getTitle')->willReturn('Page 2');
        $page2->method('getParent')->willReturn($page1);
        $page2->method('getTarget')->willReturn(null);
        $page2->method('isHybrid')->willReturn(true);

        $page1->method('getTarget')->willReturn($page2);

        $page3 = $this->createStub(PageInterface::class);
        $page3->method('getId')->willReturn(3);
        $page3->method('isHybrid')->willReturn(false);

        $this->pageManager->method('findBy')->with([
            'site' => $sourceSite,
        ])->willReturn([
            $page1,
            $page2,
            $page3,
        ]);

        $newPage1 = $page1;
        $newPage1->expects(static::once())->method('setTitle')->with('Copy of Page 1');
        $newPage1->expects(static::once())->method('setSite')->with($destSite);

        $newPage2 = $page2;
        $newPage2->expects(static::once())->method('setTitle')->with('Copy of Page 2');
        $newPage2->expects(static::once())->method('setSite')->with($destSite);
        $newPage2->expects(static::once())->method('setParent')->with($newPage1);

        $newPage1->expects(static::once())->method('setTarget')->with($newPage2);

        $this->pageManager->expects(static::exactly(4))->method('save');

        $block = $this->createMock(PageBlockInterface::class);
        $block->method('getId')->willReturn(4711);
        $block->method('getParent')->willReturn(null);

        $newBlock = $block;
        $newBlock->expects(static::once())->method('setPage')->with($newPage2);

        $this->blockManager->method('findBy')->willReturnOnConsecutiveCalls(
            [],
            [$block],
            [],
            [$newBlock]
        );
        $this->blockManager->expects(static::once())->method('save');

        $command = $this->application->find('sonata:page:clone-site');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--source-id' => 23,
            '--dest-id' => 42,
            '--prefix' => 'Copy of ',
            '--only-hybrid' => true,
        ]);

        static::assertMatchesRegularExpression('@done!@', $commandTester->getDisplay());
    }

    public function testExecuteNoSourceId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a "--source-id=SITE_ID" option.');

        $this->siteManager->method('findAll')->willReturn([]);

        $command = $this->application->find('sonata:page:clone-site');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--dest-id' => 42,
            '--prefix' => 'Copy of ',
        ]);

        static::assertMatchesRegularExpression('@Writing cache file ...\s+done!@', $commandTester->getDisplay());
    }

    public function testExecuteNoDestId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a "--dest-id=SITE_ID" option.');

        $this->siteManager->method('findAll')->willReturn([]);

        $command = $this->application->find('sonata:page:clone-site');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--source-id' => 23,
            '--prefix' => 'Copy of ',
        ]);

        static::assertMatchesRegularExpression('@Writing cache file ...\s+done!@', $commandTester->getDisplay());
    }

    public function testExecuteNoPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a "--prefix=PREFIX" option.');

        $this->siteManager->method('findAll')->willReturn([]);

        $command = $this->application->find('sonata:page:clone-site');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--source-id' => 23,
            '--dest-id' => 42,
        ]);
    }
}
