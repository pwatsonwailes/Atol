<?php
namespace DAMC\modules\_global\adapters\factory;
/**
* All controllers access this class to include required files, and load classes
* 
* Files require the full path to load correctly
* Classes are key/value arrays, with the key as the name you want to set the class as
* Classes are output as $class_setName, where setName is the key value
*/
class factory
{
	public function execute ($input) // the default method
	{
		$controller = $input['controller'];
		$action = $input['action'];

		$GLOBALS['templating'] = $this->required_files($controller, $action);

		$class = "\DAMC\modules\\$controller\\".$controller;

		if (!class_exists($class))
			throw new \Exception("Class $class does not exist.");

		$controllerInstance = new $class();

		if (!method_exists($controllerInstance, $action))
			throw new \Exception("Method '$action' not found in class '$controller'.");

		return $controllerInstance->$action();
	}

	public function required_files ($controller)
	{
		include_once($GLOBALS['app_path']."modules/$controller/structure.php");

		foreach ($required_files as $area => $file_set)
		{
			foreach ($file_set as $incType => $files)
			{
				if (is_array($files)) // models, adapters and views
				{
					foreach ($files as $file)
					{
						if ($incType == 'require')
							require($GLOBALS['app_path']."modules/$controller/$area/".$file);
						elseif ($incType == 'require_once')
							require_once($GLOBALS['app_path']."modules/$controller/$area/".$file);
						elseif ($incType == 'include')
							include($GLOBALS['app_path']."modules/$controller/$area/".$file);
						elseif ($incType == 'include_once')
							include_once($GLOBALS['app_path']."modules/$controller/$area/".$file);
					}
				}
				else $templating = $file_set;
			}
		}

		return $templating;
	}
}