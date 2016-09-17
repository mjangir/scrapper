<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class CommonTopicScrapper extends BaseScrapper {

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

        $record = array();

        if (!empty($result)) {
            $record['parent'] = array(
                'icon_src'              => $result['icon_src'],
                'domain_slug'           => $result['domain_slug'],
                'relative_url'          => $result['relative_url'],
                'creation_date'         => date('Y-m-d H:i:s', strtotime($result['creation_date'])),
                'ka_url'                => $result['ka_url'],
                'author_key'            => $result['author_key'],
                'gplus_url'             => $result['gplus_url'],
                'content_id'            => $result['id'],
                'slug'                  => $result['slug'],
                'twitter_url'           => $result['twitter_url'],
                'twitter_url'           => $result['twitter_url'],
                'description'           => $this->cleanHtml($result['description']),
                'node_slug'             => $result['node_slug'],
                'facebook_url'          => $result['facebook_url'],
                'render_type'           => $result['render_type'],
                'title'                 => $this->cleanHtml($result['title']),
                'extended_slug'         => $result['extended_slug'],
                'topic_page_url'        => $result['topic_page_url']
            );

            if(isset($result['children']) && !empty($result['children'])) {
                foreach ($result['children'] as $child) {
                    $record['children'][] = array(
                        'content_id'    => $child['internal_id'],
                        'node_slug'     => $child['node_slug'],
                        'title'         => $this->cleanHtml($child['translated_title']),
                        'ka_url'        => $child['url']
                    );
                }
            }
        }

        return $record;
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
