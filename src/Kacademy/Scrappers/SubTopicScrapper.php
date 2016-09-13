<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class SubTopicScrapper extends BaseScrapper {

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

		$subTopics = array();

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
								&& $moduleData['kind'] == 'ContentList')
							{
								$title 			= htmlentities($moduleData['title']);
								$description 	= htmlentities($moduleData['description']);
		                    	$slug       	= $moduleData['slug'];
		                    	$kaUrl 			= '';

		                    	if(isset($moduleData['contentItems'][0]['nodeUrl']))
		                    	{
									$kaUrl 		= $moduleData['contentItems'][0]['nodeUrl'];
		                    	}

		                    	$subTopics[] = array(
		                    		'title'			=> $title,
		                    		'description'	=> $description,
		                    		'slug'			=> $slug,
		                    		'ka_url' 		=> $kaUrl
		                    	);
							}
						}
					}
				}
			}
		}

		return $subTopics;
	}

	/**
     * Scrap data using simple html dom
     * 
     * @return array
     */
	private function scrap()
	{
		$htmlDom = $this->getHtmlDom();

		$subTopics = array();

		if(!empty($htmlDom))
		{
			if($htmlDom->find('div[class^="module_]') !== NULL)
			{
				foreach ($htmlDom->find('div[class^="module_]') as $module)
				{
					$subTopic = $module->find('h2',0);
					if($subTopic !== NULL && $subTopic->find('a', 0) !== NULL)
					{
						$subTopic		= $subTopic->find('a', 0);
						$title 			= $subTopic->plaintext;
						$subTopicUrl 	= $subTopic->href;
						$urlParts   	= explode('/', $subTopicUrl);
                    	$slug       	= end($urlParts);

						$subTopics[] = array(
							'title' 	=> $title,
							'slug' 		=> $slug,
							'ka_url'	=> $subTopicUrl
						);
					}
				}
			}
		}
			return $subTopics;
	}

	/**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
	public function runScrapper($callback)
	{
		$this->setHtmlDom();
		$subTopics = $this->scrapByJson();
		$callback($subTopics);
	}
}