<?php
/**
 *
 * @package FeedImportBundle
 * @subpackage Importer
 */
namespace Bundle\FeedImportBundle\Importer;

/**
 *
 * @package FeedImportBundle
 * @subpackage Importer
 */
abstract class ImporterBase implements ImporterInterface
{
    /**
     * Instance of the jackalope symfony service
     * @var \Bundle\JackalopeBundle\Loader
     */
    protected $jackalope = null;

    /**
     * Set of parameters necessary to process the data.
     * @var array
     */
    protected $params = array();

    /**
     * Instance of the PHP standard XMLReader.
     * @var \XMLReader
     */
    protected $xmlreader = null;

    /**
     * @var Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    protected $logger = null;

    /**
     * List of available log priorities.
     * @var array
     */
    protected $logPriorities = array(
        'debug',
        'info',
        'notice',
        'emerg',
        'alert',
        'error',
        'crit',
        'warn',
    );

    /*************************************************************************/
    /* abstract defintions
    /*************************************************************************/

    /**
     * Extracts meta data from xml and stores it to the storage layer.
     *
     * @params \XMLReader $reader Instance of the XMLReader.
     * @throws \RuntimeException In case of any error regarding parsing or openening the xml stream.
     *                           An additionam message shall describe the type of error.
     * @return void
     * @see \Bundle\JackalopeBundle\Loader
     */
    abstract protected function processContent(\XMLReader $reader);

    /*************************************************************************/
    /* Interface implementation
    /*************************************************************************/

    /**
     * Initializes the object to be created.
     *
     * @param array $params
     * @param \Bundle\JackalopeBundle\Loader $jackalope
     */
    public function __construct($params, $jackalope, $logger = null)
    {
        $this->params = $params;
        $this->jackalope = $jackalope;
        $this->logger = $logger;
    }

    /**
     * Verifies the schema and DTD information to process the content.
     *
     * @throws \RuntimeException If the stream could not be opened.
     * @throws \RuntimeException in case the provided schema does not match.
     */
    public function processXml()
    {
        /**
         * Enable the usage of the internal errors of the libxml.
         * @link http://ch2.php.net/manual/en/function.libxml-use-internal-errors.php
         */
        libxml_use_internal_errors(TRUE);

        $content = '';
        $reader = $this->getXMLReader();
        $this->validateSchema($reader);

        // read the xml stream
        // FIXME: make the DTD validation configurable
        //        if(!$reader->open('php://input', null, LIBXML_COMPACT & LIBXML_DTDVALID)) {
        if (!$reader->open('php://input', null, LIBXML_COMPACT)) {
            $reader->close();
            throw new \RuntimeException('Unable to open input stream.');
        }

        $content = $this->processContent($reader);
        $reader->close();
        return $content;
    }

    /*************************************************************************/
    /* Helper
    /*************************************************************************/

    /**
     * Writes a message of the given level to the logfile.
     *
     * Current available log levels (sorted by priority):
     * - debug
     * - info
     * - notice
     * - emerg
     * - alert
     * - error
     * - crit
     * - warn
     *
     * @param string $message  Message to be logged
     * @param string $level    The log level to be used.
     *
     * @throws \InvalidArgumentException in case of passing an unknown priority.
     * @see Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    public function log($message, $level = 'debug')
    {
        if (!in_array($level, $this->logPriorities)) {
            throw new \InvalidArgumentException('Unknown log priority ('.$level.')');
        }
        if ($this->logger instanceof \Symfony\Component\HttpKernel\Log\LoggerInterface) {
            $this->logger->$level($message);
        }
    }

    /**
     * Validates the xml stream against a probably existing schema.
     *
     * @params \XMLReader $reader Instance of the XMLReader.
     * @throws \RuntimeException in case the provided schema does not match.
     */
    protected function validateSchema(\XMLReader $reader)
    {
        if (isset($this->params['xml']['schema_file'])) {
            if (!$reader->setSchema($this->params['xml']['schema_file'])) {
                throw new \RuntimeException('Provided XML data does not fit the schema.');
            }
        }
    }

    /**
     * Crawler generating an associative array representing the
     *
     * @param \XMLReader $xml Stream initiated by the XMLReader
     * @param string $name Name of the Element to start the parsing from.
     */
    protected function xml2assoc(\XMLReader $xml, $name)
    {
        static $depth = 0;
        static $stopPropagate = false;
        $tree = null;

        while($xml->read()) {
            // if corresponding end tag to the named tag was found .. stop.
            if ($depth === 0 && $stopPropagate && $xml->name != $name ) {
                return $tree;
            }

            if ($xml->nodeType == $xml::END_ELEMENT) {
                --$depth;
                $stopPropagate = true;
                return $tree;
            } elseif ($xml->nodeType == $xml::ELEMENT) {
                ++$depth;
                $node = array();
                $node['tag'] = $xml->name;

                if($xml->hasAttributes) {
                    $attributes = array();
                    while($xml->moveToNextAttribute()) {
                        $attributes[$xml->name] = $xml->value;
                    }
                    $node['attr'] = $attributes;
                }

                if(!$xml->isEmptyElement) {
                    $node['children'] = $this->xml2assoc($xml, $node['tag']);
                }
                $tree[] = $node;
            } elseif ($xml->nodeType == $xml::TEXT) {
                $node = array();
                $node['text'] = trim($xml->value);
                $tree[] = $node;
            }
        }
        return $tree;
    }

    /**
     * Get an instance of the PHP standard XMLReader.
     *
     * The instance gets cached once it's instantiated.
     *
     * @return \XMLReader
     */
    public function getXMLReader($force = false)
    {
        if (true === $force || is_null($this->xmlreader)) {
            $this->xmlreader = new \XMLReader();
        }
        return $this->xmlreader;
    }

    /**
     * Sets an instance of the PHP standard XMLReader.
     *
     * @param \XMLReader $xmlreader Instance of XMLReader.
     */
    public function setXMLReader(\XMLReader $xmlreader)
    {
        $this->xmlreader = $xmlreader;
    }

    /**
     * Gets the params from the config.
     *
     * @param string $section Name of the section.
     * @return string|array Content of the requested configuration.
     */
    public function getConfiguration($section = '')
    {
        if (!empty($section)) {
            if (!isset($this->params['http'][$section])) {
                throw new \InvalidArgumentException("Unknown section ($section).");
            }
            return $this->params['http'][$section];
        }
        return $this->params['http'];
    }
}
