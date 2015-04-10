<?php

/**
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki.
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class AllCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('index:all')
            ->setDescription('Reset index and go to through everything.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->find('index:reset')->run($input, $output);
        $this->getApplication()->find('index:contexts')->run($input, $output);
        $this->getApplication()->find('index:skosconcepts')->run($input, $output);
        $this->getApplication()->find('index:intentionalelements')->run($input, $output);
        $this->getApplication()->find('index:resourcedescriptions')->run($input, $output);
    }
}
