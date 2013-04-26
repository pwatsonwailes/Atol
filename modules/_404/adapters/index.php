<?php
namespace DAMC\modules\_404;
class _404
{
	function index () {
		$view = new _404_error();
		$view->index();
	}
}