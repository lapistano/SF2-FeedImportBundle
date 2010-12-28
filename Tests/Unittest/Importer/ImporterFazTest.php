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
class ImporterFazTest extends \PHPUnit_Framework_TestCase
{
    /*************************************************************************/
    /* Helpers
    /*************************************************************************/

    /**
     * Forwards the pointer of the reader the given times.
     *
     * @param \XMLReader $reader
     * @param integer $times
     *
     * @return \XMLReader
     */
    public function fastForward(\XMLReader $reader, $times)
    {
        for($i=1; $i <= $times; ++$i) {
            $reader->read();
        }
        return $reader;
    }

    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/

    /**
     * provide a fixture of the importer proxy object.
     *
     * @return Bundle\FeedImportBundle\Importer\ImporterFazBooksProxy
     */
    public function getImporterProxyFixture($params = array(), $jackalope = null)
    {
        if (is_null($jackalope)) {
            $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
                ->disableOriginalConstructor()
                ->getMock();
        }
        return new ImporterFazBooksProxy($params, $jackalope);
    }

    /**
     * Provide a fixture of the article object.
     * @param array $methods
     */
    public function getArticleFixture(array $methods = array())
    {
        return $this->getMockBuilder('\Bundle\FeedImportBundle\Component\Article')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Provides an instance of the jackalope session class.
     *
     * @param array $methods
     *
     * @return \Jackalope\Session
     */
    public function getJackalopeSessionFixture(array $methods = array())
    {
         return $this->getMockBuilder('\Jackalope\Session')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Provides an instance of the jackalope node class.
     *
     * @param array $methods
     * @return \Jackalope\Node
     */
    public function getJackalopeNodeFixture(array $methods = array())
    {
        return $this->getMockBuilder('\Jackalope\Node')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Provides a espacially prepared fixture of a jackalope node
     *
     * Special Fixture for writeToJackalope().
     *
     * @return \Jackalope\Node
     */
    public function getJackalopeNodeFixtureForWriteToJackalope()
    {
        $jackalopeNode = $this->getJackalopeNodeFixture(array('setProperty', 'addNode'));
        $jackalopeNode
            ->expects($this->atLeastOnce())
            ->method('setProperty');
        $jackalopeNode
            ->expects($this->atLeastOnce())
            ->method('addNode')
            ->will($this->returnValue($jackalopeNode));
        return $jackalopeNode;
    }

    /**
     * Provides a espacially prepared fixture of an article
     *
     * Special Fixture for writeToJackalope().
     *
     * @param array $params
     * @return \Bundle\FeedImportBundle\Component\Article
     */
    public function getArticleFixtureForWriteToJackalope($params)
    {
        $articleData = array(
            array(
                'tag' => 'item',
                'children' => array(
                    array(
                        'tag' => 'title',
                        'children' => array( array( 'text' => 'headline' ) )
                    ),
                    array(
                        'tag' => 'link',
                        'children' => array( array( 'text' => 'http://www.faz.net' ) )
                    ),
                    array(
                        'tag' => 'guid',
                        'attr' => array( 'isPermaLink' => 'true' ),
                        'children' => array( array( 'text' => 'http://www.faz.net/s/...' ) )
                    ),
                    array(
                        'tag' => 'pubDate',
                        'children' => array( array( 'text' => 'Wed, 08 Dec 2010 16:42:43 +0100' ) )
                    ),
                    array(
                        'tag' => 'description',
                        'children' => array( array( 'text' => 'article text' ) )
                    ),
                )
            )
        );

        $metaData = array(
            'title' => array(
               array(
                   'tag' => 'title',
                   'children' => array(array('text' => 'Bücher - FAZ.NET'))
               )
            ),
            'link' => array(
               array(
                   'tag' => 'link',
                   'children' => array(array('text' => 'http://www.faz.net'))
               )
            ),
            'description' => array(
               array(
                   'tag' => 'description',
                   'children' => array(array('text' => 'FAZ.NET - Erfrischt den Kopf'))
               )
            ),
            'language' => array(
               array(
                   'tag' => 'language',
                   'children' => array(array('text' => 'de-de'))
               )
            ),
            'copyright' => array(
               array(
                   'tag' => 'copyright',
                   'children' => array(array('text' => 'Copyright 2001-2010 Frankfurter Allgemeine Zeitung'."\n".
                                                 '            GmbH. Alle Rechte vorbehalten.'))
               )
            ),
            'category' => array(
               array(
                   'tag' => 'category',
                   'children' => array(array('text' => 'Bücher - FAZ.NET'))
               )
            ),
            'generator' => array(
               array(
                   'tag' => 'generator',
                   'children' => array(array('text' => 'FAZ.NET'))
               )
            ),
            'ttl' => array(
               array(
                   'tag' => 'ttl',
                   'children' => array(array('text' => '5'))
               )
            ),
            'lastBuildDate' => array(
               array(
                   'tag' => 'lastBuildDate',
                   'children' => array(array('text' => 'Wed, 08 Dec 2010 16:42:43 +0100'))
               )
            ),
            'image' => array(
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
                       )
                   )
               )
            )
        );

        $article = new \Bundle\FeedImportBundle\Component\Article();
        $article->convert($params, $articleData, $metaData);
        return $article;
    }

