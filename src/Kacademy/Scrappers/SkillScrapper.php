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
	public function __construct($url = '')
	{
		parent::__construct();
	}

	/**
     * setScrapUrl sets the URL to be scrapped for this scrapper
     *
     * @param $url string
     * 
     * @return void
     */
	public function setUrl($url)
	{
		if(strpos($url, 'http://') !== false || strpos($url, 'www') !== false)
		{
			$this->_url = $url;
		}
		else
		{
			$this->_url = $this->getBaseUrl().$url;
		}
	}

	/**
     * getUrl get the URL to be scrapped
     * 
     * @return string
     */
	public function getUrl()
	{
		return $this->_url;
	}

	/**
     * Extract json string from raw HTML
     * 
     * @return string
     */
	private function extractJson()
	{
		$html = $this->getHtml();

		$jsonToArray = array();

		if(!empty($html))
		{
			$delimiter = '#';
			$startTag = 'ReactDOM.render(Component({"breadcrumbs"';
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
				$validJson 		= str_replace('ReactDOM.render(Component({"breadcrumbs"', '{"breadcrumbs"', $invalidJson);
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
	private function scrapByJson()
	{
		$result = $this->extractJson();

		$skills = array();

		if(!empty($result) && isset($result['tutorialNavData']['contentModels']))
		{
			foreach ($result['tutorialNavData']['contentModels'] as $key => $cModel) {

				$skills[] = array(
					'ka_id' 			=> (isset($cModel['id'])) ? $cModel['id'] : NULL,
					'type' 				=> (isset($cModel['contentKind'])) ? $cModel['contentKind'] : NULL,
					'name' 				=> (isset($cModel['name'])) ? $cModel['name'] : NULL,
					'title' 			=> (isset($cModel['title'])) ? addslashes($cModel['title']) : NULL,
					'display_name' 		=> (isset($cModel['displayName'])) ? addslashes($cModel['displayName']) : NULL,
					'short_display_name' => (isset($cModel['shortDisplayName'])) ? addslashes($cModel['shortDisplayName']) : NULL,
					'pretty_display_name' => (isset($cModel['prettyDisplayName'])) ? addslashes($cModel['prettyDisplayName']) : NULL,
					'description' 		=> (isset($cModel['descriptionHtml'])) ? addslashes($cModel['descriptionHtml']) : NULL,
					'creation_date' 	=> (isset($cModel['creationDate'])) ? $cModel['creationDate'] : NULL,
					'date_added' 		=> (isset($cModel['dateAdded'])) ? $cModel['dateAdded'] : NULL,
					'ka_url' 			=> (isset($cModel['kaUrl'])) ? $cModel['kaUrl'] : NULL,
					'image_url' 		=> (isset($cModel['imageUrl'])) ? $cModel['imageUrl'] : NULL,
					'keywords' 			=> (isset($cModel['keywords'])) ? addslashes($cModel['keywords']) : NULL,
					'license_name' 		=> (isset($cModel['licenseName'])) ? $cModel['licenseName'] : NULL,
					'license_full_name' => (isset($cModel['licenseFullName'])) ? $cModel['licenseFullName'] : NULL,
					'license_url' 		=> (isset($cModel['licenseUrl'])) ? $cModel['licenseUrl'] : NULL,
					'license_logo_url' 	=> (isset($cModel['licenseLogoUrl'])) ? $cModel['licenseLogoUrl'] : NULL,
					'slug' 				=> (isset($cModel['slug'])) ? $cModel['slug'] : NULL,
					'node_slug' 		=> (isset($cModel['nodeSlug'])) ? $cModel['nodeSlug'] : NULL,
					'ka_relative_url' 	=> (isset($cModel['relativeUrl'])) ? $cModel['relativeUrl'] : NULL,
					'file_name' 		=> (isset($cModel['fileName'])) ? $cModel['fileName'] : NULL,
					'thumbnail_default' => (isset($cModel['thumbnailUrls']['default'])) ? $cModel['thumbnailUrls']['default'] : NULL,
					'thumbnail_filtered' => (isset($cModel['thumbnailUrls']['filtered'])) ? $cModel['thumbnailUrls']['filtered'] : NULL,
					'video_youtube_id' 	=> (isset($cModel['youtubeId'])) ? $cModel['youtubeId'] : NULL,
					'video_duration' 	=> (isset($cModel['duration'])) ? $cModel['duration'] : NULL,
					'video_download_size' => (isset($cModel['downloadSize'])) ? $cModel['downloadSize'] : NULL,
					'video_download_urls' => (isset($cModel['downloadUrls'])) ? json_encode($cModel['downloadUrls']) : NULL,
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
	private function scrap()
	{
		$htmlDom = $this->getHtmlDom();

		$topics = array();

		if(!empty($htmlDom))
		{
			if($htmlDom->find('div[class^=moduleDefault_]') !== NULL)
			{
				foreach ($htmlDom->find('div[class^=moduleDefault_]') as $module)
				{
					$topic = $module->find('a',0);
					if($topic !== NULL)
					{
						$topicName		= $topic->find('h3',0)->plaintext;
						$topicUrl 		= $topic->href;
						$icon 			= '';
						$description 	= '';
						if($module->find('img[class^=icon_]',0) !== NULL)
						{
							$icon = $module->find('img[class^=icon_]',0)->src;
						}
						if($module->find('div[class^="description_]',0) !== NULL)
						{
							$description = $module->find('div[class^="description_]',0)->plaintext;
						}
						$urlParts   = explode('/', $topicUrl);
                    	$slug       = end($urlParts);

						$topics[] = array(
							'title' 	=> $topicName,
							'slug' 		=> $slug,
							'ka_url'	=> $topicUrl,
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
	public function runScrapper($callback)
	{
		$this->setHtmlDom();
		$skills = $this->scrapByJson();
		$callback($skills);
	}
}