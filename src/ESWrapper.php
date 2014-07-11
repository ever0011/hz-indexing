<?php

/**
 * This is a wrapper for the elasticsearch client such that server
 * can be easily injected.
 */

namespace TV\HZ;

class ESWrapper extends \Elasticsearch\Client
{
  public function __construct($host)
  {
    // param : array('hosts' => array($host))
    return parent::__construct();
  }
}