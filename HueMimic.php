<?php

class HueMimic {
  private $hubApi = '172.19.100.102';
  private $apiKey = '1de194bb5c60fbf527ba829d18615f2';
  
  private $apiKeys = [];
  
  function __construct() {
    $response = json_decode(file_get_contents('http://' . $this->hubApi . '/api/' . $this->apiKey));
    $this->apiKeys = array_keys(get_object_vars($response->config->whitelist));
  }
  
  protected function CheckApi(string $apiKey): bool {
    return in_array($apiKey, $this->apiKeys);
  }
}
