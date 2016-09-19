<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class BadgeScrapper extends BaseScrapper {

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
        if (strpos($url, 'http://') !== false || strpos($url, 'https://') !== false || strpos($url, 'www') !== false) {
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
        
        $jsonToArray = array();
        if(is_string($this->getHtml())) {
            $contents = (string)$this->getHtml();
            if (!empty($contents) && $contents != '') {
                $jsonToArray = json_decode($contents, true);
            }
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
        
        $records = array();

        if (!empty($result)) {
            foreach ($result as $record) {
                $records[] = array(
                    'badge_category'    => $record['badge_category'],
                    'icon_src'          => $this->arrayKeySetAndNull($record, 'icon_src'),
                    'hide_context'      => $this->arrayKeySetAndNull($record, 'hide_context'),
                    'relative_url'      => $this->arrayKeySetAndNull($record, 'relative_url'),
                    'icon_small'        => $this->arrayKeySetAndNull($record, 'icon_small'),
                    'icon_large'        => $this->arrayKeySetAndNull($record, 'icon_large'),
                    'icon_compact'      => $this->arrayKeySetAndNull($record, 'icon_compact'),
                    'icon_email'        => $this->arrayKeySetAndNull($record, 'icon_email'),
                    'absolute_url'      => $this->arrayKeySetAndNull($record, 'absolute_url'),
                    'slug'              => $this->arrayKeySetAndNull($record, 'slug'),
                    'name'              => $this->arrayKeySetAndNull($record, 'name'),
                    'safe_extended_description'        => $this->arrayKeySetAndNull($record, 'safe_extended_description'),
                    'points'            => $this->arrayKeySetAndNull($record, 'points'),
                    'is_retired'        => $this->arrayKeySetAndNull($record, 'is_retired'),
                    'description'       => $this->cleanHtml($this->arrayKeySetAndNull($record, 'description')),
                );
            }
        }

        return $records;
    }
    
    /**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
    public function runScrapper($callback) {
        $this->setHtmlDom();
        $record = $this->scrapByJson();
        $callback($record);
    }

}
