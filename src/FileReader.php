<?php

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