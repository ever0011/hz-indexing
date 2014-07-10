<?php

/**
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ\Indexer;
use TV\HZ\Ask\Entry;

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
            "output" =>             $data->getNiceTitle(),
            "payload" => array(
                "url" =>            $data->getNiceTitle(),
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
            [[" . $name . "]]
            |?skos:definition
            |?Concerns
            |?Dct:subject
            |?Context
            |?" . Entry::TITLE_PROPERTY . "
            |?" . Entry::HEADING_PROPERTY . "
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
        return array($data->getNiceTitle(),$data->getName());
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

}
