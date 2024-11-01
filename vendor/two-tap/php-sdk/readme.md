# Two Tap PHP SDK

The Two Tap SDK for PHP provides a native interface to the Two Tap API

---

## Installation

This library can be found on [Packagist](https://packagist.org/packages/two-tap/php-sdk).
The recommended way to install this is through [composer](http://getcomposer.org).

Run these commands to install composer, the library and its dependencies:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar require two-tap/php-sdk
```

You then need to install [guzzle](http://docs.guzzlephp.org/):

```bash
$ php composer.phar require guzzlehttp/guzzle:~6.0
```

Or edit `composer.json` and add:

```json
{
    "require": {
        "two-tap/php-sdk": "~1.0"
    }
}
```

And then add [guzzle](http://docs.guzzlephp.org/):

```json
{
    "require": {
        "guzzlehttp/guzzle": "~6.0"
    }
}
```

# Documentation
**For full API documentation can please consult our official [documentation page](http://docs.twotap.com/reference).**

# Usage


```php
<?php

require 'vendor/autoload.php';

use TwoTap/Api;

// create an api object
$api = new Api([
    'public_token' => 'YOUR_PUBLIC_TOKEN',
    'private_token' => 'YOUR_PRIVATE_TOKEN'
]);

// ...
```


### Product::class
---
#### get()

```php
$api->product()->get($siteId, $md5, $destinationCountry, $attributesFormat);
```

#### search()

```php
$api->product()->search($filter, $sort, $page, $perPage, $productAttributesFormat, $destinationCountry);
```

#### scroll()

```php
$api->product()->scroll($filter, $size, $scrollId, $productAttributesFormat, $destinationCountry);
```

#### filters()

```php
$api->product()->filters($filter);
```

#### taxonomy()

```php
$api->product()->taxonomy();
```

### Cart::class
---
#### create()

```php
$api->cart()->create($products, $finishedUrl, $finishedProductAttributesFormat, $notes, $testMode, $cacheTime, $destinationCountry);
```

#### status()

```php
$api->cart()->status($cartId, $productAttributesFormat, $testMode, $destinationCountry);
```

#### estimates()

```php
$api->cart()->estimates($cartId, $fieldsInput, $products, $destinationCountry);
```

### Purchase::class
---
#### create()

```php
$api->purchase()->create($cartId, $fieldsInput, $affiliateLinks, $confirm, $products, $notes, $testMode, $locale);
```

#### status()

```php
$api->purchase()->status($purchaseId, $testMode);
```

#### history()

```php
$api->purchase()->history($since);
```

#### confirm()

```php
$api->purchase()->confirm($purchaseId, $testMode);
```

### Utils::class
---
#### fieldsInputValidate()

```php
$api->utils()->fieldsInputValidate($cartId, $flatFieldsInput);
```

#### quicky()

```php
$api->utils()->quicky($products, $smsConfirmUrl, $phone, $message);
```

#### supportedSites()

```php
$api->utils()->supportedSites($cartId, $flatFieldsInput);
```

#### coupons()

```php
$api->utils()->coupons($cartId, $flatFieldsInput);
```

### PickupOptions::class
---
#### create()

```php
$api->pickupOptions()->create($cartId, $fieldsInput, $products, $finishedUrl);
```

#### status()

```php
$api->pickupOptions()->status($cartId);
```

### Wallet::class
---
#### userToken()

```php
$api->wallet()->userToken($userKey);
```

#### retrieve()

```php
$api->wallet()->retrieve($userToken, $filterFieldTypes, $filterGroupIds);
```

#### store()

```php
$api->wallet()->store($userToken, $fieldType, $groupId, $fields);
```

#### delete()

```php
$api->wallet()->delete($userToken, $fieldType, $fieldGroupId);
```

#### meta()

```php
$api->wallet()->meta($$metaFields, $fieldType, $expiresIn);
```

# Laravel usage
Two Tap API SDK has optional support for [Laravel](https://laravel.com) & [Lumen](https://lumen.laravel.com) and comes with a Service Provider and Facades for easy integration.

After you have installed TwoTap API SDK, open your Laravel config file `config/app.php` and add the following lines.

In the `$providers` array add the service providers for this package.

```php
TwoTap\TwoTapServiceProvider::class
```

Add the facade of this package to the `$aliases` array.

```php
'TwoTap' => TwoTap\Facades\TwoTap::class
```

Now the TwoTap Class will be auto-loaded by Laravel.

### Example

```php
// usage inside a laravel route
Route::get('/', function()
{
    $filter = [
        "keywords" => "Vans sneakers"
    ];

    $results = TwoTap::product()->search($filter);

    return $results->products;
});
```
