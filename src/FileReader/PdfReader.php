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

class PdfReader extends FileReader {

  public function result()
  {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf    = $parser->parseFile($this->path);
     
    $text = $pdf->getText();
    return $text;
  }

}