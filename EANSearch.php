<?php

/*
 * A PHP class for EAN and ISBN name lookup and validation using the API on ean-search.org
 *
 * (c) Jan Willamowius
 *     Relaxed Communications GmbH, 2017 - 2026
 *     https://www.ean-search.org/ean-database-api.html
 *
 */

class EANSearch {
	private $accessToken;
	private $remaining = -1;
	private $ctx; # stream context with connect timeout setting
	private const BASE_URL = 'https://api.ean-search.org/api?format=xml';
	private const MAX_API_TRIES = 3;

	public function __construct($accessToken) {
		$this->accessToken = $accessToken;
		$this->setTimout(30);
	}

	/// look up one EAN / GTIN / UPC barcode
	/// only return the product name
	public function barcodeLookup($ean, $lang = 1) {
		$xml = $this->apiCall("op=barcode-lookup&ean=$ean&language=$lang");
		if ($xml === FALSE) {
			return '';
		}
		$response = new SimpleXMLElement($xml);
		return $response->product->name;
	}

	/// look up one EAN / GTIN / UPC / ISBN-13 barcode
	/// return all product data
	public function barcodeSearch($ean, $lang = 1) {
		$xml = $this->apiCall("op=barcode-lookup&ean=$ean&language=$lang");
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->product;
	}

	/// look up one ISBN-10 code
	/// only return the book name
	public function isbnLookup($isbn) {
		$xml = $this->apiCall("op=barcode-lookup&isbn=$isbn");
		if ($xml === FALSE) {
			return '';
		}
		$response = new SimpleXMLElement($xml);
		return $response->product->name;
	}

	/// get all barcodes below a prefix
	public function barcodePrefixSearch($prefix, $page = 0) {
		$xml = $this->apiCall("op=barcode-prefix-search&prefix=$prefix&page=$page");
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	/// search for a product name or keyword (exact match)
	public function productSearch($name, $page = 0) {
		$name = urlencode($name);
		$xml = $this->apiCall("op=product-search&name=$name&page=$page");
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	/// search for a product name or keyword (get similar matches)
	public function similarProductSearch($name, $page = 0) {
		$name = urlencode($name);
		$xml = $this->apiCall("op=similar-product-search&name=$name&page=$page");
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	/// search for a product name or keyword in a product category (exact match)
	public function categorySearch($category, $name = '', $page = 0) {
		$name = urlencode($name);
		$xml = $this->apiCall("op=category-search&category=$category&name=$name&page=$page");
		if ($xml === FALSE) {
			return array();
		}
		$response = new SimpleXMLElement($xml);
		return $response->xpath('//product');
	}

	/// generate a PNG barcode image
	public function barcodeImage($ean, $width = 102, $height = 50) {
		$xml = $this->apiCall("op=barcode-image&ean=$ean&width=$width&height=$height");
		$response = new SimpleXMLElement($xml);
		return base64_decode($response->product->barcode);
	}

	/// verify the checksum of a EAN / GTIN / UPC / ISBN-13 barcode
	public function verifyChecksum($ean) {
		$xml = $this->apiCall("op=verify-checksum&ean=$ean");
		$response = new SimpleXMLElement($xml);
		return $response->product->valid;
	}

	/// get the issuing country of any EAN / GTIN / UPC / ISBN-13 barcode
	public function issuingCountryLookup($ean) {
		$xml = $this->apiCall("op=issuing-country&ean=$ean");
		$response = new SimpleXMLElement($xml);
		return $response->product->issuingCountry;
	}

	/// find the Amazon ASIN for an EAN / GTIN / UPC / ISBN-13 barcode
	public function findAsinForEan($ean) {
		$xml = $this->apiCall("op=asin-for-ean-lookup&ean=$ean");
		if ($xml === FALSE) {
			return '';
		}
		$response = new SimpleXMLElement($xml);
		return $response->asin;
	}

	/// find the EAN barcode for an Amazon ASIN
	public function findEanForAsin($asin) {
		$asin = urlencode($asin);
		$xml = $this->apiCall("op=ean-for-asin-lookup&asin=$asin");
		if ($xml === FALSE) {
			return '';
		}
		$response = new SimpleXMLElement($xml);
		return $response->ean;
	}

	/// find the Library of Congress Control Number (LCCN) for an EAN / GTIN / UPC / ISBN-13 barcode
	public function findLccnForEan($ean) {
		$xml = $this->apiCall("op=lccn-for-ean-lookup&ean=$ean");
		if ($xml === FALSE) {
			return '';
		}
		$response = new SimpleXMLElement($xml);
		return $response->lccn;
	}

	/// find the EAN barcode for a Library of Congress Control Number (LCCN)
	/// there can be multiple EANs for one LCCN, this returns the first one found
	public function findEanForLccn($lccn) {
		$lccn = urlencode($lccn);
		$xml = $this->apiCall("op=ean-for-lccn-lookup&lccn=$lccn");
		if ($xml === FALSE) {
			return '';
		}
		$response = new SimpleXMLElement($xml);
		return $response->ean;
	}

	/// get remaining API credits
	/// returns -1 before first API call
	public function creditsRemaining() {
		return $this->remaining;
	}

	/// set HTTP timeout in seconds
	public function setTimout($sec) {
		$this->ctx = stream_context_create(array('http' => array('timeout' => $sec, 'ignore_errors' => true, 'user_agent' => 'php-eansearch/1.0')));
		ini_set('default_socket_timeout', $sec);
	}

	protected function apiCall($params, $tries = 1) {
		$xml = @file_get_contents(self::BASE_URL . "&token=$this->accessToken&" . $params, false, $this->ctx);
		if (!isset($http_response_header)) {
			return FALSE; # no response at all (DNS, connect or timeout error)
		}
		foreach($http_response_header as $k=>$v) {
			$head = explode(':', $v, 2);
			if($head[0] == 'X-Credits-Remaining') {
				$this->remaining = trim($head[1]);
			}
		}
		preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
		$status = $match[1];
		if ($status == 429) {
			if ($tries < self::MAX_API_TRIES) {
				sleep(1);
				return $this->apiCall($params, $tries + 1);
			}
			return FALSE; # still rate limited after all tries
		}
		return $xml;
	}
}

