<?php

namespace TV\HZ\FileReader;

use TV\HZ\FileReader;

class PdfReader extends FileReader {

  public function result()
  {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf    = $parser->parseFile($this->path);
     
    $text = $pdf->getText();
    return $text;
  }

}