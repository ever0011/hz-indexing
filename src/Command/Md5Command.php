<?php

namespace TV\HZ\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class Md5Command extends Command
{
    protected function configure()
    {
        $this
            ->setName('test:md5')
            ->addArgument(
                'string',
                InputArgument::REQUIRED,
                'Please provide a string'
            )
            ->setDescription('Add all contexts to the index.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(md5($input->getArgument('string')));
    }
}