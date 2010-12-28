<?php
/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
namespace Bundle\FeedImportBundle\Tests\Unittest\Importer;

/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
class ImporterBaseTest extends \PHPUnit_Framework_TestCase
{
    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/

    /**
     * Provides a proxy instance of the importer under test.
     *
     * @return \Bundle\FeedImportBundle\Importer\ImporterBase
     */
    public function getImporterBaseProxy($params = array())
    {
        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = new \Bundle\FeedImportBundle\Tests\Unittest\Importer\LoggerDummy;
        $base = new ImporterBaseProxy($params, $jackalope, $logger);
        return $base;
    }

    /**
     * Forward the current reader the amount of times given.
     *
     * @param \XMLReader $reader
     * @param intger $times
     */
    public function forwardPointer($reader, $times)
    {
        for($i=0; $times >= $i; ++$i) {
            $reader->read();
        }
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::getConfiguration
     * @dataProvider getConfigurationDataProvider
     */
    public function testGetConfiguration($section, $expected)
    {
        $base = $this->getImporterBaseProxy(
            array(
                'http' => array(
                    'server_vars' => array(
                        'content_type' => 'text\/xml; charset=utf-8'
                    )
                )
            )
        );
        $this->assertEquals($expected, $base->getConfiguration($section));
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::getConfiguration
     * @expectedException \InvalidArgumentException
     */
    public function testGetConfigurationExpectingInvalidArguments()
    {
        $section = 'invalidSectionName';
        $base = $this->getImporterBaseProxy(
            array(
                'http' => array(),
            )
        );
        $base->getConfiguration($section);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::__construct
     */
    public function testConstruct()
    {
        $params['xml']['schema_file'] = 'filename';
        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->getMock();

        $base = $this->getMockForAbstractClass(
            '\Bundle\FeedImportBundle\Importer\ImporterBase',
            array($params, $jackalope)
        );

        $this->assertAttributeEquals($params, 'params', $base);
        $this->assertAttributeInstanceOf('\Bundle\JackalopeBundle\Loader', 'jackalope', $base);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::getXMLReader
     */
    public function testGetXMLReader()
    {
        $base = $this->getImporterBaseProxy();
        $this->assertInstanceOf('\XMLReader', $base->getXMLReader());
        $this->assertAttributeInstanceOf('\XMLReader', 'xmlreader', $base);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::getXMLReader
     */
    public function testGetXMLReaderFromCache()
    {
        $xmlreader = new \XMLReader;
        $base = $this->getImporterBaseProxy();
        $base->setXMLReader($xmlreader);
        $this->assertInstanceOf('\XMLReader', $base->getXMLReader());
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::setXMLReader
     */
    public function testSetXMLReader()
    {
        $reader = new \XMLReader ;
        $base = $this->getImporterBaseProxy();
        $base->setXMLReader($reader);

        $this->assertAttributeInstanceOf('\XMLReader', 'xmlreader', $base);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::processXml
     */
    public function testProcessXml()
    {
        $reader = $this->getMockBuilder('\XMLReader')
            ->setMethods(array('open', 'close'))
            ->getMock();
        $reader
            ->expects($this->once())
            ->method('open')
            ->will($this->returnValue(true));
        $reader
            ->expects($this->once())
            ->method('close')
            ->will($this->returnValue(true));

        $base = $this->getImporterBaseProxy();
        $base->setXMLReader($reader);

        $this->assertNull($base->processXml());
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::processXml
     */
    public function testProcessXmlExpectingRuntimeException()
    {
        $reader = $this->getMockBuilder('\XMLReader')
            ->setMethods(array('open', 'close'))
            ->getMock();
        $reader
            ->expects($this->once())
            ->method('open')
            ->will($this->returnValue(false));
        $reader
            ->expects($this->once())
            ->method('close')
            ->will($this->returnValue(true));

        $base = $this->getImporterBaseProxy();
        $base->setXMLReader($reader);

        try {
            $base->processXml();
            $this->fail('Expected exception (\RuntimeException) not thrown.');
        } catch (\RuntimeException $rte){}
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::validateSchema
     */
    public function testValidateSchema()
    {
        $params['xml']['schema_file'] = 'file';
        $reader = $this->getMockBuilder('\XMLReader')
            ->setMethods(array('setSchema'))
            ->getMock();
        $reader
            ->expects($this->once())
            ->method('setSchema')
            ->will($this->returnValue(true));
        $base = $this->getImporterBaseProxy($params);
        $this->assertNull($base->validateSchema($reader));
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::validateSchema
     */
    public function testValidateSchemaExpectingRuntimeException()
    {
        $params['xml']['schema_file'] = 'file';
        $reader = $this->getMockBuilder('\XMLReader')
            ->setMethods(array('setSchema'))
            ->getMock();
        $reader
            ->expects($this->once())
            ->method('setSchema')
            ->will($this->returnValue(false));
        $base = $this->getImporterBaseProxy($params);

        try {
            $base->validateSchema($reader);
            $this->fail('Expected exception (\RuntimeException) not thrown.');
        } catch (\RuntimeException $rte) {}
    }

    /**
     * @dataProvider xml2AssocReadMetadDataDataprovider
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::xml2assoc
     */
    public function testXml2AssocReadMetaData($expected, $forward, $tagName)
    {
        $reader = new \XMLReader;
        $reader->open(__DIR__.'/../Fixtures/FazBookFeed.rss');

        // forward pointer of the XMLReader to the section interesting for this test.
        $this->forwardPointer($reader, $forward);

        $base = $this->getImporterBaseProxy();
        $this->assertEquals($expected, $base->xml2assoc($reader, $tagName));

        unset($reader);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::xml2assoc
     */
    public function testXml2AssocFullRun()
    {
        $reader = new \XMLReader;
        $reader->open(__DIR__.'/../Fixtures/xml2assoc.xml');

        $expected = array(
            array(
                'tag' => 'operation-systems',
                'children' => array(
                    array(
                        'tag' => 'linux',
                        'children' => array(
                            array(
                                'tag' => 'ubuntu',
                                'attr' => array(
                                    'type' => 'distribution'
                                ),
                                'children'=> array(
                                    array('text' => 'Ubuntu is an ancient African word meaning \'humanity to others\'...')
                                )
                            )
                        )
                    )
                )
            )
        );

        $base = $this->getImporterBaseProxy();
        $this->assertEquals($expected, $base->xml2assoc($reader, 'operation-systems'));

        unset($reader);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::xml2assoc
     */
    public function testXml2AssocReadItem()
    {
        $expected = array(
            array(
                'tag' => 'item',
                'children' => array(
                    array(
                        'tag' => 'title',
                        'children' => array(
                            array(
                                'text' => "Paolo Grossi: Das Recht in der europäischen\n".
                                          "                Geschichte: Gegen die Verrücktheiten der Mächtigen"
                            )
                        )
                    ),
                    array(
                        'tag' => 'link',
                        'children' => array(
                            array(
                                'text' => 'http://www.faz.net/s/RubC17179D529AB4E2BBEDB095D7C41F468/'.
                                          'Doc~EB4ABF14C88404713A04893339EEA3DED~ATpl~Ecommon~Scontent.html'
                            )
                        )
                    ),
                    array(
                        'tag' => 'guid',
                        'attr' => array('isPermaLink' => 'true'),
                        'children' => array(
                            array(
                                'text' => 'http://www.faz.net/s/RubC17179D529AB4E2BBEDB095D7C41F468/'.
                                          'Doc~EB4ABF14C88404713A04893339EEA3DED~ATpl~Ecommon~Scontent.html'
                            )
                        )
                    ),
                    array(
                        'tag' => 'pubDate',
                        'children' => array(
                            array(
                                'text' => 'Wed, 08 Dec 2010 16:42:43 +0100'
                            )
                        )
                    ),
                    array(
                        'tag' => 'description',
                        'children' => array(
                            array(
                                'text' => '<div style="clear:left;"><img'."\n".
                                          '                style="float:left;padding-right:5px;"'."\n".
                                          '                src="http://www.faz.net/m/{BE81E114-E88E-4690-AA72-AB948F836341}File2.jpg"'."\n".
                                          '                width="111" height="178" border="0px" />Es gibt eine'."\n".
                                          '                europäische Leitkultur, und das Recht ist nicht die'."\n".
                                          '                schwächste Kraft, die diese Annahme mit Leben füllt: Das'."\n".
                                          '                neue Buch des renommierten Rechtshistorikers Paolo'."\n".
                                          '                Grossi beschreibt die Jahrhunderte währende Ausbildung'."\n".
                                          '                eines juristischen Humanismus.<div style="margin: 5px'."\n".
                                          '                0 5px 0; border-top:1px solid #7A89CC; font: 10px arial;'."\n".
                                          '                color: #7A89CC; clear: both;"> <a'."\n".
                                          '                href="http://www.faz.net/s/homepage.html"'."\n".
                                          '                style="font-size: 10px; color: #7A89CC; text-decoration:'."\n".
                                          '                none;" target="_blank">FAZ.NET - Homepage</a>'."\n".
                                          '                <a href="http://www.faz.net/politik"'."\n".
                                          '                style="font-size: 10px; color: #7A89CC; text-decoration:'."\n".
                                          '                none;" target="_blank"> | Politik</a> <a'."\n".
                                          '                href="http://www.faz.net/gesellschaft" style="font-size:'."\n".
                                          '                10px; color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Gesellschaft</a> <a'."\n".
                                          '                href="http://www.faz.net/wirtschaft" style="font-size:'."\n".
                                          '                10px; color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Wirtschaft</a> <a'."\n".
                                          '                href="http://www.faz.net/finanzmarkt" style="font-size:'."\n".
                                          '                10px; color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Finanzmarkt</a> <a'."\n".
                                          '                href="http://www.faz.net/sport" style="font-size: 10px;'."\n".
                                          '                color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Sport</a> <a'."\n".
                                          '                href="http://www.faz.net/feuilleton" style="font-size:'."\n".
                                          '                10px; color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Feuilleton</a> <a'."\n".
                                          '                href="http://www.faz.net/reise" style="font-size: 10px;'."\n".
                                          '                color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Reise</a> <a'."\n".
                                          '                href="http://www.faz.net/wissen" style="font-size: 10px;'."\n".
                                          '                color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Wissen</a> <a'."\n".
                                          '                href="http://www.faz.net/auto" style="font-size: 10px;'."\n".
                                          '                color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Auto</a> <a'."\n".
                                          '                href="http://www.faz.net/computer" style="font-size:'."\n".
                                          '                10px; color: #7A89CC; text-decoration: none;"'."\n".
                                          '                target="_blank"> | Computer</a> </div>'."\n".
                                          '                <img src="http://stat.faz.net/rss/stat.php?zi=80299"'."\n".
                                          '                width=1 height=1></div>'
                            )
                        )
                    )
                )
            )
        );

        $reader = new \XMLReader;
        $reader->open(__DIR__.'/../Fixtures/FazBookFeed.rss');

        // forward pointer of the XMLReader to the section interesting for this test.
        $this->forwardPointer($reader, 55);

        $base = $this->getImporterBaseProxy();
        $this->assertEquals($expected, $base->xml2assoc($reader, 'item'));

        unset($reader);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::log
     */
    public function testLogDefaultPriority()
    {
        $base = $this->getImporterBaseProxy(array());
        $this->assertNull($base->log('Beastie'));
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::log
     */
    public function testLogSetPriority()
    {
        $base = $this->getImporterBaseProxy(array());
        $this->assertNull($base->log('Beastie', 'info'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @covers \Bundle\FeedImportBundle\Importer\ImporterBase::log
     */
    public function testLogExpectingInvalidArgumentException()
    {
        $base = $this->getImporterBaseProxy(array());
        $base->log('Beastie', 'invalid priority');
    }

    /*************************************************************************/
    /* Dataprovider / callback functions
    /*************************************************************************/

    public static function getConfigurationDataProvider()
    {
        return array(
            'ValidSection' => array('server_vars', array('content_type' => 'text\/xml; charset=utf-8')),
            'EmptySection' => array('', array('server_vars' => array('content_type' => 'text\/xml; charset=utf-8'))),
        );
    }

    public static function xml2AssocReadMetadDataDataprovider()
    {
        return array(
            'Title' => array(
                array(
                    array(
                        'tag' => 'title',
                        'children' => array(
                            array('text' => 'Bücher - FAZ.NET'),
                        )
                    )
                ),
                3,
                'title'
            ),
            'Link' => array(
                array(
                    array(
                        'tag' => 'link',
                        'children' => array(
                            array('text' => 'http://www.faz.net')
                        )
                    )
                ),
                7,
                'link'
            ),
            'Image' => array(
                array(
                    array(
                        'tag' => 'image',
                        'children' => array(
                            array(
                                'tag' => 'title',
                                'children' => array(
                                    array('text' => 'FAZ.NET')
                                )
                            ),
                            array(
                                'tag' => 'url',
                                'children' => array(
                                    array('text' => 'http://www.faz.net/IN/INtemplates/faznet/palmversion/Faz_Logo_klein.gif')
                                )
                            ),
                            array(
                                'tag' => 'link',
                                'children' => array(
                                    array('text' => 'http://www.faz.net')
                                )
                            ),
                        )
                    )
                ),
                39,
                'image'
            ),
        );
    }
}

/**
 * Dummy implementation of the abstract importer base class.
 *
 * For testing public methods of the abstract class use
 *     {@link \PHPUnit_Framework_TestCase::getMockForAbstractClass()}
 *
 */
class ImporterBaseProxy extends \Bundle\FeedImportBundle\Importer\ImporterBase
{

    protected function processContent(\XMLReader $reader)
    {
        return null;
    }

    public function getXMLReader($force = false)
    {
        return parent::getXMLReader($force);
    }

    public function xml2assoc(\XMLReader $xml, $name)
    {
        return parent::xml2assoc($xml, $name);
    }

    public function validateSchema(\XMLReader $reader)
    {
        return parent::validateSchema($reader);
    }

}

class LoggerDummy implements \Symfony\Component\HttpKernel\Log\LoggerInterface
{
    protected $priorities = array(
        'log',
        'debug',
        'info',
        'notice',
        'emerg',
        'alert',
        'error',
        'crit',
        'warn',
    );

    public function log($message, $priority)
    {
        if (!in_array($priority, $this->priorities)) {
            throw new \InvalidArgumentException('Bad log priority ('.$priority.')');
        }
    }

    public function emerg($message)
    {
    }

    public function alert($message)
    {
    }

    public function crit($message)
    {
    }

    public function err($message)
    {
    }

    public function warn($message)
    {
    }

    public function notice($message)
    {
    }

    public function info($message)
    {
    }

    public function debug($message)
    {
    }
}