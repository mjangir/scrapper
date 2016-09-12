<?php

namespace Kacademy\Scrappers;

use Sunra\PhpSimple\HtmlDomParser;

class BaseScrapper {

	/**
     * Site Base URL for the scrappers
     * @var BASE_URL
     */
	const BASE_URL = 'https://www.khanacademy.org/';

	/**
     * Site Base Internal API URL for the scrappers
     * @var BASE_API_URL
     */
	const BASE_API_URL = self::BASE_URL + 'api/internal';

	/**
     * HTML DOM string to be parsed by simple_html_dom
     * @var $htmlDom
     */
	protected $htmlDom;

	/**
     * Class constructor
     *
     * @return void
     */
	public function __construct()
	{
	}

	/**
     * Returns Base URL
     *
     * @return void
     */
	public function getBaseUrl()
	{
		return self::BASE_URL;
	}

	/**
     * Returns Base API URL
     *
     * @return void
     */
	public function getBaseApiUrl()
	{
		return self::BASE_API_URL;
	}

	/**
     * Returns Raw HTML
     *
     * @return string
     */
	public function getHtml()
	{
		try {
			$client   = new \GuzzleHttp\Client(array( 'curl' => array( CURLOPT_SSL_VERIFYPEER => false)));
			$response = $client->request('GET', $this->getUrl());
			return $response->getBody();
		} catch (Exception $e) {
			return NULL;
		}
		
	}

	/**
     * setHtmlDom Sets the raw HTML to be parsed by parser
     * 
     * @return void
     */
	protected function setHtmlDom()
	{
		$html = $this->getHtml();
		$htmlDom = HtmlDomParser::str_get_html($html);
		$this->htmlDom = $htmlDom;
	}

	/**
     * getHtmlDom gets the html dom string
     * 
     * @return void
     */
	protected function getHtmlDom()
	{
		return $this->htmlDom;
	}
}