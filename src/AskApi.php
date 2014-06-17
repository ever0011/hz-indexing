<?php

/*
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ;

class AskApi {

  private $host;

  public function __construct($host) {

    $this->host = $host;
  
  }

  public function query($q) {
    $url = $this->host . '/api.php';
    $response = json_decode(
      file_get_contents("http://{$url}?action=ask&format=json&query=" . urlencode($q . "|limit=5000"))
    );
    $output = array();
    foreach ($response->query->results as $result) {
      $output[] = $result;
    }
    return $output;
  }

}