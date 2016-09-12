<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class TopicScrapper extends BaseScrapper {

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

	/**
     * Scrap data from valid JSON
     * 
     * @return string
     */
	private function scrapByJson()
	{
		$result = $this->extractJson();

		$topics = array();

		if(!empty($result) && isset($result['curation']['tabs']))
		{
			$modules = array();
			
			foreach ($result['curation']['tabs'] as $key => $value) {

				if($key == 'modules')
				{
					$modules = $value;
					break;
				}
			}

			if(!empty($modules))
			{
				foreach ($modules as $key => $module) {

					if(is_array($module))
					{
						foreach ($module as $moduleData) {

							if(isset($moduleData['kind']) 
								&& $moduleData['kind'] == 'TableOfContentsRow')
							{
								$title 			= $moduleData['title'];
								$kaUrl 			= $moduleData['url'];
								$description 	= $moduleData['description'];
								$icon 			= $moduleData['icon'];
								$urlParts   	= explode('/', $kaUrl);
		                    	$slug       	= end($urlParts);

		                    	$topics[] = array(
		                    		'title'			=> $title,
		                    		'icon'			=> $icon,
		                    		'description'	=> $description,
		                    		'slug'			=> $slug,
		                    		'ka_url'		=> $kaUrl
		                    	);
							}
						}
					}
				}
			}
		}

		return $topics;
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
		$topics = $this->scrapByJson();
		$callback($topics);
	}
}