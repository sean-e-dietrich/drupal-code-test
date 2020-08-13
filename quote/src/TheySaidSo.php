<?php

namespace Drupal\quote;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class TheySaidSo.
 *
 * Provides a service to interact with "They Said So" service.
 *
 * @package Drupal\quote
 */
class TheySaidSo {

  /**
   * The HTTP Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The Cache Backend Service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The URL to use for rest api services.
   *
   * @var string
   */
  private static $url = "http://quotes.rest";

  /**
   * The Quote of the Day end point.
   *
   * @var string
   */
  private static $qod = '/qod.json';

  /**
   * The Categories end point.
   *
   * @var string
   */
  private static $categories = '/qod/categories.json';

  /**
   * The amount of cache time for item.
   *
   * @var int
   */
  protected $cacheTime = 360;

  /**
   * The API Key to use for services.
   *
   * @var string
   */
  protected $apiKey = '';

  /**
   * The categories to search for.
   *
   * @var array
   */
  protected $cats = [];

  /**
   * TheySaidSo Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory Service.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP Client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The Cache Service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $client, CacheBackendInterface $cacheBackend = NULL) {
    $config = $configFactory->get('quote.settings');
    $this->apiKey = $config->get('api_key');
    $this->cats = $config->get('categories');
    $this->client = $client;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * Make Requests to service.
   *
   * @param string $url
   *   The URL to make requests against.
   * @param array $data
   *   The Query Parameters to tack on.
   * @param string $type
   *   The type of request to make.
   *
   * @return mixed
   *   The data from the request.
   */
  private function makeRequest($url, array $data = [], $type = 'GET') {
    $cid = "quote::" . $url . '::' . http_build_query($data);

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }
    else {
      try {
        $header = [];
        if (!empty($this->apiKey)) {
          $header['X-TheySaidSo-Api-Secret'] = $this->apiKey;
        }
        $request = $this->client->request($type, self::$url . $url, [
          'query' => $data,
          'headers' => $header,
        ]);
        $response = $request->getBody();
        $data = Json::decode($response);
        if (isset($data['contents'])) {
          $expire = time() + $this->cacheTime;
          $this->cacheBackend->set($cid, $data['contents'], $expire);
          return $data['contents'];
        }
      }
      catch (ClientException $exception) {
      }
      return [];
    }
  }

  /**
   * Return an array of the categories.
   *
   * @return array
   *   The Categories from service.
   */
  public function getCategories() {
    $data = $this->makeRequest(self::$categories);
    if (isset($data['categories'])) {
      return $data['categories'];
    }
    return [];
  }

  /**
   * Return the Quote of the Day by Category.
   *
   * @param string $category
   *   The Category to Search for.
   *
   * @return array
   *   Return the Quote Array.
   */
  public function getQuoteOfTheDay($category = '') {
    $catData = (empty($category) ? [] : ['category' => $category]);
    $data = $this->makeRequest(self::$qod, $catData);
    if (isset($data['quotes'])) {
      $quote = array_shift($data['quotes']);
      return ($quote !== FALSE ? $quote : []);
    }
    return [];
  }

  /**
   * Return a random category that is selected.
   *
   * @return array
   *   Return the Quote by selected categories.
   */
  public function getQuoteOfTheDayByRandomCategory() {
    $category = "";
    if (count($this->cats) > 1) {
      $index = array_rand($this->cats);
      $category = $this->cats[$index];
    }
    elseif (count($this->cats) == 1) {
      $items = $this->cats;
      $category = array_shift($items);
    }
    return $this->getQuoteOfTheDay($category);
  }

}
