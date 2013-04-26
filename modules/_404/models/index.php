<?php
namespace DAMC\modules\_404;
class _404_model extends \DAMC\modules\_global\global_model
{
	function index () {
		$view = new _404_error();
		$view->index();
	}
}