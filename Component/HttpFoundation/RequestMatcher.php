<?php
/**
 *
 * @package FeedImportBundle
 * @subpackage Component
 */

namespace Bundle\FeedImportBundle\Component\HttpFoundation;

/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Component
 */
class RequestMatcher extends \Symfony\Component\HttpFoundation\RequestMatcher
{

    /**#@+
     * Set of HTTP error codes to be used in different situations.
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * @var integer
     */
    const HTTP_OK = 200;
    const HTTP_NO_CONTENT = 204;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    /**#@-*/

    /**
     * Set of server variables to verify.
     * @var array
     */
    protected $serverVars = array();

    /**
     * Set of ip addresses granted to call the service.
     * @var array|null
     */
    protected $ipWhitelist;

    /**
     * Accepted charset of the incoming request.
     * @var array|null
     */
    protected $charsets;

    /**
     * Decides whether the rule(s) implemented by the strategy matches the supplied request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request $request The request to check for a match
     * @return integer The HTTP error code representing the accourd error.
     *
     * @see Symfony\Component\HttpFoundation.RequestMatcher::matches()
     */
    public function matches(\Symfony\Component\HttpFoundation\Request $request)
    {
        if (null !== $this->methods && !in_array(strtolower($request->getMethod()), $this->methods)) {
            return self::HTTP_NOT_IMPLEMENTED;
        }

        // match information from the current route defined in Resources\config\routing.yml.
        foreach ($this->attributes as $key => $pattern) {
            if (!preg_match('#^'.$pattern.'$#', $request->attributes->get($key))) {
                return self::HTTP_BAD_REQUEST;
            }
        }

        // match information provided by the $_SERVER var.
        foreach ($this->serverVars as $key => $pattern) {
            if (!preg_match('#^'.$pattern.'$#i', $request->server->get($key))) {
                if ('CONTENT_TYPE' == $key) {
                    return self::HTTP_UNSUPPORTED_MEDIA_TYPE;
                }
                return self::HTTP_BAD_REQUEST;
            }
        }

        if (null !== $this->path && !preg_match('#^'.$this->path.'$#', $request->getPathInfo())) {
            return self::HTTP_BAD_REQUEST;
        }

        if (null !== $this->host && !preg_match('#^'.$this->host.'$#', $request->getHost())) {
            return self::HTTP_SERVICE_UNAVAILABLE;
        }

        if (null !== $this->ip && !$this->checkIp($request->getClientIp())) {
            return self::HTTP_SERVICE_UNAVAILABLE;
        }

        if (null !== $this->ipWhitelist && !$this->checkIpWhitelist($request->getClientIp())) {
            return self::HTTP_SERVICE_UNAVAILABLE;
        }

        return self::HTTP_OK;
    }

    /**
     * Registers the $_SERVER variable to be verified.
     *
     * @param string $key Name of the variable to verify
     * @param string $regexp Expression the content of the variable has to match.
     */
    public function matchServerVar($key, $regexp)
    {
        $this->serverVars[strtoupper($key)] = $regexp;
    }

    /**
     * Registers the ip addresses granted to call the service.
     *
     * @param array $ips Set of granted ip adresses.
     */
    public function matchIpWhitelist(array $ips)
    {
        $this->ipWhitelist = $ips;
    }

    /**
     * Sanitizes the given ip address matching a whitelist.
     *
     * @param string $ip Dedicated ip address to be verified.
     * @return bool True, on success, else false.
     */
    protected function checkIpWhitelist($ip)
    {
        // temporary store the current ip address.
        $tmpIp = $this->ip;
        foreach ($this->ipWhitelist as $wlAddress) {
            $this->ip = $wlAddress;
            if (true === parent::checkIp($ip)) {
                $this->ip = $tmpIp;
                return true;
            }
        }
        $this->ip = $tmpIp;
        return false;
    }
}
