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

        $output->writeln('<bg=yellow;options=bold>Add Resource descriptions to the index</bg=yellow;options=bold>');

        # Load concepts via Ask
        $output->writeln('- Loading concepts (ASK) ...');
        $concepts = $ask->query('[[Category:Resource Description]][[Dct:title::+]]')->getResults();
        $n = count($concepts);
        $output->writeln(sprintf("  %d found.", $n));

        # Set up a progress indicator
        $output->writeln("- Creating index ...\n");
        $progress = new ProgressBar($output, $n);
        $progress->setMessage('Starting ...');
        $progress->setFormat("  %current%/%max% [%bar%] %percent%% \n  %message%");
        $progress->start();

        # Do the actual indexing
        foreach ($concepts as $concept) {
            $progress->setMessage($concept->getName());
            $indexer->index($concept->getName());
            $progress->advance();
        }

        # Finish up
        $progress->setMessage('Done.');
        $progress->finish();
        $output->writeln('');
    }
}