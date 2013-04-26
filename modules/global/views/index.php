<?php
namespace DAMC\modules\_global;
class global_view extends \DAMC\modules\_global\global_model
{
	public function __construct ()
	{
		parent::__construct();

		$this->area = $GLOBALS['factory_input']['controller'];
		$this->tpl_path = $GLOBALS['app_path'].'modules/'.$this->area.'/templating/';
		$this->tpl = array(
			'global_header' => '../../global/templating/header.php',
			'global_footer' => '../../global/templating/footer.html',
			'global_success' => '../../global/templating/success.php',
			'global_fail' => '../../global/templating/fail.php',
		);
		$this->tpl = array_merge($this->tpl, $GLOBALS['templating']);
	}

	public function render_header ()
	{
		include ($this->tpl_path.$this->tpl['global_header']);
	}

	public function render_footer ()
	{
		include ($this->tpl_path.$this->tpl['global_footer']);
	}

	public function render_nav ($nav_file = 'nav')
	{
		if (isset($this->tpl[$nav_file]) && isset($_SESSION['user_name']))
			include ($this->tpl_path.$this->tpl[$nav_file]);
		elseif (!isset($this->tpl[$nav_file]) && isset($_SESSION['user_name']))
			include ($nav_file);
	}

	public function render_file ($file, $data = NULL, $extract = TRUE)
	{
		if (is_array($data) && $extract == TRUE)
			extract($data);

		include (isset($this->tpl[$file])) ? $this->tpl_path.$this->tpl[$file] : $file;
	}

	public function render_html ($html, $data = NULL)
	{
		if (is_array($data))
			extract($data);

		echo $html;
	}

	public function render_files ($files, $data = NULL)
	{
		if (is_array($data))
			extract($data);

		foreach ($files as $file)
		{
			include (isset($this->tpl[$file])) ? $this->tpl_path.$this->tpl[$file] : $file;
		}
	}

	public function default_rendering ($html, $files = NULL, $data = NULL)
	{
		$this->render_header();
		$this->render_nav();

		if (is_array($files))
			$this->render_files($files, $data);
		elseif ($files != NULL)
			$this->render_file($files, $data);
		else
			echo $html;

		$this->render_footer();
	}
}