<?php

namespace TV\HZ\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class SkosConceptsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('index:skosconcepts')
            ->setDescription('Add all skos concepts to the index.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $container;
        $es = $container->get('elasticsearch');
        $ask = $container->get('ask');
        $formatter = $container->get('formatter');

        $output->writeln('<bg=yellow;options=bold>Add SKOS concepts to the index</bg=yellow;options=bold>');

        # Load SKOS Concepts via ASK
        $output->writeln('- Loading concepts (ASK) ...');
        $concepts = $ask->query('
        [[Category:SKOS Concept]]
        |?skos:altLabel
        |?skos:related
        |?skosem:narrower
        |?skosem:broader
        |?skosem:partOf
        |?skos:definition
        |?Context
        ');
        $n = count($concepts);
        $output->writeln("  $n found.");

        # Load all paragraphs via ASK and construct an array of raw
        # page contents.
        $output->writeln('- Loading page paragraphs (ASK) ...');
        $paragraphs = $ask->query('
        [[Paragraph::+]]
        [[Paragraph back link::<q>[[Category:SKOS Concept]]</q>]]
        |?Paragraph
        |?Paragraph subheading
        |?Paragraph language
        |?Paragraph number
        |?Paragraph back link
        ');
        $m = count($paragraphs);
        $output->writeln("  $m found.");
        $page_contents = array();
        foreach ($paragraphs as $p) {
            $url = $p->printouts->{'Paragraph back link'}[0]->fullurl;
            $content = $p->printouts->{'Paragraph'}[0];
            @$page_contents[$url] .= strip_tags($content) . " ";
        }

        # Actually index the concepts
        $output->writeln("- Creating index ...\n");
        $progress = new ProgressBar($output, $n);
        $progress->setMessage('Starting ...');
        $progress->setFormat("  %current%/%max% [%bar%] %percent%% \n  %message%");
        $progress->start();
        foreach ($concepts as $c) {

            $url = $c->fullurl;

            // $output->writeln("  {$formatter->prettify($c)}");
            $progress->setMessage($formatter->prettify($c));

            $content = "";
            if (isset($page_contents[$url])) 
                $content = $page_contents[$url];

            // find the VN pages
            $query = "[[Model link::{$c->fulltext}]]";
            $vns = $ask->query($query);
            $vnurls = array();
            foreach ($vns as $key => $value) {
              $vnurls[] = $value->fullurl;
            }

            $context_readable = implode($formatter->texts($c->printouts->{'Context'}), " ");

            // Add to index
            $params = array();
            $params['body'] = array(
                "url" => $c->fullurl,
                "title" => $c->fulltext,
                "skos:prefLabel" => $c->fulltext,
                "skos:altLabel" =>$c->printouts->{'Skos:altLabel'},
                "skos:definition" =>$c->printouts->{'Skos:definition'},
                "skos:related" => $formatter->urls($c->printouts->{'Skos:related'}),
                "skos:narrower" => $formatter->urls($c->printouts->{'Skosem:narrower'}),
                "skos:broader" => $formatter->urls($c->printouts->{'Skosem:broader'}),
                "skos:partOf" => $formatter->urls($c->printouts->{'Skosem:partOf'}),
                "context_readable"=> $context_readable,
                "context"=> $formatter->urls($c->printouts->{'Context'}),
                "content" => $content,
                "suggest" => array(
                    "input" => $c->fulltext,
                    "output" => $c->fulltext,
                    "payload" => array("url" => $c->fullurl,"vn_pages" => $vnurls,"context" => $context_readable, "type" => 'skos')
                )
            );
            $params['index'] = $container->getParameter('elastic.index');
            $params['type'] = 'skos_concept';
            $params['id'] = md5($c->fullurl);
            $ret = $es->index($params);

            $progress->advance();
        }
        $progress->setMessage('Done.');
        $progress->finish();
        $output->writeln('');
    }
}