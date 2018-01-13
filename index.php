<?php
spl_autoload_register();

use Core\API\BTCEHandler;
//echo phpinfo();
//require_once('Core\API\BTCEHandler.php');
$stock = new BTCEHandler();
$result = $stock->sendRequest("getInfo");
echo "<pre>".print_r($result, true)."</pre>";


$a = [1,2];
print_r($a);


