<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class CommentScrapper extends BaseScrapper {

    /**
     * URL that will be scrapped
     * @var $_scrapUrl string URL to be scrapped. Base URL is default
     */
    private $_url = '';

    /**
     * nodeSlug that will be scrapped
     * @var $_nodeSlug the node of skill
     */
    private $_nodeSlug = '';
    
    /**
     * Class constructor
     *
     * @param $url string URL to be scrapped. Base URL is default
     * 
     * @return void
     */
    public function __construct($url = '') {
        parent::__construct();
    }

    /**
     * setScrapUrl sets the URL to be scrapped for this scrapper
     *
     * @param $url string
     * 
     * @return void
     */
    public function setUrl($url) {
        if (strpos($url, 'http://') !== false || strpos($url, 'www') !== false) {
            $this->_url = $url;
        } else {
            $this->_url = $this->getBaseApiUrl() . $url;
        }
    }

    /**
     * getUrl get the URL to be scrapped
     * 
     * @return string
     */
    public function getUrl() {
        return $this->_url;
    }
    
    /**
     * setNodeSlug sets the node slug
     *
     * @param $nodeSlug string
     * 
     * @return void
     */
    public function setNodeSlug($nodeSlug) {
        $this->_nodeSlug = $nodeSlug;
    }

    /**
     * getNodeSlug get the slug of skill
     * 
     * @return string
     */
    public function getNodeSlug() {
        return $this->_nodeSlug;
    }

    /**
     * Extract json string from raw HTML
     * 
     * @return string
     */
    private function extractJson() {
        
        $jsonToArray = array();
        
        if(is_string($this->getHtml())) {
            $contents = (string)$this->getHtml();
            if (!empty($contents) && $contents != '') {
                $jsonToArray = json_decode($contents, true);
            }
        }

        return $jsonToArray;
    }
    
    function makePostArray($record) {
        return array(
            'content'               => $this->arrayKeySetAndNull($record, 'content'),
            'quality_kind'          => $this->arrayKeySetAndNull($record, 'qualityKind'),
            'permalink'             => $this->arrayKeySetAndNull($record, 'permalink'),
            'focus_url'             => $this->arrayKeySetAndNull($record, 'focusUrl'),
            'author_ka_id'          => $this->arrayKeySetAndNull($record, 'authorKaid'),
            'key'                   => $this->arrayKeySetAndNull($record, 'key'),
            'expand_key'            => $this->arrayKeySetAndNull($record, 'expandKey'),
            'appears_as_deleted'    => ($this->arrayKeySetAndNull($record, 'appearsAsDeleted') == 'true') ? 1 : 0,
            'flagged_by_user'       => ($this->arrayKeySetAndNull($record, 'flaggedByUser') == 'true') ? 1 : 0,
            'is_old'                => ($this->arrayKeySetAndNull($record, 'isOld') == 'true') ? 1 : 0,
            'definitely_not_spam'   => ($this->arrayKeySetAndNull($record, 'definitelyNotSpam') == 'true') ? 1 : 0,
            'from_video_author'     => ($this->arrayKeySetAndNull($record, 'fromVideoAuthor') == 'true') ? 1 : 0,
            'low_quality_score'     => $this->arrayKeySetAndNull($record, 'lowQualityScore'),
            'date'                  => date('Y-m-d H:i:s', strtotime($record['date'])),
            'last_answere_date'     => date('Y-m-d H:i:s', strtotime($record['lastAnswerDate'])),
            'number_of_flags'       => $this->arrayKeySetAndNull($record, 'numberOfFlags'),
            'sum_votes_incremented' => $this->arrayKeySetAndNull($record, 'sumVotesIncremented'),
        );
    }

    /**
     * Scrap data from valid JSON
     * 
     * @return string
     */
    private function scrapByJson($callback) {
        
        $result = $this->extractJson();

        $comments = array();

        if (!empty($result) && is_array($result)) {
            
            foreach ($result as $key => $record) {
                $comments[$key] = $this->makePostArray($record);
            }
            $callback($comments);
        }
    }
    
    /**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
    public function runScrapper($callback) {
        $this->setHtmlDom();
        $this->scrapByJson($callback);
    }

}
