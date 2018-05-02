## Saderat Payment Gatewat
##### composer package with laravel support

### Installation

    composer require hamid-a/saderat-pg
    
### Usage
##### Initializing
```php
use SaderatPaymentGateway\SaderatPG;

$tid = 'terminal-id';
$mid = 'merchant-id';
$public_key = __DIR__.'/saderat-public-key.pub'; // path to public key file
$private_key = __DIR__.'/saderat-private-key.key'; // path to private key file
$callback_url = 'callback-url'; // we can set calback url in initializing or getToken section


$gateway = new SaderatPG($tid, $mid, $public_key, $private_key, $callback_url);
```
Remember that keys must be in standard format:
```
-----BEGIN PRIVATE KEY-----
PRIVATE KEY CONTENT
-----END PRIVATE KEY-----
```
```
-----BEGIN PUBLIC KEY-----
PUUBLIC KEY CONTENT
-----END PUBLIC KEY-----
```
#### Get token
```php
$amount = 1000; // in int format
$crn = 'customer receipt number'; // must be unique in each transaction
$callback_url = 'callback-url'; // we can set calback url in initializing or getToken section
token = '';
try {

    $token = $gateway->getToken($amount, $crn, $callback_url);

} catch (\Exception $e){
    echo 'Error code:'.$e->getCode().' Error message:'.$e->getMessage();
}
if($token != '') {
// redirect user to: https://mabna.shaparak.ir/?ID=$token
} 
```

#### Verify transaction
```php
$verified = fasle;

try {

    $verified = $gateway->verifyTransaction($token, $_POST['CRN'], $_POST['TRN'], $_POST['SIGNATURE']);

} catch(\Exception $e){
    echo 'Error code:'.$e->getCode().' Error message:'.$e->getMessage();
}

if($verified) {
//transaction verified
} else {
// verification failed
}
```

### Laravel integration
In laravel >= 5.5 service provider and facade are registered automatically. But in older versions you should add facade and service provider in *config/app.php* file.
```
...
'providers' => [
    ...
    SaderatPaymentGateway\Laravel\SaderatPGServiceProvider::class,
],
'aliases' => [
    ...
    'SaderatPG' => SaderatPaymentGateway\Laravel\Facade\SaderatPG::class,
]
```
Add configs to *config/services.php*

```php
    'saderat-pg' => [
        'mid' => 'your merchant id',
        'tid' => 'your terminal id',
        'public-key' => __DIR__.'/saderat-public-key.pub', // path to public key file
        'private-key' => __DIR__.'/saderat-private-key.key', // path to private key file
        'callback-url' => '' // callback url (not required but it should provided in getToken request)
    ]
```

Use facade for requests:
```php
use SaderatPG;

// get token
$token = SaderatPG::getToken($amount, $crn, $callback_url);

// verify transaction
$verified = SaderatPG::verifyTransaction($token, $request->get('CRN'), $request->get('TRN'), $request->get('SIGNATURE'));

```

