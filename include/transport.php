<?php

if (!function_exists('curl_init')) {
    throw new GS_ADMIN_SDK_Exception("GoSquared SDK requires cURL to be installed in order to run", 100);
}

define('GS_ADMIN_SDK_TRANSPORT_INVALID_RESPONSE', 1);

class GS_ADMIN_SDK_Transport_Exception extends Exception {
    
}

;

class GS_ADMIN_SDK_Transport extends GS_ADMIN_SDK_core {

    private $request;
    public $raw_response;

    function __construct($request) {
        $this->request = $request;
    }

    public function exec() {
        $this->ch = curl_init();
        if (GS_ADMIN_SDK_API_ENV != 'production' || !preg_match('/(www\.)?gosquared\.com/i', $this->request->host)) { // If we're not conecting to gosquared.com turn off SSL peer verification
            //$this->request->scheme = "http";
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
        $post = $this->request->get_post();
        $post = $this->build_post($post);
        curl_setopt($this->ch, CURLOPT_POST, count($post));
        $url = $this->build_url($this->sign_post($post));
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($this->ch, CURLOPT_URL, $url);
//        if (GS_ADMIN_SDK_DEBUG) {
//            curl_setopt($this->ch, CURLOPT_VERBOSE, true);
//        }
        $resp = curl_exec($this->ch);
        $info = curl_getinfo($this->ch);
        if($resp === false){
            // cURL error
            throw new GS_ADMIN_SDK_Transport_Exception(curl_error($this->ch), curl_errno($this->ch));
        }
        elseif(!$resp){
          // Empty response
          throw new GS_ADMIN_SDK_Transport_Exception("Empty response from API endpoint. cURL info:\r\n".print_r($info, true), GS_ADMIN_SDK_TRANSPORT_INVALID_RESPONSE);
        }
        $this->raw_response = $resp;
        $dumpres = var_export($this->raw_response, true);
        $str = "\r\n\r\nRAW RESPONSE: $dumpres";
        $this->debug($str);
        return $this->process_response($resp);
    }

    private function build_url($signed) {
        $base = $this->request->scheme . "://" . $this->request->host . $this->request->endpoint . '?';
        $params = array(
            'format' => 'json'
            , 'api_key_hash' => sha1($this->request->api_key)
            , 'env' => GS_ADMIN_SDK_API_ENV
            , 'mode' => $this->request->mode
            , 'sign' => $signed
            , 'version' => $this->request->api_version
        );
        $query = http_build_query($params);
        $base .= $query;

        $this->debug("URL requested:\r\n$base");

        return $base;
    }

    private function build_post($post) {
        $post['timecode'] = gmdate('Y-m-d\TH:i:s\Z');
        $post = http_build_query($post);

        $this->debug("Raw POST: $post");

        return $post;
    }

    private function sign_post($post) {
        return hash_hmac('sha1', $post, $this->request->api_key);
    }

    private function process_response($data) {
        $decode = json_decode($data, true);
        if (!$decode) {
            throw new GS_ADMIN_SDK_Transport_Exception('Could not decode response; invalid format', GS_ADMIN_SDK_TRANSPORT_INVALID_RESPONSE);
        }
        return $this->request->parse_response($decode);
    }

}

?>
