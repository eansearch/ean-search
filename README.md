# relaxed/ean-search
A PHP class for EAN and ISBN name lookup and validation using the API on ean-search.org.

To use it, you need an API access token from
https://www.ean-search.org/ean-database-api.html

## Initialization
	// your access token from ean-search.org
	$accessToken = 'abcdef';

	$eanSearch = new EANSearch($accessToken);

## Usage
	$ean = '5099750442227';
	$name = $eanSearch->barcodeLookup($ean);
	echo "$ean is $name\n";

	$ok = $eanSearch->verifyChecksum($ean);
	echo "$ean is " . ($ok ? 'valid' : 'invalid') . "\n";

	$eanList = $eanSearch->productSearch('iPod');
	foreach ($eanList as $product) {
		echo "$product->ean is $product->name\n";
	}

	$eanList = $eanSearch->barcodePrefixSearch(4007249146);
	foreach ($eanList as $product) {
		echo "$product->ean is $product->name\n";
	}

