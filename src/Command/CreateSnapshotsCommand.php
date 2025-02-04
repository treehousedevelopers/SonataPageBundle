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
use Sonata\PageBundle\Processor\CreateSnapshotsProcessor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Create snapshots for a site.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * @final since sonata-project/page-bundle 3.26
 */
class CreateSnapshotsCommand extends BaseCommand
{
    protected static $defaultName = 'sonata:page:create-snapshots';

    private CreateSnapshotsProcessor $createSnapshotsProcessor;

    public function __construct(ContainerInterface $locator, CreateSnapshotsProcessor $createSnapshotsProcessor)
    {
        parent::__construct($locator);

        $this->createSnapshotsProcessor = $createSnapshotsProcessor;
    }

    public function configure(): void
    {
        $this->setDescription('Create a snapshots of all pages available');
        $this->addOption('site', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Site id', null);
        $this->addOption('base-console', null, InputOption::VALUE_OPTIONAL, 'Base symfony console command', 'php app/console');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('site')) {
            $output->writeln('Please provide an <info>--site=SITE_ID</info> option or the <info>--site=all</info> directive');
            $output->writeln('');

            $output->writeln(sprintf(' % 5s - % -30s - %s', 'ID', 'Name', 'Url'));

            foreach ($this->getSiteManager()->findBy([]) as $site) {
                $output->writeln(sprintf(' % 5s - % -30s - %s', $site->getId(), $site->getName(), $site->getUrl()));
            }

            return 0;
        }

        foreach ($this->getSites($input) as $site) {
            if ('all' !== $input->getOption('site')) {
                $output->write(sprintf('<info>%s</info> - Generating snapshots ...', $site->getName()));

                $this->createSnapshotsProcessor->process($site);

                $output->writeln(' done!');
            } else {
                $p = new Process(sprintf('%s sonata:page:create-snapshots --env=%s --site=%s %s ', $input->getOption('base-console'), $input->getOption('env'), $site->getId(), $input->getOption('no-debug') ? '--no-debug' : ''));
                $p->setTimeout(0);
                $p->run(static function ($type, $data) use ($output): void {
                    $output->write($data, OutputInterface::OUTPUT_RAW);
                });
            }
        }

        $output->writeln('<info>done!</info>');

        return 0;
    }
}
