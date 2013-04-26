<?php
namespace DAMC\modules\_404\structure;
$required_files = array(
	'adapters' => array( // paths to files for adapters required
		'require_once' => array(
			'index.php',
		),
	), 
	'views' => array( // paths to files for views required
		'require_once' => array(
			'index.php',
		),
	),
	'templating' => array( // names and paths for the templates available
		'404Content' => '404.php',
	)
);