<?php
/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Controller
 */
namespace Bundle\FeedImportBundle\Controller;
use Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher;

/**
 *
 *
 * @package FeedImportBundle
 * @subpackage Controller
 */
class ImportController
{
    /**
     * Instance of of the Symfony request class.
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request = null;

    /**
     * Instance of of the Symfony response class.
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response = null;

    /**
     * Set of attributes.
     * @var array
     */
    protected $params = array();

    /**
     * Set of registered import services.
     * @var array
     */
    protected $importers = array();

    /**
     * Instance of the request matcher class.
     * @var \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher
     */
    protected $requestMatcher = null;

    /**
     * Identifier of the information provider (e.g. nzz).
     * @var string
     */
    protected $provider = '';

    /**
     * @var Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Initialize the controller object.
     *
     * @param array $params
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param array $importers
     * @param Symfony\Component\HttpKernel\Log\LoggerInterface $logger
     */
    public function __construct($params, $request, $response, $importers, $logger = null)
    {
        $this->params = $params;
        $this->request = $request;
        $this->response = $response;
        $this->importers = $importers;
        $this->logger = $logger;
    }

    /**
     * Prepares the request to be processed by an importer identified by its dataType.
     *
     * Will be executed if url looks e.g. like :
     *   http://nzz.lo/index_dev.php/nzz
     *
     * @param string $dataType Name of the service to be handled.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($dataType)
    {
        if (!array_key_exists($dataType, $this->importers)
         || !$this->importers[$dataType] instanceof \Bundle\FeedImportBundle\Importer\ImporterInterface ) {
            $result = array(
                'code' => RequestMatcher::HTTP_SERVICE_UNAVAILABLE,
                'msg' => 'Importer '.$dataType.' not found',
            );
        } else {
            $this->provider = $dataType;
            $result = $this->verifyRequest();
            if (RequestMatcher::HTTP_OK == $result['code']){
                try {
                    $this->importers[$this->provider]->processXml();
                    $result['code'] = RequestMatcher::HTTP_NO_CONTENT;
                } catch (\RuntimeException $rte) {
                    $result['code'] = RequestMatcher::HTTP_BAD_REQUEST;
                    $result['msg'] = $rte->getMessage();
                }
            }
        }

        $this->response->setStatusCode($result['code']);
        if (isset($this->params['debug']) && true === $this->params['debug']) {
            $this->response->setContent($result['msg']);
        }
        if ($this->logger) {
            $this->logger->debug('Importer result: '.$result['code'].' '.$result['msg']);
        }
        return $this->response;
    }

    /**
     * Determines if the given request is valid to handle.
     *
     * @return array List of the HTTP response code and a message.
     * @uses \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher
     */
    protected function verifyRequest()
    {
        $requestMatcher = $this->getRequestMatcher();
        $requestMatcher->matchIpWhitelist($this->getWhitelistedIps($this->provider));

        // the importer defines the method and _SERVER variables to be respected.
        $requestMatcher->matchMethod($this->importers[$this->provider]->getConfiguration('method'));

        $this->setServerVarsMatcher($this->importers[$this->provider]->getConfiguration('server_vars'), $requestMatcher);

        $code = $requestMatcher->matches($this->request);
        return array(
            'code' => $code,
            'msg' => RequestMatcher::HTTP_OK == $code
                ? 'Request successfully verified.'
                : __METHOD__.': Request does not match expectations.'
        );
    }

    /*************************************************************************/
    /* Helper
    /*************************************************************************/

    /**
     * Gets all the server vars from the config.
     *
     * @param array $rawServerVars Set of server vars to be registered in the request matcher.
     * @param \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher $requestMatcher Instance of request matcher.
     */
    protected function setServerVarsMatcher(array $rawServerVars, $requestMatcher) {
        foreach ($rawServerVars as $key => $value) {
            $requestMatcher->matchServerVar($key, $value);
        }
    }

    /**
     * Gets the importer object related to the given datatype;
     *
     * @param string $dataType
     *
     * @return Bundle\FeedImportBundle\Importer\ImporterInterface|null Return null in case no matching provider found,
     *                                                                  else the related implementation to the interface.
     */
    protected function getImporterObject($dataType) {
        switch (strtolower($dataType)) {
            case 'nzz':
                return $this->importers['nzz'];
            case 'newsnt':
                return $this->importers['newsnt'];
            default:
                throw new \UnexpectedValueException(
                    'Importer not recognized!',
                    RequestMatcher::HTTP_BAD_REQUEST
                );
        }
    }

    /**
     * Gets the set of ip addresses registered for a specific information provider.
     *
     * An information provider is an agency sending content via the webservice.
     *
     * @param string $provider Identifier of the information provider.
     * @return array Set of id addresses registered for the given information provider.
     */
    protected function getWhitelistedIps($provider) {

        // normalize
        $provider = strtolower($provider);

        if (array_key_exists($provider, $this->params['whitelist'])) {
            return $this->params['whitelist'][$provider];
        }

        return array();
    }

    /**
     * Provides an instance of the request matcher
     *
     * The request matcher object is chached in the current object.
     *
     * @return \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher
     */
    protected function getRequestMatcher()
    {
        if (is_null($this->requestMatcher)) {
            $this->requestMatcher = new RequestMatcher;
        }
        return $this->requestMatcher;
    }

    /**
     * Set the instance of a request matcher to be used instead of the original.
     *
     * @param \Bundle\FeedImportBundle\Component\HttpFoundation\RequestMatcher $matcher
     */
    public function setRequestMatcher(RequestMatcher $matcher)
    {
        $this->requestMatcher = $matcher;
    }
}
