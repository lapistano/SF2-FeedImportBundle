<?php
/**
 *
 * @package FeedImportBundle
 * @subpackage Interfaces
 */
namespace Bundle\FeedImportBundle\Importer;

/**
 *
 * @package FeedImportBundle
 * @subpackage Interfaces
 */
interface ImporterInterface
{
    /**
     * Constructor of the class to initialize the object
     *
     * @param array $params
     * @param \Bundle\JackalopeBundle\Loader $jackalope
     */
    public function __construct($params, $jackalope);

    /**
     * Extracts the data to store onto the storage layer.
     *
     * @throws \RuntimeException
     */
    public function processXml();

    /**
     * Provides an instance of the \XMLReader.
     *
     * Usually the instance is cached in a class attribute.
     *
     * @param boolean $force Forces the method to return a new instance. A cached one will be lost then.
     * @return \XMLReader
     */
    public function getXMLReader($force = false);
}
