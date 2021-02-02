<?php

/*
 * A PHP class for EAN and ISBN name lookup and validation using the API on ean-search.org
 *
 * (c) Jan Willamowius, 2017
 *     Relaxed Communications GmbH, 2017
 *     https://www.ean-search.org/ean-database-api.html
 *
 */

class EANSearch {
	private $accessToken;

	function __construct($accessToken) {
		$this->accessToken = $accessToken;
	}

	function barcodeLookup($ean, $lang = 1) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-lookup&token=$this->accessToken&ean=$ean&language=$lang");
		$response = new SimpleXMLElement($xml);
		return $response->product->name;
	}

	function barcodeSearch($ean, $lang = 1) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-lookup&token=$this->accessToken&ean=$ean&language=$lang");
		$response = new SimpleXMLElement($xml);
		return $response->product;
	}

	function barcodePrefixSearch($prefix, $page = 0) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-prefix-search&token=$this->accessToken&prefix=$prefix&page=$page");
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	function productSearch($name, $page = 0) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=product-search&token=$this->accessToken&name=$name&page=$page");
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	function barcodeImage($ean) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=barcode-image&token=$this->accessToken&ean=$ean");
		$response = new SimpleXMLElement($xml);
		return base64_decode($response->product->barcode);
	}

	function verifyChecksum($ean) {
		$xml = file_get_contents("https://api.ean-search.org/api?"
			. "op=verify-checksum&token=$this->accessToken&ean=$ean");
		$response = new SimpleXMLElement($xml);
		return $response->product->valid;
	}

}

