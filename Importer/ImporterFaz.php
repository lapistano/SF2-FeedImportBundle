<?php
/**
 * Special importer to fetch data from a FAZ feed.
 *
 *
 * @link http://www.rssboard.org/rss-specification
 * @package FeedImportBundle
 * @subpackage Importer
 */
namespace Bundle\FeedImportBundle\Importer;

/**
 *
 * @package FeedImportBundle
 * @subpackage Importer
 */
class ImporterFaz extends ImporterBase
{

    /**
     * Meta data of the feed. Is null if not processed yet.
     * @var array|null
     */
    protected $metaData = null;

    /**
     * Instance of an article to written to or read from jackalope.
     * @var \Bundle\FeedImportBundle\Component\Article
     */
    protected $article = null;

    /**
     * Instance of a jackalope session.
     * @var \Jackalope\Session
     */
    protected $jackalopeSession = null;

    /**
     * Extracts meta data from xml and stores it to the storage layer.
     *
     * @params \XMLReader $reader Instance of the XMLReader.
     * @throws \RuntimeException in case of the convert method will be called on an initialized article.
     * @throws \RuntimeException incase of any exception thrown by the jackalope session.
     * @throws \Exception in case the stream could not be read.
     */
    public function processContent(\XMLReader $reader)
    {
        $IN_CHANNEL = false;
        $ARTICLES_PROCESSED = false;

        try {
            while($reader->read()) {

                // skip root element
                if ($reader->name == 'rss') {
                    continue;
                }

                // determine if in the channel sub tree
                if ($reader->name == 'channel' && $reader->nodeType == $reader::ELEMENT) {
                    $IN_CHANNEL = true;
                }

                // get meta data
                if (is_null($this->metaData) && true == $IN_CHANNEL) {
                    $this->metaData = $this->readMetaData($reader);
                }

                // process article
                if (false === $ARTICLES_PROCESSED && true == $IN_CHANNEL) {
                    // session start
                    $this->readArticles($reader);
                    $ARTICLES_PROCESSED = true;
                    //session save && session close
                    $this->closeJackalopeSession();
                }

                if ($reader->name == 'channel' && $reader->nodeType == $reader::END_ELEMENT) {
                    $IN_CHANNEL = false;
                }
            }
        } catch ( \RuntimeException $rte) {
            throw new \RuntimeException($rte->getMessage(), $rte->getCode(), $rte->getPrevious());
        } catch( \Exception $e ) {
            throw new \RuntimeException('XML stream invalid or request body missing ('.$e->getMessage().')');
        }
    }

    /**
     * Reads the provided metadata from the xml stream.
     *
     * <b>NOTICE</b>
     * Since this is all sequencial do never change the sequence of the items
     * of the infoSet array!!
     *
     * @param \XMLReader $reader Instance of the XMLReader.
     * @return array List of meta data provided by the xml stream.
     */
    protected function readMetaData(\XMLReader $reader)
    {
        $infoSet = array(
            'title', 'link', 'description', 'language', 'copyright', 'category', 'generator',
            'ttl', 'lastBuildDate', 'image'
            );
            $metaData = array();
            foreach($infoSet as $key) {
                $metaData[$key] = $this->xml2assoc($reader, $key);
            }
            $this->log(__METHOD__.': Following meta data has been found: '."\n". print_r($metaData, true));
            return $metaData;
    }

    /**
     * Reads the articles identified by the 'item' tag from the stream.
     *
     * @param \XMLReader $reader Instance of the XMLReader.
     * @throws \RuntimeException in case of the convert method will be called on an initialized article.
     */
    protected function readArticles(\XMLReader $reader)
    {
        while ($reader->nodeType == $reader::SIGNIFICANT_WHITESPACE) {
            $articleData = $this->xml2assoc($reader, 'item');
            if (!empty($articleData)) {
                $article = $this->getArticle(null, true);
                $article->convert($this->params, $articleData, $this->metaData);
                $this->writeToJackalope($article, $this->getJackalopeSession());
            }
        }
    }

