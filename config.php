<?php
namespace DAMC\config;
if (get_magic_quotes_gpc()) {
	$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
	while (list($key, $val) = each($process)) {
		foreach ($val as $k => $v) {
			unset($process[$key][$k]);
			if (is_array($v)) {
				$process[$key][stripslashes($k)] = $v;
				$process[] = &$process[$key][stripslashes($k)];
			}
			else {
				$process[$key][stripslashes($k)] = stripslashes($v);
			}
		}
	}
	unset($process);
}

// Sort out times for later:
date_default_timezone_set('Europe/London');

$dev = 1; // development mode? 0 is off

/*
 * Set up the routing files
 * These are the only files needed to be required by the app
 * Everything else should be required and instantiated by an adapter script
*/
$app_route = '/'; // where the app is accessible from

$app_path = ($dev == 1) ? $app_route : $app_route.'public_html/'; // where the app is on the server

/*
 * Cache settings
*/

$exp_time = 3600; // expiry time for caching. default is 1 hour
$cache_route = 'cache/'; // what's the name of the cache folder? defaults to 'cache/'
$cache_path = $app_path.$cache_route; // what's the path to the cache folder? defaults to '$app_path.$cache_route'

/*
 * Database options & config
*/
$db_vars_options = array(
	array( // db variables for live site
		'host' => '',
		'name' => '',
		'user' => '',
		'pass' => ''
	),
	array( // db variables for dev site
		'host' => '',
		'name' => '',
		'user' => '',
		'pass' => ''
	)
);

$domain_options = array(
	'//'.$app_route,
	'//localhost'.$app_route,
);

$db_vars = $db_vars_options[$dev];
$domain = $domain_options[$dev];
#$cache_state = ($dev == 1) ? 0 : 1; // caching status; 0 is off
$cache_state = 1; // caching status; 0 is off
$db_connect = TRUE; // auto-connect to db?

/*
 * Boot up the application
*/
$global_files = array( // location of the globally required files
	$app_path.'modules/global/adapters/route_control.php',
	$app_path.'modules/global/adapters/router_control.php',
	$app_path.'modules/global/adapters/factory.php',
	$app_path.'dal/db.php',
	$app_path.'dal/rbac.php',
);

foreach ($global_files as $router_file) {
	require_once($router_file);
}

$router = \DAMC\modules\_global\adapters\router_control\router_control::get_instance();
$router->set_app_dir($app_route.'/');
$executeAreas = array(); // a list of classes that should change execute to TRUE
$execute = FALSE; // whether to cache content output or not. FALSE caches, TRUE passes

$factory = new \DAMC\modules\_global\adapters\factory\factory();

// make memcache available
if ($cache_state == 1)
{
	$memcache = new \Memcache;
	$memcache->connect("localhost", 11211);
}