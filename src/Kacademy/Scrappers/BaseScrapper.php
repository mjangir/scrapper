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
        $this->httpClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
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

//        $client->getEventDispatcher()->addListener('request.error', function(Event $event) {   
//            
//            if ($event['response']->getStatusCode() != 200) {
//                $newRequest = $event['request']->clone();
//                $newResponse = $newRequest->send();
//                // Set the response object of the request without firing more events
//                $event['response'] = $newResponse;
//                // You can also change the response and fire the normal chain of
//                // events by calling $event['request']->setResponse($newResponse);
//                // Stop other events from firing when you override 401 responses
//                $event->stopPropagation();
//            
//            }            
//        });

        try {
            $response   = $client->request('GET', $this->getUrl());
            return $response->getBody();
        }
        catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $req = $e->getRequest();
            $resp =$e->getResponse();
            return NULL;
            //displayTest($req,$resp);
        }
        catch (Guzzle\Http\Exception\ServerErrorResponseException $e) {

            $req = $e->getRequest();
            $resp =$e->getResponse();
            return NULL;
            //displayTest($req,$resp);
        }
        catch (Guzzle\Http\Exception\BadResponseException $e) {

            $req = $e->getRequest();
            $resp =$e->getResponse();
            return NULL;
            //displayTest($req,$resp);
        }
        catch( Exception $e){
            return NULL;
        }
    }

    /**
     * setHtmlDom Sets the raw HTML to be parsed by parser
     * 
     * @return void
     */
    protected function setHtmlDom() {
        $html = $this->getHtml();
        $htmlDom = HtmlDomParser::str_get_html($html);
        $this->htmlDom = $htmlDom;
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
