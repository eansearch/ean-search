<?php

/*
 * A PHP class for EAN and ISBN name lookup and validation using the API on ean-search.org
 *
 * (c) Jan Willamowius
 *     Relaxed Communications GmbH, 2017 - 2021
 *     https://www.ean-search.org/ean-database-api.html
 *
 */

class EANSearch {
	private $accessToken;
	private $ctx; # stream context with connect timeout setting

	function __construct($accessToken) {
		$this->accessToken = $accessToken;
		$ctx = stream_context_create(array('http' => array('timeout' => 180)));
		ini_set('default_socket_timeout', 180);
	}

	function barcodeLookup($ean, $lang = 1) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-lookup&token=$this->accessToken&ean=$ean&language=$lang", false, $this->ctx);
		if ($xml === FALSE) {
			return '';
		}
		$response = new SimpleXMLElement($xml);
		return $response->product->name;
	}

	function barcodeSearch($ean, $lang = 1) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-lookup&token=$this->accessToken&ean=$ean&language=$lang", false, $this->ctx);
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->product;
	}

	function barcodePrefixSearch($prefix, $page = 0) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-prefix-search&token=$this->accessToken&prefix=$prefix&page=$page", false, $this->ctx);
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	function productSearch($name, $page = 0) {
		$name = urlencode($name);
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=product-search&token=$this->accessToken&name=$name&page=$page", false, $this->ctx);
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	function barcodeImage($ean) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-image&token=$this->accessToken&ean=$ean", false, $this->ctx);
		$response = new SimpleXMLElement($xml);
		return base64_decode($response->product->barcode);
	}

	function verifyChecksum($ean) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=verify-checksum&token=$this->accessToken&ean=$ean", false, $this->ctx);
		$response = new SimpleXMLElement($xml);
		return $response->product->valid;
	}

	function setTimout($sec) {
		$ctx = stream_context_create(array('http' => array('timeout' => $sec)));
		ini_set('default_socket_timeout', $sec);
	}

}

