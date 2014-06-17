<?php

/**
 * This file is part of the indexing code for the semantic search engine of
 * the HzBwNature wiki. 
 *
 * It was developed by Thijs Vogels (t.vogels@me.com) for the HZ University of
 * Applied Sciences.
 */

namespace TV\HZ\Ask;

/**
 * Output for the TV\HZ\Ask\Api class.
 * 
 * @author Thijs Vogels <t.vogels@me.com>
 */
class Output
{

    const URL_TYPE = "_wpg";

    const TXT_TYPE = "_txt";

    const NUM_TYPE = "_num";

    const DATE_TYPE = "_dat";

    const LINK_TYPE = "_uri";

    /**
     * Neat results
     */
    private $results;

    /**
     * Available properties
     */
    private $properties = array();

    /**
     * Property types (dictionary)
     */
    private $propertyTypes = array();

    /**
     * Constructor
     * 
     * @param \StdClass $askOutput Result of an ask query
     */
    public function __construct(\StdClass $askOutput)
    {
        // set the available properties
        foreach ($askOutput->query->printrequests as $property) {

            if ($property->label == "") {
                continue;
            }

            $label = self::processLabelName($property->label);
            $this->properties[] = $label;
            $this->propertyTypes[$label] = $property->typeid;
        }

        // convert raw results to something neat
        $this->results = $this->convertToArray($askOutput->query->results);
    }

    /**
     * Return the results
     * 
     * @return array Array of results in the TV\HZ\Ask\Entry format
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Available properties
     * 
     * @return array Array of available properties
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Replace colons and spaces to : in label name
     * 
     * @param string $label Input label
     * @return string Processed label
     */
    public static function processLabelName($label)
    {
        $label = strtolower($label);
        $label = str_replace(":","_", $label);
        $label = str_replace(" ","_", $label);

        return $label;
    }

    /**
     * Convert the raw input to a nice array
     * 
     * @param \StdClass $result Result entry of an ask query
     * 
     * @return array Array of results in the TV\HZ\Ask\Entry format
     */
    protected function convertToArray($result)
    {
        $out = array();

        foreach ($result as $r) {
            $out[] = new Entry($this->propertyTypes, $r);
        }

        return $out;
    }

    /**
     * This converts the output to a readable string
     * 
     * @return string var_dump representation of the output
     */
    public function __toString()
    {
        ob_start();
        var_dump($this);
        return ob_get_clean();
    }

}