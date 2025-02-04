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

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @final since sonata-project/page-bundle 3.26
 */
class MigrateToJsonTypeCommand extends Command
{
    private EntityManagerInterface $entityManager;

    protected static $defaultName = 'sonata:page:migrate-block-json';

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function configure(): void
    {
        $this->addOption('table', null, InputOption::VALUE_OPTIONAL, 'Block table', 'page__block');
        $this->setDescription('Migrate all block settings to the doctrine JsonType');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = 0;
        $table = $input->getOption('table');
        $connection = $this->entityManager->getConnection();
        $blocks = $connection->fetchAll("SELECT * FROM $table");

        foreach ($blocks as $block) {
            // if the row need to migrate
            if (0 !== strpos($block['settings'], '{') && '[]' !== $block['settings']) {
                $block['settings'] = json_encode(unserialize($block['settings']));
                $connection->update($table, ['settings' => $block['settings']], ['id' => $block['id']]);
                ++$count;
            }
        }

        $output->writeln("Migrated $count blocks");

        return 0;
    }
}
