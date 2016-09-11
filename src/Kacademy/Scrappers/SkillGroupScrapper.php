<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class SkillGroupScrapper extends BaseScrapper {

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

		$skillGroups = array();

		if(!empty($htmlDom))
		{
			if($htmlDom->find('div[class^="module_]') !== NULL)
			{
				foreach ($htmlDom->find('div[class^="module_]') as $module)
				{
					$skillGroup = $module->find('h2',0);
					if($skillGroup !== NULL && $skillGroup->find('a', 0) !== NULL)
					{
						$skillGroup		= $skillGroup->find('a', 0);
						$title 			= $skillGroup->plaintext;
						$skillGroupUrl 	= $skillGroup->href;
						$urlParts   	= explode('/', $skillGroupUrl);
                    	$slug       	= end($urlParts);

						$skillGroups[] = array(
							'title' 	=> $title,
							'slug' 		=> $slug,
							'ka_url'	=> $skillGroupUrl
						);
					}
				}
			}
		}
			return $skillGroups;
	}

	/**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
	public function runScrapper($callback)
	{
		$skillGroups = $this->scrap();
		print_r($skillGroups);die;
		$callback($skillGroups);
	}
}