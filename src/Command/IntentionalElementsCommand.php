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

class IntentionalElementsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('index:intentionalelements')
            ->setDescription('Add all intentional elements to the index (excluding SKOS concepts).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $container;
        $ask = $container->get('ask');
        $indexer = $container->get('indexer.intentionalelement');

        $output->writeln('<bg=yellow;options=bold>Add intentional elements to the index</bg=yellow;options=bold>');

        # Load concepts via Ask
        $output->writeln('- Loading concepts (ASK) ...');
        $concepts = $ask->query('[[Category:Intentional Element]][[Context::+]]')->getResults();
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