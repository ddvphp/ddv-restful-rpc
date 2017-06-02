ddv-restful-rpc 
===================

Installation - 安装
------------

```bash
composer require ddvphp/ddv-restful-rpc
```

Usage - 使用
-----


```php
$rpc = new /DdvPhp/DdvRestfulRpc();

$rpc->setRpcServerUrl([
  'http://127.0.0.1:2255',
  'http://10.8.8.8:2255'
]);

```