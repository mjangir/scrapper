<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class UserScrapper extends BaseScrapper {

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
        
        $record = array();

        if (!empty($result)) {
            $record = array(
                'username'                  => $this->arrayKeySetAndNull($result, 'username'),
                'date_joined'               => date('Y-m-d', strtotime($this->arrayKeySetAndNull($result, 'dateJoined'))),
                'has_students'              => $this->arrayKeySetAndNull($result, 'hasStudents'),
                'background_src'            => $this->arrayKeySetAndNull($result, 'backgroundSrc'),
                'child_page_root'           => $this->arrayKeySetAndNull($result, 'childPageRoot'),
                'streak_last_length'        => $this->arrayKeySetAndNull($result, 'streakLastLength'),
                'avatar_name'               => $this->arrayKeySetAndNull($result, 'avatarName'),
                'streak_last_extended'      => date('Y-m-d', strtotime($this->arrayKeySetAndNull($result, 'streakLastExtended'))),
                'profile_root'              => $this->arrayKeySetAndNull($result, 'profileRoot'),
                'email'                     => $this->arrayKeySetAndNull($result, 'email'),
                'count_videos_completed'    => $this->arrayKeySetAndNull($result, 'countVideosCompleted'),
                'bio'                       => $this->cleanHtml($this->arrayKeySetAndNull($result, 'bio')),
                'background_name'           => $this->arrayKeySetAndNull($result, 'backgroundName'),
                'is_orphan'                 => $this->arrayKeySetAndNull($result, 'isOrphan'),
                'is_public'                 => $this->arrayKeySetAndNull($result, 'isPublic'),
                'background_display_name'   => (isset($result['background']['displayName'])) ? $result['background']['displayName'] : NULL,
                'background_thumbnail_src'  => (isset($result['background']['thumbnailSrc'])) ? $result['background']['thumbnailSrc'] : NULL,
                'background_image_src'      => (isset($result['background']['imageSrc'])) ? $result['background']['imageSrc'] : NULL,
                'is_phantom'                => $this->arrayKeySetAndNull($result, 'isPhantom'),
                'nickname'                  => $this->arrayKeySetAndNull($result, 'nickname'),
            );
            
            if(isset($result['publicBadges']) && !empty($result['publicBadges'])) {
                foreach($result['publicBadges'] as $badges) {
                    $record['badges'][] = array(
                      'slug' => $badges['slug']  
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
