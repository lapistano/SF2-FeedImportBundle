<?php
/**
 *
 * @package FeedImportBundle
 * @subpackage Component
 */
namespace Bundle\FeedImportBundle\Component;

/**
 *
 *
 * @property-read string $language        Language of the article in iso format (ISO 639)
 *                                        ({@link http://tools.ietf.org/html/rfc4646} &
 *                                        {@link http://www.w3.org/TR/REC-html40/struct/dirlang.html#langcodes} &
 *                                        {@link http://www.rssboard.org/rss-language-codes}).
 * @property-read string $publisher       Name of the organisation or person published this article.
 * @property-read string $source          The RSS channel that the item came from.
 * @property-read string $ttl             Number of minutes a channel may be cached before refreshing from the source.
 * @property-read string $permaLink       Backlink to the article on the website of the publisher.
 * @property-read string $title           Title of the article.
 * @property-read string $link            back link to the article on the web (non permanent)
 * @property-read string $guid            Unique identifier of the article within the storage system.
 * @property-read string $publicationDate Indicates when the item was published.
 * @property-read string $description     The item synopsis.
 * @property-read string $copyright       Copyright notice for the article.
 * @property-read string $categories      Specify one or more categories that the channel belongs to.
 * @property-read string $lastBuildDate   The last time the content of the channel changed.
 *
 * @package FeedImportBundle
 * @subpackage Component
 */
class Article
{
    /**
     * Instance of the jackalope symfony service
     * @var \Bundle\JackalopeBundle\Loader
     */
    protected $jackalope = null;

    /**
     * The language the channel is written in.
     * Allowable values can be found here: http://cyber.law.harvard.edu/rss/languages.html
     * @var string
     */
    protected $language = '';

    /**
     * The publisher of the feed.
     * @var string
     */
    protected $publisher = '';

    /**
     * generator
     * @var string
     */
    protected $source = '';

    /**
     * Defines how long (minutes) the channel can be cached before refreshing from the source.
     * @var string
     */
    protected $ttl = '';

    /**
     * Link, which is a URL that can be opened in a web browser,
     * that points to the full item described by the <item> element.
     * @var string
     */
    protected $permalink = '';

    /**
     * Title of the item.
     * @var string
     */
    protected $title = '';

    /**
     * Link, which is a URL that can be opened in a web browser,
     * that points to the full item described by the <item> element.
     * @var string
     */
    protected $link = '';

    /**
     * Identifier of the current loaded article.
     * @var string
     */
    protected $guid = null;

    /**
     * Date when the article was published.
     * @var string
     */
    protected $publicationDate = '';

    /**
     * Description of the given item. (can also contain HTML).
     * @var string
     */
    protected $description = '';

    /**
     * Defines the copyright holder.
     * @var string
     */
    protected $copyright = '';

    /**
     * Gives the date when the feed was built last time.
     * @var string
     */
    protected $lastBuildDate = '';

    /**
     * Describes in which categories the channel is set.
     * @var array
     */
    protected $categories = array();

    /**
     * Set of attribute not to be exposed.
     * @var array
     */
    private $attributeNoGet = array('jackalope');

    /**
     * Depending on the id either the article is loaded from the workspace or an empty object is created.
     *
     * An empty article object could be filled with data using convert().
     *
     * @param null|string $guid The unique identifier of the article including the absolute path.
     * @param \Bundle\JackalopeBundle\Loader|null $jackalope Instance of the Jackalope loader class.
     */
    public function __construct($guid = null, $jackalope = null)
    {
        $this->jackalope = $jackalope;
        $this->guid = $guid;
        if (!is_null($guid)) {
            $this->init();
        }
    }

