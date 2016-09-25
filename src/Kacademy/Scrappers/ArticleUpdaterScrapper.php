<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class ArticleUpdaterScrapper extends BaseScrapper {

    /**
     * URL that will be scrapped
     * @var $_scrapUrl string URL to be scrapped. Base URL is default
     */
    private $_url = '';
    
    private $kaUrl = '';

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
                $startTag = 'ReactDOM.render(Component({"breadcrumbs"';
                $endTag = ',
                    document.getElementById("tutorial-content"));';
                $regex = $delimiter . preg_quote($startTag, $delimiter) 
                                    . '(.*?)' 
                                    . preg_quote($endTag, $delimiter) 
                                    . $delimiter 
                                    . 's';
                preg_match($regex, $html, $matches);
                if(!empty($matches[0]))
                {
                        $invalidJson 	= $matches[0];
                        $validJson 		= str_replace('ReactDOM.render(Component({"breadcrumbs"', '{"breadcrumbs"', $invalidJson);
                        $validJson = str_replace('),
                    document.getElementById("tutorial-content"));', '', $validJson);
                        $jsonToArray = json_decode($validJson, true);
                }
        }
        return $jsonToArray;
    }
    
    public function getKaUrl() {
        return $this->kaUrl;
    }
    
    public function setKaUrl($url) {
        $this->kaUrl = $url;
    }

    /**
     * Scrap data from valid JSON
     * 
     * @return string
     */
    private function scrapByJson() {
        
        $result     = $this->extractJson();
        
        $record = array();
        if(isset($result['tutorialNavData']['contentModels']) && !empty($result['tutorialNavData']['contentModels'])) {
            foreach($result['tutorialNavData']['contentModels'] as $contentModel) {
                if(isset($contentModel['relativeUrl']) && $contentModel['relativeUrl'] == $this->getKaUrl()) {
                    $record = array(
                        'relative_url'  => $this->arrayKeySetAndNull($contentModel, 'relativeUrl'),
                        'creation_date' => date('Y-m-d', strtotime($contentModel['creationDate'])),
                        'content_id'    => $this->arrayKeySetAndNull($contentModel, 'id'),
                        'thumbnail_url' => (isset($contentModel['thumbnailUrls']['default'])) ? $contentModel['thumbnailUrls']['default']:NULL,
                        'description'   => $this->cleanHtml($contentModel['description']),
                        'node_slug'     => $this->cleanHtml($contentModel['nodeSlug']),
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
        $records = $this->scrapByJson();
        $callback($records);
    }
}
