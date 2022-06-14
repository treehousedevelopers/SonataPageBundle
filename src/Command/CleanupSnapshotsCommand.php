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

use Psr\Container\ContainerInterface;
use Sonata\PageBundle\Processor\CleanupSnapshotsProcessor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Cleanups the deprecated snapshots.
 *
 * @final since sonata-project/page-bundle 3.26
 */
class CleanupSnapshotsCommand extends BaseCommand
{
    protected static $defaultName = 'sonata:page:cleanup-snapshots';

    private CleanupSnapshotsProcessor $cleanupSnapshotsProcessor;

    public function __construct(ContainerInterface $locator, CleanupSnapshotsProcessor $cleanupSnapshotsProcessor)
    {
        parent::__construct($locator);
        $this->cleanupSnapshotsProcessor = $cleanupSnapshotsProcessor;
    }

    public function configure(): void
    {
        $this->setDescription('Cleanups the deprecated snapshots by a given site');

        $this->addOption('site', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Site id', null);
        $this->addOption('base-console', null, InputOption::VALUE_OPTIONAL, 'Base Symfony console command', 'app/console');
        $this->addOption('keep-snapshots', null, InputOption::VALUE_OPTIONAL, 'Keep a given count of snapshots per page', 5);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->getOption('site')) {
            $output->writeln('Please provide an <info>--site=SITE_ID</info> option or the <info>--site=all</info> directive');
            $output->writeln('');

            $output->writeln(sprintf(' % 5s - % -30s - %s', 'ID', 'Name', 'Url'));

            foreach ($this->getSiteManager()->findBy([]) as $site) {
                $output->writeln(sprintf(' % 5s - % -30s - %s', $site->getId(), $site->getName(), $site->getUrl()));
            }

            return;
        }

        if (!is_numeric($input->getOption('keep-snapshots'))) {
            throw new \InvalidArgumentException('Please provide an integer value for the option "keep-snapshots".');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getSites($input) as $site) {
            if ('all' !== $input->getOption('site')) {
                $output->write(sprintf('<info>%s</info> - Cleaning up snapshots ...', $site->getName()));

                $this->cleanupSnapshotsProcessor->process($site, $input->getOption('keep-snapshots'));

                $output->writeln(' done!');
            } else {
                $p = new Process(sprintf(
                    '%s sonata:page:cleanup-snapshots --env=%s --site=%s --keep-snapshots=%s %s',
                    $input->getOption('base-console'),
                    $input->getOption('env'),
                    $site->getId(),
                    $input->getOption('keep-snapshots'),
                    $input->getOption('no-debug') ? '--no-debug' : ''
                ));

                $p->run(static function ($type, $data) use ($output): void {
                    $output->write($data, OutputInterface::OUTPUT_RAW);
                });
            }
        }

        $output->writeln('<info>done!</info>');

        return 0;
    }
}