    /**
     * Fetch information about the article from jackalope.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function init()
    {
        if (is_null($this->jackalope)) {
            throw new \InvalidArgumentException('When passing a guid it is mandatory to pass a jacklalope instance, too.');
        }

        // get article from jackalope.
        $session = $this->jackalope->getSession();
        $rootNode = $session->getRootNode();
        $articleNode = $rootNode->getNode($this->guid);

        if ($articleNode->hasNode('metaData')) {
            $this->initMetaData($articleNode->getNode('metaData'));
        }
        $this->description = $articleNode->getPropertyValue('text');
        $this->title = $articleNode->getPropertyValue('title');
    }

    /**
     * Reads the meta data from the the node and stored it the the corresponding attributes.
     *
     * @param \jackalope\Node $dataNode
     */
    protected function initMetaData($dataNode)
    {
        $this->publisher = $dataNode->getPropertyValue('publisher');
        $this->language = $dataNode->getPropertyValue('language');
        $this->permalink = $dataNode->getPropertyValue('link');
        $this->publicationDate = $dataNode->getPropertyValue('pubDate');
        $this->ttl = $dataNode->getPropertyValue('timeToLive');
        $this->copyright = $dataNode->getPropertyValue('copyright');
        $this->categories = explode(', ', $dataNode->getPropertyValue('category'));
    }

    /**
     * Converts the given information to be used as an article.
     *
     * @param array $params
     * @param array $articleData
     * @param array $metaData
     * @return void
     *
     * @throws \RuntimeException in case of the convert method will be called on an initialized article.
     */
    public function convert($params, array $articleData, array $metaData)
    {
        if (!is_null($this->guid)) {
            throw new \RuntimeException('It is not allowed to override an initialized article.');
        }

        $articleData = $articleData[0]['children'];
        $this->publisher = $params['article']['publisher'];

        $this->title = $articleData[0]['children'][0]['text'];
        $this->link = $articleData[1]['children'][0]['text'];
        $this->publicationDate = $articleData[3]['children'][0]['text'];
        $this->description = $articleData[4]['children'][0]['text'];
        if('true' == $articleData[2]['attr']['isPermaLink']) {
            $this->guid = $this->createGuid($this->urlize($this->title), $articleData[2]['children'][0]['text']);
        } else {
            $this->guid = $this->createGuid($this->urlize($this->title));
        }

        if (! empty($metaData)) {
            $this->language = $metaData['language'][0]['children'][0]['text'];
            $this->source = $metaData['generator'][0]['children'][0]['text'];
            $this->ttl = $metaData['ttl'][0]['children'][0]['text'];
            $this->copyright = $metaData['copyright'][0]['children'][0]['text'];
            $this->lastBuildDate = $metaData['lastBuildDate'][0]['children'][0]['text'];
            $this->addCategory($metaData['category'][0]['children'][0]['text']);
        }
    }

    /**
     * Adds the current article to a new category.
     *
     * @param string $category
     * @return void
     */
    public function addCategory($category)
    {
        $this->categories[] = $category;
    }

    /**
     * Creates a unique identifier to be used to store the article.
     *
     * @param string $prefix    Text the hash string shall be prifixed with (e.g. the title of the article).
     * @param string $permaLink The string to be hashed.
     *
     * @return string A reasonable unique string to identify this article.
     */
    protected function createGuid($prefix, $permaLink = '')
    {
        $permaLink = empty($permaLink) ? $prefix : $permaLink;
        return $prefix.'_'.md5($permaLink);
    }

    /**
     * Encodes the given text to be usable in an url.
     *
     * @param string $text
     * @return string
     */
    protected function urlize($text)
    {
        $text = preg_replace('([^0-9a-z_-]+)i', '_', $text);
        return urlencode($text);
    }

    /*************************************************************************/
    /* Magic methods
    /*************************************************************************/

    /**
     * Returns the content of the class attribute.
     *
     * @param string $name
     * @return string content of the class attribute.
     * @throws \InvalidArgumentException in case of the $name does not match a class attribute.
     */
    public function __get($name)
    {
        if(!isset($this->$name) || in_array($name, $this->attributeNoGet)) {
            throw new \InvalidArgumentException('Unknown class attribute requestet ('.$name.').');
        }
        return $this->$name;
    }
}
