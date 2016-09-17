<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class VideoScrapper extends BaseScrapper {

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

        $videos = array();

        if (!empty($result)) {
            
            foreach($result as $key => $video) {
                $videos[$key] = array(
                    'relative_url'              => $video['relative_url'],
                    'creation_date'             => date('Y-m-d', strtotime($video['creation_date'])),
                    'download_urls'             => json_encode($video['download_urls']),
                    'ka_url'                    => $video['ka_url'],
                    'duration'                  => $video['duration'],
                    'author_key'                => $video['author_key'],
                    'content_id'                => $video['id'],
                    'download_size'             => $video['download_size'],
                    'progress_key'              => $video['progress_key'],
                    'thumbnail_url'             => (isset($video['thumbnail_data']) && isset($video['thumbnail_data']['url'])) ? $video['thumbnail_data']['url'] : NULL,
                    'thumbnail_title_text'      => (isset($video['thumbnail_data']) && isset($video['thumbnail_data']['title_text'])) ? $video['thumbnail_data']['title_text'] : NULL,
                    'description'               => $this->cleanHtml($video['description']),
                    'node_slug'                 => $video['node_slug'],
                    'date_added'                => date('Y-m-d H:i:s', strtotime($video['date_added'])),
                    'type'                      => $video['kind'],
                    'youtube_url'               => $video['url'],
                    'global_id'                 => $video['global_id'],
                    'image_url'                 => $video['image_url'],
                    'keywords'                  => $video['keywords'],
                    'youtube_id'                => $video['youtube_id'],
                    'title'                     => $this->cleanHtml($video['title']),
                );
            }
        }

        return $videos;
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
