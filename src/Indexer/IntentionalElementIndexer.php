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
class IntentionalElementIndexer extends IndexerAbstract
{

    const TYPE = "intentional_element";

    /**
     * This does the actual indexing
     * 
     * @param string $name The name of the context to be indexed
     */
    public function index($name)
    {
        $data = $this->getData($name);
        
        if (count($data->values("skos_definition")) > 0) {
            $skos++;
            return;
        }

        $autoCompleteInput = $this->getAutocompleteInput($data);
        $vnUrls = $this->findVnPages($data);
        $paragraphs = $this->findParagraphs($data);
        $content = implode(" ", $paragraphs);

        $params['body'] = array(
          "url" =>                  $data->getUrl(),
          "title" =>                $data->getName(),
          "content" =>              $content,
          "concerns_readable" =>    $data->values_cs('concerns'),
          "concerns" =>             $data->urls('concerns'),
          "subject" =>              $data->urls('dct_subject'),
          "context_readable"=>      $data->values_cs('context'),
          "context"=>               $data->urls('context'),
          "vn_pages" =>             $vnUrls,
          "suggest" => array(
            "input" =>              $autoCompleteInput,
            "output" =>             $data->getName(),
            "payload" => array(
                "url" =>            $data->getName(),
                "vn_pages" =>       $vnUrls,
                "context" =>        $data->values_cs('context'), 
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
            |?skos:definition
            |?Concerns
            |?Dct:subject
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
            return $p->values_cs('paragraph_subheading') . ": " . $p->values_cs('paragraph');
        }, $paragraphs);
    }

    private function old () {
                $es = $container->get('elasticsearch');
        $ask = $container->get('ask');
        $formatter = $container->get('formatter');

        $output->writeln('<bg=yellow;options=bold>Add intentional elements to the index (excluding SKOS concepts)</bg=yellow;options=bold>');

        # Load SKOS Concepts via ASK
        $output->writeln('- Loading intentional elements (ASK) ...');
        $elements = $ask->query('
        [[Category:Intentional Element]]
        [[Context::+]]
        |?skos:definition
        |?Concerns
        |?Dct:subject
        |?Context
        ');
        $n = count($elements);
        $output->writeln("  $n found.");

        # Load all paragraphs via ASK and construct an array of raw
        # page contents.
        $output->writeln('- Loading page paragraphs (ASK) ...');
        $paragraphs = $ask->query('
        [[Paragraph::+]]
        [[Paragraph back link::<q>[[Category:Intentional Element]]</q>]]
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
        $skos=0;
        foreach ($elements as $c) {

            $progress->advance();

            // Skip SKOS concepts
            if (count($c->printouts->{"Skos:definition"}) > 0) {
                $skos++;
                continue;
            }

            $url = $c->fullurl;

            // $output->writeln("  {$formatter->prettify($c)}");
            $progress->setMessage($formatter->prettify($c));

            $content = "";
            if (isset($page_contents[$url])) 
                $content = $page_contents[$url];

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

            $context_readable = implode($formatter->texts($c->printouts->{'Context'}), " ");
            $params['body'] = array(
              "url" => $c->fullurl,
              "title" => $c->fulltext,
              "content" => $content,
              "concerns_readable" => implode($formatter->texts($c->printouts->{'Concerns'}), " "),
              "concerns" => $formatter->urls($c->printouts->{'Concerns'}),
              "subject" => $formatter->urls($c->printouts->{'Dct:subject'}),
              "context_readable"=> $context_readable,
              "context"=> $formatter->urls($c->printouts->{'Context'}),
              "vn_pages" => $vnurls,
              "suggest" => array(
                "input" => $autoCompleteInput,
                "output" => $c->fulltext,
                "payload" => array("url" => $c->fullurl,"vn_pages" => $vnurls,"context" => $context_readable, "type" => 'intentional')
              )
            );
            $params['index'] = $container->getParameter('elastic.index');
            $params['type'] = 'intentional_element';
            $params['id'] = md5($c->fullurl);
            $ret = $es->index($params);

        }
        $progress->setMessage('Done.');
        $progress->finish();
        $output->writeln('');
        $output->writeln('  <info>Skipped ' . $skos . ' SKOS concepts.</info>');
    }
}