    /**
     * Write given article to a Jackalope instance.
     *
     * Structure of the article array:
     * array(
     *     'tag' => 'item',
     *     'children' => array(
     *         array(
     *             'tag' => 'title',
     *             'children' => array( array( 'text' => 'headline' ) )
     *         ),
     *         array(
     *             'tag' => 'link',
     *             'children' => array( array( 'text' => 'http://www.faz.net' ) )
     *         ),
     *         array(
     *             'tag' => 'guid',
     *             'attr' => array( 'isPermaLink' => 'true' )
     *             'children' => array( array( 'text' => 'http://www.faz.net/s/...' ) )
     *         ),
     *         array(
     *             'tag' => 'pubDate',
     *             'children' => array( array( 'text' => 'Wed, 08 Dec 2010 16:42:43 +0100' ) )
     *         ),
     *         array(
     *             'tag' => 'description',
     *             'children' => array( array( 'text' => 'article text' ) )
     *         ),
     *     )
     * )
     *
     *
     * @param \Bundle\FeedImportBundle\Component\Article $article
     * @param \Jackalope\Session $jackalopeSession
     * @throws \RuntimeException in case of any exception was thrown by any called method.
     */
    protected function writeToJackalope(\Bundle\FeedImportBundle\Component\Article $article,
                                        \Jackalope\Session $jackalopeSession)
    {
        try {
            if (!$jackalopeSession->nodeExists($this->params['jackalope']['root_path'])) {
                $this->log(__METHOD__.": Setup article feed base node (".$this->params['jackalope']['root_path'].").");
                $feedRootNode = $this->jackalope->initPath($this->params['jackalope']['root_path']);
            } else {
                $this->log(__METHOD__.": Get article feed base node to store article.");
                $feedRootNode = $jackalopeSession->getNode($this->params['jackalope']['root_path']);
            }

            $articleNode = $this->getNode(
                $this->params['jackalope']['root_path'].'/'.$article->guid,
                $article->guid,
                $feedRootNode,
                $article->guid,
                $jackalopeSession,
                array(
                    'exists' => 'Article node exists update it',
                    'new'    => 'Create new article node',
                )
            );

            // process article structure

            // store article meta data
            $this->log(__METHOD__.": Apply meta data to the article node (".$articleNode->getPath().").");
            $metadataNode = $this->getNode(
                $this->params['jackalope']['root_path'].'/'.$article->guid.'/'.$this->params['jackalope']['meta_path'],
                $this->params['jackalope']['meta_path'],
                $articleNode,
                $article->guid,
                $jackalopeSession,
                array(
                    'exists' => 'MetaData node exists update it',
                    'new'    => 'Create new metaData node',
                )
            );

            $this->log(__METHOD__.": Apply meta data to node (".$metadataNode->getPath().").");

            $metadataNode->setProperty('publisher' , $article->publisher);
            $metadataNode->setProperty('language'  , $article->language);
            $metadataNode->setProperty('link'      , $article->permalink);
            $metadataNode->setProperty('pubDate'   , $article->publicationDate);
            $metadataNode->setProperty('timeToLive', $article->ttl);
            $metadataNode->setProperty('copyright' , $article->copyright);
            $metadataNode->setProperty('category'  , implode(', ', $article->categories));

            // store article main data
            $this->log(__METHOD__.": Apply main data to the article node (".$articleNode->getPath().").");
            $articleNode->setProperty('title'      , $article->title);
            $articleNode->setProperty('text'       , $article->description);

        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

    }

    /*************************************************************************/
    /* Helpers
    /*************************************************************************/

    /**
     * Gets the node identified by its path.
     *
     * Structure os $msg:
     * <code>
     * array(
     *     'exists' => 'text',
     *     'new'    => 'text',
     * )
     * </code>
     *
     * @param string $absPath Absolute path to the node to be created
     * @param string $nodePath realtive path to the node to be created
     * @param \PHPCR\NodeInterface $rootNode Instance of the node the new node shall be created.
     * @param string $articleGuid
     * @param \Jackalope\Session $jackalopeSession Instance of teh jackalope session to be used.
     * @param array $msg
     *
     * @return \PHPCR\NodeInterface Either the current created node or the retrieved one.
     */
    protected function getNode($absPath, $nodePath, \PHPCR\NodeInterface $rootNode, $articleGuid,
                               \Jackalope\Session $jackalopeSession, array $msg = array())
    {

        $this->log(__METHOD__.": absPath: $absPath");

        if ($jackalopeSession->nodeExists($absPath)) {
            if (!empty($msg['exists'])) {
                $this->log(__METHOD__.": ${msg['exists']}! (node path: $absPath)");
            }
            $node = $jackalopeSession->getNode($absPath);
        } else {
            if (!empty($msg['new'])) {
                $this->log(__METHOD__.": ${msg['new']}. (guid: $articleGuid");
            }
            $node = $rootNode->addNode($nodePath, null, $articleGuid);
        }

        return $node;
    }

    /**
     * Generates an article object.
     *
     * @param null|integer $id Guid of the article to be fetched.
     * @param boolean $force   if set to true the caching is disabled.
     *
     * @return \Bundle\FeedImportBundle\Component\Article
     */
    protected function getArticle($id = null, $force = false)
    {
        if ($force || is_null($this->article)) {
            $this->article = new \Bundle\FeedImportBundle\Component\Article($id);
        }
        return $this->article;
    }

    /**
     * Sets the article object to be used insatead of the one lazily instatiated.
     *
     * @param \Bundle\FeedImportBundle\Component\Article $article
     */
    public function setArticle(\Bundle\FeedImportBundle\Component\Article $article)
    {
        $this->article = $article;
    }

    /**
     * Provides a Jackalope session.
     *
     * Once a session has been established it will be cached.
     *
     * @return \Jackalope\Session
     */
    protected function getJackalopeSession()
    {
        if (is_null($this->jackalopeSession)) {
            $this->jackalopeSession = $this->jackalope->getSession();
        }
        return $this->jackalopeSession;
    }

    /**
     * Closes the current jackalope session and saves changes before.
     *
     * @todo: determine if there is s.th. special to do in casse of an exception.
     *
     * @throws \RuntimeException incase of any exception thrown by the jackalope session.
     */
    protected function closeJackalopeSession()
    {
        $jackalopeSession = $this->getJackalopeSession();
        try {
            $jackalopeSession->save();
            $jackalopeSession->logout();
        } catch( \Exception $e) {
            throw new \RuntimeException(__METHOD__.': '.$e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
