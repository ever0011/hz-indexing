<?php

namespace TV\HZ\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ContextsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('index:contexts')
            ->setDescription('Add all contexts to the index.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $container;
        $es = $container->get('elasticsearch');
        $ask = $container->get('ask');
        $formatter = $container->get('formatter');

        $output->writeln('<bg=yellow;options=bold>Add contexts to the index</bg=yellow;options=bold>');

        # Load SKOS Concepts via ASK
        $output->writeln('- Loading contexts (ASK) ...');
        $contexts = $ask->query('
        [[Category:Context]]
        |?Category
        |?Supercontext
        ');
        $n = count($contexts);
        $output->writeln("  $n found.");

        $output->writeln('- Add to the index ...');
        # Index the contexts
        foreach ($contexts as $c) {

            // Display
            $output->writeln("  <info>" . $formatter->prettify($c) . "</info>");

            // make a list of terms for auto completion
            // $autoCompleteInput = explode(" ",$c->fulltext);
            $autoCompleteInput = array();
            $autoCompleteInput[] = $c->fulltext;

            // find the VN pages
            $query = "[[Model link::{$c->fulltext}]]";
            $vns = $ask->query($query);
            $vnurls = array();
            foreach ($vns as $key => $value) {
              $vnurls[] = $value->fullurl;
            }

            // Add to the index
            $params = array();
            $super = 'ROOT';
            if (count($c->printouts->{'Supercontext'}) > 0) 
              $super = $c->printouts->{'Supercontext'}[0]->fullurl;

            $super_readable = '';
            if (count($c->printouts->{'Supercontext'}) > 0) 
              $super_readable = $c->printouts->{'Supercontext'}[0]->fulltext;

            $params['body'] = array(
                'url' => $c->fullurl,
                'name' => $c->fulltext,
                'supercontext' => $super,
                'category' => $formatter->urls($c->printouts->{'Category'}),
                'category_readable' => $formatter->texts($c->printouts->{'Category'}),
                'vn_pages' => $vnurls,
                "suggest" => array(
                    "input" => $autoCompleteInput,
                    "output" => $c->fulltext,
                    "payload" => array(
                        "url" => $c->fullurl,
                        "context" => $super_readable,
                        'vn_pages' => $vnurls,
                        "type"=>'context'
                    )
                )
            );
            $params['index'] = $container->getParameter('elastic.index');
            $params['type'] = 'context';
            $params['id'] = md5($c->fullurl);
            $ret = $es->index($params);
        }
        $output->writeln("Done.");
    }
}