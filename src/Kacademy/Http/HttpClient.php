<?php

namespace Kacademy\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Log\LoggerInterface;

class HttpClient {

    const MAX_RETRIES = 4;

    protected $client;
    
    protected $logger;

    /**
     * @param LoggerInterface $logger
     * @return Client
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $stack = HandlerStack::create(new CurlHandler());
        $stack->push(\GuzzleHttp\Middleware::retry($this->createRetryHandler($logger)));
        $client = new Client([
            'handler' => $stack,
            'http_errors' => true,
            'verify' => false
        ]);
        $this->client = $client;
        return $this;
    }
    
    private function makeRequest($type, $url) {
        
        $date       = date('Y-m-d H:i:s');
        $response   = NULL;
        $logMessage = PHP_EOL."============{$date}===========".PHP_EOL;

        try {
            $requestResponse = $this->client->request($type, $url);
            $response = $requestResponse->getBody();
            
            $logMessage .= "Success Request".PHP_EOL;
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            
            $request = $e->getRequest();
            $response = $e->getResponse();
            $logMessage .= "Client Exception Occured At The Following URL".PHP_EOL;
            $logMessage .= "URL :::".$request->getUri()->__toString().PHP_EOL;
            $logMessage .= "Status Code :::".$response->getStatusCode().PHP_EOL;
            $logMessage .= "Actual Message :::".$e->getMessage();
            
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            
            $request = $e->getRequest();
            $response = $e->getResponse();
            $logMessage .= "Server Exception Occured".PHP_EOL;
            $logMessage .= "URL :::".$request->getUri()->__toString().PHP_EOL;
            $logMessage .= "Status Code :::".$response->getStatusCode().PHP_EOL;
            $logMessage .= "Actual Message :::".$e->getMessage();
            
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            
            $request = $e->getRequest();
            $response = $e->getResponse();
            $logMessage .= "Bad Response Exception Occured".PHP_EOL;
            $logMessage .= "URL :::".$request->getUri()->__toString().PHP_EOL;
            $logMessage .= "Status Code :::".$response->getStatusCode().PHP_EOL;
            $logMessage .= "Actual Message :::".$e->getMessage();
            
        } catch (Exception $e) {
            $logMessage .= "Request Could Not Be Completed For Unknown Reason".PHP_EOL;
        }

        if($response == NULL) {
            $this->logger->error($logMessage);
        } else {
            $this->logger->info($logMessage);
        }
        
        
        return $response;
    }

    /**
     * Make GET Request
     * @param string $url
     * @return mixed
     */
    public function makeGetRequest($url) {
        return $this->makeRequest('GET', $url);
    }

    /**
     * Retry handler
     * @param LoggerInterface $logger
     * @return type
     */
    private function createRetryHandler(LoggerInterface $logger) {
        return function (
                $retries,
                Psr7Request $request,
                Psr7Response $response = null,
                RequestException $exception = null
                ) use ($logger) {
            if ($retries >= self::MAX_RETRIES) {
                return false;
            }
            if (!($this->isServerError($response) || $this->isConnectError($exception))) {
                return false;
            }
            $logger->warning(sprintf(
                            'Retrying %s %s %s/%s, %s', $request->getMethod(), $request->getUri(), $retries + 1, self::MAX_RETRIES, $response ? 'status code: ' . $response->getStatusCode() : $exception->getMessage()
                    ), [$request->getHeader('Host')[0]]);
            return true;
        };
    }

    /**
     * @param Psr7Response $response
     * @return bool
     */
    private function isServerError(Psr7Response $response = null) {
        return $response && $response->getStatusCode() >= 500;
    }

    /**
     * @param RequestException $exception
     * @return bool
     */
    private function isConnectError(RequestException $exception = null) {
        return $exception instanceof ConnectException;
    }

}
