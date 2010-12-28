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
class RequestMatcherTest extends \PHPUnit_Framework_TestCase
{
    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/


    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatches()
    {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $this->assertEquals(
            $matcher::HTTP_OK,
            $matcher->matches($request)
        );
    }

    /**
     * @dataProvider getMatchesWithMethodDataprovider
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatchesWithMethod($expected, $setMethod, $getMethod)
    {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchMethod($setMethod);
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request
            ->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($getMethod));

        $this->assertEquals(
            $expected,
            $matcher->matches($request)
        );
    }

    /**
     * @dataProvider getMatchesWithPathDataprovider
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatchesWithPath($expected, $getPathInfo)
    {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchPath("[^0-9]+");
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->will($this->returnValue($getPathInfo));

        $this->assertEquals(
            $expected,
            $matcher->matches($request)
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatchesWithAttribute()
    {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchAttribute('dataType', 'nzz');

        $request = \Symfony\Component\HttpFoundation\Request::create(
            '\/nzz',
            'POST'
        );
        $request->attributes = new \Symfony\Component\HttpFoundation\ParameterBag(array('dataType' => 'invalid DataType'));
        $this->assertEquals($matcher::HTTP_BAD_REQUEST, $matcher->matches($request));
    }

    /**
     * @dataProvider matchesWithServerVarsDataprovider
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatchesWithServerVars($expected, $serverVars, $serverVar, $value)
    {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchServerVar($serverVars[0], $serverVars[1]);

        $server = array(
            $serverVar => $value,
        );

        $request = \Symfony\Component\HttpFoundation\Request::create(
            '\/nzz',
            'POST',
            array(),
            array(),
            array(),
            $server
        );
        $this->assertEquals($expected, $matcher->matches($request));
    }

    /**
     * @dataProvider getMatchesWithHostDataprovider
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatchesWithHost($expected, $hostname)
    {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchHost("[^0-9]+");
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request
            ->expects($this->once())
            ->method('getHost')
            ->will($this->returnValue($hostname));

        $this->assertEquals(
            $expected,
            $matcher->matches($request)
        );

    }

    /**
     * @dataProvider getMatchesWithIpDataprovider
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatchesWithIp($expected, $clientIp, $ip) {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchIp($ip);
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request
            ->expects($this->once())
            ->method('getClientIp')
            ->will($this->returnValue($clientIp));

        $this->assertEquals(
            $expected,
            $matcher->matches($request)
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matches
     */
    public function testMatchesWithIpWhitelist()
    {
        $matcher = new RequestMatcherProxy();
        $matcher->matchIpWhitelist(
            array('192.168.10.12', '192.168.10.128/25')
        );
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request
            ->expects($this->once())
            ->method('getClientIp')
            ->will($this->returnValue('192.168.1.200'));

        $this->assertEquals(
            \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_SERVICE_UNAVAILABLE,
            $matcher->matches($request)
        );
    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matchServerVar
     */
    public function testMatchServerVars()
    {
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchServerVar('content_type', 'text\/xml; charset=utf-8');
        $this->assertAttributeEquals(array('CONTENT_TYPE' =>'text\/xml; charset=utf-8'), 'serverVars', $matcher);

    }

    /**
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::matchIpWhitelist
     */
    public function testMatchIpWhitelist()
    {
        $ips = array('192.168.10.12');
        $matcher = new \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher();
        $matcher->matchIpWhitelist($ips);
        $this->assertAttributeEquals($ips, 'ipWhitelist', $matcher);
    }

    /**
     * @dataProvider checkIpWhitelistDataprovider
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::checkIpWhitelist
     */
    public function testCheckIpWhitelist($expected, $clientIp)
    {
        $matcher = new RequestMatcherProxy();
        $matcher->matchIpWhitelist(
            array('192.168.10.12', '192.168.10.128/25')
        );

        $this->assertEquals($expected, $matcher->checkIpWhitelist($clientIp));
    }

    /**
     * @dataProvider checkIpWhitelistConsistantIpDataprovider
     * @covers \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::checkIpWhitelist
     */
    public function testCheckIpWhitelistResetTmpIp($clientIp) {
        $matcher = new RequestMatcherProxy();
        $matcher->matchIpWhitelist(
            array('192.168.10.12', '192.168.10.128/25')
        );
        $matcher->matchIp('192.168.10.1');
        $matcher->checkIpWhitelist($clientIp);

        $this->assertAttributeEquals('192.168.10.1', 'ip', $matcher);
    }

    /*************************************************************************/
    /* Dataprovider
    /*************************************************************************/

    public static function matchesWithServerVarsDataprovider()
    {
        return array(
            'invalid server var' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_BAD_REQUEST,
                array('CONTENT_LENGTH', '422123'),
                'CONTENT_LENGTH',
                '21'
            ),
            'valid server var' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_OK,
                array('CONTENT_TYPE', 'text\/xml; charset=utf-8'),
                'CONTENT_TYPE',
                'text/xml; charset=utf-8'
            ),
            'invalid CONTENT_TYPE' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_UNSUPPORTED_MEDIA_TYPE,
                array('CONTENT_TYPE', 'text\/xml'),
                'CONTENT_TYPE',
                'text/xml; charset=utf-8'
            ),
        );
    }

    public static function checkIpWhitelistConsistantIpDataprovider()
    {
        return array(
            'matching Ip' => array('192.168.10.12'),
            'not matching Ip' => array('192.168.1.1'),
        );
    }

    public static function checkIpWhitelistDataprovider()
    {
        return array(
            'simple IP' => array(
                true,
                '192.168.10.12',
            ),
            'IP in defined range' => array(
                true,
                '192.168.10.200',
            ),
            'simple IP out of defined range' => array(
                false,
                '192.168.1.200',
            ),
        );
    }

    public static function getMatchesWithMethodDataprovider()
    {
        return array(
            'matching methods' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_OK,
                'POST',
                'POST'
             ),
            'not matching methods' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_NOT_IMPLEMENTED,
                'POST',
                'GET'
             ),
            'matching methods by array' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_OK,
                array('POST'),
                'POST'
             ),
            'not matching methods by array' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_NOT_IMPLEMENTED,
                array('POST'),
                'GET'
             ),
        );
    }

    public static function getMatchesWithPathDataprovider()
    {
        return array(
            'matching path info' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_OK,
                'BeastieForEver'
             ),
            'not matching path info' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_BAD_REQUEST,
                'Beastie4Ever'
             ),
        );
    }

    public static function getMatchesWithHostDataprovider()
    {
        return array(
            'matching hostname' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_OK,
                'BeastieForEver'
             ),
            'not matching hostname' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_SERVICE_UNAVAILABLE,
                'Beastie4Ever'
             ),
        );
    }

    public static function getMatchesWithIpDataprovider()
    {
        return array(
            'simple IP' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_OK,
                '192.168.10.12',
                '192.168.10.12',
            ),
            'IP with netmask' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_OK,
                '192.168.10.200',
                '192.168.10.0/24',
            ),
            'simple IP not matching' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_SERVICE_UNAVAILABLE,
                '192.168.10.1',
                '192.168.10.12',
            ),
            'IP with netmask not matching' => array(
                \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher::HTTP_SERVICE_UNAVAILABLE,
                '192.168.1.200',
                '192.168.10.0/24',
            ),
        );
    }
}

class RequestMatcherProxy extends \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher
{
    public function checkIpWhitelist($ip)
    {
        return parent::checkIpWhitelist($ip);
    }

    public function
}