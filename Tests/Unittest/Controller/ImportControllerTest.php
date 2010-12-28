<?php
/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
namespace Bundle\FeedImportBundle\Tests\Unittest\Controller;

/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Tests
 */
use Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher;

class ImportControllerTest extends \PHPUnit_Framework_TestCase
{

    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/

    /**
     * Provide a proxied instance of the import controller class.
     *
     * @return \Bundle\FeedImportBundle\Controller\ImportController
     */
    public function getImportControllerProxy($params = array())
    {
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $response = $this->getMock('\Symfony\Component\HttpFoundation\Response');
        $importers = array(
            'faz' => $this->getMockBuilder('\Bundle\FeedImportBundle\Importer\ImporterFaz')
                         ->disableOriginalConstructor()
                         ->getMock(),
        );

        return new ImportControllerContentProxy($params, $request, $response, $importers);
    }

    /**
     * Provide a proxied instance of the import controller class specialized for the indexAction method.
     *
     * @return \Bundle\FeedImportBundle\Controller\ImportController
     */
    public function getImportControllerProxyFixtureForIndexAction($dataType, $importer)
    {
        $importController = new ImportControllerContentProxy();
        $importController->response = new \Symfony\Component\HttpFoundation\Response();
        $importController->importers[$dataType] = $importer;
        $importController->request = $this->getSymfonyRequestFixture();
        $importController->params['whitelist'][$dataType] = array('192.168.80.0/24');

        return $importController;
    }

