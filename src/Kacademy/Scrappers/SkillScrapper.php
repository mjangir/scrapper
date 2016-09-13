<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class SkillScrapper extends BaseScrapper {

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
            $this->_url = $this->getBaseUrl() . $url;
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

        if (!empty($html)) {
            $delimiter = '#';
            $startTag = 'ReactDOM.render(Component({"breadcrumbs"';
            $endTag = '})';
            $regex = $delimiter . preg_quote($startTag, $delimiter)
                    . '(.*?)'
                    . preg_quote($endTag, $delimiter)
                    . $delimiter
                    . 's';
            preg_match($regex, $html, $matches);

            if (!empty($matches[0])) {
                $invalidJson = $matches[0];
                $validJson = str_replace('ReactDOM.render(Component({"breadcrumbs"', '{"breadcrumbs"', $invalidJson);
                $validJson = str_replace('})', '}', $validJson);

                $jsonToArray = json_decode($validJson, true);
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

        $skills = array();

        if (!empty($result) && isset($result['tutorialNavData']['contentModels'])) {
            foreach ($result['tutorialNavData']['contentModels'] as $key => $cModel) {

                $skills[] = array(
                    'ka_id'                 => $this->mayNull($cModel['id']),
                    'type'                  => $this->mayNull($cModel['contentKind']),
                    'name'                  => $this->mayNull($cModel['name']),
                    'title'                 => $this->cleanHtml($this->mayNull($cModel['title'])),
                    'display_name'          => $this->mayNull($cModel['displayName']),
                    'short_display_name'    => $this->mayNull($cModel['shortDisplayName']),
                    'pretty_display_name'   => $this->mayNull($cModel['prettyDisplayName']),
                    'description'           => $this->cleanHtml($this->mayNull($cModel['descriptionHtml'])),
                    'creation_date'         => date('Y-m-d H:i:s', strtotime($this->mayNull($cModel['creationDate']))),
                    'date_added'            => date('Y-m-d H:i:s', strtotime($this->mayNull($cModel['dateAdded']))),
                    'ka_url'                => $this->mayNull($cModel['kaUrl']),
                    'image_url'             => $this->mayNull($cModel['imageUrl']),
                    'keywords'              => $this->mayNull($cModel['keywords']),
                    'license_name'          => $this->cleanHtml($this->mayNull($cModel['licenseName'])),
                    'license_full_name'     => $this->cleanHtml($this->mayNull($cModel['licenseFullName'])),
                    'license_url'           => $this->mayNull($cModel['licenseUrl']),
                    'license_logo_url'      => $this->mayNull($cModel['licenseLogoUrl']),
                    'slug'                  => $this->mayNull($cModel['slug']),
                    'node_slug'             => $this->mayNull($cModel['nodeSlug']),
                    'ka_relative_url'       => $this->mayNull($cModel['relativeUrl']),
                    'file_name'             => $this->mayNull($cModel['fileName']),
                    'thumbnail_default'     => $this->mayNull($cModel['thumbnailUrls']['default']),
                    'thumbnail_filtered'    => $this->mayNull($cModel['thumbnailUrls']['filtered']),
                    'video_youtube_id'      => $this->mayNull($cModel['youtubeId']),
                    'video_duration'        => $this->mayNull($cModel['duration']),
                    'video_download_size'   => $this->mayNull($cModel['downloadSize']),
                    'video_download_urls'   => $this->mayNull(json_encode($cModel['downloadUrls']))
                );
            }
        }

        return $skills;
    }

    /**
     * Scrap data using simple html dom
     * 
     * @return array
     */
    private function scrap() {
        $htmlDom = $this->getHtmlDom();

        $topics = array();

        if (!empty($htmlDom)) {
            if ($htmlDom->find('div[class^=moduleDefault_]') !== NULL) {
                foreach ($htmlDom->find('div[class^=moduleDefault_]') as $module) {
                    $topic = $module->find('a', 0);
                    if ($topic !== NULL) {
                        $topicName = $topic->find('h3', 0)->plaintext;
                        $topicUrl = $topic->href;
                        $icon = '';
                        $description = '';
                        if ($module->find('img[class^=icon_]', 0) !== NULL) {
                            $icon = $module->find('img[class^=icon_]', 0)->src;
                        }
                        if ($module->find('div[class^="description_]', 0) !== NULL) {
                            $description = $module->find('div[class^="description_]', 0)->plaintext;
                        }
                        $urlParts = explode('/', $topicUrl);
                        $slug = end($urlParts);

                        $topics[] = array(
                            'title' => $topicName,
                            'slug' => $slug,
                            'ka_url' => $topicUrl,
                            'description' => $description
                        );
                    }
                }
            }
        }
        return $topics;
    }

    /**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
    public function runScrapper($callback) {
        $this->setHtmlDom();
        $skills = $this->scrapByJson();
        $callback($skills);
    }

}
