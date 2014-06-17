<?php

/*
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

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