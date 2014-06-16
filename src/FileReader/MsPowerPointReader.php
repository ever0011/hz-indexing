<?php

namespace TV\HZ\FileReader;

use TV\HZ\FileReader;
use TV\HZ\FileReader\PptConversion;

class MsPowerPointReader extends FileReader {

  public function result()
  {

    $fileArray = pathinfo($this->path);
    $file_ext  = $fileArray['extension'];

    switch ($file_ext) {
      case 'ppt':
        $reader = new PptConversion($this->path);
        return $reader->convertToText();
        break;

      case 'pptx':
        $reader = new DocxConversion($this->path);
        return $reader->convertToText();
        break;
      
      default:
        return "";
        break;
    }

  }

}