<?php
namespace DAMC\modules\_global\adapters\route_control;
class route_control
{
	private $is_matched = FALSE;
	private $params; // Any additional params to send in the $GET array
	private $additional_params;
	private $controller; // The controller to associate with this route
	private $action; // The method to call on the associated controller
	private $url; // The route string. E.g. /:controller/:action
	private $conditions; // Specify custom rules for URI parameters. E.g., "/user/:id" would use array('id' => '[0-9]+')
	private $regex;
	private $request_uri; // The request URI for the page. Default to $SERVER['request_uri']

	// Construct an instance of the Route class
	public function __construct ($url, $request_uri, $controller, $action, $params = array(), $conditions = array())
	{
		$this->url = $url;
		$this->additional_params = $params;
		$this->conditions = $conditions;
		$this->controller = $controller;
		$this->action = $action;
		// set the request URI to a reference to when it changes in the Router, is changes here too.
		$this->request_uri = &$request_uri;
		// create one regex for this URL rule
		$url_regex = preg_replace_callback('@:[\w]+@', array($this, 'regexUrl'), $url);
		$url_regex .= '/?';
		// Store the regex used to match this pattern.
		$this->regex = '@^' . $url_regex . '$@';
	}

	// Performs all matching on the request URI. If route matches request URI, = TRUE else FALSE
	// Also handles assignment for parameters array (not the additional parameters array), thus matches are not available until this method has been called
	public function match ()
	{
		//echo "Debug: {$this->url} - {$this->request_uri}\n";
		$this->is_matched = false;
		$pnames = array();
		$pvalues = array();
		$this->params = array();

		// match all of the variables (e.g. :id) in the URL.
		preg_match_all('@:([\w]+)@', $this->url, $pnames, PREG_PATTERN_ORDER );
		$pnames = $pnames[0];

		if (preg_match($this->regex, $this->request_uri, $pvalues))
		{
			array_shift($pvalues);

			// add the matched :variable in the URL to the params array of this object
			foreach ($pnames as $index => $value)
				$this->params[substr($value, 1)] = urldecode($pvalues[$index]);
			// add the additionally specified params to the params array
			foreach ($this->additional_params as $key => $value)
				$this->params[$key] = $value;

			// set the object to matched
			$this->is_matched = true;
		}

		return $this->is_matched;
	}

	// Gets regex used by this route for matching against the request URI and determining whether or not it's a match
	public function get_regex ()
	{
		return $this->regex;
	}

	// Gets the action to perform on the controller for this route
	public function getAction ()
	{
		return $this->action;
	}

	// Gets the string that was used to match this route
	public function getRouteString ()
	{
		return $this->url;
	}

	// Gets the parameters associated with this route, including parameters matched in the URI and additional parameters specified in the $router->map() call
	// Only available after a call to match() 
	public function getParams ()
	{
		return $this->params;
	}

	// Gets additional parameters associated with route
	public function get_additional_params ()
	{
		return $this->additional_params;
	}

	// Gets the controller for this route
	public function getController ()
	{
		return $this->controller;
	}

	// Check for route match to current URI
	public function is_matched ()
	{
		return $this->is_matched;
	}

	// Takes matches from a pregreplacecallback function call and decides what regex to use for that section of the Route URL
	private function regexUrl ($matches)
	{
		// trim the colon from the start of the variable
		$key = str_replace(':', '', $matches[0]);

		// if the variable has its own regex condition specified, use that
		if (array_key_exists($key, $this->conditions))
		{
			return '(' . $this->conditions[$key] . ')';
		} else
		{
			// else default to this regex for matching variables
			return '([a-zA-Z0-9\+\-\_%]+)';
		}
	}
}