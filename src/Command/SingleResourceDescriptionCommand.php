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
use TV\HZ\FileReader;

/**
 * This command adds or updates a given Resource Description in the index
 * Consumes a lot of php cli memory - disable memory limit
 *
 * @author WME
 */
class SingleResourceDescriptionCommand extends Command
{

    /**
     * This configures the command.
     */
    protected function configure()
    {
        $this
            ->setName('index:single_resourcedescription')
            ->setDescription('Add individual resource descriptions to the index')
            ->addArgument(
                'resdesc_name',
                InputArgument::REQUIRED,
                'Please provide a string like "Bestand:<file name>" or "File:<file name>"'
            )
        ;
    }

    /**
     * This executes the command.
     *
     * @param Symfony\Component\Console\Input\InputInterface $input
     * @param Symfony\Component\Console\Input\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $container;
        $ask = $container->get('ask');
        $indexer = $container->get('indexer.resourcedescription');

        $output->writeln('<bg=yellow;options=bold>Adding given resource description to the index</bg=yellow;options=bold>');

        $resdesc = $input->getArgument('resdesc_name');
        $output->writeln("- Adding {$resdesc}...\n");
        $res = $indexer->index($resdesc);
        // $output->writeln($res);
        var_dump($res);
        /*
          array(5) {
          ["_index"]=>
          string(10) "hzbwnature"
          ["_type"]=>
          string(20) "resource_description"
          ["_id"]=>
          string(32) "f0f28dc0079b08bafc2e29067794bdb4"
          ["_version"]=>
          int(7)
          ["created"]=>
          bool(false)
        */
        // echo "MEM_USAGE: ".memory_get_peak_usage(true)."\n";
        $output->writeln('');
    }
}
