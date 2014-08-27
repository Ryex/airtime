<?php

class Cache
{
	private function createCacheKey($key, $isUserValue, $userId = null) {

		$CC_CONFIG = Config::getConfig();
		$a = $CC_CONFIG["apiKey"][0];

		if ($isUserValue) {
			$cacheKey = "{$key}{$userId}{$a}";
		}
		else {
			$cacheKey = "{$key}{$a}";
		}

		return $cacheKey;
	}

	private static function getMemcached() {

	    $CC_CONFIG = Config::getConfig();

	    $memcached = new Memcached();
	    //$server is in the format "host:port"
	    foreach($CC_CONFIG['memcached']['servers'] as $server) {

	        list($host, $port) = explode(":", $server);
	        $memcached->addServer($host, $port);
	    }

	    return $memcached;
	}

	public function store($key, $value, $isUserValue, $userId = null) {

		//$cacheKey = self::createCacheKey($key, $userId);
		return false; ///apc_store($cacheKey, $value);
	}

	public function fetch($key, $isUserValue, $userId = null) {

		//$cacheKey = self::createCacheKey($key, $isUserValue, $userId);
		return false; //apc_fetch($cacheKey);
	}
}
