<?php

namespace Kacademy\Scrappers;

use Sunra\PhpSimple\HtmlDomParser;
use Kacademy\Http\HttpClient;
use Kacademy\Utils\Logger;

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
    const BASE_API_URL = 'https://www.khanacademy.org/api/internal/';

    /**
     * HTML DOM string to be parsed by simple_html_dom
     * @var $htmlDom
     */
    protected $htmlDom;
    
    /**
     * HTTP Client
     * @var obj 
     */
    protected $httpClient;

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct() {
        $loggerObj  = new Logger();
        $this->httpClient     = new HttpClient($loggerObj->getLogger());
    }
    
    protected function getHttpClient() {
        return $this->httpClient;
    }

    /**
     * Returns Base URL
     *
     * @return void
     */
    public function getBaseUrl() {
        return self::BASE_URL;
    }

    /**
     * Returns Base API URL
     *
     * @return void
     */
    public function getBaseApiUrl() {
        return self::BASE_API_URL;
    }

    /**
     * Returns Raw HTML
     *
     * @return string
     */
    public function getHtml() {
        $client     = $this->getHttpClient();
        $result = $client->makeGetRequest($this->getUrl());
        return $result;
    }

    /**
     * setHtmlDom Sets the raw HTML to be parsed by parser
     * 
     * @return void
     */
    protected function setHtmlDom() {
        $html = $this->getHtml();
        if(is_string($html)) {
            $htmlDom = HtmlDomParser::str_get_html($html);
        } else {
            
        }
        $this->htmlDom = '';
    }

    /**
     * getHtmlDom gets the html dom string
     * 
     * @return void
     */
    protected function getHtmlDom() {
        return $this->htmlDom;
    }
    
    /**
     * Check if value is null or not
     * 
     * @param mixed $value
     * @return mixed
     */
    protected function mayNull($value) {
        return (isset($value) && !empty($value)) ? $value : NULL;
    }
    
    /**
     * Check key is set in array or not
     * 
     * @param mixed $array
     * @param mixed $key
     * @return mixed
     */
    protected function arrayKeySetAndNull($array, $key) {
        return (array_key_exists($key, $array) && isset($array[$key]) && !empty($array[$key])) ? $array[$key] : NULL;
    }
    
    /**
     * Clean the HTML
     * @param string $string
     * @return string
     */
    protected function cleanHtml($string) {
        return addslashes(htmlentities($string));
    }

}
