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
     * @param int $limit Limit the number of results (optional, defaults to self::RETURN_LIMIT)
     *
     * @return TV\HZ\Ask\Output Output of the query
     *
     * @throws \Exception
     */
    public function query($q, $limit=0, $offset=0)
    {
        $limit = ($limit > 0) ? $limit : self::RETURN_LIMIT;

        $params = new \FauxRequest(
            array(
                'action' => 'ask',
                'query' => $q."|limit=" . $limit . "|offset=" . $offset,
            )
        );
        $api = new \ApiMain( $params );
        $api->execute();
        $result = $api->getResultData();
        return new Output($result);
    }

}
