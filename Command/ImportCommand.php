<?php
/**
 *
 * @package FeedImportBundle
 * @subpackage Command
 */
namespace Bundle\FeedImportBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 *
 * Sypnosis:
 *
 *  $> app/console service feed
 *
 * @package FeedImportBundle
 * @subpackage Command
 */
class FeedImportCommand extends \Symfony\Bundle\FrameworkBundle\Command\Command
{
    /**
     * @see \Symfony\Bundle\FrameworkBundle\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('nzz:importer')
            ->setDescription('Import data from a feed.')
            ->setDefinition(array(
                new InputArgument('service', InputArgument::REQUIRED, 'The name of the service to be used to import the retrieved data.'),
                new InputArgument('feed', InputArgument::REQUIRED, 'The feed to be read.'),
            ))
            ->setHelp(<<<EOT
To find the available 'services' consult app/config/config.yml.
Every service prefixed with 'import.importer.' is a candidate for being used as a the value of the 'service' parameter.

E.g.:
    import.importer.faz.books  to call this service use 'faz.books' as the parameter value.
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $feed = $input->getArgument('feed');

        $output->write("\nReading feed ($feed)… ");
        // load importer
        $importer = $this->container->get('import.importer.'.$input->getArgument('service'));
        // fetch data from URL
        $response = $this->fetchFeed($feed);
        $output->write( "done!", true);

        $contentHeader = $this->readContentType($response['header']);

        $output->write( "Importing… ");
        $reader = $importer->getXMLReader();
        if (!$reader->xml($response['content'], $contentHeader['encoding'], LIBXML_COMPACT)) {
            throw new \RuntimeException('Unable to read the feed data.');
        }
        $importer->processContent($reader);
        $output->write( "done!", true);

    }

    /*************************************************************************/
    /* Helpers
    /*************************************************************************/

    /**
     * fetches the data form the given feed url.
     *
     * @param string $url
     * @throws \RuntimeException in case of any other thrown exception.
     */
    protected function fetchFeed($url)
    {
        $options = array( 'http' => array(
            'user_agent'    => 'NZZ - Feed Reader',           // who am i
            'max_redirects' => 10,                            // stop after 10 redirects
            'timeout'       => 120,                           // timeout on response
            'http' => array('header'=>'Connection: close'),   // prevent script from waiting on apache timeout.
            )
        );
        $context = stream_context_create($options);

        try {
            $feed['content'] = file_get_contents( $url, null, $context );
            $feed['header'] = isset($http_response_header) ? $http_response_header : array();
        } catch (\Exception $ee)  {
            throw new \RuntimeException($ee->getMessage(), $ee->getCode(), $ee->getPrevious());
        }

        return $feed;
    }

    /**
     * Extracts the content-* info from the given HTTP header information.
     *
     * @param string $header
     * @return string
     */
    protected function readContentType($header)
    {
        $contentInfo = array('type' => 'text/xml', 'encoding' => null, 'length' => 0 );

        if (isset($header[3])) {
            $pattern = "(^Content-Type: ([^;]+); charset=(.*)$)";
            preg_match($pattern, $header[3], $matches);
            $contentInfo['type'] = $matches[1];
            $contentInfo['encoding'] = strtolower($matches[2]);
        }

        if (isset($header[2])) {
            $pattern = "(^Content-Length: (\d*)$)";
            preg_match($pattern, $header[2], $matches);
            $contentInfo['length'] = $matches[1];
        }

        return $contentInfo;
    }

    /**
     * Normalizes the name given service.
     *
     * @param string $serviceName
     * @return string
     */
    public function normalizeService($serviceName)
    {
        return str_replace('.', '', $serviceName);
    }
}
