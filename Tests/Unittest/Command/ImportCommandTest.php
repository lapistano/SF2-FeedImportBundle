<?php
/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
namespace Bundle\FeedImportBundle\Tests\Unittest\Command;

/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
class ImportCommandTest extends \PHPUnit_Framework_TestCase
{

    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/

    /**
     * Provides a mock object of the XMLReader
     * Enter description here ...
     * @param unknown_type $return
     */
    public function getXmlReaderMockFixture($return)
    {
        $xmlReader = $this->getMockBuilder('\XMLReader')
            ->setMethods(array('xml'))
            ->getMock();
        $xmlReader
            ->expects($this->any())
            ->method('xml')
            ->will($this->returnValue($return));
        return $xmlReader;
    }

    /**
     * Provides a mock object to simulate an Importer
     *
     * @param array $methods
     * @return \stdClass
     */
    public function getImporterMockFixture($xmlReaderReturnValue, array $methods = array())
    {
        $methods = array_merge(array('getXMLReader'), $methods);
        $importer = $this->getMockBuilder('\stdClass')
            ->setMethods($methods)
            ->getMock();
        $importer
            ->expects($this->any())
            ->method('getXMLReader')
            ->will($this->returnValue($this->getXmlReaderMockFixture($xmlReaderReturnValue)));
        return $importer;
    }

    /**
     * Provides a mock object simulating the DIC of SF2;
     *
     * @param object $importer
     * @return \stdClass
     */
    public function getContainerMockFixture($importer)
    {
        $container = $this->getMockBuilder('\stdClass')
            ->setMethods(array('get'))
            ->getMock();
        $container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue($importer));
        return $container;
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Bundle\FeedImportBundle\Command\ImportCommand::configure
     */
    public function testConfigure()
    {
        $comand = new \Bundle\FeedImportBundle\Command\ImportCommand();
        $this->assertAttributeEquals('importer', 'name', $comand);
        $this->assertAttributeEquals('nzz', 'namespace', $comand);
        $this->assertAttributeEquals('Import data from a feed.', 'description', $comand);
        $this->assertAttributeNotEmpty('help', $comand);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Command\ImportCommand::execute
     */
    public function testExecute()
    {
        $importer = $this->getImporterMockFixture(true, array('processContent'));
        $importer
            ->expects($this->any())
            ->method('processContent');

        $input = $this->getMockBuilder('\Bundle\FeedImportBundle\Tests\Unittest\Command\InputDummy')
            ->setMethods(array('getArgument'))
            ->getMock();
        $input
            ->expects($this->atLeastOnce())
            ->method('getArgument')
            ->will($this->returnCallback(array($this, 'executeGetArgumentCallback')));

        $output = new OutputDummy();

        $command = new ImportCommandProxy();
        $command->container = $this->getContainerMockFixture($importer);
        $this->assertNull($command->execute($input, $output));

    }

    /**
     * @expectedException \RuntimeException
     * @covers \Bundle\FeedImportBundle\Command\ImportCommand::execute
     */
    public function testExecuteExpectingRuntimeException()
    {
        $input = $this->getMockBuilder('\Bundle\FeedImportBundle\Tests\Unittest\Command\InputDummy')
            ->setMethods(array('getArgument'))
            ->getMock();
        $input
            ->expects($this->atLeastOnce())
            ->method('getArgument')
            ->will($this->returnCallback(array($this, 'executeGetArgumentCallback')));

        $output = new OutputDummy();

        $command = new ImportCommandProxy();
        $command->container = $this->getContainerMockFixture($this->getImporterMockFixture(false));
        $command->execute($input, $output);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Command\ImportCommand::fetchFeed
     */
    public function testFetchFeed()
    {
        $url = __DIR__.'/../Fixtures/FazBookFeed.rss';

        $command = new ImportCommandProxy();
        $feed = $command->fetchFeed($url);

        $this->assertXmlStringEqualsXmlFile($url, $feed['content']);
        $this->assertEmpty($feed['header']);
    }

    /**
     * @expectedException \RuntimeException
     * @covers \Bundle\FeedImportBundle\Command\ImportCommand::fetchFeed
     */
    public function testFetchFeedExpectingRuntimeException()
    {
        $command = new ImportCommandProxy();
        $feed = $command->fetchFeed('invalid Url');
    }

    /**
     * @dataProvider readContentTypeDataprovider
     * @covers \Bundle\FeedImportBundle\Command\ImportCommand::readContentType
     */
    public function testReadContentType($expected, $header)
    {
        $command = new ImportCommandProxy();
        $this->assertEquals($expected, $command->readContentType($header));
    }

    /**
     * @covers \Bundle\FeedImportBundle\Command\ImportCommand::normalizeService
     */
    public function testNormalizeService()
    {
        $command = new ImportCommandProxy();
        $this->assertEquals('fazbooks', $command->normalizeService('faz.books'));
    }

    /*************************************************************************/
    /* Dataprovider & Callbacks
    /*************************************************************************/

    public function readContentTypeDataprovider()
    {
        return array(
            'empty header' => array(
                array('type' => 'text/xml', 'encoding' => null, 'length' => 0 ),
                ''
             ),
            'header without Content-Length' => array(
                array('type' => 'text/xml', 'encoding' => 'utf-8', 'length' => 0 ),
                array(3 => "Content-Type: text/xml; charset=UTF-8")
             ),
            'header without Content-Type' => array(
                array('type' => 'text/xml', 'encoding' => null, 'length' => 40531 ),
                array(2 => "Content-Length: 40531")
             ),
             'full header' => array(
                array('type' => 'text/xml', 'encoding' => 'utf-8', 'length' => 40531 ),
                array(2 => "Content-Length: 40531", 3 => "Content-Type: text/xml; charset=UTF-8")
             ),
        );
    }

    public function executeGetArgumentCallback($arg)
    {
        switch ($arg) {
            case 'feed'   : return __DIR__.'/../Fixtures/FazBookFeed.rss';
            case 'service': return 'faz.book';
        }

        $command = new ImportCommandProxy();
    }
}

class ImportCommandProxy extends \Bundle\FeedImportBundle\Command\ImportCommand
{
    public $container;

    public function execute(\Symfony\Component\Console\Input\InputInterface $input,
                   \Symfony\Component\Console\Output\OutputInterface $output)
    {
        return parent::execute($input, $output);
    }

    public function fetchFeed($url)
    {
        return parent::fetchFeed($url);
    }

    public function readContentType($header)
    {
        return parent::readContentType($header);
    }
}

class OutputDummy implements \Symfony\Component\Console\Output\OutputInterface
{
    public function write($messages, $newline = false, $type = 0)
    {
    }

    public function setVerbosity($level)
    {
    }

    public function setDecorated($decorated)
    {
    }
}

class InputDummy implements \Symfony\Component\Console\Input\InputInterface
{
    public function getFirstArgument()
    {
    }

    public function hasParameterOption($value)
    {
    }

    public function bind( \Symfony\Component\Console\Input\InputDefinition $definition)
    {
    }

    public function validate()
    {
    }

    public function getArguments()
    {
    }

    public function getArgument($name)
    {
    }

    public function getOptions()
    {
    }

    public function getOption($name)
    {
    }
}