<?php

/**
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki.
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ\Indexer;
use TV\HZ\FileReader;

/**
 * This class is meant to index contexts
 *
 * @author Thijs Vogels <t.vogels@me.com>
 */
class ResourceDescriptionIndexer extends IndexerAbstract
{

    const TYPE = "resource_description";

    /**
     * Upload dir parameter
     */
    private $uploadDir;

    public function __construct($ask, $es, $index, $uploadDir)
    {
        $this->ask = $ask;
        $this->es = $es;
        $this->index = $index;
        $this->uploadDir = $uploadDir;
        $this->noFileCount = 0;
    }

    /**
     * This does the actual indexing
     *
     * @param string $name The name of the context to be indexed
     */
    public function index($name)
    {
        $data = $this->getData($name);

        $filecontents = "";
        $fullpath = "";
        $filepath = "";
        $hyperlink = false;
        $link = null;
        $notFoundCount = 0;

        // case of a File
        if (preg_match(
            "@(Bestand|Media|File):(.*\.([a-z]+))$@i",
            $data->getUrl(),
            $matches
        )) {

            $filename = $matches[2];
            $md5 = md5($filename);
            $filepath = "/" . $md5[0] . "/" . substr($md5, 0,2) . "/" . $filename;
            $fullpath = $this->uploadDir . $filepath;

            // determine extension & type
            $extension = strtolower($matches[3]);
            $extension_type = $this->mapExtension($extension);

            if (file_exists($fullpath)) {
                $reader = $this->getReader($extension_type, $fullpath);
                try {
                    $filecontents = $reader->result();
                } catch (\Exception $e) {
                    $filecontents = "";
                }
            } else {
                $notFoundCount++;
            }

        }
        // hyperlink
        elseif (count($data->urls('hyperlink')) > 0)
        {
            $hyperlink = true;
            $link = $data->urls_cs('hyperlink');
        }
        else
        {
            $this->noFileCount++;
            return "noFileOrHyperlink";
        }

        $autoCompleteInput[] = $data->values('dct_title');

        //WME debugging - //WME: prevent index exception with certain contents
        // $filecontents = strlen($filecontents);
        // $filecontents = quoted_printable_encode($filecontents);
        $filecontents = utf8_encode($filecontents);
        // $filecontents = addslashes($filecontents); //geeft ook die empty doc fout
        //$filecontents = mb_convert_encoding($filecontents, "EUC-JP", "auto");
        // print_r($filecontents);exit;

        $params['body'] = array(
            "url" => $data->getUrl(),
            "title" => $data->values_cs('dct_title'),
            "creator" => $data->values_cs('dct_creator'),
            "date" => $data->values_cs('dct_date'),
            "description" => $data->values_cs('dct_description'),
            "subject" => $data->urls('dct_subject'),
            "filename" => $data->getName(),
            "content" => $filecontents,
            "context_readable"=> 'TODO',
            "context"=> 'TODO',
            "vn_pages" => array(),
            "suggest" => array(
                "input" => $autoCompleteInput,
                "output" => $data->getName(),
                "payload" => array(
                    "url" => $data->getUrl(),
                    "vn_pages" => array(),
                    "context" => 'TODO',
                    "type" => 'resource_description'
                )
            )
        );
        $params['index'] = $this->index;
        $params['type'] = self::TYPE;
        $params['id'] = $this->getPageId($data);

        //WME debugging
        // echo "FILENAME: ".$params['body']['filename']."\n";
        // echo "CONTENT: ".$params['body']['content']."\n";
        // echo "MEM_USAGE: ".memory_get_peak_usage()."\n";

        //WME TODO/FIXME: use bulk indexing of eleastic search.

        try{
          $stat = array("hyperlink"     => $hyperlink,
                        "notFoundCount" => $notFoundCount);
          $res = $this->es->index($params);
          return array_merge($stat, (array)$res);
        }
        catch (Exception $e) {
          echo 'WME Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    /**
     * This gets the data from ASK
     *
     * @param string $name The name of the context to be indexed
     *
     * @throws \Exception
     */
    private function getData($name)
    {
        $output = $this->ask->query("
            [[{$name}]]
            |?Dct:title
            |?Dct:creator
            |?Dct:date
            |?Dct:description
            |?Dct:subject
            |?Hyperlink
        ")->getResults();

        if (count($output) == 0) {
            throw new \Exception(sprintf("The page '%s' could not be found", $name));
        }

        return $output[0];
    }

    /**
     * Get the autocomplete input from data
     *
     * @param \TV\HZ\Ask\Entry $data
     *
     * @return string id
     */
    public function getAutocompleteInput(\TV\HZ\Ask\Entry $data)
    {
        return $data->getName();
    }

    /**
     * Get the VN pages for a page
     *
     * @param \TV\HZ\Ask\Entry $data
     *
     * @return array urls
     */
    public function findVnPages(\TV\HZ\Ask\Entry $data)
    {
        $query = "[[Model link::{$data->getName()}]]";
        $vns = $this->ask->query($query)->getResults();
        $vnurls = array();
        foreach ($vns as $vn) {
          $vnurls[] = $vn->getUrl();
        }

        return $vnurls;
    }

    /**
     * Get the paragraphs connected to the page
     *
     * @param \TV\HZ\Ask\Entry $data
     *
     * @return array paragraphs
     */
    public function findParagraphs(\TV\HZ\Ask\Entry $data)
    {
        $query = '[[Paragraph::+]]
        [[Paragraph back link::'.$data->getName().']]
        |?Paragraph
        |?Paragraph subheading
        |?Paragraph language
        |?Paragraph number
        |?Paragraph back link
        ';
        $paragraphs = $this->ask->query($query)->getResults();
        return array_map(function ($p) {
            return $p->values_cs('paragraph_subheading') . ": " . $p->values_cs('paragraph');
        }, $paragraphs);
    }



    /**
     * Private dictionary with extensions and their type
     */
    private $extensions_map = array(
        "jpeg" => "image",
        "jpg" => "image",
        "png" => "image",
        "gif" => "image",
        "bmp" => "image",
        "pdf" => "pdf",
        "doc" => "ms-word",
        "docx" => "ms-word",
        "xls" => "ms-excel",
        "xlsx" => "ms-excel",
        "ppt" => "ms-powerpoint",
        "pptx" => "ms-powerpoint"
    );

    /**
     * This maps extensions to their type
     * If the type is unknown, it is seen as a type of its own
     *
     * @param string $extension
     *
     * @return string output file type
     */
    private function mapExtension($extension)
    {
        if(array_key_exists($extension, $this->extensions_map)) {
            return $this->extensions_map[$extension];
        } else {
            return $extension;
        }
    }

    /**
     * This returns an instance of the appropriate reader class
     * for a certain file type.
     *
     * @param string $type File type (defined in $extensions_map)
     * @param string $fullpath Path to the file
     *
     * @return FileReader file reader object.
     */
    private function getReader($type, $fullpath)
    {
        switch ($type) {
            case 'image':
                return new FileReader\ImageReader($fullpath);
                break;
            case 'pdf':
                return new FileReader\PdfReader($fullpath);
                break;
            case 'ms-word':
                return new FileReader\MsWordReader($fullpath);
                break;
            case 'ms-powerpoint':
                return new FileReader\MsPowerPointReader($fullpath);
                break;
            default:
                return new FileReader($fullpath);
                break;
        }
    }

}
