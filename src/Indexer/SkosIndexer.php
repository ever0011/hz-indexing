<?php

/**
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ\Indexer;

/**
 * This class is meant to index contexts
 * 
 * @author Thijs Vogels <t.vogels@me.com>
 */
class SkosIndexer extends IndexerAbstract
{

    const TYPE = "skos";

    /**
     * This does the actual indexing
     * 
     * @param string $name The name of the context to be indexed
     */
    public function index($name)
    {
        $data = $this->getData($name);

        $autoCompleteInput = $this->getAutocompleteInput($data);
        $vnUrls = $this->findVnPages($data);
        // $paragraphs = $this->findParagraphs($data);

        // Add to the index
        $params = array();

        // Add to index
        $params = array();
        $params['body'] = array(
            "url" => $data->getUrl(),
            "title" => $data->getName(),
            "skos:prefLabel" => $data->getName(),
            "skos:altLabel" =>$data->values('skos_altlabel'),
            "skos:definition" =>$data->values_cs('skos_definition'),
            "skos:related" => $data->urls('skos_related'),
            "skos:narrower" => $data->urls('skosem_narrower'),
            "skos:broader" => $data->urls('skosem_broader'),
            "skos:partOf" => $data->urls('skosem_partof'),
            "context_readable"=> $data->values_cs('context'),
            "context"=> $data->urls('context'),
            "suggest" => array(
                "input" => $autoCompleteInput,
                "output" => $data->getName(),
                "payload" => array(
                    "url" => $data->getUrl(),
                    "vn_pages" => $vnUrls,
                    "context" => $data->values_cs('context'), 
                    "type" => self::TYPE
                )
            )
        );

        $params['index'] = $this->index;
        $params['type'] = self::TYPE;
        $params['id'] = $this->getPageId($data);

        return $this->es->index($params);
    }

    /**
     * This gets the data from ASK
     * 
     * @param string $name The name of the context to be indexed
     * 
     * @throws \Exception
     */
    private function getData($name)
    {
        $output = $this->ask->query("
            [[{$name}]]
            |?skos:altLabel
            |?skos:related
            |?skosem:narrower
            |?skosem:broader
            |?skosem:partOf
            |?skos:definition
            |?Context
        ")->getResults();

        if (count($output) == 0) {
            throw new \Exception(sprintf("The page '%s' could not be found", $name));
        }

        return $output[0];
    }

    /**
     * Get the autocomplete input from data
     * 
     * @param \TV\HZ\Ask\Entry $data
     * 
     * @return string id
     */
    public function getAutocompleteInput(\TV\HZ\Ask\Entry $data)
    {
        return $data->getName();
    }

    /**
     * Get the VN pages for a page
     * 
     * @param \TV\HZ\Ask\Entry $data
     * 
     * @return array urls
     */
    public function findVnPages(\TV\HZ\Ask\Entry $data)
    {
        $query = "[[Model link::{$data->getName()}]]";
        $vns = $this->ask->query($query)->getResults();
        $vnurls = array();
        foreach ($vns as $vn) {
          $vnurls[] = $vn->getUrl();
        }

        return $vnurls;
    }

    /**
     * Get the paragraphs connected to the page
     * 
     * @param \TV\HZ\Ask\Entry $data
     * 
     * @return array paragraphs
     */
    public function findParagraphs(\TV\HZ\Ask\Entry $data)
    {
        $query = '[[Paragraph::+]]
        [[Paragraph back link::'.$data->getName().']]
        |?Paragraph
        |?Paragraph subheading
        |?Paragraph language
        |?Paragraph number
        |?Paragraph back link
        ';
        $paragraphs = $this->ask->query($query)->getResults();

        return array_map(function ($p) {
            return $p->values('paragraph-subheading') . ": " . $p->values('paragraph');
        }, $paragraphs);
    }

    public function old () {
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
