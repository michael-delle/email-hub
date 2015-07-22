<?php
/*
 * Name:	Admin
 * Author:	Draven
 *
 * The Admin Class is used to access and manipulate user info.
 *
 */
class Admin
{
	/*** public methods ***/

	/**
	 * listUsers
	 *
	 * Displays all users.
	 *
	 * @access	public
	 */
	public function listUsers()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			if(isset($_GET['sort']) && isset($_GET['asc-desc']))
			{
				# Get users sorting options
				$sort_options=$this->getUsersSortOptions($user_id);

				if($sort_options->users_sort_field!=$_GET['sort'] || $sort_options->users_sort_order!=$_GET['asc-desc'])
				{
					$users_sort_field=$_GET['sort'];
					$users_sort_order=$_GET['asc-desc'];

					$database->query('UPDATE users_sorting SET users_sort_field=:userssortfield, users_sort_order=:userssortorder WHERE user_id=:userid', array(':userssortfield'=>$users_sort_field, ':userssortorder'=>$users_sort_order, ':userid'=>$user_id));
				}
			}

			# Get users sorting options
			$sort_options=$this->getUsersSortOptions($user_id);
			($sort_options->users_sort_order == "ASC" ? $asc_desc="DESC" : $asc_desc="ASC");

			$database->query('SELECT id, full_name, username, email, usertype FROM users ORDER BY '.$sort_options->users_sort_field.' '.$sort_options->users_sort_order, array());
			$results=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			$data='
	  <div class="boxcontainer">
	    <div class="boxcontainerlabel">User</div>
	    <div class="boxcontainercontent">
	      <table border="0" cellpadding="3" cellspacing="1" width="100%">
	        <tr>
		  <td class="ticketlistheaderrow" align="left" valign="middle" width="150"><a href="admin.php?action=list-users&sort=username&asc-desc='.$asc_desc.'">Username&nbsp;'.($sort_options->users_sort_field=="username" ? '<img src="assets/images/'.($sort_options->users_sort_order=="ASC" ? 'sortasc.gif' : 'sortdesc.gif').'"' : '').'</a></td>
		  <td class="ticketlistheaderrow" align="center" valign="middle" width=""><a href="admin.php?action=list-users&sort=usertype&asc-desc='.$asc_desc.'">Usertype&nbsp;'.($sort_options->users_sort_field=="usertype" ? '<img src="assets/images/'.($sort_options->users_sort_order=="ASC" ? 'sortasc.gif' : 'sortdesc.gif').'"' : '').'</a></td>
		  <td class="ticketlistheaderrow" align="center" valign="middle" width="400"><a href="admin.php?action=list-users&sort=email&asc-desc='.$asc_desc.'">Email&nbsp;'.($sort_options->users_sort_field=="email" ? '<img src="assets/images/'.($sort_options->users_sort_order=="ASC" ? 'sortasc.gif' : 'sortdesc.gif').'"' : '').'</a></td>
		</tr>';

			foreach($results as $row)
			{
					$data.='
		<tr>
		  <td class="ticketlistsubject" align="left" valign="middle" colspan="4"><a href="admin.php?action=view-user&id='.$row['id'].'">'.$row['full_name'].'</a></td>
		</tr>
		<tr class="ticketlistproperties" style="background: #8BB467;">
		  <td class="ticketlistpropertiescontainer" align="left" valign="middle">'.$row['username'].'</td>
		  <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.$row['usertype'].'</td>
		  <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.$row['email'].'</td>
		</tr>
		<tr class="ticketlistpropertiesdivider">
		  <td colspan="4">&nbsp;</td>
		</tr>';
			}

