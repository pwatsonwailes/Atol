<?php
namespace DAMC\modules\_global;
class global_model
{
	public function __construct ()
	{
		if (!isset($_SESSION))
			session_start();

		$db = '\DAMC\dal\db\db_ops';
		$this->db_ops = new $db;

		$rbac = '\DAMC\dal\rbac\rbac';
		$this->rbac = new $rbac;

		// hand off the dbconn to rbac for user/project checking
		$this->rbac->db_ops = $this->db_ops;

		$factory_input = $GLOBALS['factory_input'];
		$this->rbac->controller = $factory_input['controller'];
		$this->rbac->action = $factory_input['action'];
	}

	public function calculate_pagination ($page_number, $limit)
	{
		if (!is_integer($page_number) || !is_integer($limit))
			throw new \Exception("Error Processing Request: one of the supplied variables was not an integer: ".$page_number." ".$limit, 1);

		$n = ($page_number - 1) * $limit;
		return "LIMIT $n, $limit";
	}

	public function do_curl (url, $cookie = NULL, $return = 0)
	{
		// create a new cURL resource
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		if ($cookie != NULL)
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);

		// grab URL and pass it to the browser
		$content = curl_exec($ch);
		$errmsg = curl_error($ch);
		$header = curl_getinfo($ch);

		// close cURL resource, and free up system resources
		curl_close($ch);

		if ($errmsg != '')
		{
			var_dump($errmsg);
			var_dump($header);
		}
		elseif ($return == 0)
			return $content;
		else
			return $header;
	}

	/*
	0 returns content only
	1 returns headers only
	2 returns an array of both
	*/
	public function do_curl_multi ($urls, $cookie = NULL, $return = 0)
	{
		$content = $headers = $ch = array();
		$mh = curl_multi_init();

		foreach ($urls as $key => $url)
		{
			$encrypted_url = $this->encrypt_url($url);

			$ch[$encrypted_url] = curl_init();
			curl_setopt($ch[$encrypted_url], CURLOPT_HEADER, 0);
			curl_setopt($ch[$encrypted_url], CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch[$encrypted_url], CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch[$encrypted_url], CURLOPT_URL, $url);

			if ($cookie != NULL)
				curl_setopt($ch[$encrypted_url], CURLOPT_COOKIE, $cookie);

			curl_multi_add_handle($mh, $ch[$encrypted_url]);
		}

		$running = NULL;

		do {
			curl_multi_exec($mh, $running);
			$ready = curl_multi_select($mh); // this will pause the loop
			if ($ready > 0)
			{
				while ($info = curl_multi_info_read($mh))
				{
					$headers[$key] = curl_getinfo($info['handle'],CURLINFO_HTTP_CODE);
				}
			}
		} while ($running > 0);

		// Get content and remove handles.
		foreach ($ch as $key => $url)
		{
			$content[$key] = curl_multi_getcontent($url);
			curl_multi_remove_handle($mh, $url);
		}

		curl_multi_close($mh);

		if ($return == 0) 
			return $content;
		elseif ($return == 1)
			return $headers;
		else
			return array('content' => $content, 'headers' => $headers);
	}

	public function do_curl_post ($url, $data, $header)
	{
		$ch = curl_init();  

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$content = curl_exec($ch);
		$errmsg = curl_error($ch);
		$header = curl_getinfo($ch);

		curl_close ($ch);

		if ($errmsg != '')
		{
			var_dump($errmsg);
			var_dump($header);
		}
		else
			return $content;
	}

	public function encrypt_string (string)
	{
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5('curl_multi'), string, MCRYPT_MODE_CBC, md5(md5('curl_multi'))));
	}

	public function decrypt_string (string)
	{
		return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5('curl_multi'), base64_decode(string), MCRYPT_MODE_CBC, md5(md5('curl_multi'))), "\0");
	}

	public function send_mail ($user_names)
	{
		$sql = 'SELECT `email`
		FROM `users`
		WHERE ';
		$bindings = array();

		$n = 0;
		foreach ($user_names as $key => $user_name)
		{
			$bind_key = 'user_name'.$n;
			$sql .= ($n == 0) ? '`user_name` = :'.$bind_key : ' OR `user_name` = :'.$bind_key;
			$bindings[$bind_key] = ($user_name == 'on') ? $key : $user_name;
			$n++;
		}

		$emails = $this->db_ops->query($sql, $bindings, FALSE);

		if (is_array($emails))
		{
			$mail = new \DAMC\modules\_global\adapters\phpmailer\phpmailer;
			$mail->IsSMTP();  // boot up SMTP
			$mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
			$mail->SMTPAuth = TRUE;
			$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
			$mail->Host = "smtp.domain.com";
			$mail->Port = 465;
			$mail->IsHTML(TRUE);

			$mail->From = ($GLOBALS['dev'] == 1) ? 'user@localhost' : 'user@domain.com';
			$mail->FromName = "user";

			$mail->Username = 'user@domain.com';
			$mail->Password = 'password';
			
			foreach ($emails as $key => $row) 
			{
				$mail->AddAddress($row['email']);
			}

			$mail->Subject = $this->email_title;
			$mail->Body = $this->email_body;
			$mail->WordWrap = 50;

			$state = ($mail->Send()) ? TRUE : FALSE;

			return $state;
		}
		else
			return TRUE;
	}
}