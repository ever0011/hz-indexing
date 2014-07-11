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

class ResetCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('index:reset')
            ->setDescription('Removes the Elasticsearch index and regenerates it.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $container;
        $es = $container->get('elasticsearch');

        # Delete the index
        # Catch exceptions, this should also work if the index does not exist.
        $deleteParams['index'] = $container->getParameter('elastic.index');
        try {
            $es->indices()->delete($deleteParams);
            $output->writeln("- Old index was deleted.");
        } catch (\Exception $e) {
            $output->writeln("<comment>Old index was not present.</comment>");
        }

        # Create a new index
        $indexParams['index']  = $container->getParameter('elastic.index');
        $indexParams['body']['settings']['index']['analysis'] = array (
            "filter" => array (
                "skosfilter" => array (
                    "type" => "skos",
                    "path" => $container->getParameter('elastic.tmpdir'),
                    "skosFile" => $container->getParameter('elastic.skosn3file'),
                    "expansionType" => "URI"
                )
            ),
            "analyzer" => array (
                "skos" => array (
                    "type" => "custom",
                    "tokenizer" => "keyword",
                    "filter" => "skosfilter"
                ),
                "my_analyzer" => array (
                    "type" => "snowball",
                    "language" => "Dutch",
                    "stopwords" => array("aan","af","al","alles","als","altijd","andere","ben","bij","daar","dan","dat","de","der","deze","die","dit","doch","doen","door","dus","een","eens","en","er","ge","geen","geweest","haar","had","heb","hebben","heeft","hem","het","hier","hij ","hoe","hun","iemand","iets","ik","in","is","ja","je ","kan","kon","kunnen","maar","me","meer","men","met","mij","mijn","moet","na","naar","niet","niets","nog","nu","of","om","omdat","ons","ook","op","over","reeds","te","tegen","toch","toen","tot","u","uit","uw","van","veel","voor","want","waren","was","wat","we","wel","werd","wezen","wie","wij","wil","worden","zal","ze","zei","zelf","zich","zij","zijn","zo","zonder","zou")
                )
            )
        );
        $indexParams['body']['mappings']['_default_']['properties']['subject'] = array (
            "type" => "string",
            "index_analyzer" => "skos",
            "search_analyzer" => "standard"
        );
        $indexParams['body']['mappings']['_default_']['properties']['content'] = array (
            "type" => "string",
            "index_analyzer" => "my_analyzer",
            "search_analyzer" => "my_analyzer"
        );
        $indexParams['body']['mappings']['_default_']['properties']['title'] = array (
            "type" => "string",
            "index_analyzer" => "my_analyzer",
            "search_analyzer" => "my_analyzer"
        );  
        $indexParams['body']['mappings']['_default_']['properties']['name'] = array (
            "type" => "multi_field",
            "fields" => array(
                "name" => array(
                    "type" => "string"
                ), 
                "untouched" => array(
                    "type" => "string",
                    "index" => "not_analyzed"
                )
            )
        );  



        $indexParams['body']['mappings']['_default_']['properties']['suggest'] = array (
            "type" => "completion",
            "index_analyzer" => "simple",
            "search_analyzer" => "simple",
            "payloads" => true
        );

        if ($es->indices()->create($indexParams)) {
            $output->writeln("- Index <info>{$container->getParameter('elastic.index')}</info> was reinitialized.");
        }

    }
}