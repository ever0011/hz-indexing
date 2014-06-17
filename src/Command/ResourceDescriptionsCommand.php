<?php

/**
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use TV\HZ\FileReader;

/**
 * This command updates all Resource Descriptions in the index
 * 
 * @author Thijs Vogels <t.vogels@me.com>
 */
class ResourceDescriptionsCommand extends Command
{

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

    /**
     * This configures the command.
     */
    protected function configure()
    {
        $this
            ->setName('index:resourcedescriptions')
            ->setDescription('Add all resource descriptions to the index.')
        ;
    }

    /**
     * This executes the command.
     * 
     * @param Symfony\Component\Console\Input\InputInterface $input
     * @param Symfony\Component\Console\Input\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $container;
        $es = $container->get('elasticsearch');
        $ask = $container->get('ask');
        $formatter = $container->get('formatter');

        $output->writeln('<bg=yellow;options=bold>Add resource descriptions to the index</bg=yellow;options=bold>');

        # Load SKOS Concepts via ASK
        $output->writeln('- Loading resource descriptions (ASK) ...');
        $out = $ask->query('
        [[Category:Resource Description]]
        [[Dct:title::+]]
        |?Dct:title
        |?Dct:creator
        |?Dct:date
        |?Dct:description
        |?Dct:subject
        |?Hyperlink
        ');
        $elements = $out->getResults();
        $n = count($elements);
        $output->writeln("  $n found.");

        $output->writeln("- Creating index ...\n");
        $progress = new ProgressBar($output, $n);
        $progress->setMessage('Starting ...');
        $progress->setFormat("  %current%/%max% [%bar%] %percent%% \n  %message%");
        $progress->start();

        $noFileCount = 0;
        $notFoundCount = 0;
        foreach ($elements as $c) {

            $progress->setMessage($formatter->prettify($c));
            $progress->advance();

            $filecontents = "";
            $fullpath = "";
            $filepath = "";
            $hyperlink = false;
            $link = null;
            // case of a File
            if (preg_match(
                "@(Bestand|Media|File):(.*\.([a-z]+))$@i", 
                $c->getUrl(), 
                $matches
            )) {

                $filename = $matches[2];
                $md5 = md5($filename);
                $filepath = "/" . $md5[0] . "/" . substr($md5, 0,2) . "/" . $filename;
                $fullpath = $container->getParameter('upload.dir') . $filepath;

                // determine extension & type
                $extension = strtolower($matches[3]);
                $extension_type = $this->mapExtension($extension);

                if (file_exists($fullpath)) {
                    $reader = $this->getReader($extension_type, $fullpath);
                    $filecontents = $reader->result();
                } else {
                    $notFoundCount++;
                }

            }
            // hyperlink
            elseif (count($c->urls('hyperlink')) > 0)
            {
                $hyperlink = true;
                $link = $c->urls_cs('hyperlink');
            }
            else 
            {
                $noFileCount++;
                continue;
            }

            $autoCompleteInput[] = $c->values('dct_title');


            $params['body'] = array(
                "url" => $c->getUrl(),
                "title" => $c->values_cs('dct_title'),
                "creator" => $c->values_cs('dct_creator'),
                "date" => $c->values_cs('dct_date'),
                "description" => $c->values_cs('dct_description'),
                "subject" => $c->urls('dct_subject'),
                "filename" => $c->getName(),
                "content" => $filecontents,
                "context_readable"=> 'TODO',
                "context"=> 'TODO',
                "vn_pages" => array(),
                "suggest" => array(
                    "input" => $autoCompleteInput,
                    "output" => $c->getName(),
                    "payload" => array(
                        "url" => $c->getUrl(),
                        "vn_pages" => array(),
                        "context" => 'TODO',
                        "type" => 'resource_description'
                    )
                )
            );
            $params['index'] = $container->getParameter('elastic.index');
            $params['type'] = 'resource_description';
            $params['id'] = md5($c->getUrl());
            $ret = $es->index($params);
        }

        $progress->setMessage('Done.');
        $progress->finish();
        $output->writeln('');
        $output->writeln('  <info>Skipped ' . $noFileCount . ' rs\'s that are no file and have no link.</info>');
        $output->writeln('  <info>' . $notFoundCount . ' files were not found.</info>');
    }
}