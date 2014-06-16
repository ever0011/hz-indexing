<?php

namespace TV\HZ\FileReader;

use TV\HZ\FileReader;
use TV\HZ\FileReader\DocxConversion;

class MsWordReader extends FileReader {

  public function result()
  {
    $docObj = new DocxConversion($this->path);
    return $docObj->convertToText();
  }

}