<?php

/**
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ\Ask;

/**
 * Ask API class.
 * Queries the API of a Semantic Media Wiki.
 * 
 * @author Thijs Vogels <t.vogels@me.com>
 */
class ApiInternal
{

    const RETURN_LIMIT = 5000;

    /**
     * Constructor
     * 
     */
    public function __construct()
    {

    }

    /**
     * Query the Ask API
     *
     * @param string $q The ask query
     * 
     * @return TV\HZ\Ask\Output Output of the query 
     * 
     * @throws \Exception
     */
    public function query($q)
    {
        $params = new \FauxRequest( 
            array(
                'action' => 'ask',
                'query' => $q."|limit=" . self::RETURN_LIMIT
            )
        );
        $api = new \ApiMain( $params );
        $api->execute();
        $result = $api->getResultData();
        return new Output(json_decode(json_encode($result)));
    }

}
