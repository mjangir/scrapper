<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class BadgeCategoryScrapper extends BaseScrapper {

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
                    'category'          => $record['category'],
                    'chart_icon_src'    => $this->arrayKeySetAndNull($record, 'chart_icon_src'),
                    'icon_src'          => $this->arrayKeySetAndNull($record, 'icon_src'),
                    'large_icon_src'    => $this->arrayKeySetAndNull($record, 'large_icon_src'),
                    'type_label'        => $this->arrayKeySetAndNull($record, 'type_label'),
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
