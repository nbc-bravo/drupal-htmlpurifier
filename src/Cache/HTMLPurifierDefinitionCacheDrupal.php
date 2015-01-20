<?php
/**
 * @file
 * Cache handler that stores all data in Drupal's built-in cache.
 */

namespace Drupal\htmlpurifier\Cache;

class HTMLPurifierDefinitionCacheDrupal extends \HTMLPurifier_DefinitionCache {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public function __construct($type) {
    parent::__construct($type);

    $this->cache = \Drupal::cache('htmlpurifier');
  }

  /**
   * Add an object to the cache without overwriting
   */
  function add($def, $config) {
    if (!$this->checkDefType($def)) {
      return;
    }
    $key = $this->generateKey($config);

    if ($this->fetchFromDrupalCache($key)) {
      // already cached
      return FALSE;
    }
    $this->storeInDrupalCache($def, $key);

    return TRUE;
  }

  /**
   * Unconditionally add an object to the cache, overwrites any existing object.
   */
  function set($def, $config) {
    if (!$this->checkDefType($def)) {
      return;
    }
    $key = $this->generateKey($config);

    $this->storeInDrupalCache($def, $key);

    return TRUE;
  }

  /**
   * Replace an object that already exists in the cache.
   */
  function replace($def, $config) {
    if (!$this->checkDefType($def)) {
      return;
    }
    $key = $this->generateKey($config);

    if (!$this->fetchFromDrupalCache($key)) {
      // object does not exist in cache
      return FALSE;
    }

    $this->storeInDrupalCache($def, $key);

    return TRUE;
  }

  /**
   * Retrieve an object from the cache
   */
  function get($config) {
    $key = $this->generateKey($config);

    return $this->fetchFromDrupalCache($key);
  }

  /**
   * Delete an object from the cache
   */
  function remove($config) {
    $key = $this->generateKey($config);
    $this->cache->delete($key);

    return TRUE;
  }

  function flush($config) {
    $this->cache->deleteAll();

    return TRUE;
  }

  function cleanup($config) {
    $this->cache->garbageCollection();
  }

  function fetchFromDrupalCache($key) {
    $cached = $this->cache->get($key);
    if ($cached) {
      return unserialize($cached->data);
    }
    return FALSE;
  }

  function storeInDrupalCache($def, $key) {
    $this->cache->set($key, serialize($def));
  }

}

