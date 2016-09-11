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
     * scrap Actual scrapper function
     * 
     * @return void
     */
	private function scrap()
	{
		$this->setHtmlDom();

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
		$topics = $this->scrap();
		$callback($topics);
	}
}