			$data.='
	      </table>
	    </div>
	  </div>';
			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * viewUser
	 *
	 * Displays user information.
	 *
	 * @access	public
	 */
	public function viewUser()
	{
		global $database;
		global $member;
		global $EmailClass;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			$id=$_GET['id'];

			if(isset($_POST['change-usertype']))
			{
				if(empty($_POST['change-usertype']))
				{
					$error[]="Please select a user type";
				}
				if(!isset($error))
				{
					$usertype=$_POST['usertype'];
					$database->query('UPDATE users SET usertype=:usertype WHERE id=:id', array(':usertype'=>$usertype, ':id'=>$id));
					$success[]="User Updated!";

					$usertype_array=array(
						1=>"Member",
						2=>"Administrator",
						3=>"Webmaster"
					);

					$users_name=$member->getUsersName($id);
					$email=$member->getUsersEmail($id);
					$subject=$EmailClass->site_name." - Account Updated";
					$body_content=$users_name.",\r\n\r\nYour account has just been changed to ".$usertype_array[$usertype];
					$EmailClass->sendEmail($email, $subject, $body_content, $users_name);
				}
			}

			$database->query('SELECT users.full_name, users.username, users.email, users_activity.time AS last_active, users.usertype FROM users, users_activity WHERE users.id=:userid', array(':userid'=>$id));
			$result=$database->statement->fetch(PDO::FETCH_ASSOC);

			if(!empty($result) && ($is_admin))
			{
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

				# Combine Data
				$data='
		      <form name="change-usertype" action="'.$member->currentPage().'" method="post">
		        <div class="boxcontainer">
			  <div class="boxcontainerlabel">View User: '.$result['full_name'].'</div>
			  <div class="boxcontainercontenttight">
			    <table width="100%" border="0" cellspacing="1" cellpadding="4">';

				if($is_admin)
				{
					$usertype_form='
			          <select class="swiftselect" name="usertype">
				    <option value="1"'.($result['usertype']=="Member" ? ' selected' : '').'>Member</option>
				    <option value="2"'.($result['usertype']=="Administrator" ? ' selected' : '').'>Administrator</option>
						';

					if($is_admin=="Webmaster")
					{
						$usertype_form.='  <option value="3"'.($result['usertype']=="Webmaster" ? ' selected' : '').'>Webmaster</option>';
					}

					$usertype_form.='
			          </select>';
				}
				else
				{
					$usertype_form=$result['usertype'];
				}
				$result['usertype']=$usertype_form;

				if($member->findUserActivity($id) <= 0)
				{
					$user_activity='Hasn\'t logged in';
				}
				else
				{
					$database->query('SELECT UNIX_TIMESTAMP(time) AS last_active FROM users_activity WHERE user_id=:userid', array(':userid'=>$id));
					$last_active_result=$database->statement->fetch(PDO::FETCH_OBJ);
					$user_activity=$member->elapsedTime($last_active_result->last_active, 3);
				}
				$result['last_active']=$user_activity;

				foreach($result as $key=>$val)
				{
					$field=ucwords(str_replace('_', ' ', $key));

					$data.='
			      <tr>
			        <td width="200" align="left" valign="middle" class="zebraodd">'.$field.':</td>
			        <td>'.$val.'</td>
			      </tr>';
				}

				# Status Form
				$data.='
			    </table><br>
			    <div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="change-usertype" /></div>
			  </div>
			</div>
		      </form>';

				return $data;
			}
			else
			{
				header("Location: index.php");
			}
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * addUser
	 *
	 * Displays a form to add a user to the database.
	 *
	 * @access	public
	 */
	public function addUser()
	{
		global $database;
		global $member;
		global $EmailClass;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			# Set Message Array
			$message=array();

			# Check if Login is set
			if(isset($_POST['AddUserForm']))
			{
				# Check Full Name
				if(!empty($_POST['full_name']))
				{
					$check_name=ucwords($_POST['full_name']);

					# Check the Full Name length
					$name_length=strlen($check_name);
					if($name_length >= 6)
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

				# Check E-Mail
				if(!empty($_POST['email']))
				{
					$check_email=strtolower($_POST['email']);
					$check_email_again=strtolower($_POST['email_again']);

					# Do E-Mails match?
					if(isset($check_email_again) && $check_email_again == $check_email)
					{
						$length=strlen($check_email);

						# Is the E-Mail really an E-Mail?
						if(filter_var($check_email, FILTER_VALIDATE_EMAIL) == TRUE)
						{
							$database->query('SELECT id FROM users WHERE email=:email', array(':email'=>$check_email));

							# Check if user exist with email
							if($database->count() == 0)
							{
								/* Require use to validate account */
								if($EmailClass->email_verification == TRUE)
								{
									# Check if user exist with email in inactive
									$database->query('SELECT date FROM users_inactive WHERE email=:email', array(':email'=>$check_email));

									# If user incative is older than 24 hours
									$user=$database->statement->fetch(PDO::FETCH_OBJ);
									if($database->count() == 0 or time() >= strtotime($user->date) + 86400)
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

				# Is both Full Name and Email set?
				if(!isset($error))
				{
					# Send the user a welcome E-Mail
					if($EmailClass->email_welcome == TRUE)
					{
						# Send the user an E-Mail
						# Can we send a user an E-Mail?
						if(function_exists('mail') && $EmailClass->email_master!=NULL)
						{
							$subject=$EmailClass->site_name." - Account Created";
							$body_content="Hi ".$full_name.",\r\n\r\nAn account has been created for you!";

							# Send it
							$EmailClass->sendEmail($email, $subject, $body_content, $full_name);
						}
					}

					# Require use to validate account
					if($EmailClass->email_verification == TRUE)
					{
						# Send the user an E-Mail
						# Can we send a user an E-Mail?
						if(function_exists('mail') && $EmailClass->email_master!=NULL)
						{
							$verCode=md5(uniqid(rand(), TRUE) . md5(uniqid(rand(), TRUE)));
							$subject=$EmailClass->site_name." - Account Created";
							$body_content="Hi ".$full_name.",\r\n\r\nAn account has been created for you!\r\n\r\nTo activate your account please click the link below, or copy paste it into the address bar of your web browser\r\n\r\n".$member->currentPath()."member.php?action=verification&vercode=".$verCode;

							# Send it
							if($EmailClass->sendEmail($email, $subject, $body_content, $full_name))
							{
								# Insert Data
								$date=date("Y-m-d H:i:s", time());
								$database->query('INSERT INTO users_inactive(verCode, full_name, email, date) VALUES (?, ?, ?, ?)', array($verCode, $full_name, $email, $date));
								$success[]="The account has been created!";
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
						$database->query('INSERT INTO users (full_name, email, date) VALUES (?, ?, ?, ?)', array($full_name, $email, $date));
						$success[]="The account has been created!";
					}
				}
			}
			else
			{
				$full_name=NULL;
				$email=NULL;
				$email_again=NULL;
			}

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

			$data.='
	  <form method="post" action="'.$member->currentPage().'" name="AddUserForm">
	    <div class="boxcontainer">
	      <div class="boxcontainerlabel">Add User</div>
	      <div class="boxcontainercontent">
		<table width="100%" border="0" cellspacing="1" cellpadding="4">
		  <tr>
		    <td width="200" align="left" valign="middle" class="zebraodd">Full Name:</td>
		    <td><input name="full_name" type="text" size="20" class="swifttextlarge" value="'.$full_name.'" /></td>
		  </tr>
		  <tr>
		    <td width="200" align="left" valign="middle" class="zebraodd">Email:</td>
		    <td><input name="email" type="text" size="20" class="swifttextlarge" value="'.$email.'" /></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd">Email Again:</td>
		    <td><input name="email_again" type="text" size="20" class="swifttextlarge" value="'.$email_again.'" /></td>
		  </tr>
		</table>
		<div class="subcontent"><input class="rebuttonwide2" value="Submit" type="submit" name="AddUserForm" /></div>
	      </div>
	    </div>
	  </form>';

			# Return data
			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * viewSettings
	 *
	 * Displays the website settings
	 *
	 * @access	public
	 */
	public function viewSettings()
	{
	}

	/**
	 * viewSiteSettings
	 *
	 * Displays the websites policy settings
	 *
	 * @access	public
	 */
	public function viewSiteSettings()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			if(isset($_POST['DeleteConLocation']))
			{
				if(!isset($_POST['con_location_id']))
				{
					$error[]="Please choose a convention location to delete";
				}

				if(!isset($error))
				{
					$database->query('DELETE FROM con_locations WHERE id=:conlocationid', array(':conlocationid'=>$_POST['con_location_id']));
				}
			}

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

			$data.='
            <div class="boxcontainer">
              <div class="boxcontainerlabel">
                <div style="float: right">
                  <div class="headerbuttongreen" onclick="javascript:window.location.href=\'admin.php?action=con_location_add\';">Add Con Location</div>
                </div>
                Site Settings
              </div>
              <div class="boxcontainercontent">
                <table class="hlineheader"><tr><th rowspan="2" nowrap>Convention Locations</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table><br>
                <table width="100%" border="1" cellspacing="1" cellpadding="4">
                  <tr>
                    <th width="20px">Edit</th>
                    <th width="20px">Delete</th>
                    <th>City Name</th>
                    <th width="72px">Year</th>
                  </tr>';

			$get_con_locations=$member->getConLocations();
			foreach($get_con_locations as $con_location)
			{
				$data.='
                  <tr>
                    <td align="center"><a style="display: block; background-image: url(assets/images/icon_edit.gif); width: 14px; height: 14px;" href="admin.php?action=con_location_edit&con_location_id='.$con_location['id'].'"></a></td>
                    <td align="center">
                      <form name="DeleteConLocation" id="DeleteConLocation" method="post" action="'.$member->currentPage().'">
                        <input type="hidden" name="con_location_id" value="'.$con_location['id'].'">
                        <input name="DeleteConLocation" class="trashbutton" type="submit" value="">
                      </form>
                    </td>
                    <td>'.$con_location['city_name'].'</td>
                    <td align="center">'.$con_location['year'].'</td>
                  </tr>';
			}

			$data.='
                </table><br>
              </div>
            </div>';

			# Return data
			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * addConLocation
	 *
	 * Adds or Edits a Convention Location.
	 *
	 * @access	public
	 */
	public function addConLocation()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			$con_location_id=NULL;
			$con_location_city_name=NULL;
			$con_location_year=NULL;

			if(isset($_GET['con_location_id']))
			{
				$con_location_result=$this->getConLocation($_GET['con_location_id']);
				$con_location_id=$con_location_result->id;
				$con_location_city_name=$con_location_result->city_name;
				$con_location_year=$con_location_result->year;
			}
			if(isset($_POST['AddConLocationForm']))
			{
				$con_location_city_name=ucwords(str_replace(array('_','-'), ' ', $_POST['con_location_city_name']));
				$con_location_year=$_POST['con_location_year'];

				# Check the Convention City Name length
				$con_location_city_name_length=strlen($con_location_city_name);
				if($con_location_city_name_length < 6)
				{
					$error[]="Please enter a city name with 6 or more characters";
					$con_location_city_name=$con_location_city_name;
				}

				if(!isset($error))
				{
					# Insert convention location
					if(!isset($con_location_id))
					{
						$database->query('INSERT INTO con_locations (city_name, year) VALUES (?, ?)', array($con_location_city_name, $con_location_year));
						$success[]=$con_location_city_name.' has been added';
					}
					elseif(isset($con_location_id))
					{
						$database->query('UPDATE con_locations SET city_name=:conlocationcityname, year=:conlocationyear WHERE id=:conlocationid', array(':conlocationid'=>$con_location_id, ':conlocationcityname'=>$con_location_city_name, ':conlocationyear'=>$con_location_year));
						$success[]=$con_location_city_name.' has been updated';
					}
				}
			}

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

			$data.='
          <form name="AddConLocationForm" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
              <div class="boxcontainerlabel">Add Convention Location</div>
              <div class="boxcontainercontent">
                <table class="hlineheader"><tr><th rowspan="2" nowrap>Convention Location Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
                <table width="100%" border="0" cellspacing="1" cellpadding="4">
                  <tr>
                    <td align="left" valign="middle" class="zebraodd"><label for="con_location_city_name">City Name:</label></td>
                    <td><input id="con_location_city_name" name="con_location_city_name" type="text" size="20" maxlength="30" class="swifttextlarge" value="'.$con_location_city_name.'" required></td>
                  </tr>
                  <tr>
                    <td align="left" valign="middle" class="zebraodd"><label for="con_location_year">Year:</label></td>
                    <td>
                      <select name="con_location_year" class="swiftselect" required>';

			# Number of years to go back
			$start_year=1;

			# Number of years to go forward
			$end_year=2;

			# Generate Options
			$this_year=date('Y');
			$start_year_range=($this_year - $start_year);
			$end_year_range=($this_year + $end_year);
			$select_year=($this_year - $end_year);

			foreach(range($start_year_range, $end_year_range) as $year)
			{
				$selected="";
				if($con_location_year!==NULL)
				{
					if($year==$con_location_year)
					{
						$selected=" selected";
					}
				}
				elseif($year==$this_year)
				{
					$selected=" selected";
				}

				$data.='
                        <option value="'.$year.'"'.$selected.'>'.$year.'</option>';
			}

            $data.='
                      </select>
                    </td>
                  </tr>
                </table><br />
                <div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="AddConLocationForm"></div>
              </div>
            </div>
          </form>';

			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * viewPolicySettings
	 *
	 * Displays the websites policy settings
	 *
	 * @access	public
	 */
	public function viewPolicySettings()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			if(isset($_POST['UpdatePolicyOrder']))
			{
				foreach($_POST['policy_order'] as $key=>$value)
				{
					$database->query('UPDATE policies SET policy_order=:policyorder WHERE id=:policyid', array(':policyorder'=>$value, ':policyid'=>$key));
				}
				$success[]="Policies have been updated";
			}

			if(isset($_POST['DeletePolicy']))
			{
				if(!isset($_POST['policy_id']))
				{
					$error[]="Please choose a policy to delete";
				}

				if(!isset($error))
				{
					$database->query('DELETE FROM policies WHERE id=:policyid', array(':policyid'=>$_POST['policy_id']));
				}
			}

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

			$data.='
          <form name="UpdatePolicyOrder" id="UpdatePolicyOrder" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
              <div class="boxcontainerlabel">
                <div style="float: right">
                  <input name="UpdatePolicyOrder" class="headerbutton" type="submit" value="Update">
                  <div class="headerbuttongreen" onclick="javascript:window.location.href=\'admin.php?action=policy_add\';">Add Policy</div>
	        </div>
                Policy Settings
              </div>
              <div class="boxcontainercontent">
                <table class="hlineheader"><tr><th rowspan="2" nowrap>Policies</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table><br>
                <table width="100%" border="1" cellspacing="1" cellpadding="4">
                  <tr>
                    <th width="20px">Edit</th>
                    <th width="20px">Delete</th>
		    <th width="20px">Order</th>
		    <th width="120px">Button Name</th>
		    <th>Policy Question</th>
		    <th width="72px">Clipboard</th>
                  </tr>';

			$get_policies=$member->getPolicies();
			foreach($get_policies as $policy)
			{
				$data.='
                  <tr>
                    <td align="center"><a style="display: block; background-image: url(assets/images/icon_edit.gif); width: 14px; height: 14px;" href="admin.php?action=policy_edit&policy_id='.$policy['id'].'"></a></td>
		    <td align="center">
                      <form name="DeletePolicy" id="DeletePolicy" method="post" action="'.$member->currentPage().'">
                        <input type="hidden" name="policy_id" value="'.$policy['id'].'">
                        <input name="DeletePolicy" class="trashbutton" type="submit" value="">
                      </form>
                    </td>
                    <td><input type="text" name="policy_order['.$policy['id'].']" id="policy_order" size="1" maxlength="3" value="'.$policy['policy_order'].'" style="text-align:center"></td>
                    <td>'.$policy['button_name'].'</td>
		    <td>'.$policy['question'].'</td>
		    <td align="center">'.$policy['clipboard'].'</td>
                  </tr>';
			}

			$data.='
                </table><br>
              </div>
            </div>
          </form>';

			# Return data
			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * addPolicy
	 *
	 * Adds or Edits a Policy.
	 *
	 * @access	public
	 */
	public function addPolicy()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			$policy_id=NULL;
			$policy_button_name=NULL;
			$policy_question=NULL;
			$policy_response=NULL;
			$policy_clipboard=NULL;

			if(isset($_GET['policy_id']))
			{
				$policy_result=$this->getPolicy($_GET['policy_id']);
				$policy_id=$policy_result->id;
				$policy_button_name=$policy_result->button_name;
				$policy_question=$policy_result->question;
				$policy_response=$policy_result->response;
				$policy_clipboard=$policy_result->clipboard;
			}
			if(isset($_POST['AddPolicyForm']))
			{
				$policy_button_name=ucwords(str_replace(array('_','-'), ' ', $_POST['policy_button_name']));
				$policy_question=$_POST['policy_question'];
				$policy_response=$_POST['policy_response'];
				$policy_clipboard=$_POST['policy_clipboard'];

				# Check the Policy Button Name length
				$policy_button_name_length=strlen($policy_button_name);
				if($policy_button_name_length < 4)
				{
					$error[]="Please enter a policy button name with 4 or more characters";
					$policy_button_name=$policy_button_name;
				}

				if(!isset($error))
				{
					# Insert policy
					if(!isset($policy_id))
					{
						$get_policy_order=$member->getPolicies('DESC', ' LIMIT 1');
						$policy_order=$get_policy_order[0]['policy_order']+1;

						$database->query('INSERT INTO policies (button_name, question, response, clipboard, policy_order) VALUES (?, ?, ?, ?, ?)', array($policy_button_name, $policy_question, $policy_response, $policy_clipboard, $policy_order));
						$success[]=$policy_button_name.' has been added';
					}
					elseif(isset($policy_id))
					{
						$database->query('UPDATE policies SET button_name=:policybuttonname, question=:policyquestion, response=:policyresponse, clipboard=:policyclipboard WHERE id=:policyid', array(':policyid'=>$policy_id, ':policybuttonname'=>$policy_button_name, ':policyquestion'=>$policy_question, ':policyresponse'=>$policy_response, ':policyclipboard'=>$policy_clipboard));
						$success[]=$policy_button_name.' has been updated';
					}
				}
			}

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

			$data.='
          <form name="AddPolicyForm" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
               <div class="boxcontainerlabel">Add Policy</div>
               <div class="boxcontainercontent">
                 <table class="hlineheader"><tr><th rowspan="2" nowrap>Policy Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
                <table width="100%" border="0" cellspacing="1" cellpadding="4">
		  <tr>
                    <td align="left" valign="middle" class="zebraodd"><label for="policy_button_name">Button Name:</label></td>
		    <td><input id="policy_button_name" name="policy_button_name" type="text" size="20" maxlength="30" class="swifttextlarge" value="'.$policy_button_name.'"></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="policy_question">Question:</label></td>
		    <td><input id="policy_question" name="policy_question" type="text" size="30" maxlength="255" class="swifttextlarge" value="'.$policy_question.'"></td>
                  </tr>
                  <tr>
                    <td align="left" valign="middle" class="zebraodd"><label for="policy_response">Response:</label></td>
		    <td><textarea name="policy_response" cols="25" rows="6" id="policy_response" class="swifttextarea">'.$policy_response.'</textarea></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd">Clipboard:</td>
		    <td>
                    <select class="swiftselect" name="policy_clipboard">
                      <option value=""'.($policy_clipboard=="" ? ' selected' : '').'>&nbsp;</option>
                      <option value="No"'.($policy_clipboard=="No" ? ' selected' : '').'>No</option>
                      <option value="Yes"'.($policy_clipboard=="Yes" ? ' selected' : '').'>Yes</option>
                    </select>
		    </td>
		  </tr>
                </table><br />
                <div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="AddPolicyForm"></div>
              </div>
            </div>
	  </form>';

			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * viewPolicySettings
	 *
	 * Displays the websites policy settings
	 *
	 * @access	public
	 */
	/*
	public function viewPolicySettings()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			# Check if form was submitted
			if(isset($_POST['PolicyForm']))
			{
				# Check if values are empty
				if(!empty($_POST['question']) && !empty($_POST['response']) && !empty($_POST['clipboard']))
				{
					# Loops through policy rows
					foreach($_POST['id'] as $value)
					{
						$item=$value-1;
						$question=$_POST['question'];
						$response=$_POST['response'];
						$clipboard=$_POST['clipboard'];
						$database->query('UPDATE policies SET question=:question, response=:response, clipboard=:clipboard WHERE id=:policyid', array(':question'=>$question[$item], ':response'=>$response[$item], ':clipboard'=>$clipboard[$item], ':policyid'=>$value));
					}
				}

				# If new policy
				if(isset($_POST['new_question']) && isset($_POST['new_response']) && isset($_POST['new_clipboard']))
				{
					# Loops through new policy rows
					for($z=0; $z < count($_POST['new_question']); $z++)
					{
						$combine_arrays=array($_POST['new_question'][$z], $_POST['new_response'][$z], $_POST['new_clipboard'][$z]);
						$new_array[]=$combine_arrays;
					}
					for($i=0; $i < count($new_array); $i++)
					{
						$new_question=$_POST['new_question'][$i];
						$new_response=$_POST['new_response'][$i];
						$new_clipboard=$_POST['new_clipboard'][$i];
						$database->query('INSERT INTO policies (question, response, clipboard) VALUES (?, ?, ?)', array($new_question, $new_response, $new_clipboard));
					}
				}
			}

			$data='
        <form method="post" action="'.$member->currentPage().'" name="PolicyForm">
	   <div class="boxcontainer">
	     <div class="boxcontainerlabel">Policy Settings</div>
	     <div class="boxcontainercontent">
	       <table class="hlineheader">
	         <tbody>
	 	    <tr>
	  	      <th rowspan="2" nowrap="">
                      Policies [
	  	        <div class="addplus">
	  		  <a href="#newpolicy" onclick="javascript: AddPolicy();">Add Policy</a>
	  	        </div>
	  	        ]
	  	      </th>
                  </tr>
	  	    <tr>
	  	      <td class="hlinelower"/>
	  	    </tr>
	  	  </tbody>
		</table>
		<table width="100%" border="0" cellspacing="1" cellpadding="4" id="policycontainer">';

			$get_policies=$member->getPolicies();
			foreach($get_policies as $policy)
			{
				$data.='
		  <tr>
		    <td align="left" valign="middle" class="zebraodd">Question:</td>
		    <td>
                    <input type="hidden" name="id[]" value="'.$policy['id'].'">
                    <textarea name="question[]" cols="25" rows="5" id="question" class="swifttextareawide">'.$policy['question'].'</textarea>
                  </td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd">Response:</td>
		    <td><textarea name="response[]" cols="25" rows="5" id="response" class="swifttextareawide">'.$policy['response'].'</textarea></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd">Clipboard:</td>
		    <td>
                    <select class="swiftselect" name="clipboard[]">
                      <option value=""'.($policy['clipboard']=="" ? ' selected' : '').'>&nbsp;</option>
                      <option value="1"'.($policy['clipboard']=="No" ? ' selected' : '').'>No</option>
                      <option value="2"'.($policy['clipboard']=="Yes" ? ' selected' : '').'>Yes</option>
                    </select>
		    </td>
		  </tr>
                <tr>
                  <td colspan="2">
                    <hr>
                  </td>
                </tr>';

				$data.='
	<div class="policyitem">
	  <div class="policyitemdelete" onclick="javascript: $(this).parent().remove();">&nbsp;</div>
	  <div class="policydata">
	    '.nl2br($policy['question']).'<br>
	    '.nl2br($policy['response']).'
	  </div>
	  <input type="hidden" name="policylist[]" value="'.$policy['id'].'" />
	</div>';
			}

			$data.='
		</table><br>
	       <div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="PolicyForm"/></div>
	     </div>
	   </div>
        </form>';

			# Return data
			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}
	 */

	/**
	 * googleDrive
	 *
	 * Displays the websites google drive files
	 *
	 * @access	public
	 */
	public function googleDrive()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			if(isset($_POST['UpdateFileOrder']))
			{
				foreach($_POST['file_order'] as $key=>$value)
				{
					$database->query('UPDATE google_drive SET file_order=:fileorder WHERE file_id=:fileid', array(':fileorder'=>$value, ':fileid'=>$key));
				}
				$success[]="Files have been updated";
			}

			if(isset($_POST['DeleteFile']))
			{
				if(!isset($_POST['file_id']))
				{
					$error[]="Please choose a file to delete";
				}

				if(!isset($error))
				{
					$database->query('DELETE FROM google_drive WHERE file_id=:fileid', array(':fileid'=>$_POST['file_id']));
				}
			}

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

			$data.='
          <form name="UpdateFileOrder" id="UpdateFileOrder" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
              <div class="boxcontainerlabel">
                <div style="float: right">
                  <input name="UpdateFileOrder" class="headerbutton" type="submit" value="Update">
                  <div class="headerbuttongreen" onclick="javascript:window.location.href=\'admin.php?action=file_add\';">Add File</div>
	        </div>
                Google Drive Settings
              </div>
              <div class="boxcontainercontent">
                <table class="hlineheader"><tr><th rowspan="2" nowrap>Files</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table><br>
                <table width="100%" border="1" cellspacing="1" cellpadding="4">
                  <tr>
                    <th width="20px">Edit</th>
                    <th width="20px">Delete</th>
                    <th width="20px">Order</th>
                    <th width="255px">File</th>
                    <th>URL</th>
                  </tr>';

			$get_files=$this->getAllFiles();
			foreach($get_files as $file)
			{
				$data.='
                  <tr>
                    <td align="center"><a style="display: block; background-image: url(assets/images/icon_edit.gif); width: 14px; height: 14px;" href="admin.php?action=file_edit&file_id='.$file['file_id'].'"></a></td>
		    <td align="center">
                      <form name="DeleteFile" id="DeleteFile" method="post" action="'.$member->currentPage().'">
                        <input type="hidden" name="file_id" value="'.$file['file_id'].'">
                        <input name="DeleteFile" class="trashbutton" type="submit" value="">
                      </form>
                    </td>
                    <td><input type="text" name="file_order['.$file['file_id'].']" id="file_order" size="1" maxlength="3" value="'.$file['file_order'].'" style="text-align:center"></td>
		    <td>'.$file['file_name'].'</td>
		    <td><a href="'.$file['file_url'].'" title="'.$file['file_name'].'" target="blank">'.$file['file_url'].'</td>
                  </tr>';
			}

			$data.='
                </table><br>
              </div>
            </div>
          </form>';

			# Return data
			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * addFile
	 *
	 * Adds or Edits a Google Drive file.
	 *
	 * @access	public
	 */
	public function addFile()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			$file_id=NULL;
			$file_name=NULL;
			$file_url=NULL;

			if(isset($_GET['file_id']))
			{
				$file_result=$this->getFile($_GET['file_id']);
				$file_id=$file_result->file_id;
				$file_name=$file_result->file_name;
				$file_url=$file_result->file_url;
			}
			if(isset($_POST['AddFileForm']))
			{
				$file_name=ucwords(str_replace(array('_','-'), ' ', $_POST['file_name']));
				$file_url=$_POST['file_url'];

				# Check the File Name length
				$file_name_length=strlen($file_name);
				if($file_name_length < 8)
				{
					$error[]="Please enter a file name with 8 or more characters";
					$file_name=$file_name;
				}

				if(!isset($error))
				{
					# Insert file
					if(!isset($file_id))
					{
						$get_file_order=$this->getAllFiles('DESC', ' LIMIT 1');
						$file_order=$get_file_order[0]['file_order']+1;

						$database->query('INSERT INTO google_drive (file_name, file_url, file_order) VALUES (?, ?, ?)', array($file_name, $file_url, $file_order));
						$success[]=$file_name.' has been added';
					}
					elseif(isset($file_id))
					{
						$database->query('UPDATE google_drive SET file_name=:filename, file_url=:fileurl WHERE file_id=:fileid', array(':fileid'=>$file_id, ':filename'=>$file_name, ':fileurl'=>$file_url));
						$success[]=$file_name.' has been updated';
					}
				}
			}

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

			$data.='
          <form name="AddFileForm" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
               <div class="boxcontainerlabel">Add File</div>
               <div class="boxcontainercontent">
                 <table class="hlineheader"><tr><th rowspan="2" nowrap>File Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
                <table width="100%" border="0" cellspacing="1" cellpadding="4">
		  <tr>
                    <td align="left" valign="middle" class="zebraodd"><label for="file_name">Category Name:</label></td>
		    <td><input id="file_name" name="file_name" type="text" size="20" maxlength="30" class="swifttextlarge" value="'.$file_name.'"></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="file_url">File URL:</label></td>
		    <td><input id="file_url" name="file_url" type="text" size="30" maxlength="255" class="swifttextlarge" placeholder="https://docs.google.com/" pattern="https?://.+" value="'.$file_url.'"></td>
		  </tr>
                </table><br />
                <div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="AddFileForm"></div>
              </div>
            </div>
	  </form>';

			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/**
	 * getPolicy
	 *
	 * Retreives the policy from the `policies` table.
	 *
	 * @param	$policy_id
	 * @param	$where
	 * @access	public
	 */
	public function getPolicy($policy_id=NULL, $where=NULL)
	{
		global $database;

		$database->query('SELECT id, button_name, question, response, clipboard FROM policies WHERE id=:policyid'.$where, array(':policyid'=>$policy_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getConLocation
	 *
	 * Retreives the convention location from the `con_location` table.
	 *
	 * @param	$con_location_id
	 * @param	$where
	 * @access	public
	 */
	public function getConLocation($con_location_id=NULL, $where=NULL)
	{
		global $database;

		$database->query('SELECT id, city_name, year FROM con_locations WHERE id=:conlocationid'.$where, array(':conlocationid'=>$con_location_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getFile
	 *
	 * Retreives the file from the `google_drive` table.
	 *
	 * @param	$file_id
	 * @param	$where
	 * @access	public
	 */
	public function getFile($file_id=NULL, $where=NULL)
	{
		global $database;

		$database->query('SELECT file_id, file_name, file_url FROM google_drive WHERE file_id=:fileid'.$where, array(':fileid'=>$file_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getAllFiles
	 *
	 * Retrieves all files from the `google_drive` table.
	 *
	 * @param	$asc_desc
	 * @param	$limit
	 * @access	public
	 */
	public function getAllFiles($asc_desc='ASC', $limit=NULL)
	{
		global $database;

		$database->query('SELECT file_id, file_name, file_url, file_order FROM google_drive ORDER BY file_order '.$asc_desc.$limit, array());
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/*** End public methods ***/



	/*** protected methods ***/

	/**
	 * getUsersSortOptions
	 *
	 * Retrieves users sorting options from the `users_sorting` table.
	 *
	 * @access	private
	 */
	private function getUsersSortOptions($user_id)
	{
		global $database;

		$database->query('SELECT users_sort_field, users_sort_order FROM users_sorting WHERE user_id=:userid', array(':userid'=>$user_id));
		$results=$database->statement->fetch(PDO::FETCH_OBJ);

		return $results;
	}

	/*** End protected methods ***/
}
?>