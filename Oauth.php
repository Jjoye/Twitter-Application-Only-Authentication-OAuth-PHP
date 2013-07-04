<?php

// Defines twitter API constants
define(TW_PROTOCOL, 'https://');
define(TW_HOST, 'api.twitter.com');
define(TW_VERSION, '1.1');
define(TW_BASEURL, TW_PROTOCOL . TW_HOST .'/'. TW_VERSION);

// Define Cache time to 5mn
define(APP_CACHE_TIME, 300);

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
    try {
      $this->_setBearerToken();
      return $this;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
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
    if ($request = $this->_sendAuthRequest('/oauth2/token', 'grant_type=client_credentials')) {
      $data = json_decode(end(explode("\n", $request)));
      if (isset($data->errors[0]->message)) {
        throw new Exception($data->errors[0]->message);
        return FALSE;
      }
      $this->_bearer_token = $data->access_token;
    }
    else {
      return FALSE;
    }
}

  /**
   * Invalidates the Bearer Token
   * Should the bearer token become compromised or need to be invalidated for any reason,
   * Call this method.
   */
  private function _invalidateBearerToken()
  {
    return $this->_sendAuthRequest('/oauth2/invalidate_token', 'access_token='. $this->_bearer_token, FALSE);
  }

  /**
   * Send Oauth Requests to set or invalidate bearer token
   * @see _setBearerToken() and _invalidateBearerToken()
   */
  private function _sendAuthRequest($endpoint, $data, $errors = TRUE) {
    $url = TW_PROTOCOL . TW_HOST . $endpoint;
    $headers = array(
      'POST '. $endpoint .' HTTP/1.1',
      'Host: '. TW_HOST,
      'User-Agent: '. $this->_app_name,
      'Authorization: Basic '. $this->_consumer_token,
      'Accept: */*',
      'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
      'Content-Length: '. strlen($data),
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $header = curl_setopt($ch, CURLOPT_HEADER, 1);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $return = curl_exec($ch);
    if ($return === FALSE) {
      if ($errors) {
        throw new Exception('Request Failed: '. curl_error($ch));
      }
      return FALSE;
    }
    curl_close($ch);

    return $return;
  }

  /**
   * Send Basic GET Requests to the Twitter API
   */
<<<<<<< HEAD
  private function _sendHttpRequest($endpoint, $data)
=======
  private function _getHttpRequest($endpoint, $params)
>>>>>>> dev
  {
    if ($cached_data = $this->_getCachedData($endpoint, $params)) {
      return $cached_data;
    }
    $headers = array(
      'GET /'. TW_VERSION . $endpoint . $params .' HTTP/1.1',
      'Host: '. TW_HOST,
      'User-Agent:'. $this->_app_name,
      'Authorization: Bearer '. $this->_bearer_token,
    );

    $ch = curl_init(TW_BASEURL . $endpoint . $params);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $return = curl_exec($ch);

    if ($return === FALSE) {
      throw new Exception(curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    $data = json_decode(end(explode("\n", $return)));
    if (isset($data->errors[0]->message)) {
      throw new Exception($data->errors[0]->message);
    }

    $this->_setCachedData($endpoint, $params, $data);
    return $data;
  }

  /**
   * Get the session-stored data if it exists and is not expired
   */
  private function _getCachedData($endpoint, $params) {
    $cache_name = $this->_getCacheName($endpoint, $params);
    if (isset($_SESSION[$cache_name]) && $_SESSION[$cache_name]['expires'] >= time()) {
      return unserialize($_SESSION[$cache_name]['data']);
    }
    return FALSE;
  }

  /**
   * Store data in session to avoid requests spam
   */
  private function _setCachedData($endpoint, $params, $data) {
    $cache_name = $this->_getCacheName($endpoint, $params);
    unset($_SESSION[$cache_name]);
    $_SESSION[$cache_name] = array(
      'data' => serialize($data),
      'expires' => time() + APP_CACHE_TIME
    );
  }

  /**
   * Get the cache name to identify a cache data
   */
  private function _getCacheName($endpoint, $params) {
    return md5($endpoint . $params);
  }

  /**
   * Search
   * Basic Tweets Search of the Search API
   * Based on https://dev.twitter.com/docs/api/1.1/get/search/tweets
   */
  public function getSearchResults($query, $result_type = 'recent', $count = '5', $entities = TRUE)
  {
    $entities = $entities ? 'true' : 'false';
    $endpoint = '/search/tweets.json';
    $data = '?q='. urlencode(trim($query)) .'&result_type='. $result_type .'&count='. $count .'&include_entities='. $entities;

    try {
      return $this->_sendHttpRequest($endpoint, $data)->statuses;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * User Timeline
   * Basic User Timeline of the Statuses API
   * Based on https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
   */
  public function getAccountStatuses($screen_name, $result_type = 'recent', $count = 5, $entities = TRUE)
  {
    $entities = $entities ? 'true' : 'false';
    $endpoint = '/statuses/user_timeline.json';
    $data = '?screen_name='. urlencode(ltrim($screen_name, '@')) .'&result_type='. $result_type .'&count='. $count .'&include_entities='. $entities;

    try {
      return $this->_sendHttpRequest($endpoint, $data);
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * User Informations
   * Basic User of the Users API
   * Based on https://dev.twitter.com/docs/api/1.1/get/users/show
   */
  public function getAccountInfos($screen_name)
  {
    $endpoint = '/users/show.json'; // base url
    $data = '?screen_name='. urlencode(ltrim($screen_name, '@'));

    try {
      return $this->_sendHttpRequest($endpoint, $data);
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

}
