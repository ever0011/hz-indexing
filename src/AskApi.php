<?php

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