<?php

namespace TV\HZ\FileReader;

class PptConversion{

    private $filename;

    public function __construct($filePath) {
        $this->filename = $filePath;
    }

    function convertToText() {
        $filename = $this->filename;
        // This approach uses detection of the string "chr(0f).Hex_value.chr(0x00).chr(0x00).chr(0x00)" to find text strings, which are then terminated by another NUL chr(0x00). [1] Get text between delimiters [2] 
        $fileHandle = fopen($filename, "r");
        $line = @fread($fileHandle, filesize($filename));
        $lines = explode(chr(0x0f),$line);
        $outtext = '';

        foreach($lines as $thisline) {
            if (strpos($thisline, chr(0x00).chr(0x00).chr(0x00)) == 1) {
                $text_line = substr($thisline, 4);
                $end_pos   = strpos($text_line, chr(0x00));
                $text_line = substr($text_line, 0, $end_pos);
                $text_line = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$text_line);
                if (strlen($text_line) > 1) {
                    $outtext.= substr($text_line, 0, $end_pos)."\n";
                }
            }
        }
        return $outtext;
    }

}