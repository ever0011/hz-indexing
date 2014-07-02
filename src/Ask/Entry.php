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
 * Ask API Result entry class
 * 
 * @author Thijs Vogels <t.vogels@me.com>
 */
class Entry
{

    /**
     * Data for this entry (stored key-value)
     */
    private $data;

    /**
     * Types per property (stored key-value)
     */
    private $propertyTypes;

    /**
     * Constructor
     * 
     * @param array $propertyTypes associative array with propertys 
     *        as keys and their types as values
     * @param \StdClass $entry entry for this particular result
     */
    public function __construct($propertyTypes, array $entry)
    {
        $this->propertyTypes = $propertyTypes;

        $this->data['name'] = $entry['fulltext'];
        $this->data['url'] = $entry['fullurl'];

        foreach ($entry['printouts'] as $key => $value) {
            $property = Output::processLabelName($key);
            $this->data[$property] = $value;
        }
    }

    /**
     * Check if this entry has a certain property
     * 
     * @param string $property property name (lowercase with underscores)
     * @return bool Does the entry have the given property?
     */
    public function hasProperty($property)
    {
        return array_key_exists($property, $this->data);
    }

    /**
     * Return an array of URLS for a property
     * 
     * @param string $property property name (lowercase with underscores)
     * 
     * @return array Returns an array of urls
     * 
     * @throws \Exception
     */
    public function urls($property)
    {
        if (!$this->hasProperty($property)) {
            throw new \Exception(sprintf("Property %s does not exist", $property));
        }

        $data = $this->data[$property];

        switch ($this->propertyTypes[$property]) {
            case Output::URL_TYPE:
                return array_map(function ($a) { return $a['fullurl']; }, $data);

            case Output::LINK_TYPE:
                return array_map(function ($a) { return $a; }, $data);
            
            default:
                throw new \Exception(sprintf("Property %s does not have URL's", $property));
        }
    }

    /**
     * Return an array of values for a property
     * 
     * @param string $property property name (lowercase with underscores)
     * 
     * @return array Returns an array of urls
     * 
     * @throws \Exception
     */
    public function values($property)
    {
        if (!$this->hasProperty($property)) {
            throw new \Exception(sprintf("Property %s does not exist", $property));
        }

        $data = $this->data[$property];

        switch ($this->propertyTypes[$property]) {
            case Output::URL_TYPE:
                return array_map(function ($a) { return $a['fulltext']; }, $data);
            case Output::TXT_TYPE:
                return $data;
            case Output::NUM_TYPE:
                return $data;
            case Output::DATE_TYPE:
                return array_map(function ($a) { return gmdate('d-m-Y', $a); }, $data);
            case Output::LINK_TYPE:
                return $data;
            default:
                throw new \Exception(sprintf(
                    "Property type '%s' is not supported", 
                    $this->propertyTypes[$property]
                ));
        }
    }

    /**
     * Return a comma separated list of URLS for a property
     * 
     * @param string $property property name (lowercase with underscores)
     * 
     * @return string Returns a comma separated list of urls
     * 
     * @throws \Exception
     */
    public function urls_cs($property)
    {
        return implode(", ", $this->urls($property));
    }

    /**
     * Return a comma separated list of values for a property
     * 
     * @param string $property property name (lowercase with underscores)
     * 
     * @return string Returns a comma separated list of values
     * 
     * @throws \Exception
     */
    public function values_cs($property)
    {
        return implode(", ", $this->values($property));
    }

    /**
     * Return the name of the entry
     * 
     * @return string name
     */
    public function getName()
    {
        return $this->data['name'];
    }

    /**
     * Return the URL of the entry
     * 
     * @return string URL
     */
    public function getUrl()
    {
        return $this->data['url'];
    }

    /**
     * Converts the class to a string
     * 
     * @return string String representation of the class
     */
    public function __toString()
    {
        return $this->data['name'];
    }

}
