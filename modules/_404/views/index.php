<?php
namespace DAMC\modules\_404;
class _404_error
{
	function index ()
	{
		if (headers_sent($filename, $linenum)) {
			echo "Headers already by $filename line $linenum\n";
			exit;
		}
		
		$templating = $GLOBALS['templating'];

		require ($GLOBALS['app_path'].'modules/_404/templating/'.$templating['404Content']);
	}
}