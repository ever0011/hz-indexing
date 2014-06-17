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
abstract class IndexerAbstract
{
    /**
     * ASK Service
     */
    protected $ask;

    /**
     * Elasticsearch Service
     */
    protected $es;

    /**
     * Name of Elasticsearch index
     */
    protected $index;

    /**
     * Constructor
     * 
     */
    public function __construct($ask, $es, $index)
    {
        $this->ask = $ask;
        $this->es = $es;
        $this->index = $index;
    }

    /**
     * Convert URL to ID for ElasticSearch
     * 
     * @param \TV\HZ\Ask\Entry $data
     * 
     * @return string id
     */
    public function getPageId(\TV\HZ\Ask\Entry $data)
    {
        return md5($data->getUrl());
    }

}
