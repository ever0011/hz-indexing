<?php

/*
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ;

class FileReader {

  protected $path;

  public function __construct ($fullpath)
  {
    $this->path = $fullpath;
  }

  public function result ()
  {
    return file_get_contents($fullpath);
  }

}