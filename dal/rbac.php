<?php
namespace DAMC\dal\rbac;
class rbac {
	public function __construct ()
	{
		$this->operations = array();

		$this->default_permissions = array(
			'GET' => FALSE,
			'POST' => FALSE,
			'PUT' => FALSE,
			'DELETE' => FALSE
		);

		$this->db_ops = FALSE; // holder to register the db connection
		$this->user_access = FALSE;

		$this->restricted_areas = array( // boot them out if they're in the wrong project area
			'messages',
			'tasks',
		);

		$this->tool_areas = array(
			'content_bank',
			'majestic_seo',
			'ga_attribution',
			'serp_analysis',
			'fullcontact',
			'mozscape',
			'scraper',
			'pinger',
			'scrs',
			'content_analysis',
		);

		$this->admin_areas = array(
			'users',
			'projects',
			'user_projects',
		);
	}

	/**
	Takes in the value of a usergroup (numeric), and a method type (GET/POST/PUT/DELETE)
	Returns whether the usergroup has access to the requested action
	User Roles are:
	0 - Projects
	1 - Directors
	2 - Senior Staff
	3 - Junior Staff
	4 - Outsourced
	5 - Contact
	*/ 
	public function check_access ($type, $user_role)
	{
		$bit_flag = (isset($this->operations[$this->controller][$this->action][$user_role])) ? $this->operations[$this->controller][$this->action][$user_role] : $this->default_permissions;

		if (in_array($this->controller, $this->restricted_areas))
		{  // if the user is a staff member accessing a tool
			if ($user_role == 1 || $user_role == 2 || $user_role == 3)
				$this->user_access = TRUE;
		}
		
		if (in_array($this->controller, $this->restricted_areas))
		{  // If the user isn't an admin, and the area is sensitive, and 
			if (is_array($this->project_users))
			{
				foreach ($this->project_users as $project_area)
				{
					if ($this->user_access == FALSE)
						$this->user_access = ($project_area['project_url_name'] == $_GET['project_url_name']) ? TRUE : FALSE;
				}
			}
			else
			{
				$this->project_users = FALSE;
			}
		}

		if ($user_role == 1) // Directors get access to everything
			$this->user_access = TRUE;
		else
			$this->user_access = ($bit_flag[$type] == FALSE) ? FALSE : TRUE;

		if ($this->user_access == FALSE)
			$this->kick();
	}

	public function project_users ($json_return = FALSE)
	{
		if (in_array($this->controller, $this->restricted_areas))
		{
			$sql = "SELECT `projects`.`project_url_name`, `users`.`first_name`,  `users`.`last_name`,  `users`.`user_name`
			FROM `user_projects` 
			LEFT JOIN `users` on `users`.`id` = `user_projects`.`user_id`
			LEFT JOIN `projects` on `projects`.`id` = `user_projects`.`project_id`
			WHERE `projects`.`project_url_name` = :project_url_name";

			$payload = array('project_url_name' => $_GET['project_url_name']);

			$users = $this->db_ops->query($sql, $payload, $json_return);
			
			$this->project_users = (is_array($users)) ? $users : FALSE;
		}
	}

	public function assign_role_operations ($class = NULL, $method = NULL, $roles, $bitMask = 0)
	{
		foreach ($roles as $role)
		{
			$i = 0;
			$this->operations[$class][$method][$role] = $this->default_permissions;

			foreach ($this->operations[$class][$method][$role] as $key => $value)
			{
				$this->operations[$class][$method][$role][$key] = (($bitMask & pow(2,$i)) != 0) ? TRUE : FALSE; 
				$i++;
			}
		}
	}

	public function kick ()
	{
		header ('HTTP/1.1 301 Moved Permanently');
		header ('Location: /disallow/');
		exit();
	}

	public function disabled ()
	{
		header ('HTTP/1.1 301 Moved Permanently');
		header ('Location: /disabled/');
		exit();
	}
}