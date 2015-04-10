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
 * This command updates all Resource Descriptions in the index
 * Consumes a lot of php cli memory - disable memory limit
 *
 * @author Thijs Vogels <t.vogels@me.com>
 */
class ResourceDescriptionsCommand extends Command
{
    /**
     * This configures the command.
     */
    protected function configure()
    {
        $this
            ->setName('index:resourcedescriptions')
            ->setDescription('Add all resource descriptions to the index.')
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_REQUIRED,
                'Start indexing at the position indicated by the given offset (0-based)',
                0
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit the number of resource descriptions to index this run to the given limit',
                100
            )
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Print debug info'
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
        $offset = $input->getOption('offset');
        $limit = $input->getOption('limit');
        $debug = $input->getOption('debug');

        $output->writeln('<bg=yellow;options=bold>Add Resource Descriptions to the index</bg=yellow;options=bold>');

        # Load concepts via Ask
        $output->writeln("- Loading Resource Descriptions (ASK), considering limit ({$limit}) and offset ({$offset})...");
        $concepts = $ask->query('[[Category:Resource Description]][[Dct:title::+]]', $limit, $offset)->getResults();
        $n = count($concepts);
        $output->writeln(sprintf("  %d loaded.", $n));

        # Set up a progress indicator
        $output->writeln("- Indexing loaded Resource Descriptions, starting at position #" . ($offset) ."...\n");
        $progress = new ProgressBar($output, $n);
        $progress->setFormat("  %current%/%max% [%bar%] %percent%% \n  %message%");
        $progress->setMessage('Starting ...');
        $progress->start();

        # Do the actual indexing
        foreach ($concepts as $concept) {
            $progress->setMessage($concept->getName());
            $progress->display();     //make sure the message of the bar is updated...
            $indexResult = $indexer->index($concept->getName());
            $progress->advance();
            //WMEdebug
            if($debug)$indexedDebug[] = array("Name" => $concept->getName(), "indexResult" => $indexResult);
        }

        # Finish up
        $progress->setMessage('Done.');
        $progress->finish();
        $output->writeln("\nMemory Usage: ". memory_get_peak_usage()/1024/1024 . " MB.");
        $output->writeln('');

        //WMEdebug, write to file or use fancy symfony shizzle
        if($debug && isset($indexedDebug)) print_r($indexedDebug);
    }
}
