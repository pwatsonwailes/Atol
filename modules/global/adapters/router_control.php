<?php
namespace DAMC\modules\_global\adapters\router_control;
require_once('route_control.php');

class router_control
{
	private $default_controller = 'pages';
	private $err_controller = '_404';
	private $default_action = 'index';
	private $matched_route;
	private $app_dir = '';
	private $request_uri; // The request URI of the page request as found in $SERVER['REQUEST_URI']
	private $routes = array(); // Route objects used to match a URL to a controller
	private $controller; // class name of the controller to use. NULL if no URI matches in Routes
	private $params; // parameters for the matched route. NULL if no URI matches in Routes

	private static $instance = NULL;

	public static function get_instance()
	{
		if (is_null(self::$instance))
			self::$instance = new router_control();

		return self::$instance;
	}

	// Search currently mapped rules for a match on the request URI, then instantiate required controller and call action
	// You can force this method to return controller actions instead of printing using $router->execute(TRUE);
	// If controller or action doesn't exist, will throw an exception
	public function execute ($return = false)
	{
		$route = $this->match_route();

		if ($route == NULL) // 404
		{
			$controller = $this->err_controller;
			$action = $this->default_action;
		}
		else
		{
			$controller = $route->getController();
			$action = $route->getAction();
		}

		if ($route != NULL) // merge the Route parameters with the $_GET superglobal
			$_GET = array_merge($_GET, $route->getParams());

		return array(
			'controller' => $controller,
			'action' => $action
		);
	}
	
	// Pulls URI apart to build request. If $request_uri != NULL, uses value in place of $SERVER['REQUEST_URI']
	public function set_request_uri ($request_uri = NULL)
	{
		$request = is_null($request_uri) ? $_SERVER['REQUEST_URI'] : $request_uri;
		$pos = strpos($request, '?');
		if ($pos)
			$request = substr($request, 0, $pos);

		// strip _app_directory from the request URI
		$this->request_uri = str_replace($this->app_dir, '', $request);

		// ensure that the request uri has a leading forward slash
		if (strpos($this->request_uri, '/') !== 0)
		{
			$this->request_uri = '/' . $this->request_uri;
		}
	}

	// Unsets the Router so Router::get_instance() wil create a fresh instance. Also resets the $_GET unless specified
	public static function reset ($resetGet = TRUE)
	{
		self::$instance = NULL;
		if ($resetGet)
		{
			$_GET = array();
		}
	}

	// sets rules for mapping URLs. After a URL has been mapped to a controller, this function does nothing
	// $rule = rule for URL mapping, $params = parameters to send, $conditions = regex for sections of the URL
	public function map ($rule, $controller = NULL, $action = NULL, $params = array(), $conditions = array())
	{
		if ($controller == NULL)
			$controller = $this->default_controller;
		if ($action == NULL)
			$action = $this->default_action;

		$new_route = new \DAMC\modules\_global\adapters\route_control\route_control ($rule, $this->request_uri, $controller, $action, $params, $conditions);

		// Make sure that the new route does not match any of the current matching rules.
		foreach ($this->routes as $route)
		{
			if ($new_route->get_regex() == $route->get_regex())
			{
				throw new Exception(
					"<p>Tried to overwrite an existing URL mapping rule:</p><p>'".
					$new_route->getRouteString()
					."'</p><p>has the same matching regex as</p><p>'".
					$route->getRouteString()
					."'</p>"
				);
			}
		}

		$this->routes[$rule] = $new_route;
	}

	// searches in Router and returns match to current request URI. If !match, returns NULL
	public function match_route ()
	{
		foreach ($this->routes as $route)
		{
			if ($route->match())
			{
				$this->matched_route = $route;
				return $route;
				break;
			}
		}

		// if the method gets to this point, no route was found
		return NULL;
	}

	// If app is not in web root, folder needs trimming
	public function set_app_dir ($new_app_dir)
	{
		$this->app_dir = $new_app_dir;
		$this->set_request_uri();
	}

	// If a route is unmatched, the fallback controller is err_controller. Use this controller to display 404s
	public function set_err_controller ($new_err_controller)
	{
		$this->err_controller = $new_err_controller;
	}

	// If no controller specified in map() method, default is used. Use this method to set that
	public function set_default_controller ($new_default_controller)
	{
		$this->default_controller = $new_default_controller;
	}

	// If no action specified in map() method, default action is used. Use this method to set that
	public function set_default_action ($new_default_action)
	{
		$this->default_action = $new_default_action;
	}
}