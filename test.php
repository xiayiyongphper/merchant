<?php
date_default_timezone_set('UTC');
//$a = microtime(true);
$a = floor(microtime(true) * 1000);
print_r($a);
//print_r(time());
echo PHP_EOL;
date_default_timezone_set('PRC');
//$a = microtime(true);
$a = floor(microtime(true) * 1000);
print_r($a);
//print_r(time());
echo PHP_EOL;
print_r(floor(microtime(true) * 1000));