    /**
     * Provides a espacially prepared fixture of a jackalope session.
     *
     * Special Fixture for writeToJackalope().
     *
     * @param \Jackalope\Node $jackalopeNode
     * @param boolean $nodeExists
     * @return \Jackalope\Session
     */
    public function getJackalopeSessionFixtureForWriteToJackalope($jackalopeNode, $nodeExists = false)
    {
        $jackalopeSession = $this->getJackalopeSessionFixture(array('nodeExists', 'getNode', 'save', 'logout'));
        $jackalopeSession
            ->expects($this->any())
            ->method('nodeExists')
            ->will($this->returnValue($nodeExists));
        $jackalopeSession
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue($jackalopeNode));
         $jackalopeSession
            ->expects($this->any())
            ->method('save');
         $jackalopeSession
            ->expects($this->any())
            ->method('logout');
        return $jackalopeSession;
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @dataProvider getNodeMessageDataprovider
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::getNode
     */
    public function testGetNodeExistingNode($msg)
    {
        $guid = 'Das_Bilderbuch_WUM_und_BUM_und_die_Damen_DING_DONG_Das_gro_e_Tschingderassabum_'.
                '6fc0d9f443747e07f29d01e43d1d21d9';
        $path = '/feeds/faz/books/'.
                'Das_Bilderbuch_WUM_und_BUM_und_die_Damen_DING_DONG_Das_gro_e_Tschingderassabum_'.
                '6fc0d9f443747e07f29d01e43d1d21d9';

        $node = $this->getJackalopeNodeFixture();

        $session = $this->getJackalopeSessionFixture(array('nodeExists', 'getNode'));
        $session
            ->expects($this->once())
            ->method('getNode')
            ->will($this->returnValue($node));
        $session
            ->expects($this->once())
            ->method('nodeExists')
            ->will($this->returnValue(true));

        $importer = $this->getImporterProxyFixture();
        $this->assertInstanceOf(
            '\PHPCR\NodeInterface',
            $importer->getNode($path, $guid, $node, $guid, $session, $msg)
        );
    }

    /**
     * @dataProvider getNodeMessageDataprovider
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::getNode
     */
    public function testGetNodeMissingNode($msg)
    {
        $guid = '/feeds/faz/books/'.
                'Das_Bilderbuch_WUM_und_BUM_und_die_Damen_DING_DONG_Das_gro_e_Tschingderassabum_'.
                '6fc0d9f443747e07f29d01e43d1d21d9';
        $path = '/feeds/faz/books/'.
                'Das_Bilderbuch_WUM_und_BUM_und_die_Damen_DING_DONG_Das_gro_e_Tschingderassabum_'.
                '6fc0d9f443747e07f29d01e43d1d21d9';
        $importer = $this->getImporterProxyFixture();

        $node = $this->getJackalopeNodeFixture(array('addNode'));
        $node
            ->expects($this->once())
            ->method('addNode')
            ->will($this->returnValue($node));

        $session = $this->getJackalopeSessionFixture(array('nodeExists'));

        $this->assertInstanceOf(
            '\PHPCR\NodeInterface',
            $importer->getNode($path, $guid, $node, $guid, $session, $msg)
        );
    }

