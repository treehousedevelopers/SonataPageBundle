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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migrates the name setting of all blocks into a code setting.
 *
 * @final since sonata-project/page-bundle 3.26
 */
class RenderBlockCommand extends BaseCommand
{
    protected static $defaultName = 'sonata:page:render-block';


    public function configure(): void
    {
        $this->setDescription('Dump page information');
        $this->setHelp(
            <<<HELP
Dump page information

Available manager:
 - sonata.page.cms.snapshot
 - sonata.page.cms.page
HELP
        );

        $this->addArgument('manager', InputArgument::REQUIRED, 'The manager service id');
        $this->addArgument('block_id', InputArgument::REQUIRED, 'The block id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manager = $input->getArgument('manager');

        if (!\in_array($manager, ['sonata.page.cms.snapshot', 'sonata.page.cms.page'], true)) {
            throw new \RuntimeException(
                'Available managers are "sonata.page.cms.snapshot" and "sonata.page.cms.page"'
            );
        }

        $managerService = 'sonata.page.cms.snapshot' === $manager ? $this->getCmsSnapshotManager() : $this->getCmsPageManager();

        $block = $managerService->getBlock($input->getArgument('block_id'));

        if (!$block) {
            throw new \RuntimeException('Unable to find the related block');
        }

        $output->writeln('<info>Block Information</info>');
        $output->writeln(sprintf('  > Id: %d - type: %s - name: %s', $block->getId(), $block->getType(), $block->getName()));

        foreach ($block->getSettings() as $name => $value) {
            $output->writeln(sprintf('   >> %s: %s', $name, json_encode($value)));
        }

        $context = $this->getBlockContextManager()->get($block);

        $output->writeln("\n<info>BlockContext Information</info>");
        foreach ($context->getSettings() as $name => $value) {
            $output->writeln(sprintf('   >> %s: %s', $name, json_encode($value)));
        }

        $output->writeln("\n<info>Response Output</info>");

        // fake request
        $output->writeln($this->getBlockRenderer()->render($context));

        return 0;
    }
}
