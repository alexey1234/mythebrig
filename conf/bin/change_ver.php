#!/usr/local/bin/php-cgi -f
<?php
include ("config.inc");
if ($config['thebrig']['version'] == 1) { echo "this is first thebrig version"; }
else {echo "You have new version"; echo "\n"; exit;} 
$langfile = file("/usr/local/www/ext/thebrig/lang.inc");
$version_1 = preg_split ( "/VERSION_NBR, 'v/", $langfile[1]);
echo "\n";
$version=substr($version_1[1],0,3);
echo $version;
$config['thebrig']['version'] = $version;
write_config();
echo "\n";
?>
