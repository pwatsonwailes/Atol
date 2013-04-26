<?php
namespace DAMC;
// Include app settings
require_once('config.php');
try
{
	/*
	 * App route mapping
	 * Make sure you put in fixed urls before paramater ones, to ensure correct cascading
	 * See readmes/readmeRouter.txt for information
	*/

	/**
	Root - c: users
	*/	
	$router->map('/', 'adapter', 'method');

	/**
	Execute output
	*/

	if (!isset($_SESSION))
		session_start();

	$factory_input = $router->execute();

	$trigger_cache = array(
	);
	$fi_c = $factory_input['controller'];
	$fi_a = $factory_input['action'];

	$cache_control = (isset($trigger_cache[$fi_c][$fi_a])) ? sha1($_SERVER['REQUEST_URI']) : FALSE;

	if ($cache_state == 0)
		$cache_control = FALSE;

	if ($cache_control != FALSE)
	{
		$cache_content = $memcache->get($cache_control);

		if ($cache_content != FALSE)
		{
			echo $cache_content;
			exit();
		}
		else
		{
			ob_start();

			$factory->execute($factory_input);

			$buffer = ob_get_contents();

			$memcache->set($cache_control, $buffer, FALSE, 10);

			ob_end_flush();
			exit();
		}
	}
	else
	{
		$factory->execute($factory_input);
	}
}
catch (Exception $e) // If we run in to a problem, error it out here
{
	echo $e->getMessage();
}