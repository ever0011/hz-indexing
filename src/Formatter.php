<?php

/*
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ;

class Formatter {

  # This prettifies objects returned by the ASK api.
  public function prettify($obj) {
    if (isset($obj->fullurl)) {
      return $obj->fulltext;
    } else {
      return $obj;
    }
  }

  # Returns the URL's of an array returned by the ASK api.
  public function urls($array) {
    return array_map(function ($a) { return $a->fullurl; }, $array);
  }
  # Returns the names of an array returned by the ASK api.
  public function texts($array) {
    return array_map(function ($a) { return $a->fulltext; }, $array);
  }

}