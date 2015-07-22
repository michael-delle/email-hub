<?php
/*
Name:          Member Class
Author:        FireDart
License:       Creative Commons Attribution-ShareAlike 3.0 Unported License
                - http://creativecommons.org/licenses/by-sa/3.0/
*/
class member
{
	# Simple Variables
	private $remember=TRUE;
	private $captcha=TRUE;
	private $bcryptRounds=12;

	/**
	 * __construct
	 *
	 * Needed member stuff.
	 *
	 */
	public function __construct()
	{
		# Prevent JavaScript from reaidng Session cookies
		ini_set('session.cookie_httponly', TRUE);

		# Start Session
		session_start();

		# Check if last session is fromt he same pc
		if(!isset($_SESSION['last_ip']))
		{
			$_SESSION['last_ip']=$_SERVER['REMOTE_ADDR'];
		}
		if($_SESSION['last_ip']!==$_SERVER['REMOTE_ADDR'])
		{
			# Clear the SESSION
			$_SESSION=array();
			# Destroy the SESSION
			session_unset();
			session_destroy();
		}
	}

	/**
	 * elapsedTime
	 *
	 * Compares $timestamp with current time() and display the difference.
	 *
	 * @param	int $timestamp
	 * @param	int $precision
	 * @access	public
	 */
	public function elapsedTime($timestamp, $precision=2)
	{
		$result=NULL;
		$time=time()-$timestamp;
		$a=array(
			'decade'=>315576000,
			'year'=>31557600,
			'month'=>2629800,
			'week'=>604800,
			'day'=>86400,
			'hour'=>3600,
			'minute'=>60,
			'second'=>1
			);
		$i=0;
		foreach($a as $k=>$v)
		{
			$$k=floor($time/$v);
			if($$k) $i++;
			$time=$i>=$precision ? 0 : $time-$$k*$v;
			$s=$$k>1 ? 's' : '';
			$$k=$$k ? $$k.' '.$k.$s.' ' : '';
			$result.=$$k;
		}
		return $result ? $result.'ago' : '0 seconds ago';
	}

