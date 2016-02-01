#!/usr/local/bin/php-cgi -q -f
<?php
/*
	file: thebrig_start.php
	
	Copyright 2012-2015 Matthew Kempe & Alexey Kruglov

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/
/*
 * File name: 	thebrig_start.php
 * Author:      Matt Kempe, Alexey Kruglov
 * Modified:	Jan 2015
 * 
 * Purpose: 	This script is used to prepare the extension for use by
 * 				Nas4Free's lighttpd webgui server.
 * 
 * 				Additionally, it is used to auto-start thebrig service, 
 * 				which manages jail start/stop/restart operations. Before 
 * 				script is executed, all jails managed by thebrig must be 
 * 				stopped!!!
 * 
 * Variables used:
 * 
 * thebrig_ext	a string containing the real storage location of
 * 				the ext/thebrig folder. It simplifies code.
 * brig_ver		String (then floating point) version value stored in 
 * 				the installed copy of TheBrig's lang.inc
 * php_list		An array of all the php files in the ext/thebrig
 * php_file		Variable used to control a "for" loop
 * a_jail		An array of all the jail information from the config.xml
*/
header_remove('x-powered-by');
header("content-type: none");
header_remove("content-type");
require_once ("config.inc");
require_once ("{$config['thebrig']['rootfolder']}conf/ext/thebrig/functions.inc");
require_once ("{$config['thebrig']['rootfolder']}conf/ext/thebrig/lang.inc");
unlink_if_exists("/etc/rc.d/thebrig");
if ( ! symlink ( $config['thebrig']['rootfolder']."conf/bin/jail.sh", "/etc/rc.d/thebrig"))  
	{ exec ("logger Failed copy rc script");} 
chmod("/etc/rc.d/thebrig", 0755);

/* Clean up operations
 * 
 * These steps serve two purposes:
 * 1. To clean up old versions of TheBrig's file schema
 * 2. To reset all symlinks, in case a new version was installed, and the
 * 	  file list has changed.
 * 
 * These steps must be carried out on both "full" and "embedded" installs
 * because we don't necessarily require users to restart Nas4Free.
 */
	
// Get rid of the erroneously created file (by early versions).
unlink_if_exists ( "/usr/local/www/\*.php" );

// Get rid of old schema - which was a separate copy of entire ext folder
if ( is_dir( '/usr/local/www/ext/thebrig') ) {
	exec ( "rm -rf /usr/local/www/ext/thebrig");
}

// Get a list of all the symlinks or files from TheBrig that are currently 
// in the webroot, and destroy them.
foreach ( glob('/usr/local/www/extensions_thebrig*.php') as $link) {
	unlink( $link );
}

/*
 * End of clean-up operations
 */
 
// This checks to make sure the XML config concurs with the 
// installed version of lang.inc
$brig_ver = preg_split ( "/v/", _THEBRIG_VERSION_NBR);
// Convert the string to a float so that it can be used in comparisons
$brig_ver = 0 + substr($brig_ver[1],0,4);
if ( ($config['thebrig']['version'] != $brig_ver )){
	// We need to update the XML config to reflect reality
	$config['thebrig']['version'] = $brig_ver;
	write_config();
} 

// This might be the first extension, so we need to create the folder for it
exec( "mkdir -p /usr/local/www/ext" );
// Make life a little easier
$thebrig_ext = "{$config['thebrig']['rootfolder']}conf/ext/thebrig";
// Link the entire folder into the extension location
exec( "ln -s {$thebrig_ext} /usr/local/www/ext/thebrig");
// Create a list of all the php files that need to be linked into the webroot
$php_list = glob( "{$thebrig_ext}/*.php" ); 
// We need to extract just the file name so the symbolic links make sense
foreach ( $php_list as $php_file ) {
	// Cut off the prefix to obtain the filename
	$php_file = str_replace( "{$thebrig_ext}/" , "", $php_file);
	// Link the real storage location to the webroot
	exec ( "ln -s {$thebrig_ext}/{$php_file} /usr/local/www/{$php_file}");
}
// If the array 'content' has at least one entry, then we need to create
// the jail config file, and devfs rules.
if ( count ( $config['thebrig']['content'] ) > 0 ) {
	write_jailconf ();
	write_defs_rules ();
}
//BHYVE adds
if ( ! symlink ( $config['thebrig']['rootfolder']."conf/rc.d/vm", "/usr/local/etc/rc.d/vm"))  
	{ exec ("logger Failed copy rc script");} 
chmod("/usr/local/etc/rc.d/vm", 0755);
if ( ! symlink ( $config['thebrig']['rootfolder']."conf/bin/vm", "/usr/local/bin/vm"))  
	{ exec ("logger Failed copy rc script");} 
chmod("/usr/local/bin/vm", 0755);
exec( "mkdir /usr/local/lib/vm-bhyve");
$bhyve_lib = "{$config['thebrig']['rootfolder']}conf/lib";
$lib_list = glob( "{$bhyve_lib}/*" );
foreach ( $lib_list as $lib_file ) {
	// Cut off the prefix to obtain the filename
	$lib_file = str_replace( "{$bhyve_lib}/" , "", $lib_file);
	// Link the real storage location to the webroot
	exec ( "ln -s {$bhyve_lib}/{$lib_file} /usr/local/lib/vm-bhyve/{$lib_file}");
}
exec ("rconf service enable vm");
exec ("rconf attribute set vm_dir " . $config['thebrig']['rootfolder']."work");
// If thebrig service is enabled, then starting its rc script(s) need to 
// be updated and run 
if (isset ( $config['thebrig']['thebrig_enable']) ) {
		rc_update_service('thebrig');
		rc_start_service('thebrig');
}
?>
