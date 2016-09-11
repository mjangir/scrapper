<?php

namespace Kacademy\Scrappers;

use Kacademy\Scrappers\BaseScrapper;

class SubjectScrapper extends BaseScrapper {

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

	private function scrap()
	{
		$this->setHtmlDom();

		$htmlDom = $this->getHtmlDom();

		$subjects = array();

		if(!empty($htmlDom))
		{
			if($htmlDom->find('ul[class^=domains_]',0) !== NULL)
			{
				foreach ($htmlDom->find('ul[class^=domains_]',0)->find('li') as $li)
				{
					$header = $li->find('header', 0);

					if($header !== NULL)
					{
						$anchor					= $header->find('a',0);
						$subjectLink			= $anchor->href;
						$subjectName 			= $anchor->plaintext;
						$subjects[$subjectName] = array(
							'subject_name' => $subjectName,
							'url' => $subjectLink,
						);
					}

					if($li->find('ul',0) !== NULL)
					{
						foreach ($li->find('ul',0)->find('li') as $childSubjectList) {
							$childAnchor			= $childSubjectList->find('a',0);
							if($childAnchor !== NULL)
							{
								$childSubjectLink		= $childAnchor->href;
								$childSubjectName 		= $childAnchor->plaintext;
								$subjects[$subjectName]['children'][] = array(
									'subject_name' => $childSubjectName,
									'url' => $childSubjectLink
								);
							}
						}
					}
				}
			}
		}
		return $subjects;
	}

	/**
     * runScrapper starts scrapping the URL
     * 
     * @return void
     */
	public function runScrapper($callback)
	{
		$subjects = $this->scrap();
		$callback($subjects);
	}
}