	/**
	 * Current Path
	 */
	public function currentPath($type=0)
	{
		$currentPath='http';
		if(isset($_SERVER["HTTPS"])=="on")
		{
			$currentPage.="s";
		}
		$currentPath.="://";
		$currentPath.=dirname($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]).'/';
		return $currentPath;
	}

	/**
	 * Current Page
	 */
	public function currentPage()
	{
		# Current Page
		$currentPage='http';
		if(isset($_SERVER["HTTPS"])=="on")
		{
			$currentPage.="s";
		}
		$currentPage.="://";
		$currentPage.=$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		return $currentPage;
	}

	/**
	 * Gen Salt
	 */
	public function genSalt()
	{
		# openssl_random_pseudo_bytes(16) Fallback
		$seed='';
		for($i=0; $i<16; $i++)
		{
			$seed.=chr(mt_rand(0, 255));
		}
		# GenSalt
		$salt=substr(strtr(base64_encode($seed), '+', '.'), 0, 22);
		# Return
		return $salt;
	}

	/**
	 * genHash
	 *
	 * Generates Password.
	 *
	 */
	public function genHash($salt, $password)
	{
		/* Explain '$2y$' . $this->rounds . '$' */
		/* 2a selects bcrypt algorithm */
		/* $this->rounds is the workload factor */

		# GenHash
		$hash=crypt($password, '$2y$'.$this->bcryptRounds.'$'.$this->genSalt());
		# Return
		return $hash;
	}

	/**
	 * verify
	 *
	 * Verify Password.
	 *
	 */
	public function verify($password, $existingHash)
	{
		# Hash new password with old hash
		$hash=crypt($password, $existingHash);

		# Do Hashs match?
		if($hash===$existingHash)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * login
	 *
	 * Returns a login form that user can login with
	 * It then checks to see if the login is successful
	 *
	 * If so create session and/or remember cookie
	 */
	public function login()
	{
		global $database;

		# User Rember me feature?
		if($this->remember==TRUE)
		{
			$remember='<div class="clearer"> </div><p class="remember_me"><input type="checkbox" name="remember_me" value="1" /> Remember me?</p>';
		}
		else
		{
			$remember="";
		}

		# Login Form
		$form='
<form name="login" action="'.$this->currentPage().'" method="post" class="group">
	<label>
		<span>Username</span>
		<input type="text" name="username" class="swifttextlarge" />
	</label>
	<label>
		<span>Password</span>
		<input type="password" name="password" class="swifttextlarge" />
	</label>
	'.$remember.'
	<input name="login" type="submit" class="headerbutton" style="margin-top: 10px; margin-left: -4px;" value="Login" />
</form>
<p class="options group"><!--a href="member.php?action=register">Register</a> &bull; --><a href="member.php?action=recover-password">Recover Password</a></p>
		';

		# Check if Login is set
		if(isset($_POST['login']))
		{
			# Set username and password
			if(empty($_POST['username']))
			{
				$username=NULL;
			}
			else
			{
				$username=$_POST['username'];
			}
			if(empty($_POST['password']))
			{
				$password=NULL;
			}
			else
			{
				$password=$_POST['password'];
			}

			# Is both Username and Password set?
			if($username && $password)
			{
				# Get User data
				$database->query('SELECT id, password FROM users WHERE username=:username', array(':username'=>$username));

				# Check if user exist
				if($database->count()>=1)
				{
					# Get the users info
					$user=$database->statement->fetch(PDO::FETCH_OBJ);

					# Check hash
					if($this->verify($password, $user->password)==TRUE)
					{
						# If correct create session
						session_regenerate_id();
						$_SESSION['member_id']=$user->id;
						$_SESSION['member_valid']=1;

						# User Rember me feature?
						$this->createNewCookie($user->id);

						# Log
						$this->userLogger($user->id, 0);

						# Report Status
						$message_type=2;
						$return_form=0;

						# Redirect
						header("Location: member.php");
					}
					else
					{
						# Report Status
						$message="Authentication Failed";
						$message_type=1;
						$return_form=1;
					}
				}
				else
				{
					# Report Status
					$message="Authentication Failed";
					$message_type=1;
					$return_form=1;
				}
			}
			else
			{
				# Report Status
				$message="Authentication Failed";
				$message_type=1;
				$return_form=1;
			}
		}
		else
		{
			# Report Status
			$message="Please authenticate your self";
			$message_type=0;
			$return_form=1;
		}

		# What type of message?
		switch($message_type)
		{
			case 0:
				$type="info";
				break;
			case 1:
				$type="error";
				break;
			case 2:
				$type="success";
				break;
		}

		# Combine Data
		$data='<div id="login" class="group">
			  <h1>Login</h1>
			  <div class="notice '.$type.'">'.$message.'</div>';

		# We need the login form?
		if($return_form==1)
		{
			$data.=$form;
		}
		$data.='</div>';

		# Return data
		return $data;
	}

	/**
	 * LoggedIn
	 *
	 * Check if the user is logged-in
	 * Check for session and/or cookie is set then reference it
	 * in the database to see if it is valid if so allow the
	 * user to login
	 */
	public function LoggedIn($redirect=NULL)
	{
		global $database;

		# Is a SESSION set?
		if(isset($_SESSION['member_valid']) && $_SESSION['member_valid'])
		{
			# Return TRUE
			$status=TRUE;

			# Check if user needs account reset
			$database->query('SELECT reset FROM users WHERE id=:id', array(':id'=>$_SESSION['member_id']));
			$user=$database->statement->fetch(PDO::FETCH_OBJ);
			# Is a password Reset needed?
			if($user->reset==1)
			{
				$reset=TRUE;
			}
			else
			{
				$reset=FALSE;
			}
		}
		# Is a COOKIE set?
		elseif(isset($_COOKIE['remember_me_id']) && isset($_COOKIE['remember_me_hash']))
		{
			# If so, find the equivilent in the db
			$database->query('SELECT id, hash FROM users_logged WHERE id=:id', array(':id'=>$_COOKIE['remember_me_id']));
			# Does the record exist?
			if($database->count()>=1)
			{
				# If so load the data
				$user=$database->statement->fetch(PDO::FETCH_OBJ);
				# Do the hashes match?
				if($user->hash==$_COOKIE['remember_me_hash'])
				{
					# If so Create a new cookie and mysql record
					$this->createNewCookie($user->id);
					# Return TRUE
					$status=TRUE;
					# If correct recreate session
					session_regenerate_id();
					$_SESSION['member_id']=$user->id;
					$_SESSION['member_valid']=1;
					# Check if user needs account reset
					$database->query('SELECT reset FROM users WHERE id=:id', array(':id'=>$_COOKIE['remember_me_id']));
					$user=$database->statement->fetch(PDO::FETCH_OBJ);
					# Is a password Reset needed?
					if($user->reset==1)
					{
						$reset=TRUE;
					}
					else
					{
						$reset=FALSE;
					}
				}
				else
				{
					# Return FALSE
					$status=FALSE;
					$reset=FALSE;
				}
			}
		}
		else
		{
			# Return FALSE
			$status=FALSE;
			$reset=FALSE;
		}

		if($status!=TRUE)
		{
			if($redirect===NULL)
			{
				header("Location: member.php?action=login");
			}
		}
		else
		{
			if($reset==TRUE && basename($_SERVER["REQUEST_URI"])!="member.php?action=reset-password")
			{
				header("Location: member.php?action=reset-password");
			}
			elseif($redirect==TRUE)
			{
				header("Location: member.php");
			}
		}
	}

	/**
	 * findUserActivity
	 *
	 * Find if $user_id is in the `users_activty` table.
	 *
	 * @param	$user_id
	 * @access	public
	 */
	public function findUserActivity($user_id=NULL)
	{
		global $database;

		$database->query('SELECT user_id FROM users_activity WHERE user_id=:userid', array(':userid'=>$user_id));
		$result=$database->count();

		return $result;
	}

	/**
	 * updateUserActivity
	 *
	 * Updates the users activity in the `users_activity` table.
	 *
	 * @param	$user_id
	 * @access	public
	 */
	public function updateUserActivity($user_id)
	{
		global $database;

		$date=date('c');
		if($this->findUserActivity($user_id) > 0)
		{
			$database->query('UPDATE users_activity SET time=:date WHERE user_id=:userid', array(':date'=>$date, ':userid'=>$user_id));
		}
		else
		{
			$database->query('INSERT INTO users_activity (user_id, time) VALUES (?, ?)', array($user_id, $date));
		}
	}

	/**
	 * Logout
	 */
	public function logout()
	{
		# Log
		if(isset($_SESSION['member_id']))
		{
			$user_id=$_SESSION['member_id'];
		}
		else
		{
			$user_id=$_COOKIE['remember_me_id'];
		}
		$this->userLogger($user_id, 1);

		# Clear the SESSION
		$_SESSION=array();

		# Destroy the SESSION
		session_unset();
		session_destroy();

		# Delete all old cookies and user_logged
		if(isset($_COOKIE['remember_me_id']))
		{
			$this->deleteCookie($_COOKIE['remember_me_id']);
		}

		# Redirect
		header('Location: index.php');
	}

	/**
	 * clearSession
	 *
	 * Resets Session and destroyes it,
	 * deletes any cookies
	 */
	public function clearSession()
	{
		# Log
		if(isset($_SESSION['member_id']))
		{
			$user_id=$_SESSION['member_id'];
		}
		else
		{
			$user_id=$_COOKIE['remember_me_id'];
		}
		$this->userLogger($user_id, 1);
		# Clear the SESSION
		$_SESSION=array();
		# Destroy the SESSION
		session_unset();
		session_destroy();
		# Delete all old cookies and user_logged
		if(isset($_COOKIE['remember_me_id']))
		{
			$this->deleteCookie($_COOKIE['remember_me_id']);
		}
	}

	/**
	 * Is the user Logged In?
	 */
	public function createNewCookie($id)
	{
		global $database;

		# User Rember me feature?
		if($this->remember==TRUE)
		{
			# Gen new Hash
			$hash=$this->genHash($this->genSalt(), $_SERVER['REMOTE_ADDR']);

			# Set Cookies
			# expire in 1 year
			setcookie("remember_me_id", $id, time() + 31536000);
			# expire in 1 year
			setcookie("remember_me_hash", $hash, time() + 31536000);

			# Delete old record, if any
			$database->query('DELETE FROM users_logged WHERE id=:id', array(':id'=>$id));

			# Insert new cookie
			$database->query('INSERT INTO users_logged (id, hash) VALUES(:id, :hash)', array(':id'=>$id, ':hash'=>$hash));
		}
	}

	/**
	 * Delete Cookies?
	 */
	public function deleteCookie($id)
	{
		global $database;

		# User Rember me feature?
		if($this->remember==TRUE)
		{
			# Destroy Cookies
			setcookie("remember_me_id", "", time() - 31536000);  /* expire in 1 year */
			setcookie("remember_me_hash", "", time() - 31536000);  /* expire in 1 year */

			# Clear DB
			$database->query('DELETE FROM users_logged WHERE id=:id', array(':id'=>$id));
		}
	}

	/**
	 * User Logger
	 */
	public function userLogger($userid, $action)
	{
		global $database;

		# What type of action?
		switch($action)
		{
			case 0:
				$action="Logged In";
				break;
			case 1:
				$action="Logged Out";
				break;
			case 2:
				$action="Recover Password";
				break;
			case 2:
				$action="Reset Password";
				break;
			case 3:
				$action="Accepted Press Application";
				break;
			case 4:
				$action="Denied Press Application";
				break;
			case 5:
				$action="Accepted Volunteer Application";
				break;
			case 6:
				$action="Denied Volunteer Application";
				break;
			case 7:
				$action="Deleted Email";
				break;
			case 8:
				$action="Merged Email";
				break;
		}

		# Get User's IP
		$ip=$_SERVER['REMOTE_ADDR'];

		# Date
		$timestamp=date("Y-m-d H:i:s", time());
		$database->query('INSERT INTO users_logs (userid, action, time, ip) VALUES(:userid, :action, :time, :ip)', array(':userid'=>$userid, ':action'=>$action, ':time'=>$timestamp, ':ip'=>$ip));
	}

	/**
	 * Register
	 */
	public function register()
	{
		global $database;
		global $EmailClass;

		# Set Message Array
		$message=array();

		# Check if Login is set
		if(isset($_POST['register']))
		{
			# Check Full Name
			if(!empty($_POST['full_name']))
			{
				$check_name=ucwords($_POST['full_name']);

				# Check the Full Name length
				$name_length=strlen($check_name);
				if($name_length>=6)
				{
					# Is the name Alphanumeric?
					if(!ctype_alpha(str_replace(' ', '', $check_name)))
					{
						$error[]="Please enter a valid alphabetic name";
						$full_name=NULL;
					}
					else
					{
						$full_name=$_POST['full_name'];
					}
				}
				else
				{
					$error[]="Please enter a name with 6 or more characters";
					$full_name=$check_name;
				}
			}
			else
			{
				$error[]="Please enter your full name";
				$full_name=NULL;
			}

			# Check Username
			if(!empty($_POST['username']))
			{
				$check_username=strtolower($_POST['username']);

				# Check the username length
				$length=strlen($check_username);
				if($length>=5 && $length<=25)
				{
					# Is the username Alphanumeric?
					if(preg_match('/[^a-zA-Z0-9_]/', $check_username))
					{
						$error[]="Please enter a valid alphanumeric username";
						$username=NULL;
					}
					else
					{
						$database->query('SELECT id FROM users WHERE username=:username', array(':username'=>$check_username));

						# Check if user exist in database
						if($database->count()==0)
						{
							# Require use to validate account
							if($EmailClass->email_verification==TRUE)
							{
								# Check if user exist in inactive database
								$database->query('SELECT date FROM users_inactive WHERE username=:username', array(':username'=>$check_username));

								# If user incative is older than 24 hours
								$user=$database->statement->fetch(PDO::FETCH_OBJ);
								if($database->count()==0 or time()>=strtotime($user->date)+86400)
								{
									# If user incative is older than 24 hours
									$username=$_POST['username'];
								}
								else
								{
									$error[]="Username already in use";
									$username=$check_username;
								}
							}
							else
							{
								$username=$_POST['username'];
							}
						}
						else
						{
							$error[]="Username already in use";
							$username=$check_username;
						}
					}
				}
				else
				{
					$error[]="Please enter a username between 5 to 25 characters";
					$username=$check_username;
				}
			}
			else
			{
				$error[]="Please enter a username";
				$username=NULL;
			}

			# Check Password
			if(!empty($_POST['password']))
			{
				# Do passwords match?
				if(isset($_POST['password_again']) && $_POST['password_again']==$_POST['password'])
				{
					# Is the password long enough?
					$length=strlen($_POST['password']);
					if($length>=8)
					{
						$password=$_POST['password'];
					}
					else
					{
						$error[]="Passwords must be atleast 8 characters";
					}
				}
				else
				{
					$error[]="Passwords must match";
				}
			}
			else
			{
				$error[]="Please enter a password";
			}

			# Check E-Mail
			if(!empty($_POST['email']))
			{
				$check_email=strtolower($_POST['email']);
				$check_email_again=strtolower($_POST['email_again']);

				# Do E-Mails match?
				if(isset($check_email_again) && $check_email_again==$check_email)
				{
					$length=strlen($check_email);

					# Is the E-Mail really an E-Mail?
					if(filter_var($check_email, FILTER_VALIDATE_EMAIL)==TRUE)
					{
						$database->query('SELECT id FROM users WHERE email=:email', array(':email'=>$check_email));

						# Check if user exist with email
						if($database->count()==0)
						{
							# Require use to validate account
							if($EmailClass->email_verification==TRUE)
							{
								# Check if user exist with email in inactive
								$database->query('SELECT date FROM users_inactive WHERE email=:email', array(':email'=>$check_email));

								# If user incative is older than 24 hours
								$user=$database->statement->fetch(PDO::FETCH_OBJ);
								if($database->count()==0 or time()>=strtotime($user->date)+86400)
								{
									$email=$check_email;
									$email_again=$check_email_again;
								}
								else
								{
									$error[]="E-Mail already in use";
									$email=NULL;
									$email_again=NULL;
								}
							}
							else
							{
								$email=$check_email;
								$email_again=$check_email_again;
							}
						}
						else
						{
							$error[]="E-Mail already in use";
							$email=NULL;
							$email_again=NULL;
						}
					}
					else
					{
						$error[]="Invalid E-Mail";
						$email=$check_email;
						$email_again=$check_email_again;
					}
				}
				else
				{
					$error[]="E-Mails must match";
					$email=$check_email;
					$email_again=$check_email_again;
				}
			}
			else
			{
				$error[]="Please enter an E-Mail";
				$email=NULL;
				$email_again=NULL;
			}

			# Captcha?
			if($this->captcha==TRUE)
			{
				# Check E-Mail
				if(!empty($_POST['captcha']))
				{
					if($_POST['captcha']!=$_SESSION['captcha'])
					{
						$error[]="Invalid Captcha";
					}
				}
				else
				{
					$error[]="Please fill in the Captcha";
				}
			}

			# Is both Username and Password set?
			if(!isset($error))
			{
				# User is really making an account, flush any current users
				$this->clearSession();

				$return_form=0;

				# Final Format
				$password=$this->genHash($this->genSalt(), $password);

				# Send the user a welcome E-Mail
				if($EmailClass->email_welcome==TRUE)
				{
					# Send the user an E-Mail
					# Can we send a user an E-Mail?
					if(function_exists('mail') && $EmailClass->email_master!=NULL)
					{
						$subject=$EmailClass->site_name." - Account Created";
						$body_content="Hi ".$username.",\r\n\r\nThanks for signing-up!";

						# Send it
						$EmailClass->sendEmail($email, $subject, $body_content, $username);
					}
				}

				# Require use to validate account
				if($EmailClass->email_verification==TRUE)
				{
					# Send the user an E-Mail
					# Can we send a user an E-Mail?
					if(function_exists('mail') && $EmailClass->email_master!=NULL)
					{
						$verCode=md5(uniqid(rand(), TRUE).md5(uniqid(rand(), TRUE)));
						$subject=$EmailClass->site_name." - Account Created";
						$body_content="Hi ".$username.",\r\n\r\nThanks for signing-up!\r\n\r\nTo activate your account please click the link below, or copy / paste it into the address bar of your web browser\r\n\r\n".$this->currentPath()."member.php?action=verification&vercode=".$verCode;

						# Send it
						if($EmailClass->sendEmail($email, $subject, $body_content, $username))
						{
							# Insert Data
							$date=date("Y-m-d H:i:s", time());
							$database->query('INSERT INTO users_inactive (verCode, full_name, username, password, email, date) VALUES (?, ?, ?, ?, ?, ?)', array($verCode, $full_name, $username, $password, $email, $date));
							$success[]="You account has been created!";
							$info[]="Please check your e-mail to activate your account";

							# Redirect
							echo '<meta http-equiv="refresh" content="2;url=index.php" />';
						}
						else
						{
							$error[]="Could not send e-mail!<br />Please contact the site admin.";
						}
					}
					else
					{
						$error[]="It seems this server cannot send e-mails!<br />Could not send e-mail!<br />Please contact the site admin.";
					}
				}
				else
				{
					# Insert Data
					$date=date("Y-m-d", time());
					$database->query('INSERT INTO users (username, password, email, usertype, date) VALUES (?, ?, ?, ?)', array($username, $password, $email, 'Member', $date));
					$success[]="You account has been created!";

					# Redirect
					echo '<meta http-equiv="refresh" content="2;url=index.php" />';
				}
			}
			else
			{
				if($this->captcha==TRUE)
				{
					# If an error recreate captcha
					$this->randomString();
				}
				$return_form=1;
			}
		}
		else
		{
			# Report Status
			$info[]="Please fill in all the information";
			$return_form=1;
			$full_name=NULL;
			$username=NULL;
			$email=NULL;
			$email_again=NULL;
			if($this->captcha==TRUE)
			{
				# If an error recreate captcha
				$this->randomString();
			}
		}

		# Register Form
		# Captcha?
		if($this->captcha==TRUE)
		{
			$captcha_input='
        <label>
		<span>Captcha</span>
		<span id="captcha">
			<input type="text" name="captcha" value="" />
			<img alt="Captcha" src="'.$this->currentPath().'assets/captcha.php" />
		</span>
	</label>
			';
		}
		else
		{
			$captcha_input=NULL;
		}

		$form='
<form name="register" action="'.$this->currentPage().'" method="post">
	<label>
		<span>Full Name</span>
		<input type="text" name="full_name" value="'.$full_name.'" />
	</label>
	<label>
		<span>Username</span>
		<input type="text" name="username" value="'.$username.'" />
	</label>
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	<label>
		<span>Password Again</span>
		<input type="password" name="password_again" />
	</label>
	<label>
		<span>E-Mail</span>
		<input type="text" name="email" value="'.$email.'" />
	</label>
	<label>
		<span>E-Mail Again</span>
		<input type="text" name="email_again" value="'.$email_again.'" />
	</label>
	'.$captcha_input.'
	<input name="register" type="submit" value="Register" />
</form>
		';

		# Combine Data
		$data='<div id="register" class="group">
			<h1>Create an account</h1>';

		# Report any Info
		if(isset($info))
		{
			foreach($info as $message)
			{
				$data.='<div class="notice info">'.$message.'</div>';
			}
		}
		# Report any Errors
		if(isset($error))
		{
			foreach($error as $message)
			{
				$data.='<div class="notice error">'.$message.'</div>';
			}
		}
		# Report any Success
		if(isset($success))
		{
			foreach($success as $message)
			{
				$data.='<div class="notice success">'.$message.'</div>';
			}
		}

		# Do we need the login form?
		if($return_form==1)
		{
			$data.=$form;
		}

		$data.='</div>';

		# Return data
		return $data;
	}

	/**
	 * Random String
	 */
	public function randomString()
	{
		$chars='1234567890AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
		$string="";

		for($i=0; $i<6; $i++)
		{
			$string.=($i%2) ? $chars[mt_rand(10, 23)] : $chars[mt_rand(0, 18)];
		}
		$_SESSION['captcha']=$string;

		return $_SESSION['captcha'];
	}

	/**
	 * Recover Password
	 */
	public function recoverPassword()
	{
		global $database;
		global $EmailClass;

		# Recover Password Form
		$form='
<form name="recover" action="'.$this->currentPage().'" method="post" class="group">
	<input type="text" name="email" class="swifttextlarge" />
	<input name="recover" type="submit" class="headerbutton" style="margin-top: 10px; margin-left: 0;" value="Recover" />
</form>
		';
		if(isset($_POST['recover']))
		{
			$database->query('SELECT username, email FROM users WHERE email=:email', array(':email'=>$_POST['email']));

			# Check if user exist
			if($database->count()>='1')
			{
				# Get the users info
				$user=$database->statement->fetch(PDO::FETCH_OBJ);

				# Create a random password
				$chars='1234567890AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz!@#$%^&*';
				$temp_password="";
				for($i=0; $i<10; $i++)
				{
					$temp_password.=($i%2) ? $chars[mt_rand(10, 23)] : $chars[mt_rand(0, 18)];
				}

				# Can we send a user an E-Mail?
				if(function_exists('mail') && $EmailClass->email_master!=NULL)
				{
					# E-Mail
					$subject=$EmailClass->site_name." - Requested Password Reset";
					$body_content="Hi ".$user->username.",\n\nYour password has been temporarily set to ".$temp_password.".\n\nPlease change your password once your are logged in.";

					# Send it
					if($EmailClass->sendEmail($user->email, $subject, $body_content, $username))
					{
						# Upadte password only if you can mail them it!
						$database->query('UPDATE users SET password=:password WHERE email=:email', array(':password'=>$this->genHash($this->genSalt(), $temp_password), ':email'=>$_POST['email']));

						if(isset($_SESSION['member_id']))
						{
							$user_id=$_SESSION['member_id'];
						}
						else
						{
							$user_id=$_COOKIE['remember_me_id'];
						}
						$this->userLogger($user_id, 2);

						$success[]='Please check your e-mail';
						$return_form=0;
					}
					else
					{
						$error[]='Could not send e-mail!<br />Contact the site admin.';
						$return_form=0;
					}
				}
				else
				{
					$error[]='Could not send e-mail!<br />Contact the site admin.';
					$return_form=0;
				}
			}
			else
			{
				$error[]='Sorry that e-mail does not exist in our database';
				$return_form=1;
			}
		}
		else
		{
			$info[]='Please enter your e-mail';
			$return_form=1;
		}

		$data='<div id="recover-password" class="group">
			<h1>Recover your password</h1>';

		# Report any Info
		if(isset($info))
		{
			foreach($info as $message)
			{
				$data.='<div class="notice info">'.$message.'</div>';
			}
		}
		# Report any Errors
		if(isset($error))
		{
			foreach($error as $message)
			{
				$data.='<div class="notice error">'.$message.'</div>';
			}
		}
		# Report any Success
		if(isset($success))
		{
			foreach($success as $message)
			{
				$data.='<div class="notice success">'.$message.'</div>';
			}
		}

		# We need the login form?
		if($return_form==1)
		{
			$data.=$form;
		}

		$data.='</div>';

		return $data;
	}

	/**
	 * Reset Password
	 */
	public function resetPassword()
	{
		global $database;

		if(isset($_POST['reset-password']))
		{
			# Check Password
			if(!empty($_POST['password']))
			{
				# Do passwords match?
				if(isset($_POST['password_again']) && $_POST['password_again']==$_POST['password'])
				{
					# Is the password long enough?
					$length=strlen($_POST['password']);
					if($length>=8)
					{
						$password=$_POST['password'];
					}
					else
					{
						$error[]="Passwords must be atleast than 8 characters";
					}
				}
				else
				{
					$error[]="Passwords must match";
				}
			}
			else
			{
				$error[]="Please enter a password";
			}
			if(!isset($error))
			{
				if(isset($_SESSION['member_valid']))
				{
					$id=$_SESSION['member_id'];
				}
				else
				{
					$id=$_COOKIE['remember_me_id'];
				}
				$password=$this->genHash($this->genSalt(), $password);
				$database->query('UPDATE users SET password=:password, reset=0 WHERE id=:id', array(':password'=>$password, ':id'=>$id));
				$this->userLogger($id, 3);

				# Report Status
				$success[]="Password has been updated!";
				$return_form=0;

				# Redirect
				echo '<meta http-equiv="refresh" content="2;url=index.php" />';
			}
			else
			{
				$return_form=1;
			}
		}
		else
		{
			# Report Status
			$info[]="Please choose a new password";
			$return_form=1;
		}

		# Reset Password Form
		$form='
<form name="reset-password" action="'.$this->currentPage().'" method="post">
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	<label>
		<span>Password Again</span>
		<input type="password" name="password_again" />
	</label>
	<input name="reset-password" type="submit" value="Reset Password" />
</form>
		';

		# Combine Data
		$data='<div id="reset-password" class="group">
			<h1>Reset your password</h1>';

		# Report any Info
		if(isset($info))
		{
			foreach($info as $message)
			{
				$data.='<div class="notice info">'.$message.'</div>';
			}
		}
		# Report any Errors
		if(isset($error))
		{
			foreach($error as $message)
			{
				$data.='<div class="notice error">'.$message.'</div>';
			}
		}
		# Report any Success
		if(isset($success))
		{
			foreach($success as $message)
			{
				$data.='<div class="notice success">'.$message.'</div>';
			}
		}

		# Do we need the login form?
		if($return_form==1)
		{
			$data.=$form;
		}

		$data.='</div>';

		# Return data
		return $data;
	}

	/**
	 * preferences
	 *
	 * Retreives account notification settings from the `users` table.
	 *
	 * @access	public
	 */
	public function preferences()
	{
		global $database;
		global $forum;
		global $EmailClass;

		$user_id=$this->getUsersID();
		$view_all_assigned=$this->getUsersAssignedView($user_id);
		$email_notify=$this->getEmailNotify($user_id);
		$pa_notify=$this->getPaNotify($user_id);
		$va_notify=$this->getVaNotify($user_id);
		$forum_notify=$forum->getForumNotify($user_id);

		if(isset($_POST['PreferencesForm']))
		{
			if(isset($_POST['view_all_assigned']) || isset($_POST['email_notify']) || isset($_POST['pa_notify']) || isset($_POST['va_notify']) || isset($_POST['forum_notify']))
			{
				$view_all_assigned=$_POST['view_all_assigned'];
				$email_notify=$_POST['email_notify'];
				$pa_notify=$_POST['pa_notify'];
				$va_notify=$_POST['va_notify'];
				$forum_notify=$_POST['forum_notify'];
			}

			# Is both Full Name and Email set?
			if(!isset($error))
			{
				# Insert Data
				$date=date("Y-m-d", time());
				$database->query('UPDATE users SET view_all_assigned=:viewallassigned, email_notify=:emailnotify, pa_notify=:panotify, va_notify=:vanotify, forum_notify=:forumnotify WHERE id=:id', array(':viewallassigned'=>$view_all_assigned, ':emailnotify'=>$email_notify, ':panotify'=>$pa_notify, ':vanotify'=>$va_notify, ':forumnotify'=>$forum_notify, ':id'=>$user_id));
				$success[]="Your preferences have been updated!";
			}
		}

		$form='
	  <form method="post" action="'.$this->currentPage().'" name="PreferencesForm">
		<div class="boxcontainer">
		  <div class="boxcontainerlabel">Preferences</div>
		  <div class="boxcontainercontent">
			<table class="hlineheader"><tr><th rowspan="2" nowrap>Notification Options</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			<table width="100%" border="0" cellspacing="1" cellpadding="4">
			  <tr>
		    <td width="220" align="left" valign="middle" class="zebraodd">New Email:</td>
		    <td>
		      <input type="radio" name="email_notify" id="email_notify" value="Yes"'.($email_notify=="Yes" ? ' checked' : '').'> Yes &nbsp;&nbsp; <input type="radio" name="email_notify" id="email_notify" value="No"'.($email_notify=="No" ? ' checked' : '').'> No
		    </td>
		  </tr>
  		  <tr>
		    <td width="220" align="left" valign="middle" class="zebraodd">New Press Application:</td>
		    <td>
		      <input type="radio" name="pa_notify" id="pa_notify" value="Yes"'.($pa_notify=="Yes" ? ' checked' : '').'> Yes &nbsp;&nbsp; <input type="radio" name="pa_notify" id="pa_notify" value="No"'.($pa_notify=="No" ? ' checked' : '').'> No
		    </td>
		  </tr>
		  <tr>
		    <td width="220" align="left" valign="middle" class="zebraodd">New Volunteer Application:</td>
		    <td>
		      <input type="radio" name="va_notify" id="va_notify" value="Yes"'.($va_notify=="Yes" ? ' checked' : '').'> Yes &nbsp;&nbsp; <input type="radio" name="va_notify" id="va_notify" value="No"'.($va_notify=="No" ? ' checked' : '').'> No
		    </td>
		  </tr>
		  <tr>
		    <td width="220" align="left" valign="middle" class="zebraodd"><label for="forum_notify">Subscribe to Forum Topics:</label></td>
		    <td>
		      <select class="swiftselect" name="forum_notify" id="forum_notify">
				<option value="1"'.($forum_notify=="Off" ? ' selected' : '').'>Do Not Subscribe</option>
				<option value="2"'.($forum_notify=="Automatic" ? ' selected' : '').'>Automatically Subscribe</option>
				<option value="3"'.($forum_notify=="Manual" ? ' selected': '').'>Manually Subscribe</option>
			  </slect>
			</td>
		  </tr>
		</table><br />
		<table class="hlineheader"><tr><th rowspan="2" nowrap>Viewing Options</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
		<table width="100%" border="0" cellspacing="1" cellpadding="4">
		  <tr>
		    <td width="220" align="left" valign="middle" class="zebraodd">All Assigned Emails:</td>
		    <td>
		      <input type="radio" name="view_all_assigned" id="view_all_assigned" value="Yes"'.($view_all_assigned=="Yes" ? ' checked' : '').'> Yes &nbsp;&nbsp; <input type="radio" name="view_all_assigned" id="view_all_assigned" value="No"'.($view_all_assigned=="No" ? ' checked' : '').'> No
		    </td>
		  </tr>
		</table><br />
		<div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="PreferencesForm" /></div>
	  </div>
	</div>
  </form>';

		# Combine Data
		$data="";

		# Report any Errors
		if(isset($error))
		{
			foreach($error as $message)
			{
				$data.='<div class="dialogerror"><div class="dialogerrorsub"><div class="dialogerrorcontent">'.$message.'</div></div></div>';
			}
		}
		# Report any Success
		if(isset($success))
		{
			foreach($success as $message)
			{
				$data.='<div class="dialoginfo"><div class="dialoginfosub"><div class="dialoginfocontent">'.$message.'</div></div></div>';
			}
		}

		# Add the form
		$data.=$form;

		# Return data
		return $data;
	}

	/**
	 * changeSettings
	 *
	 * Retreives account information from the `users` table.
	 *
	 * @access	public
	 */
	public function changeSettings()
	{
		global $database;
		global $EmailClass;

		$user_id=$this->getUsersID();
		$salutation=$this->getUsersSalutation($user_id);
		$full_name=$this->getUsersName($user_id);
		$userdesignation="";
		$phone="";
		$email=$this->getUsersEmail($user_id);

		if(isset($_POST['ProfileForm']))
		{
			# Check Full Name
			if(!empty($_POST['full_name']))
			{
				$check_name=ucwords($_POST['full_name']);

				# Check the Full Name length
				$name_length=strlen($check_name);
				if($name_length >= 6)
				{
					# Is the name Alphabetic?
					if(!ctype_alpha(str_replace(' ', '', $check_name)))
					{
						$error[]="Please enter a valid alphabetic name";
					}
					else
					{
						$full_name=$_POST['full_name'];
					}
				}
				else
				{
					$error[]="Please enter a name with 6 or more characters";
					$full_name=$check_name;
				}
			}

			# Check E-Mail
			if(!empty($_POST['email']))
			{
				if($_POST['email']!=$email)
				{
					$check_email=strtolower($_POST['email']);

					$length=strlen($check_email);

					# Is the E-Mail really an E-Mail?
					if(filter_var($check_email, FILTER_VALIDATE_EMAIL)==TRUE)
					{
						$database->query('SELECT id FROM users WHERE email=:email', array(':email'=>$check_email));

						# Check if user exist with email
						if($database->count()==0)
						{
							# Require use to validate account
							if($EmailClass->email_verification==TRUE)
							{
								# Check if user exist with email in inactive
								$database->query('SELECT date FROM users_inactive WHERE email=:email', array(':email'=>$check_email));

								# If user incative is older than 24 hours
								$user=$database->statement->fetch(PDO::FETCH_OBJ);
								if($database->count()==0 or time() >= strtotime($user->date) + 86400)
								{
									$email=$check_email;
								}
								else
								{
									$error[]="E-Mail already in use";
								}
							}
							else
							{
								$email=$check_email;
							}
						}
						else
						{
							$error[]="E-Mail already in use";
						}
					}
					else
					{
						$error[]="Invalid E-Mail";
						$email=$check_email;
					}
				}
			}
			else
			{
				$error[]="Please enter an E-Mail";
			}

			# Is both Full Name and Email set?
			if(!isset($error))
			{
				$salutation=$_POST['salutation'];

				# Insert Data
				$date=date("Y-m-d", time());
				$database->query('UPDATE users SET salutation=:salutation, full_name=:fullname, email=:email WHERE id=:id', array(':salutation'=>$salutation, ':fullname'=>$full_name, ':email'=>$email, ':id'=>$user_id));
				$success[]="Your account has been updated!";
			}
		}

		$form='
	  <form method="post" action="'.$this->currentPage().'" name="ProfileForm">
	    <div class="boxcontainer">
	      <div class="boxcontainerlabel">My Profile</div>
	      <div class="boxcontainercontent">
	        <table class="hlineheader"><tr><th rowspan="2" nowrap>General Information</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
	        <table width="100%" border="0" cellspacing="1" cellpadding="4">
		  <tr>
		    <td align="left" valign="middle">
		      <table width="100%" border="0" cellspacing="1" cellpadding="4">
		        <tr>
			  <td width="200" align="left" valign="middle" class="zebraodd">Full Name:</td>
			  <td><select class="swiftselect" name="salutation"><option value=""'.($salutation=="" ? ' selected' : '').'>&nbsp;</option><option value="2"'.($salutation=="Mr" ? ' selected' : '').'>Mr.</option><option value="3"'.($salutation=="Ms" ? ' selected' : '').'>Ms.</option><option value="4"'.($salutation=="Mrs" ? ' selected' : '').'>Mrs.</option><option value="5"'.($salutation=="Dr" ? ' selected' : '').'>Dr.</option></select> <input name="full_name" type="text" size="20" class="swifttextlarge" value="'.$full_name.'" /></td>
			</tr>
		      </table>
		    </td>
		  </tr>
		</table><br />
		<table class="hlineheader"><tr><th rowspan="2" nowrap>Profile Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
		<table width="100%" border="0" cellspacing="1" cellpadding="4">
		  <tr>
		    <td width="200" align="left" valign="middle" class="zebraodd">Email:</td>
		    <td><input name="email" type="text" size="20" class="swifttextlarge" value="'.$email.'" /></td>
		  </tr>
		</table>
		<div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="ProfileForm" /></div>
	      </div>
	    </div>
	  </form>';

		# Combine Data
		$data="";

		# Report any Errors
		if(isset($error))
		{
			foreach($error as $message)
			{
				$data.='<div class="dialogerror"><div class="dialogerrorsub"><div class="dialogerrorcontent">'.$message.'</div></div></div>';
			}
		}
		# Report any Success
		if(isset($success))
		{
			foreach($success as $message)
			{
				$data.='<div class="dialoginfo"><div class="dialoginfosub"><div class="dialoginfocontent">'.$message.'</div></div></div>';
			}
		}

		# Add the form
		$data.=$form;

		# Return data
		return $data;
	}

	# Change Password
	public function changePassword()
	{
		global $database;

		if(isset($_POST['ChangePasswordForm']))
		{
			# Check Password
			if(!empty($_POST['password']))
			{
				# Do passwords match?
				if(isset($_POST['password_again']) && $_POST['password_again']==$_POST['password'])
				{
					# Is the password long enough?
					$length=strlen($_POST['password']);
					if($length>=8)
					{
						$password=$_POST['password'];
					}
					else
					{
						$error[]="Passwords must be atleast than 8 characters";
					}
				}
				else
				{
					$error[]="Passwords must match";
				}
			}
			else
			{
				$error[]="Please enter a password";
			}
			if(!isset($error))
			{
				if(isset($_SESSION['member_valid']))
				{
					$id=$_SESSION['member_id'];
				}
				else
				{
					$id=$_COOKIE['remember_me_id'];
				}
				$password=$this->genHash($this->genSalt(), $password);
				$database->query('UPDATE users SET password=:password WHERE id=:id', array(':password'=>$password, ':id'=>$id));
				$this->userLogger($id, 3);

				# Report Status
				$success[]="Password has been updated!";
			}
		}

		$form='
	  <form method="post" action="'.$this->currentPage().'" name="ChangePasswordForm">
	    <div class="boxcontainer">
	      <div class="boxcontainerlabel">Change Password</div>
	      <div class="boxcontainercontent">
	        <table class="hlineheader"><tr><th rowspan="2" nowrap>Password Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
		<table width="100%" border="0" cellspacing="1" cellpadding="4">
		  <tr>
		    <td align="left" valign="middle" class="zebraodd">New Password:</td>
		    <td><input name="password" type="password" size="20" value="" class="swifttextlarge swiftpassword" /></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd">New Password (repeat):</td>
		    <td><input name="password_again" type="password" size="20" value="" class="swifttextlarge swiftpassword" /></td>
		  </tr>
		</table><br />
		<div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="ChangePasswordForm" /></div>
	      </div>
	    </div>
	  </form>';

		$data="";

		# Report any Errors
		if(isset($error))
		{
			foreach($error as $message)
			{
				$data.='<div class="notice error">' . $message . '</div>';
			}
		}
		# Report any Success
		if(isset($success))
		{
			foreach($success as $message)
			{
				$data.='<div class="notice success">' . $message . '</div>';
			}
		}

		$data.=$form;

		# Return data
		return $data;
	}

	# E-Mail Verification
	public function verification()
	{
		global $database;
		global $EmailClass;

		if(isset($_GET['vercode']))
		{
			$verCode=$_GET['vercode'];
			$database->query('SELECT full_name, username, password, email FROM users_inactive WHERE verCode=:verCode', array(':verCode'=>$verCode));
			$user=$database->statement->fetch(PDO::FETCH_OBJ);

			if(!empty($user->username) && !empty($user->password))
			{
				# Insert Data
				$database->query('INSERT INTO users (full_name, username, password, email, date) VALUES (?, ?, ?, ?, ?)', array($user->full_name, $user->username, $user->password, $user->email, date('Y-m-d')));
				$database->query('INSERT INTO users_sorting (user_id) VALUES (?)', array($database->lastInsertId()));
				$verified=TRUE;
			}
			else
			{
				if(isset($_POST['verify']))
				{
					if(!empty($_POST['username']))
					{
						$check_username=strtolower($_POST['username']);

						$length=strlen($check_username);
						if($length >= 5 && $length <= 25)
						{
							if(preg_match('/[^a-zA-Z0-9_]/', $check_username))
							{
								$error[]="Please enter a valid alphanumeric username";
								$username=NULL;
							}
							else
							{
								$database->query('SELECT id FROM users WHERE username=:username', array(':username'=>$check_username));

								if($database->count()==0)
								{
									if($EmailClass->email_verification==TRUE)
									{
										$database->query('SELECT date FROM users_inactive WHERE username=:username', array(':username'=>$check_username));

										if($database->count()==0 or time() >= strtotime($user->date) + 86400)
										{
											$username=$_POST['username'];
										}
										else
										{
											$error[]="Username already in use";
											$username=$check_username;
										}
									}
									else
									{
										$username=$_POST['username'];
									}
								}
								else
								{
									$error[]="Username already in use";
									$username=$check_username;
								}
							}
						}
						else
						{
							$error[]="Please enter a username between 5 to 25 characters";
							$username=$check_username;
						}
					}
					else
					{
						$error[]="Please enter a username";
						$username=NULL;
					}

					if(!empty($_POST['password']))
					{
						if(isset($_POST['password_again']) && $_POST['password_again']==$_POST['password'])
						{
							$length=strlen($_POST['password']);
							if($lengtH>=8)
							{
								$password=$_POST['password'];
							}
							else
							{
								$error[]="Passwords must be atleast 8 characters";
							}
						}
						else
						{
							$error[]="Passwords must match";
						}
					}
					else
					{
						$error[]="Please enter a password";
					}

					if(!isset($error))
					{
						$return_form=0;
						$verified=TRUE;

						# Final Format
						$password=$this->genHash($this->genSalt(), $password);

						# Insert Data
						$date=date("Y-m-d", time());
						$database->query('INSERT INTO users (full_name, username, password, email, date) VALUES (?, ?, ?, ?, ?)', array($user->full_name, $username, $password, $user->email, date('Y-m-d')));
						$database->query('INSERT INTO users_sorting (user_id) VALUES (?)', array($database->lastInsertId()));
					}
					else
					{
						$return_form=1;
					}
				}
				else
				{
					# Report Status
					$info[]="Please fill in all the information";
					$return_form=1;
					$username=NULL;
				}
				$form='
					<form name="verify" action="'.$this->currentPage().'" method="post">
						<label>
							<span>Username</span>
							<input type="text" name="username" value="'.$username.'" class="swifttextlarge" />
						</label>
						<label>
							<span>Password</span>
							<input type="password" name="password" class="swifttextlarge" />
						</label>
						<label>
							<span>Password Again</span>
							<input type="password" name="password_again" class="swifttextlarge" />
						</label>
						<input name="verify" type="submit" class="headerbutton" style="margin-top: 10px; margin-left: 0px;" value="Verify" />
					</form>
					';
			}

			if($verified)
			{
				# Clear Inactive
				$database->query('DELETE FROM users_inactive WHERE verCode=:verCode', array(':verCode'=>$verCode));
				# Message
				$success[]="Your account has been verified!";

				# Redirect
				echo '<meta http-equiv="refresh" content="2;url=index.php" />';
			}
		}
		else
		{
			$info[]='No Verification Code!';
			$return_form=FALSE;
		}

		# Combine Data
		$data='<div id="verification" class="group">
			<h1>Account Verification</h1>';

		# Report any Info
		if(isset($info))
		{
			foreach($info as $message)
			{
				$data.='<div class="notice info">' . $message . '</div>';
			}
		}
		# Report any Errors
		if(isset($error))
		{
			foreach($error as $message)
			{
				$data.='<div class="notice error">' . $message . '</div>';
			}
		}
		# Report any Success
		if(isset($success))
		{
			foreach($success as $message)
			{
				$data.='<div class="notice success">' . $message . '</div>';
			}
		}

		# Do we need the login form?
		if($return_form==1)
		{
			$data.=$form;
		}

		$data.='</div>';

		# Return data
		return $data;
	}

	/**
	 * getUsersID
	 *
	 * Retreives the users ID from the `users` table.
	 *
	 * @access	public
	 */
	public function getUsersID()
	{
		if(isset($_SESSION['member_id']))
		{
			$user_id=$_SESSION['member_id'];
		}
		else
		{
			$user_id=$_COOKIE['remember_me_id'];
		}
		return $user_id;
	}

	/**
	 * getUsersSalutation
	 *
	 * Retreives the users salutation from the `users` table.
	 *
	 * @access	public
	 */
	public function getUsersSalutation($user_id)
	{
		global $database;

		$database->query('SELECT salutation FROM users WHERE id=:userid', array(':userid'=>$user_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->salutation;
	}

	/**
	 * getUsersName
	 *
	 * Retreives the users name from the `users` table. If no result then return username.
	 *
	 * @access	public
	 */
	public function getUsersName($user_id)
	{
		global $database;

		$database->query('SELECT full_name, username FROM users WHERE id=:userid', array(':userid'=>$user_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		if(!empty($result->full_name))
		{
			return $result->full_name;
		}
		else
		{
			# Return PHP errors. Commented out for now.
			//return $result->username;
		}
	}

	/**
	 * getUsersEmail
	 *
	 * Retreives the users emails from the `users` table.
	 *
	 * @access	public
	 */
	public function getUsersEmail($user_id)
	{
		global $database;

		$database->query('SELECT email FROM users WHERE id=:userid', array(':userid'=>$user_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		if($result)
		{
			return $result->email;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * getUsertype
	 *
	 * Retreives the users type from the `users` table.
	 *
	 * @access	public
	 */
	public function getUsertype($email=NULL, $user_id=NULL)
	{
		global $database;

		if($email!==NULL)
		{
			$database->query('SELECT usertype FROM users WHERE email=:email', array(':email'=>$email));
		}
		elseif($user_id!==NULL)
		{
			$database->query('SELECT usertype FROM users WHERE id=:userid', array(':userid'=>$user_id));
		}

		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		if($result)
		{
			return $result->usertype;
		}
	}

	/**
	 * getUsersEmailView
	 *
	 * Retreives the users view_all_email from the `users` table.
	 *
	 * @access	public
	 */
	public function getUsersEmailView($user_id)
	{
		global $database;

		$database->query('SELECT view_all_email FROM users WHERE id=:userid', array(':userid'=>$user_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->view_all_email;
	}

	/**
	 * getUsersAssignedView
	 *
	 * Retreives the users view_all_assigned from the `users` table.
	 *
	 * @access	public
	 */
	public function getUsersAssignedView($user_id)
	{
		global $database;

		$database->query('SELECT view_all_assigned FROM users WHERE id=:userid', array(':userid'=>$user_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->view_all_assigned;
	}

	/**
	 * getEmailNotify
	 *
	 * Retreives the users email_notify from the `users` table.
	 *
	 * @access	public
	 */
	public function getEmailNotify($user_id=NULL)
	{
		global $database;

		if($user_id!==NULL)
		{
			$database->query('SELECT email_notify FROM users WHERE id=:userid', array(':userid'=>$user_id));
			$result=$database->statement->fetch(PDO::FETCH_ASSOC);
			return $result['email_notify'];
		}
		else
		{
			$database->query('SELECT full_name, email FROM users WHERE email_notify=1', array());
			$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		}
	}

	/**
	 * getPolcies
	 *
	 * Retrieves policies from the `policies` table.
	 *
	 * @param	$asc_desc
	 * @param	$limit
	 * @access	public
	 */
	public function getPolicies($asc_desc='ASC', $limit=NULL)
	{
		global $database;

		$database->query('SELECT id, button_name, question, response, clipboard, policy_order FROM policies ORDER BY policy_order '.$asc_desc.$limit, array());
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * isAdmin
	 *
	 * Checks if user is an Administrator or Webmaster.
	 *
	 * @access	public
	 */
	public function isAdmin($user_id)
	{
		global $database;

		$database->query('SELECT email FROM users WHERE id=:userid', array(':userid'=>$user_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		$usertype=$this->getUsertype($result->email);

		if(($usertype=="Administrator") || ($usertype=="Webmaster"))
		{
			return $usertype;
		}
		elseif($usertype=="Member")
		{
			return FALSE;
		}
		else
		{
			header("Location: member.php?action=login");
		}
	}

	/**
	 * listAdmins
	 *
	 * Lists Administrators and Webmasters
	 *
	 * @access	public
	 */
	public function listAdmins()
	{
		global $database;

		$database->query('SELECT id, full_name FROM users WHERE usertype=:admin OR usertype=:webmaster', array(':admin'=>"Administrator", ':webmaster'=>"Webmaster"));
		$results=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $results;
	}

	/**
	 * listCategories
	 *
	 * Lists the category feild in the emails table
	 *
	 * @access	public
	 */
	public function listCategories()
	{
		global $database;

		$database->query('SELECT category FROM emails WHERE category=:booking OR category=:info OR category=:vendors GROUP BY category', array(':booking'=>"Booking", ':info'=>"Info", ':vendors'=>"Vendors"));
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * countAssigned
	 *
	 * Count assigned emails
	 *
	 * @access	public
	 */
	public function countAssigned($user_id)
	{
		global $database;

		$database->query('SELECT id FROM emails WHERE assigned_to=:userid AND status!=3', array(':userid'=>$user_id));
		$result=$database->count();

		return $result;
	}

	/**
	 * removeIndex
	 *
	 * Removes "index.php" from the passed URL.
	 *
	 * @param 	$url 					The URL to check.
	 * @access	public
	 */
	public function removeIndex($url)
	{
		# Check if the link is to an index page.
		if(strpos($url, 'index.php')===FALSE)
		{
			$url=$url;
		}
		else
		{
			$url=str_replace('index.php', '', $url);
		}

		return $url;
	}

	/**
	 * removeSchemeName
	 *
	 * Removes scheme name (ie http://) from the passed URL.
	 *
	 * @param 	$url					The URL to check.
	 * @access	public
	 */
	public function removeSchemeName($url)
	{
		# Check if the link has a scheme name.
		if(preg_match('/^((https?|s?ftp)\:\/\/)|(mailto\:)/', $url)===0)
		{
			$url=$url;
		}
		else
		{
			$url=preg_replace('/^((https?|s?ftp)\:\/\/)|(mailto\:)/', '', $url, 1);
		}

		return $url;
	}

	/**
	 * removePageQuery
	 *
	 * Removes "?page=#" query from the passed URL.
	 *
	 * @param 	$url					The URL to check.
	 * @access	public
	 */
	public function removePageQuery($url)
	{
		# Check if the link has a page query.
		if(strpos($url, '?page=')===FALSE)
		{
			$url=$url;
		}
		else
		{
			$url=preg_replace('/page\=[0-9]+\&/', '', $url);
		}

		return $url;
	}

	/**
	 * addCurrentClass
	 *
	 * Adds the "current" css class if we are already at the page that the link sends us to.
	 *
	 * @param 	$link					The link to check.
	 * @access	public
	 */
	public function addCurrentClass($link, $exact_match=FALSE)
	{
		# Create and empty variable to hold the potential class tag.
		$class='';

		# Remove any Scheme Name (ie http://) from the passed link.
		$link=$this->removeSchemeName($link);

		# Remove any index page from the passed link.
		$current_page=$this->removeIndex(FULL_URL);

		# Remove www. from $link.
		$link=str_replace('www.', '', $link);

		# Remove www. from $current_page.
		$current_page=str_replace('www.', '', $current_page);

		# Remove any Query (ie GET data) from the passed link.
		$current_page=$this->removePageQuery($current_page);

		# Are we looking for an exact match?
		if($exact_match===TRUE)
		{
			# Check if the url passed matches the current page exactly.
			if($current_page==$link)
			{
				# Set the class tag to the variable.
				$class=' class="current"';
			}
		}
		# Check if the url passed is part of the current page.
		elseif(strpos($current_page, $link)!==FALSE)
		{
			# Set the class tag to the variable.
			$class=' class="current"';
		}

		return $class;
	}

	/**
	 * removeParameters
	 */
	public function removeParameters($var)
	{
		$var=parse_url($var, PHP_URL_QUERY);
		$var=html_entity_decode($var);
		$var=explode('&', $var);
		$arr=array();

		foreach($var as $val)
		{
			$x=explode('=', $val);
			if(isset($x[1]))
				$arr[$x[0]]=$x[1];
		}
		unset($val, $x, $var);
		return $arr;
	}

	/**
	 * showPubHeader
	 *
	 * Displays the public header.
	 *
	 * @access	public
	 */
	public function showPubHeader()
	{
		global $EmailClass;

		$data="";
		$data.='
    <div id="wrapper" class="group">
      <div id="header" class="group">
        <div id="member_name">Site Name<span id="member_message">Hub</span></div>
        <ul id="nav">
          <li class="here"><a href="index.php">Home</a></li>
          <li><a href="member.php?action=login">Login</a></li>
        </ul>
      </div>
      <div id="body" class="group">';

		return $data;
	}

	/**
	 * Show Public Footer
	 */
	public function showPubFooter()
	{
		global $database;

		$data="";
		$data.='
      </div>
      <div style="height: 20px;">Copyright &copy; 2012. Created by <a href="https://www.facebook.com/draven714" class="email">Michael Delle</a></div>
    </div>';

		return $data;
	}

	/**
	 * Show Header
	 */
	public function showPrivHeader()
	{
		global $database;

		$user_id=$this->getUsersID();
		$username=$this->getUsersName($user_id);
		$usertype=$this->getUsertype(NULL, $user_id);
		$count_assigned=$this->countAssigned($user_id);

		$this->updateUserActivity($user_id);

		$data="";
		$data.='
    <div id="main">
      <div id="topbanner"><a href="member.php"><img src="assets/images/logo_bloodcell.png" alt="Site Name - Hub" id="logo" /></a></div>
      <div id="toptoolbar">
        <ul id="toptoolbarlinklist">
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/member.php', TRUE).'><a class="toptoolbarlink" style="background-image: url(assets/images/icon_widget_home_small.png);" href="member.php">Home</a></li>';
		if($usertype=="Administrator" || $usertype=="Webmaster")
		{
			$data.='
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/admin.php', TRUE).$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=list-emails').$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=view-email').'>
	    <a class="toptoolbarlink" style="background-image: url(assets/images/icon_widget_viewticket_small.png);" href="admin.php?action=list-emails">View Emails</a>
	    <ul>
              <li><a href="admin.php?action=list-emails&assigned=TRUE">Assigned Emails ('.$count_assigned.')</a></li>';

			$categories_result=$this->listCategories();
			foreach($categories_result as $category)
			{
				$data.='<li><a href="admin.php?action=list-emails&category='.$category['category'].'">'.$category['category'].'</a></li>';
			}

			$data.='</ul>
	  </li>
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/member.php?action=list-press-apps').$this->addCurrentClass(APPLICATION_URL.'hub/member.php?action=view-press-apps').'><a class="toptoolbarlink" style="background-image: url(assets/images/icon_widget_submitticket_small.png);" href="member.php?action=list-press-apps">Press Applications</a></li>
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/member.php?action=list-volunteer-apps').$this->addCurrentClass(APPLICATION_URL.'hub/member.php?action=view-volunteer-apps').'><a class="toptoolbarlink" style="background-image: url(assets/images/icon_widget_submitticket_small.png);" href="member.php?action=list-volunteer-apps">Volunteer Applications</a></li>
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/member.php?action=forum').$this->addCurrentClass(APPLICATION_URL.'hub/member.php?action=forum').'><a class="toptoolbarlink" style="background-image: url(assets/images/icon_widget_ticket.png);" href="member.php?action=forum">Forum</a></li>
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=list-users').$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=add-user').$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=view-user').'>
	    <a class="toptoolbarlink" style="background-image: url(assets/images/icon_widget_knowledgebase_small.png);" href="admin.php?action=list-users">Users</a>
	    <ul>
              <li><a href="admin.php?action=add-user">Add User</a></li>
            </ul>
	  </li>
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/member.php?action=policies').'><a class="toptoolbarlink" style="background-image: url(assets/images/icon_widget_news_small.png);" href="member.php?action=policies">Policies</a></li>
	  <li'.$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=settings').$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=policy_setting').$this->addCurrentClass(APPLICATION_URL.'hub/admin.php?action=forum_setting').'>
	    <a class="toptoolbarlink" style="background-image: url(assets/images/icon_preferences.gif);" href="admin.php?action=settings">Settings</a>
	    <ul>
	      <li><a href="admin.php?action=site_settings" title="Site Settings">Site Settings</a></li>
	      <li><a href="admin.php?action=policy_setting" title="Policy Settings">Policy Settings</a></li>
	      <li><a href="admin.php?action=forum_setting" title="Forum Settings">Forum Settings</a></li>
	      <li><a href="admin.php?action=google_drive" title="Google Drive">Google Drive</a></li>
	    </ul>
	  </li>';
		}

		$data.='
	</ul>
      </div>
      <div id="maincore">
        <div id="maincoreleft">
          <div id="leftloginsubscribebox">
            <div class="tabrow" id="leftloginsubscribeboxtabs"><a id="leftloginsubscribeboxlogintab" href="#" class="atab"><span class="tableftgap">&nbsp;</span><span class="tabbulk"><span class="tabtext">Account</span></span></a></div>
            <div id="leftloginbox" class="switchingpanel active">
              <div class="maitem maprofile" onclick="javascript: Redirect(\'member.php?action=change-settings\');">My Profile</div>
              <div class="maitem mapreferences" onclick="javascript: Redirect(\'member.php?action=preferences\');">Preferences</div>
              <div class="maitem machangepassword" onclick="javascript: Redirect(\'member.php?action=change-password\');">Change Password</div>
              <div class="maitem malogout" onclick="javascript: Redirect(\'member.php?action=logout\');">Logout</div>
            </div>
	  </div>';

		if(isset($_GET['action']) && in_array($_GET['action'], array("list-emails", "list-press-apps", "list-volunteer-apps"))!==FALSE)
		{
			if($_GET['action']=="list-emails") $folder_type=1;
			elseif($_GET['action']=="list-press-apps") $folder_type=2;
			elseif($_GET['action']=="list-volunteer-apps") $folder_type=3;

			$data.='
	  <div class="leftnavboxbox">
	    <div class="leftnavboxtitle"><span class="leftnavboxtitleleftgap">&nbsp;</span><span class="leftnavboxtitlebulk"><span class="leftnavboxtitletext">Folders</span><div style="float: right; margin-top: 4px;"><a href="#" onclick="javascript: AddFolder();"><div class="addplus">&nbsp;</div></a></div></span></div>
	    <div class="leftnavboxcontent">
	      <form method="post" id="folderform" action="'.$this->currentPage().'" name="folderform">
		';

			$database->query('SELECT id, name FROM folders WHERE type='.$folder_type.' ORDER BY name', array());
			$results=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			if(!empty($results))
			{
				foreach($results as $row)
				{
					if($_GET['action']=="list-emails") $count_folder=$this->countEmailsFolder($row['id']);
					elseif($_GET['action']=="list-press-apps") $count_folder=$this->countPressFolder($row['id']);
					elseif($_GET['action']=="list-volunteer-apps") $count_folder=$this->countVolunteerFolder($row['id']);

					$data.='<a class="zebraeven" href="javascript: setGetParameter(\'folder\', \''.$row['id'].'\');">'.$row['name'].' <span class="graytext">('.$count_folder.')</span></a>';
				}
			}

			$data.='
		<div id="foldercontainer"></div><input id="addBtn" name="addFolder" type="submit" value="Add">
	      </form>
	    </div>
	  </div>';
		}

		if(isset($_GET['action']) && $_GET['action']=="list-volunteer-apps" && isset($_GET['folder']))
		{
			$data.='
	  <div class="leftnavboxbox">
	    <div class="leftnavboxtitle"><span class="leftnavboxtitleleftgap">&nbsp;</span><span class="leftnavboxtitlebulk"><span class="leftnavboxtitletext">T-Shirts</span></div>
	    <div class="leftnavboxcontent">
             <ul style="list-style:none;margin:0;padding:10px">
		';

			$database->query("SELECT shirt_size, COUNT(shirt_size) AS count_shirt_size FROM volunteer_apps WHERE folder_id=:folderid AND status=2 GROUP BY shirt_size ORDER BY FIELD(shirt_size,'Small','Medium','Large','X-Large','XX-Large','XXX-Large','XXXX-Large')", array(':folderid'=>$_GET['folder']));
			$results=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			if(!empty($results))
			{
				foreach($results as $row)
				{
					$data.='<li>'.$row['shirt_size'].' <span class="graytext">('.$row['count_shirt_size'].')</span></li>';
				}
			}

			$data.='
             </ul>
	    </div>
	  </div>';
		}

		$user_id=$this->getUsersID();
		$is_admin=$this->isAdmin($user_id);

		$data.='
	  <div class="leftnavboxbox">
	    <div class="leftnavboxtitle"><span class="leftnavboxtitleleftgap">&nbsp;</span><span class="leftnavboxtitlebulk"><span class="leftnavboxtitletext">Google Drive</span>'.($is_admin ? '<div style="float: right; margin-top: 4px;"><a href="admin.php?action=file_add"><div class="addplus">&nbsp;</div></a></div>' : '').'</span></div>
	    <div class="leftgoogleboxcontent">';

		$database->query('SELECT file_name, file_url FROM google_drive ORDER BY file_order ASC', array());
		$gd_result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		foreach($gd_result as $gd_row)
		{
			$data.='
              <a class="zebraeven" href="'.$gd_row['file_url'].'" target="_blank">'.$gd_row['file_name'].'</a>';
		}

		$data.='
	    </div>
	  </div>
	  <div class="leftnavboxbox">
	    <div class="leftnavboxtitle"><span class="leftnavboxtitleleftgap">&nbsp;</span><span class="leftnavboxtitlebulk"><span class="leftnavboxtitletext">Users Online</span></span></div>
	    <div class="leftusersonlineboxcontent">
	      <ul style="list-style:none;margin:0;padding:0">';

		$d=date('c',time()-5*60); # last 5 minutes
		$database->query('SELECT user_id FROM users_activity WHERE time > :d', array(':d'=>$d));
		$users_online_result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		foreach($users_online_result as $users_online_row)
		{
			$data.='
            <li>'.$this->getUsersName($users_online_row['user_id']).'</li>';
		}

		$data.='
          </ul>
	    </div>
	  </div>
    </div>
	<div id="maincorecontent">
      <form method="post" id="searchform" action="member.php?action=search" name="searchform">
        <div class="searchboxcontainer">
	      <div class="searchbox">
	        <span class="searchbuttoncontainer">
		      <a class="searchbutton" href="javascript: void(0);" onclick="$(\'#searchform\').submit();"><span></span>SEARCH</a>
		    </span>
		    <span class="searchinputcontainer"><input type="text" name="searchquery" class="searchquery" onclick="javascript: if ($(this).val()==\'Please type your question here\' || $(this).val()==\'Please type your search query here\') { $(this).val(\'\').addClass(\'searchqueryactive\'); }" value="Please type your search query here" /></span>
          </div>
	    </div>
	  </form>';

		return $data;
	}

	/**
	 * Show Footer
	 */
	public function showPrivFooter()
	{
		global $database;

		$data="";
		$data.='
	  <script type="text/javascript"> $(function(){ $(\'.dialogerror, .dialoginfo, .dialogalert\').fadeIn(\'slow\');});</script>
	  <script type="text/javascript">
if (navigator.appVersion.indexOf("Mac")!=-1) {
	console.log(\'Mac detected, applying class...\');
    $(\'#toptoolbarlinklist ul\').addClass(\'safari-mac\');
} else {
    console.log(\'PC detected, applying class...\');
	$(\'#toptoolbarlinklist\').addClass(\'pc\');
}
</script>
	</div>
	<div id="bottomfooter" class="bottomfooterpadding">Hub designed by <a href="https://www.facebook.com/draven714" target="_blank" class="bottomfooterlink">Michael Delle</a></div>
      </div>
    </div>';

		return $data;
	}

	/**
	 * countEmailsFolder
	 *
	 * Counts how many applications in the `folders` table.
	 *
	 * @param	$folder_id
	 * @access	private
	 */
	private function countEmailsFolder($folder_id)
	{
		global $database;

		$database->query('SELECT folder_id FROM emails WHERE folder_id=:folder_id', array(':folder_id'=>$folder_id));

		return $database->count();
	}
}
?>