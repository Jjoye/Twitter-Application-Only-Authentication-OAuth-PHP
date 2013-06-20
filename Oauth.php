<?php

// Defines twitter API constants
define(TW_PROTOCOL, 'https://');
define(TW_HOST, 'api.twitter.com');
define(TW_VERSION, '1.1');
define(TW_BASEURL, TW_PROTOCOL . TW_HOST .'/'. TW_VERSION);

/**
 * OauthTwitter Class
 * Used for authenticate your application and run request to Twitter API
 *
 *  Usage :
 *    // Get the Object authenticated
 *    $obj = new OauthTwitter($consumer_key, $consumer_secret, $app_name);
 *
 *    // Get Account Tweets
 *    $obj->getAccountStatuses($account);
 *
 *    // Get Account Infos (Followers count..)
 *    $obj->getAccountInfos($account);
 *
 *    // Get the Results for a term
 *    $obj->getSearchResults($term);
 */
class OauthTwitter {

  private $_app_name;
  private $_consumer_key;
  private $_consumer_secret;

  private $_consumer_token;
  private $_bearer_token;

  /**
   * On object construction, populate private variables with consumer params
   */
  function __construct($consumer_key, $consumer_secret, $app_name = 'Twitter Application-only OAuth App v.1')
  {
    // Defines client params
    $this->_consumer_key = $consumer_key;
    $this->_consumer_secret  = $consumer_secret;
    $this->_app_name = $app_name;

    // Encode the consumer token for authentication
    $this->_setConsumerToken();

    // Define Bearer token for requests
    $this->_setBearerToken();
  }

  /**
   * On object destruction, we invalidate the Bearer Token
   */
  function __destruct()
  {
    $this->_invalidateBearerToken();
  }

  /**
   * Set the Consumer Token.
   * Used to get, or invalidate the Bearer Token
   */
  private function _setConsumerToken()
  {
    // Url encode the consumer_key and consumer_secret in accordance with RFC 1738
    $encoded_consumer_key = urlencode($this->_consumer_key);
    $encoded_consumer_secret = urlencode($this->_consumer_secret);

    $this->_consumer_token = base64_encode($encoded_consumer_key .':'. $encoded_consumer_secret);
  }

  /**
   * Get and set the Bearer Token.
   * Authenticates the Application with oauth2
   */
  private function _setBearerToken()
  {
    $endpoint = '/oauth2/token';
    $url = TW_PROTOCOL . TW_HOST . $endpoint;
    $posted_data = 'grant_type=client_credentials';
    $headers = array(
      'POST '. $endpoint .' HTTP/1.1',
      'Host: '. TW_HOST,
      'User-Agent: '. $this->_app_name,
      'Authorization: Basic '. $this->_consumer_token,
      'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
      'Content-Length: '. strlen($posted_data),
    );
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posted_data);
    $header = curl_setopt($ch, CURLOPT_HEADER, 1);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    try
    {
      $res = curl_exec($ch);
      curl_close($ch);
      $bearer_token = json_decode(end(explode("\n", $res)));
      $this->_bearer_token = $bearer_token->access_token;
    }
    catch (HttpException $ex) {
      throw new Exception('Could not retrieve data from Twitter. '. $ex);
    }
  }

  /**
   * Invalidates the Bearer Token
   * Should the bearer token become compromised or need to be invalidated for any reason,
   * Call this method.
   */
  private function _invalidateBearerToken()
  {
    $endpoint = '/oauth2/invalidate_token';
    $url = TW_PROTOCOL . TW_HOST . $endpoint;
    $posted_data = 'access_token='. $this->_bearer_token;
    $headers = array(
      'POST '. $endpoint .' HTTP/1.1',
      'Host: '. TW_HOST,
      'User-Agent: '. $this->_app_name,
      'Authorization: Basic '. $this->_consumer_token,
      'Accept: */*',
      'Content-Type: application/x-www-form-urlencoded',
      'Content-Length: '. strlen($posted_data)
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posted_data);
    $header = curl_setopt($ch, CURLOPT_HEADER, 1);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    try {
      $return = curl_exec($ch);
      curl_close($ch);
    }
    catch (HttpException $ex) {
      throw new Exception('Could not retrieve data from Twitter. '. $ex);
    }

    return $return;
  }

  /**
   * Send Basic GET Requests to the Twitter API
   */
  private function _getHttpRequest($endpoint, $data)
  {
    $headers = array(
      'GET /'. TW_VERSION . $endpoint .' HTTP/1.1',
      'Host: '. TW_HOST,
      'User-Agent:'. $this->_app_name,
      'Authorization: Bearer '. $this->_bearer_token,
    );

    $ch = curl_init(TW_BASEURL . $endpoint . $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $header = curl_setopt($ch, CURLOPT_HEADER, 1);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    try {
      $return = curl_exec($ch);
      curl_close($ch);
      $data = end(explode("\n", $return));
    }
    catch (HttpException $ex) {
      throw new Exception('Could not retrieve data from Twitter. '. $ex);
    }

    return json_decode($data);
  }

  /**
   * Search
   * Basic Tweets Search of the Search API
   * Based on https://dev.twitter.com/docs/api/1.1/get/search/tweets
   */
  public function getSearchResults($query, $result_type = 'mixed', $count = 5, $entities = TRUE)
  {
    $entities = $entities ? 'true' : 'false';
    $endpoint = '/search/tweets.json';
    $data = '?q='. $query .'&result_type='. $result_type .'&count='. $count .'&include_entities='. $entities;

    return $this->_getHttpRequest($endpoint, $data);
  }

  /**
   * User Timeline
   * Basic User Timeline of the Statuses API
   * Based on https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
   */
  public function getAccountStatuses($screen_name, $result_type = 'recent', $count = 2)
  {
    $endpoint = '/statuses/user_timeline.json';
    $data = '?screen_name='. ltrim($screen_name, '@') .'&count='. $count .'&result_type='. $result_type .'&rpp='. $count .'&include_entities=true';

    return $this->_getHttpRequest($endpoint, $data);
  }

  /**
   * User Informations
   * Basic User of the Users API
   * Based on https://dev.twitter.com/docs/api/1.1/get/users/show
   */
  public function getAccountInfos($screen_name)
  {
    $endpoint = '/users/show.json'; // base url
    $data = '?screen_name='. ltrim($screen_name, '@');

    return $this->_getHttpRequest($endpoint, $data);
  }

}
