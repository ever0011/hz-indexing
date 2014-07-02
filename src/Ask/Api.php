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
class Api
{

    const API_SCRIPT = "/api.php";

    const RETURN_LIMIT = 5000;

    const PROTOCOL = 'http';

    /**
     * Hostname of the wiki, set through the service provider
     */
    private $host;

    /**
     * HTTP Basic User (optional)
     */
    private $user;

    /**
     * HTTP Basic pwd
     */
    private $pass;



    /**
     * Constructor
     * 
     * @param string $host The URL of the wiki (will be used as $host/api.php)
     */
    public function __construct($host, $user, $pass)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
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
        $url = self::PROTOCOL . '://' . $this->host . self::API_SCRIPT;

        // Curl GET Request
        $curl = curl_init(
            $url . "?action=ask&format=json&query=" . 
            urlencode("{$q}|limit=" . self::RETURN_LIMIT)
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($this->user != '') {
          curl_setopt($curl, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
        }
        $json = curl_exec($curl);
        curl_close($curl);

        if (false === $json) {
            throw new \Exception(sprintf("Cannot connect to the ask api."));
        }

        return new Output(json_decode($json));
    }

}
