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
class ContextIndexer extends IndexerAbstract
{

    const TYPE = "context";

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

        // Add to the index
        $params = array();

        $super = 'ROOT';
        if (count($supers = $data->urls('supercontext')) > 0) {
            $super = $supers[0];
        }

        $params['body'] = array(
            'url' =>                $data->getUrl(),
            'name' =>               $data->getName(),
            'supercontext' =>       $super,
            'category' =>           $data->urls('category'),
            'category_readable' =>  $data->values_cs('category'),
            'vn_pages' =>           $vnUrls,
            "suggest" => array(
                "input" =>          $autoCompleteInput,
                "output" =>         $data->getName(),
                "payload" => array(
                    "url" =>        $data->getUrl(),
                    "context" =>    $super_readable,
                    'vn_pages' =>   $vnUrls,
                    "type" =>       self::TYPE
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
            |?Category
            |?Supercontext
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
}