    /**
     * @dataProvider processContentExpectingRuntimeExceptionDataprovider
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::processContent
     */
    public function testProcessContentExpectingRuntimeException($exception)
    {
        $importer = $this->getImporterProxyFixture();
        $reader = $this->getMockBuilder('\XMLReader')
            ->setMethods(array('read'))
            ->getMock();
        $reader
            ->expects($this->once())
            ->method('read')
            ->will($this->throwException($exception));

        try{
            $importer->processContent($reader);
            $this->fail('Expected exception (\RuntimeException) not thrown!');
        } catch (\RuntimeException $rte) {}
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::processContent
     */
    public function testProcessContent()
    {
        $params = array(
            'jackalope' => array(
                'root_path' => '/feeds/faz/books',
                'article_path' => '/article',
                'meta_path' => '/metaData',
            ),
            'article' => array(
                'publisher' => 'Frankfurter Allgemeine Zeitung GmbH'
            )
        );

        $jackalopeSession = $this->getJackalopeSessionFixtureForWriteToJackalope(
            $this->getJackalopeNodeFixtureForWriteToJackalope()
        );

        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->setMethods(array('getSession'))
            ->getMock();
        $jackalope
            ->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($jackalopeSession));

        $reader = new \XMLReader;
        $reader->open(__DIR__.'/../Fixtures/FazBookFeed.rss');

        $importer = $this->getImporterProxyFixture($params, $jackalope);
        $importer->setArticle($this->getArticleFixtureForWriteToJackalope($params));

        $importer->processContent($reader);
        $metaData = $this->readAttribute($importer, 'metaData');
        $this->assertEquals(10, count($metaData));

        unset($reader);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::readMetaData
     */
    public function testReadMetaData()
    {
        $expectedMetaData = array(
            'title' => array(
                array(
                    'tag' => 'title',
                    'children' => array(array('text' => 'Bücher - FAZ.NET'))
                )
            ),
            'link' => array(
                array(
                    'tag' => 'link',
                    'children' => array(array('text' => 'http://www.faz.net'))
                )
            ),
            'description' => array(
                array(
                    'tag' => 'description',
                    'children' => array(array('text' => 'FAZ.NET - Erfrischt den Kopf'))
                )
            ),
            'language' => array(
                array(
                    'tag' => 'language',
                    'children' => array(array('text' => 'de-de'))
                )
            ),
            'copyright' => array(
                array(
                    'tag' => 'copyright',
                    'children' => array(array('text' => 'Copyright 2001-2010 Frankfurter Allgemeine Zeitung'."\n".
                                                  '            GmbH. Alle Rechte vorbehalten.'))
                )
            ),
            'category' => array(
                array(
                    'tag' => 'category',
                    'children' => array(array('text' => 'Bücher - FAZ.NET'))
                )
            ),
            'generator' => array(
                array(
                    'tag' => 'generator',
                    'children' => array(array('text' => 'FAZ.NET'))
                )
            ),
            'ttl' => array(
                array(
                    'tag' => 'ttl',
                    'children' => array(array('text' => '5'))
                )
            ),
            'lastBuildDate' => array(
                array(
                    'tag' => 'lastBuildDate',
                    'children' => array(array('text' => 'Wed, 08 Dec 2010 16:42:43 +0100'))
                )
            ),
            'image' => array(
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
                        )
                    )
                )
            )
        );

        $reader = new \XMLReader;
        $reader->open(__DIR__.'/Fixtures/FazBookFeed.rss');
        $reader = $this->fastForward($reader, 4);
        $importer = $this->getImporterProxyFixture();
        $this->assertEquals($expectedMetaData, $importer->readMetaData($reader));

        unset($reader);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::readArticles
     */
    public function testReadArticles()
    {
        $params = array(
            'jackalope' => array(
                'root_path' => '/feeds/faz/books',
                'article_path' => 'article',
                'meta_path' => 'metaData',
            ),
            'article' => array(
                'publisher' => 'Frankfurter Allgemeine Zeitung GmbH'
            )
        );

        $jackalopeSession = $this->getJackalopeSessionFixture(array('nodeExists', 'getNode'));
        $jackalopeSession
            ->expects($this->any())
            ->method('nodeExists')
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue(true),
                    $this->returnValue(false)
                )
            );
        $jackalopeSession
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue(
                $this->getJackalopeNodeFixtureForWriteToJackalope()
            ));

        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->setMethods(array('getSession'))
            ->getMock();
        $jackalope
            ->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($jackalopeSession));

        $reader = new \XMLReader;
        $reader->open(__DIR__.'/../Fixtures/FazBookFeed.rss');
        $reader = $this->fastForward($reader, 56);

        $importer = $this->getImporterProxyFixture($params, $jackalope);
        $importer->metaData = array();
        $this->assertNull($importer->readArticles($reader));
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::getArticle
     */
    public function testGetArticleFromCache() {
        $importer = $this->getImporterProxyFixture();
        $importer->article = new \Bundle\FeedImportBundle\Component\Article();
        $this->assertInstanceOf(
            '\Bundle\FeedImportBundle\Component\Article',
            $importer->getArticle()
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::getArticle
     */
    public function testGetArticle() {
        $importer = $this->getImporterProxyFixture();
        $this->assertInstanceOf(
            '\Bundle\FeedImportBundle\Component\Article',
            $importer->getArticle()
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::setArticle
     */
    public function testSetArticle()
    {
        $importer = $this->getImporterProxyFixture();
        $article = $this->getArticleFixture();
        $importer->setArticle($article);
        $this->assertAttributeEquals($article, 'article', $importer);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::writeToJackalope
     */
    public function testWriteToJackalope()
    {
        $params = array(
            'jackalope' => array(
                'root_path' => '/feeds/faz/books',
                'article_path' => '/article',
                'meta_path' => '/metaData',
            ),
            'article' => array(
                'publisher' => 'Frankfurter Allgemeine Zeitung GmbH'
            )
        );

        $jackalopeNode = $this->getJackalopeNodeFixtureForWriteToJackalope();
        $jackalopeSession = $this->getJackalopeSessionFixture(array('nodeExists', 'getNode', 'save', 'logout'));
        $jackalopeSession
            ->expects($this->any())
            ->method('nodeExists')
            ->will(
                $this->onConsecutiveCalls(
                    $this->returnValue(true),
                    $this->returnValue(false)
                )
            );
        $jackalopeSession
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue($jackalopeNode));
         $jackalopeSession
            ->expects($this->any())
            ->method('save');
         $jackalopeSession
            ->expects($this->any())
            ->method('logout');

        $reader = new \XMLReader;
        $reader->open(__DIR__.'/../Fixtures/FazBookFeed.rss');
        $reader = $this->fastForward($reader, 56);

        $importer = $this->getImporterProxyFixture($params);
        $this->assertNull(
            $importer->writeToJackalope(
                $this->getArticleFixtureForWriteToJackalope($params),
                $jackalopeSession
            )
        );
    }

    /**
     * @expectedException \Exception
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::writeToJackalope
     */
    public function testWriteToJackalopeExpectingException()
    {
        $params = array(
            'jackalope' => array(
                'root_path' => '/feeds/faz/books',
                'article_path' => 'article',
                'meta_path' => 'metaData',
            ),
            'article' => array(
                'publisher' => 'Frankfurter Allgemeine Zeitung GmbH'
            )
        );

        $jackalopeNode = $this->getJackalopeNodeFixture(array('addNode'));

        $jackalopeSession = $this->getJackalopeSessionFixture(array('nodeExists', 'getNode', 'save', 'logout'));
        $jackalopeSession
            ->expects($this->any())
            ->method('nodeExists')
            ->will($this->throwException(new \Exception));

        $importer = $this->getImporterProxyFixture($params);
        $importer->writeToJackalope(
            $this->getArticleFixtureForWriteToJackalope($params),
            $jackalopeSession
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::writeToJackalope
     */
    public function testWriteToJackalopeMissingRootNode()
    {
        $params = array(
            'jackalope' => array(
                'root_path' => '/feeds/faz/books',
                'article_path' => 'article',
                'meta_path' => 'metaData',
            ),
            'article' => array(
                'publisher' => 'Frankfurter Allgemeine Zeitung GmbH'
            )
        );

        $jackalopeNode = $this->getJackalopeNodeFixture(array('addNode', 'setProperty'));
        $jackalopeNode
            ->expects($this->atLeastOnce())
            ->method('addNode')
            ->will($this->returnValue($jackalopeNode));
        $jackalopeNode
            ->expects($this->atLeastOnce())
            ->method('setProperty')
            ->with($this->isType('string'), $this->isType('string'));

        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->getMock();
        $jackalope
            ->expects($this->once())
            ->method('initPath')
            ->will($this->returnValue($jackalopeNode));

        $importer = $this->getImporterProxyFixture($params, $jackalope);
        $importer->writeToJackalope(
            $this->getArticleFixtureForWriteToJackalope($params),
            $this->getJackalopeSessionFixtureForWriteToJackalope($jackalopeNode)
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::getJackalopeSession
     */
    public function testGetJackalopeSessionFromCache()
    {
        $importer = $this->getImporterProxyFixture();
        $importer->jackalopeSession = $this->getJackalopeSessionFixture();
        $this->assertInstanceOf('\Jackalope\Session', $importer->getJackalopeSession());
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::getJackalopeSession
     */
    public function testGetJackalopeSession()
    {
        $jackalope = $this->getJackalopeSessionFixture(array('getSession'));
        $jackalope
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue(new \stdClass));
        $importer = $this->getImporterProxyFixture(array(), $jackalope);
        $this->assertInstanceOf('\stdClass', $importer->getJackalopeSession());
    }

    /**
     * @expectedException \RuntimeException
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::closeJackalopeSession
     */
    public function testClosejackalopeSessionExpectingRuntimeException()
    {
        $session = $this->getMockBuilder('\stdClass')
            ->setMethods(array('logout', 'save'))
            ->disableOriginalConstructor()
            ->getMock();
        $session
            ->expects($this->once())
            ->method('logout')
            ->will($this->throwException(new \Exception));
        $session
            ->expects($this->once())
            ->method('save');
        $jackalope = $this->getJackalopeSessionFixture(array('getSession'));
        $jackalope
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));
        $importer = $this->getImporterProxyFixture(array(), $jackalope);
        $importer->closeJackalopeSession();
    }

    /**
     * @covers \Bundle\FeedImportBundle\Importer\ImporterFazBooks::closeJackalopeSession
     */
    public function testClosejackalopeSession()
    {
        $session = $this->getMockBuilder('\stdClass')
            ->setMethods(array('logout', 'save'))
            ->disableOriginalConstructor()
            ->getMock();
        $session
            ->expects($this->once())
            ->method('logout');
        $session
            ->expects($this->once())
            ->method('save');
        $jackalope = $this->getJackalopeSessionFixture(array('getSession'));
        $jackalope
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));
        $importer = $this->getImporterProxyFixture(array(), $jackalope);
        $this->assertNull($importer->closeJackalopeSession());
    }

    /*************************************************************************/
    /* Dataprovider
    /*************************************************************************/

    public static function getNodeMessageDataprovider()
    {
        return array(
            'empty message array' => array(array()),
            'message available' => array(
                array(
                    'exists' => 'Node exists',
                    'new' => 'Node created',
                )
            ),
        );
    }

    public static function processContentExpectingRuntimeExceptionDataprovider()
    {
        return array(
            'explicit \RuntimeException' => array( new \RuntimeException ),
            'plain \Exception' => array( new \Exception ),
        );
    }
}

class ImporterFazBooksProxy extends \Bundle\FeedImportBundle\Importer\ImporterFazBooks
{
    public $article;
    public $metaData;
    public $jackalopeSession;

    public function processContent(\XMLReader $reader)
    {
        return parent::processContent($reader);
    }

    public function getArticle($id = null, $force = false)
    {
        return parent::getArticle($id, $force);
    }

    public function getJackalopeSession()
    {
        return parent::getJackalopeSession();
    }

    public function closeJackalopeSession()
    {
        return parent::closeJackalopeSession();
    }

    public function readMetaData(\XMLReader $reader)
    {
        return parent::readMetaData($reader);
    }

    public function readArticles(\XMLReader $reader)
    {
        return parent::readArticles($reader);
    }

    public function writeToJackalope(\Bundle\FeedImportBundle\Component\Article $article, \Jackalope\Session $jackalopeSession)
    {
        return parent::writeToJackalope($article, $jackalopeSession);
    }

    public function getNode($absPath, $nodePath, \PHPCR\NodeInterface $rootNode, $articleGuid,
                            \Jackalope\Session $jackalopeSession, array $msg = array())
    {
        return parent::getNode($absPath, $nodePath, $rootNode, $articleGuid, $jackalopeSession, $msg);
    }
}