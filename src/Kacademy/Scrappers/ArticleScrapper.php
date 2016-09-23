<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class ArticleScrapper extends BaseScrapper {

    /**
     * URL that will be scrapped
     * @var $_scrapUrl string URL to be scrapped. Base URL is default
     */
    private $_url = '';
    
    private $nodeSlug = '';

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
        
        $html = $this->getHtml();
        $jsonToArray = array();
        if(!empty($html))
        {
                $delimiter = '#';
                $startTag = 'ReactDOM.render(Component({"curation"';
                $endTag = '})';
                $regex = $delimiter . preg_quote($startTag, $delimiter) 
                                    . '(.*?)' 
                                    . preg_quote($endTag, $delimiter) 
                                    . $delimiter 
                                    . 's';
                preg_match($regex, $html, $matches);
                if(!empty($matches[0]))
                {
                        $invalidJson 	= $matches[0];
                        $validJson 		= str_replace('ReactDOM.render(Component({"curation"', '{"curation"', $invalidJson);
                        $validJson = str_replace('})', '}', $validJson);
                        $jsonToArray = json_decode($validJson, true);
                }
        }
        return $jsonToArray;
    }
    
    public function getNodeSlug() {
        return $this->nodeSlug;
    }
    
    public function setNodeSlug($slug) {
        $this->nodeSlug = $slug;
    }

    /**
     * Scrap data from valid JSON
     * 
     * @return string
     */
    private function scrapByJson() {
        
        $result     = $this->extractJson();
        
        $articles = array();
        
        if(isset($result['modules']) && !empty($result['modules'])) {
            foreach($result['modules'] as $module) {
                
                if(isset($module['slug']) && !empty($module['slug']) && $module['slug'] == $this->getNodeSlug()) {
                    
                    if(isset($module['contentItems']) && !empty($module['contentItems'])) {
                        foreach($module['contentItems'] as $contentItem) {
                            if($contentItem['kind'] != 'Article') {
                                continue;
                            }
                            $articles[] = array(
                                'type'          => 'Article',
                                'description'   => $this->cleanHtml($this->arrayKeySetAndNull($contentItem, 'description')),
                                'thumbnail_url' => $this->arrayKeySetAndNull($contentItem, 'thumbnailUrl'),
                                'title'         => $this->cleanHtml($this->arrayKeySetAndNull($contentItem, 'title')),
                            );
                        }
                    }
                }
            }
        }
	return $articles;
        
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
