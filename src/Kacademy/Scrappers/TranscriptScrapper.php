<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class TranscriptScrapper extends BaseScrapper {

    /**
     * URL that will be scrapped
     * @var $_scrapUrl string URL to be scrapped. Base URL is default
     */
    private $_url = '';

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
     * Extract json string from raw HTML
     * 
     * @return string
     */
    private function extractJson() {
        
        $contents = $this->getHtml();

        $jsonToArray = array();
        
        $contents = (string)$contents;

        if (!empty($contents)) {
            $jsonToArray = json_decode($contents, true);
        }
        return $jsonToArray;
    }

    /**
     * Scrap data from valid JSON
     * 
     * @return string
     */
    private function scrapByJson() {
        
        $result = $this->extractJson();

        $transcripts = array();

        if (!empty($result)) {
            foreach ($result as $key => $ts) {

                $text       = $this->cleanHtml($this->arrayKeySetAndNull($ts, 'text'));
                $kaIsValid  = $ts['kaIsValid'];
                $startTime  = (int)$ts['startTime'];
                $endTime    = (int)$ts['endTime'];

                $transcripts[$key] = array(
                    'text'          => $text,
                    'ka_is_valid'   => $kaIsValid,
                    'start_time'    => $startTime,
                    'end_time'      => $endTime
                );
            }
        }

        return $transcripts;
    }
    
    /**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
    public function runScrapper($callback) {
        $this->setHtmlDom();
        $transcripts = $this->scrapByJson();
        $callback($transcripts);
    }

}
