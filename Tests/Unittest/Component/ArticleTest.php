<?php
/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
namespace Bundle\FeedImportBundle\Tests\Unittest\Component;

/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
class ArticleTest extends \PHPUnit_Framework_TestCase
{
    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::__construct
     */
    public function testConstruct()
    {
        $guid = 'Im_Gespr_ch_Umberto_Eco_Sind_Sie_der_ideale_Leser_Signore_Eco__2033bf17f2a870f5365fb92202c48210';
        $jackalopeNode = $this->getMockBuilder('\Jackalope\Node')
            ->disableOriginalConstructor()
            ->setMethods(array('getNode', 'getPropertyValue', 'hasNode'))
            ->getMock();
        $jackalopeNode
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue($jackalopeNode));
        $jackalopeNode
            ->expects($this->any())
            ->method('getPropertyValue')
            ->will($this->returnValue('property'));
        $jackalopeNode
            ->expects($this->any())
            ->method('hasNode')
            ->will($this->returnValue(true));

        $jackalopeSession = $this->getMockBuilder('\Jackalope\Session')
            ->disableOriginalConstructor()
            ->setMethods(array('getRootNode'))
            ->getMock();
        $jackalopeSession
            ->expects($this->any())
            ->method('getRootNode')
            ->will($this->returnValue($jackalopeNode));

        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->setMethods(array('getSession'))
            ->getMock();
        $jackalope
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($jackalopeSession));
        $article = new \Bundle\FeedImportBundle\Component\Article($guid, $jackalope);
        $this->assertAttributeEquals($guid, 'guid', $article);
        $this->assertAttributeEquals($jackalope, 'jackalope', $article);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::__construct
     */
    public function testConstructWithDefaultParameters()
    {
        $article = new \Bundle\FeedImportBundle\Component\Article();

        $this->assertNull($this->readAttribute($article, 'guid'));
        $this->assertNull($this->readAttribute($article, 'jackalope'));
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::init
     */
    public function testInitExpectedInvalidArgumentException()
    {
        $guid = 'Test%20Title_'.md5('Test%20Title_');

        try {
            $article = new \Bundle\FeedImportBundle\Component\Article($guid);
            $this->fail('Expected exception (\InvalidArgumentException) not thrown.');
        } catch (\InvalidArgumentException $rte){}
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::init
     */
    public function testInit()
    {
        $jackalopeNode = $this->getMockBuilder('\Jackalope\Node')
            ->disableOriginalConstructor()
            ->setMethods(array('getNode', 'getPropertyValue', 'hasNode'))
            ->getMock();
        $jackalopeNode
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue($jackalopeNode));
        $jackalopeNode
            ->expects($this->any())
            ->method('getPropertyValue')
            ->will($this->returnValue('property'));
        $jackalopeNode
            ->expects($this->any())
            ->method('hasNode')
            ->will($this->returnValue(true));

        $jackalopeSession = $this->getMockBuilder('\Jackalope\Session')
            ->disableOriginalConstructor()
            ->setMethods(array('getRootNode'))
            ->getMock();
        $jackalopeSession
            ->expects($this->any())
            ->method('getRootNode')
            ->will($this->returnValue($jackalopeNode));

        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->setMethods(array('getSession'))
            ->getMock();
        $jackalope
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($jackalopeSession));

        $guid = 'Im_Gespr_ch_Umberto_Eco_Sind_Sie_der_ideale_Leser_Signore_Eco__2033bf17f2a870f5365fb92202c48210';
        $article = new \Bundle\FeedImportBundle\Component\Article($guid, $jackalope);
        $this->assertAttributeEquals('property', 'description', $article);
        $this->assertAttributeEquals('property', 'language', $article);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::initMetaData
     */
    public function testInitMetaData()
    {
        $jackalopeNode = $this->getMockBuilder('\Jackalope\Node')
            ->disableOriginalConstructor()
            ->setMethods(array('getPropertyValue'))
            ->getMock();
        $jackalopeNode
            ->expects($this->any())
            ->method('getPropertyValue')
            ->will($this->returnValue('property'));

        $article = new ArticleProxy();
        $article->initMetaData($jackalopeNode);
        $this->assertAttributeEquals('property', 'permalink', $article);
    }

    /**
     * @dataProvider convertDataprovider
     * @covers \Bundle\FeedImportBundle\Component\Article::convert
     */
    public function testConvert($guidData, $guid)
    {
        $params = array('article' => array('publisher' => 'http://www.faz.net'));
        $article = new \Bundle\FeedImportBundle\Component\Article();

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
                    $guidData,
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

        $article->convert($params, $articleData, $metaData);
        $this->assertAttributeEquals('http://www.faz.net', 'publisher', $article);
        $this->assertAttributeEquals('headline', 'title', $article);
        $this->assertAttributeEquals('http://www.faz.net', 'link', $article);
        $this->assertAttributeEquals('Wed, 08 Dec 2010 16:42:43 +0100', 'publicationDate', $article);
        $this->assertAttributeEquals('article text', 'description', $article);
        $this->assertAttributeEquals($guid, 'guid', $article);

        $this->assertAttributeEquals('de-de', 'language', $article);
        $this->assertAttributeEquals('FAZ.NET', 'source', $article);
        $this->assertAttributeEquals('5', 'ttl', $article);
        $this->assertAttributeEquals('Copyright 2001-2010 Frankfurter Allgemeine Zeitung'."\n".'            GmbH. Alle Rechte vorbehalten.', 'copyright', $article);
        $this->assertAttributeEquals('Wed, 08 Dec 2010 16:42:43 +0100', 'lastBuildDate', $article);
        $this->assertAttributeEquals(array('Bücher - FAZ.NET'), 'categories', $article);
    }

    /**
     * @expectedException \RuntimeException
     * @covers \Bundle\FeedImportBundle\Component\Article::convert
     */
    public function testConvertExpectingRuntimException()
    {
        $guid = 'test%20Title_'.md5('Test%20Title_');
        $jackalopeNode = $this->getMockBuilder('\Jackalope\Node')
            ->disableOriginalConstructor()
            ->setMethods(array('getNode', 'getPropertyValue', 'hasNode'))
            ->getMock();
        $jackalopeNode
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue($jackalopeNode));
        $jackalopeNode
            ->expects($this->any())
            ->method('getPropertyValue')
            ->will($this->returnValue('property'));
        $jackalopeNode
            ->expects($this->any())
            ->method('hasNode')
            ->will($this->returnValue(true));

        $jackalopeSession = $this->getMockBuilder('\Jackalope\Session')
            ->disableOriginalConstructor()
            ->setMethods(array('getRootNode'))
            ->getMock();
        $jackalopeSession
            ->expects($this->any())
            ->method('getRootNode')
            ->will($this->returnValue($jackalopeNode));

        $jackalope = $this->getMockBuilder('\Bundle\JackalopeBundle\Loader')
            ->disableOriginalConstructor()
            ->setMethods(array('getSession'))
            ->getMock();
        $jackalope
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($jackalopeSession));
        $params = array('article' => array('publisher' => 'http://www.faz.net'));
        $article = new \Bundle\FeedImportBundle\Component\Article($guid, $jackalope);

        $article->convert($params, array(), array());
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::addCategory
     */
    public function testAddCategory()
    {
        $article = new \Bundle\FeedImportBundle\Component\Article();
        $article->addCategory('Bücher - FAZ.NET');
        $this->assertAttributeEquals(array('Bücher - FAZ.NET'), 'categories', $article);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::__get
     */
    public function testGet()
    {
        $article = new \Bundle\FeedImportBundle\Component\Article();
        $article->addCategory('Bücher - FAZ.NET');
        $this->assertEquals(array('Bücher - FAZ.NET'), $article->categories);
    }

    /**
     * @dataProvider magicGetDataprovider
     * @expectedException \InvalidArgumentException
     * @covers \Bundle\FeedImportBundle\Component\Article::__get
     */
    public function testGetExpectingInvalidArgumentException($value)
    {
        $article = new \Bundle\FeedImportBundle\Component\Article();
        $parole = $article->$value;
    }

    /**
     * @dataProvider createGuidDataprovider
     * @covers \Bundle\FeedImportBundle\Component\Article::createGuid
     */
    public function testCreateGuid($expected, $permaLink)
    {
        $article = new ArticleProxy();
        $this->assertEquals($expected, $article->createGuid('prefix', $permaLink));

    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\Article::urlize
     */
    public function testUrlize()
    {
        $article = new ArticleProxy();
        $this->assertEquals('Text_mit_whitespaces_', $article->urlize('Text mit whitespaces!'));
    }

    /*************************************************************************/
    /* Dataprovider
    /*************************************************************************/

    public static function createGuidDataprovider() {
        return array(
            'with permaLink' => array( 'prefix_901410934be56b0767bd3093135876ac', 'permalink'),
            'without permaLink' => array( 'prefix_851f5ac9941d720844d143ed9cfcf60a', ''),
        );
    }

    public static function convertDataprovider()
    {
        return array(
            'guid is a permalink' => array(
                array(
                    'tag' => 'guid',
                    'attr' => array( 'isPermaLink' => 'true' ),
                    'children' => array( array( 'text' => 'http://www.faz.net/s/...' ) )
                ),
                'headline_8893185fe2f5b8dd4342abddd0078300'
            ),
            'guid is not a permalink' => array(
                array(
                    'tag' => 'guid',
                    'attr' => array( 'isPermaLink' => 'false' ),
                    'children' => array( array( 'text' => 'http://www.faz.net/s/...' ) )
                ),
                'headline_ef983058688ff58732b0fe2b8779fc0a'
            ),
        );
    }

    public static function magicGetDataprovider()
    {
        return array(
            'in blacklist' => array('params'),
            'not in blacklist' => array('beastie'),
        );
    }
}

class ArticleProxy extends \Bundle\FeedImportBundle\Component\Article
{
    public function createGuid($prefix, $permaLink)
    {
        return parent::createGuid($prefix, $permaLink);
    }

    public function initMetaData($dataNode)
    {
        return parent::initMetaData($dataNode);
    }
}