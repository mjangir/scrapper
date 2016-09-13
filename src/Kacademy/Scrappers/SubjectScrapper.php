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
			$startTag = 'ReactDOM.render(Component({"domains"';
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
				$validJson 		= str_replace('ReactDOM.render(Component({"domains"', '{"domains"', $invalidJson);
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

		$subjects = array();

		if(!empty($result) && isset($result['domains']))
		{
			foreach ($result['domains'] as $subject) {

				$title 	= $subject['translatedTitle'];
				$kaUrl 	= $subject['href'];
				$slug 	= $subject['identifier'];

				$subjects[$title] 	= array(
					'title' => htmlentities($title),
					'ka_url'=> $kaUrl,
					'slug'	=> $slug
				);

				if(isset($subject['children']))
				{
					foreach ($subject['children'] as $child) {
						
						$titleC 	= $child['translatedTitle'];
						$kaUrlC 	= $child['href'];
						$slugC 		= $child['identifier'];

						$subjects[$title]['children'][] = array(
							'title' => $titleC,
							'ka_url'=> $kaUrlC,
							'slug'	=> $slugC
						);
					}
				}
			}
		}

		return $subjects;
	}

	/**
     * Scrap data using simple html dom
     * 
     * @return array
     */
	private function scrap()
	{
		$htmlDom = $this->getHtmlDom();

		$subjects = array();

		if(!empty($htmlDom))
		{
			if($htmlDom->find('ul[class^=domains_]',0) !== NULL)
			{
				$i = 0;
				foreach ($htmlDom->find('ul[class^=domains_]',0)->find('li') as $li)
				{
					$header = $li->find('header', $i);

					if($header !== NULL)
					{
						$anchor				= $header->find('a',0);
						$kaUrl				= $anchor->href;
						$title 				= $anchor->plaintext;
						$urlParts   		= explode('/', $kaUrl);
                    	$slug       		= end($urlParts);
						$subjects[$title] 	= array(
							'title' => $title,
							'ka_url'=> $kaUrl,
							'slug'	=> $slug
						);
					}

					if($li->find('ul',0) !== NULL)
					{
						foreach ($li->find('ul',0)->find('li') as $childSubjectList) {
							$childAnchor			= $childSubjectList->find('a',0);
							if($childAnchor !== NULL)
							{
								$childKaUrl			= $childAnchor->href;
								$childTitle 		= $childAnchor->plaintext;
								$childUrlParts   	= explode('/', $childKaUrl);
                    			$childSlug       	= end($childUrlParts);
								$subjects[$title]['children'][] = array(
									'title'  => $childTitle,
									'ka_url' => $childKaUrl,
									'slug'	 => $childSlug
								);
							}
						}
					}

					$i++;
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
		$this->setHtmlDom();
		$subjects = $this->scrapByJson();
		$callback($subjects);
	}
}