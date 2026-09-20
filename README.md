# relaxed/ean-search
A PHP class for EAN and ISBN name lookup and validation using the API on ean-search.org.

To use it, you need an API access token from
https://www.ean-search.org/ean-database-api.html

## Initialization
```php
require_once("EANSearch.php");

// your access token from ean-search.org
$accessToken = getenv("EAN_SEARCH_API_TOKEN");

$eanSearch = new EANSearch($accessToken);
```

## Usage
```php
$ean = '5099750442227';
$name = $eanSearch->barcodeLookup($ean);
echo "$ean is $name\n";

// more detailed response, preferably in English
$product = $eanSearch->barcodeSearch($ean, 1);
echo "$ean is $product->name from category $product->categoryName (Google category $product->googleCategoryId) issued in $product->issuingCountry\n";

$isbn = '1119578884';
$title = $eanSearch->isbnLookup($isbn);
echo "$isbn is $title\n";

$ok = $eanSearch->verifyChecksum($ean);
echo "$ean is " . ($ok ? 'valid' : 'invalid') . "\n";

$eanList = $eanSearch->productSearch('Apple iPod');
foreach ($eanList as $product) {
	echo "$product->ean is $product->name\n";
}

$eanList = $eanSearch->similarProductSearch('Apple iPod with extra feature');
foreach ($eanList as $product) {
	echo "$product->ean is $product->name\n";
}

$eanList = $eanSearch->categorySearch(45, 'Thriller');
foreach ($eanList as $product) {
	echo "$product->ean from Music category is $product->name\n";
}

$eanList = $eanSearch->barcodePrefixSearch(4007249146);
foreach ($eanList as $product) {
	echo "$product->ean is $product->name\n";
}

$ean = '5099750442227';
$asin = $eanSearch->findAsinForEan($ean);
echo "EAN $ean has the Amazon ASIN $asin\n";
$ean = $eanSearch->findEanForAsin($asin);
echo "ASIN $asin is EAN $ean\n";

$isbn13 = '9780815346333';
$lccn = $eanSearch->findLccnForEan($isbn13);
echo "iISBN-13 $isbn13 has the Library of Congress control number (LCCN) $lccn\n";
$ean = $eanSearch->findEanForLccn($lccn); // there can be several EANs for one LCCN, you get the first one found
echo "LCCN $lccn has EAN $ean\n";

$ean = '5099750442227';
$country = $eanSearch->issuingCountryLookup($ean);
echo "$ean was issued in $country\n";

$barcode = $eanSearch->barcodeImage($ean, 300, 200);
print "Image for EAN $ean in HTML: <img src=\"data:image/png;base64," . base64_encode($barcode) . "\">\n";

$credits = $eanSearch->creditsRemaining();
echo "$credits credits remaining\n";

```

