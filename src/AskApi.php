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

  public function __construct($host, $user, $pass) {

    $this->host = $host;
    $this->user = $user;
    $this->pass = $pass;

  }

  public function query($q) {
    $url = $this->host . '/api.php';

    $curl = curl_init("http://{$url}?action=ask&format=json&query=" . urlencode($q . "|limit=5000");
    if ($this->user != '') {
      curl_setopt($curl, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
    }
    $response = curl_exec($curl);
    curl_close($curl);

    $output = array();
    foreach ($response->query->results as $result) {
      $output[] = $result;
    }
    return $output;
  }

}