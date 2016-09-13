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
                $skill = array(
                    'ka_id'                 => $this->arrayKeySetAndNull($cModel, 'id'),
                    'type'                  => $this->arrayKeySetAndNull($cModel, 'contentKind'),
                    'name'                  => $this->arrayKeySetAndNull($cModel, 'name'),
                    'title'                 => $this->cleanHtml($this->arrayKeySetAndNull($cModel, 'title')),
                    'display_name'          => $this->arrayKeySetAndNull($cModel, 'displayName'),
                    'short_display_name'    => $this->arrayKeySetAndNull($cModel, 'shortDisplayName'),
                    'pretty_display_name'   => $this->arrayKeySetAndNull($cModel, 'prettyDisplayName'),
                    'description'           => $this->cleanHtml($this->arrayKeySetAndNull($cModel, 'descriptionHtml')),
                    'creation_date'         => date('Y-m-d H:i:s', strtotime($this->arrayKeySetAndNull($cModel, 'creationDate'))),
                    'date_added'            => date('Y-m-d H:i:s', strtotime($this->arrayKeySetAndNull($cModel, 'dateAdded'))),
                    'ka_url'                => ($this->arrayKeySetAndNull($cModel, 'kaUrl') == NULL) ? '' : $this->arrayKeySetAndNull($cModel, 'kaUrl'),
                    'image_url'             => $this->arrayKeySetAndNull($cModel, 'imageUrl'),
                    'keywords'              => $this->arrayKeySetAndNull($cModel, 'keywords'),
                    'license_name'          => $this->cleanHtml($this->arrayKeySetAndNull($cModel, 'licenseName')),
                    'license_full_name'     => $this->cleanHtml($this->arrayKeySetAndNull($cModel, 'licenseFullName')),
                    'license_url'           => $this->arrayKeySetAndNull($cModel, 'licenseUrl'),
                    'license_logo_url'      => $this->arrayKeySetAndNull($cModel, 'licenseLogoUrl'),
                    'slug'                  => $this->arrayKeySetAndNull($cModel, 'slug'),
                    'node_slug'             => $this->arrayKeySetAndNull($cModel, 'nodeSlug'),
                    'ka_relative_url'       => $this->arrayKeySetAndNull($cModel, 'relativeUrl'),
                    'file_name'             => $this->arrayKeySetAndNull($cModel, 'fileName'),
                    'thumbnail_default'     => (isset($cModel['thumbnailUrls']) && isset($cModel['thumbnailUrls']['default'])) ? $cModel['thumbnailUrls']['default'] : NULL,
                    'thumbnail_filtered'    => NULL,
                    'video_youtube_id'      => $this->arrayKeySetAndNull($cModel, 'youtubeId'),
                    'video_duration'        => $this->arrayKeySetAndNull($cModel, 'duration'),
                    'video_download_size'   => $this->arrayKeySetAndNull($cModel, 'downloadSize'),
                    'video_download_urls'   => json_encode($this->arrayKeySetAndNull($cModel, 'downloadUrls'))
                );
                $skills[] = $skill;
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