    /**
     * Provides a mocked instance of the Requestmatcher
     *
     * @return \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher
     */
    public function getRequestMatcherMockFixture()
    {
        $matcher = $this->getMockBuilder('\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $matcher
            ->expects($this->once())
            ->method('matches')
            ->will($this->returnValue(RequestMatcher::HTTP_OK));
        $matcher
            ->expects($this->once())
            ->method('matchIpWhitelist')
            ->with($this->isType('array'));
        $matcher
            ->expects($this->once())
            ->method('matchMethod')
            ->with($this->isType('string'));
        $matcher
            ->expects($this->once())
            ->method('matchServerVar')
            ->with($this->isType('string'), $this->isType('string'));
        return $matcher;
    }

    /**
     * Provide an instance of the import controller class.
     *
     * @return \Bundle\FeedImportBundle\Controller\ImportController
     */
    public function getImportController($params = array(), $request = null, $response = null, $importerFaz=null )
    {
        $request = is_null($request) ? $this->getMock('\Symfony\Component\HttpFoundation\Request') : $request;
        $response = is_null($response) ? $this->getMock('\Symfony\Component\HttpFoundation\Response') : $response;
        $importerFaz =
            is_null($importerFaz)
                ? $this->getMockBuilder('\Bundle\FeedImportBundle\Importer\ImporterFaz')
                    ->disableOriginalConstructor()
                    ->getMock()
                : $importerFaz;

        $importers = array(
            'faz' => $importerFaz,
        );

        return new \Bundle\FeedImportBundle\Controller\ImportController (
            $params, $request, $response, $importers
        );
    }

    /**
     * Provides an instance of the Sf Request class.
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getSymfonyRequestFixture()
    {
        $server = array(
            'REMOTE_ADDR'  => '192.168.80.129',
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        );

        return \Symfony\Component\HttpFoundation\Request::create(
            '/nzz',
            'POST',
            array(),
            array(),
            array(),
            $server
        );
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
    * @covers \Bundle\FeedImportBundle\Controller\ImportController::setServerVarsMatcher
    */
    public function testSetServerVarsMatcher()
    {
        $rawServerVars = array('content_type' => 'text/xml');
        $requestMatcher = $this->getMockBuilder('\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMatcher
            ->expects($this->once())
            ->method('matchServerVar')
            ->with($this->isType('string'), $this->isType('string'));

        $importController = $this->getImportControllerProxy();
        $importController->setServerVarsMatcher($rawServerVars, $requestMatcher);
    }

    /**
    * @covers \Bundle\FeedImportBundle\Controller\ImportController::setServerVarsMatcher
    */
    public function testSetServerVarsMatcherEmptyServerVars()
    {
        $rawServerVars = array();
        $requestMatcher = $this->getMockBuilder('\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $importController = $this->getImportControllerProxy();
        $importController->setServerVarsMatcher($rawServerVars, $requestMatcher);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::__construct
     */
    public function testConstruct()
    {
        $params = array();
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $response = $this->getMock('\Symfony\Component\HttpFoundation\Response');
        $importerFaz = $this->getMockBuilder('\Bundle\FeedImportBundle\Importer\ImporterFaz')
            ->disableOriginalConstructor()
            ->getMock();

        $importers = array(
            'faz' => $importerFaz,
        );

        $importController = new \Bundle\FeedImportBundle\Controller\ImportController(
                                $params, $request, $response, $importers);

        $this->assertAttributeEquals(array(), 'params', $importController);
        $this->assertAttributeInstanceOf('\Symfony\Component\HttpFoundation\Request', 'request', $importController);
        $this->assertAttributeInstanceOf('\Symfony\Component\HttpFoundation\Response', 'response', $importController);

        $importer = $this->readAttribute($importController, 'importers');
        $this->assertInstanceOf('\Bundle\FeedImportBundle\Importer\ImporterFaz', $importer['faz']);
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::verifyRequest
     */
    public function testVerifyRequest()
    {
        $importer = $this->getMockBuilder('\Bundle\FeedImportBundle\Importer\ImporterNzz')
            ->disableOriginalConstructor()
            ->getMock();
        $importer
            ->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->will($this->returnCallback(array($this, 'verifyRequestCallback')));

        $importController = new ImportControllerContentProxy();
        $importController->provider = 'nzz';
        $importController->importers['nzz'] = $importer;
        $importController->params['whitelist']['nzz'] = array('192.168.80.0/24');
        $importController->request = $this->getSymfonyRequestFixture();
        $importController->setRequestMatcher($this->getRequestMatcherMockFixture());

        $this->assertEquals(
            array(
                'code' => RequestMatcher::HTTP_OK,
                'msg' => 'Request successfully verified.'
            ),
            $importController->verifyRequest()
        );
    }

    /**
     * @dataProvider indexActionServiceUnavailableDataprovider
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::indexAction
     */
    public function testIndexActionExpectingServiceUnavailableStatus($dataType, $importer, $debug)
    {
        $importController = $this->getImportControllerProxyFixtureForIndexAction($dataType, $importer);
        $importController->params['debug'] = $debug;
        $response = $importController->indexAction($dataType);

        $this->assertEquals(
            \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_SERVICE_UNAVAILABLE,
            $response->getStatusCode()
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::indexAction
     */
    public function testIndexActionLoggingEnabled()
    {
        $importController = $this->getImportControllerProxyFixtureForIndexAction('Beastie', null);
        $importController->logger = $this->getMockBuilder('\stdClass')
            ->disableOriginalConstructor()
            ->setMethods(array('debug'))
            ->getMock();
        $importController->logger
            ->expects($this->once())
            ->method('debug');


        $response = $importController->indexAction('Beastie');
        $this->assertEquals(
            \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_SERVICE_UNAVAILABLE,
            $response->getStatusCode()
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::indexAction
     */
    public function testIndexAction()
    {
        $importer = $this->getMockBuilder('\Bundle\FeedImportBundle\Importer\ImporterFaz')
            ->disableOriginalConstructor()
            ->getMock();
        $importer
            ->expects($this->once())
            ->method('processXml');
        $importer
            ->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->will($this->returnCallback(array($this, 'verifyRequestCallback')));

        $importController = $this->getImportControllerProxyFixtureForIndexAction('fazbook', $importer);
        $importController->setRequestMatcher($this->getRequestMatcherMockFixture());
        $response = $importController->indexAction('fazbook');
        $this->assertEquals(RequestMatcher::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::indexAction
     */
    public function testIndexActionExpectingBadRequestStatus()
    {
        $importer = $this->getMockBuilder('\Bundle\FeedImportBundle\Importer\ImporterFaz')
            ->disableOriginalConstructor()
            ->getMock();
        $importer
            ->expects($this->once())
            ->method('processXml')
            ->will($this->throwException(new \RuntimeException));
        $importer
            ->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->will($this->returnCallback(array($this, 'verifyRequestCallback')));

        $importController = $this->getImportControllerProxyFixtureForIndexAction('fazbook', $importer);
        $importController->setRequestMatcher($this->getRequestMatcherMockFixture());
        $response = $importController->indexAction('fazbook');
        $this->assertEquals(RequestMatcher::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::getRequestMatcher
     */
    public function testGetRequestMatcherUsingPredefinedRequestMatcher()
    {
        $importController = $this->getImportControllerProxy();
        $importController->setRequestMatcher(
            $this->getMock('\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher')
        );

        $this->assertInstanceOf(
            '\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher',
            $importController->getRequestMatcher()
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::getRequestMatcher
     */
    public function testGetRequestMatcherUsingLazyInitialization()
    {
        $importController = $this->getImportControllerProxy();
        $this->assertInstanceOf(
            '\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher',
            $importController->getRequestMatcher()
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::setRequestMatcher
     */
    public function testSetRequestMatcher()
    {
        $importController = $this->getImportController();
        $importController->setRequestMatcher(
            $this->getMock('\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher')
        );

        $this->assertAttributeInstanceOf(
            '\Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher',
            'requestMatcher',
            $importController
        );
    }

    /**
     * @dataProvider getImporterObjectDataprovider
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::getImporterObject
     */
    public function testGetImporterObject($expected, $dataType)
    {
        $importController = $this->getImportControllerProxy();
        $this->assertInstanceOf($expected, $importController->getImporterObject($dataType));
    }

    /**
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::getImporterObject
     * @expectedException \UnexpectedValueException
     */
    public function testGetImporterObjectDataTypeNotRecongized()
    {
        $importController = $this->getImportControllerProxy();
        $this->assertNull($importController->getImporterObject('invalid'));
    }

    /**
     * @dataProvider getWhitelistIpsDataprovider
     * @covers \Bundle\FeedImportBundle\Controller\ImportController::getWhitelistedIps
     */
    public function testGetWhitelistedIps($expected, $provider)
    {
        $whitelist = array('liipzh' => array('192.168.80.129', '192.168.80.192/26'));
        $params['whitelist'] = $whitelist;

        $importController = $this->getImportControllerProxy($params);
        $this->assertEquals(
            $expected,
            $importController->getWhitelistedIps($provider)
        );
    }

    /*************************************************************************/
    /* Dataprovider / callback functions
    /*************************************************************************/

    public function verifyRequestCallback($section)
    {
        switch (strtolower($section)) {
            case 'method':
                return 'post';
            case 'server_vars':
                return array('content_type' => 'text/xml');
        }
    }

    public static function indexActionServiceUnavailableDataprovider()
    {
        return array(
            'unknown/invalid dataType with debuging' => array('invalid Datatype', null, true),
            'unknown/invalid dataType' => array('invalid Datatype', null, false),
            'identified dataType is not an importer' => array('nzz', new \stdClass, false),
        );
    }

    public static function getWhitelistIpsDataprovider()
    {
        return array(
            'with valid provider' => array (
                array('192.168.80.129', '192.168.80.192/26'),
                'LiipZH'
            ),
            'with unknown provider' => array (
                array(),
                'unknown provider'
            )
        );
    }

    public static function getImporterObjectDataprovider()
    {
        return array(
            'expecting Nzz importer' => array('\Bundle\FeedImportBundle\Importer\ImporterNzz', 'nzz'),
            'expecting NewsNt importer' => array('\Bundle\FeedImportBundle\Importer\ImporterNewsNt', 'newsnt')
        );
    }
}

class ImportControllerContentProxy extends \Bundle\FeedImportBundle\Controller\ImportController
{
    public $response;
    public $request;
    public $importers;
    public $logger;
    public $params;
    public $provider;

    /**
     * Disable parent constructor
     */
    public function __construct($params = array(), $request = null, $response = null, $importers = null, $logger = null)
    {
        parent::__construct($params, $request, $response, $importers, $logger);
    }

    public function verifyRequest()
    {
        return parent::verifyRequest();
    }
}
