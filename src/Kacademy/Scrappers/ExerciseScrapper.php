<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class ExerciseScrapper extends BaseScrapper {

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

        $exercises = array();

        if (!empty($result)) {
            
            foreach($result as $key => $exercise) {
                $exercises[$key] = array(
                    'relative_url'              => $exercise['relative_url'],
                    'file_name'                 => $exercise['file_name'],
                    'author_name'               => $exercise['author_name'],
                    'creation_date'             => date('Y-m-d', strtotime($exercise['creation_date'])),
                    'ka_url'                    => $exercise['ka_url'],
                    'author_key'                => $exercise['author_key'],
                    'is_quiz'                   => $exercise['is_quiz'],
                    'content_id'                => $exercise['id'],
                    'display_name'              => $exercise['display_name'],
                    'tracking_document_url'     => $exercise['tracking_document_url'],
                    'description'               => $exercise['description'],
                    'type'                      => $exercise['kind'],
                    'short_display_name'        => $exercise['short_display_name'],
                    'image_url'                 => $exercise['image_url'],
                    'global_id'                 => $exercise['global_id'],
                    'title'                     => $this->cleanHtml($exercise['title']),
                    'image_url'                 => $exercise['image_url'],
                    'tutorial_only'             => $exercise['tutorial_only'],
                    'image_url_256'             => $exercise['image_url_256'],
                    'node_slug'                 => $exercise['node_slug'],
                );
            }
        }

        return $exercises;
    }
    
    /**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
    public function runScrapper($callback) {
        $this->setHtmlDom();
        $records = $this->scrapByJson();
        $callback($records);
    }
}
