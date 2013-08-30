#!/usr/local/bin/php-cgi -f
<?php
/*
*/
require_once ("config.inc");
require_once ("{$config['thebrig']['rootfolder']}conf/ext/thebrig/functions.inc");

exec( "mkdir -p /usr/local/www/ext/thebrig/" );
exec( "cp {$config['thebrig']['rootfolder']}conf/ext/thebrig/* /usr/local/www/ext/thebrig/");
$php_list = glob( "/usr/local/www/ext/thebrig/*.php" ); 
foreach ( $php_list as $php_file ) {
	$php_file = str_replace( "/usr/local/www/ext/thebrig/" , "", $php_file);
	if ( is_link ( "/usr/local/www/ext/thebrig/" . $php_file ) ) {	unlink (  "/usr/local/www/ext/thebrig/" . $php_file );	} else {}
	exec ( "ln -s /usr/local/www/ext/thebrig/{$php_file} /usr/local/www/{$php_file}");
}
if ( count ( $config['thebrig']['content'] ) > 0 ) {
	if ( !is_file ( "/etc/rc.conf.local" ) ) {
		// This means we are on embedded
		write_rcconflocal ();
		cmd_exec("/etc/rc.d/jail restart",$a_tolog, $a_tolog1);
		$filelog = $config['thebrig']['rootfolder']."thebrig.log";
		$handle1 = fopen($filelog, "a+");
		foreach ($a_tolog1 as $tolog1 ) { fwrite ($handle1, "[".date("Y/m/d H:i:s")."]: TheBrig error!: ".trim($tolog1)."\n" ); }
		fclose ($handle1);
	}
}
?>
