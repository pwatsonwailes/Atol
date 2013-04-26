<?php
namespace DAMC\dal\db;
define('DB_DSN', 'mysql:host='.$GLOBALS['db_vars']['host'].';dbname='.$GLOBALS['db_vars']['name']);
define('DB_USER', $GLOBALS['db_vars']['user']);
define('DB_PASS', $GLOBALS['db_vars']['pass']);

class db_conn {
	private static $conn_instance;
	
	private function __construct()
	{} 
	private function __clone()
	{} 

	public static function get_instance ()
	{ 
		if(!self::$conn_instance){ 
			self::$conn_instance = new \PDO(DB_DSN, DB_USER, DB_PASS); 
			self::$conn_instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); 
		} 

		return self::$conn_instance; 
	}

	final public static function __callStatic( $method, $arguements )
	{ 
		$conn_instance = self::get_instance(); 
		return call_user_func_array(array($conn_instance, $method), $arguements); 
	}
}

class db_ops
{
	function __construct()
	{
		$this->memcache = $GLOBALS['memcache'];
	}

	public function check_table ($t)
	{
		$sql = "SHOW TABLES FROM ".$GLOBALS['db_vars']['name'];

		// prepare and execute
		$stmt = db_conn::prepare($sql);
		$stmt->execute();
		$output = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$db_tables = array();

		foreach ($output as $value)
		{
			$this->db_tables[] = $GLOBALS['db_tables'][] = $value['Tables_in_'.$GLOBALS['db_vars']['name']];
		}

		$run = (!in_array($t, $this->db_tables)) ? FALSE : TRUE;

		if ($run == FALSE)
		{
			throw new \Exception("Error: table is not present in the database");
			exit;
		}
	}

	public function query ($sql, $data = NULL, $json = TRUE, $mode = NULL)
	{
		ini_set('memory_limit', '-1');
		$stmt = db_conn::prepare($sql);

		if (strstr($sql, 'SELECT'))
			$mode = 'SELECT';
		elseif (strstr($sql, 'INSERT'))
			$mode = 'INSERT';
		elseif (strstr($sql, 'UPDATE'))
			$mode = 'UPDATE';
		elseif (strstr($sql, 'DELETE'))
			$mode = 'DELETE';

		if (is_array($data))
		{
			$keys = array_keys($data);
			foreach ($keys as $key)
			{
				$stmt->bindParam(':'.$key, $data[$key]);
			}
		}

		if ($mode == 'SELECT')
		{
			$data_implode = ($data != NULL) ? $this->implode_r($data) : 1;

			if ($GLOBALS['cache_state'] == 0)
			{
				$stmt->execute();
				$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

				if (count($results))
				{
					return ($json == FALSE) ? $results : json_encode($results);
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				$cache_control = sha1($sql.$data_implode);
				$db_cache = $this->memcache->get($cache_control);

				if ($db_cache != FALSE)
				{
					return ($json == FALSE) ? $db_cache : json_encode($db_cache);
				}
				else
				{
					$stmt->execute();
					$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					if (count($results))
					{
						$this->memcache->set($cache_control, $results, FALSE, 10);
						return ($json == FALSE) ? $results : json_encode($results);
					}
					else
					{
						return FALSE;
					}
				}
			}
		}
		else
		{
			$output = ($stmt->execute()) ? TRUE : FALSE;
			if ($output == FALSE)
			{
				throw new \Exception("Error Processing Request: provided query and data failed to run properly", 1);
				return FALSE;
			}
			else
				return TRUE;
		}
	}

	public function last_insert ()
	{
		return db_conn::lastInsertId();
	}

	public function implode_r ($array, $glue = '.')
	{ 
		$out = NULL;
		foreach ($array as $value)
		{
			if (is_array ($value))
				$out .= $this->implode_r($glue, $value); // recurse 
			else
				$out .= $glue.$value; 
		}

		return $out; 
	}
}