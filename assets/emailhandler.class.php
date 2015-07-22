<?php

/**
 * Name:	EmailHandler
 * Author:	Draven
 *
 * The EmailHandler class is used to assign variables to caught emails and manipulate data in the `emails` and `email_*` tables.
 *
 */
class EmailHandler
{
	/*** public methods ***/

	/**
	 * randGuid
	 *
	 * Generates a random number
	 *
	 * @access	public
	 */
	public function randGuid($len=6, $start=FALSE, $end=FALSE)
	{
		mt_srand((double)microtime()*1000000);
		$start=(!$len && $start) ? $start : str_pad(1, $len, "0", STR_PAD_RIGHT);
		$end=(!$len && $end) ? $end : str_pad(9, $len, "9", STR_PAD_RIGHT);

		return mt_rand($start, $end);
	}

	/**
	 * genRandID
	 *
	 * Sets the length of the ID and checks the genGuid against the database to see if it exists.
	 *
	 * @access	public
	 */
	public function genRandID()
	{
		global $database;

		$length=rand(6,8);
		$ticket_id=$this->randGuid($length);
		$database->query('SELECT id FROM emails WHERE ticket_id=:ticketid', array(':ticketid'=>$ticket_id));
		if($database->count()>0)
		{
			return $this->genRandID();
		}

		return $ticket_id;
	}

	/**
	 * getEmailID
	 *
	 * Gets the ID from the `emails` table.
	 *
	 * @access	public
	 */
	public function getEmailID($ticket_id)
	{
		global $database;

		$database->query('SELECT id FROM emails WHERE ticket_id=:ticketid', array(':ticketid'=>$ticket_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->id;
	}

	/**
	 * getTicketID
	 *
	 * Retrieves and returns the ticket_id of an email.
	 *
	 * @access	public
	 */
	public function getTicketID($email_id)
	{
		global $database;

		$database->query('SELECT ticket_id FROM emails WHERE id=:emailid', array(':emailid'=>$email_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->ticket_id;
	}

	/**
	 * getSenderEmail
	 *
	 * Retreives and returns the sender_email
	 *
	 * @access	public
	 */
	public function getSenderEmail($email_id)
	{
		global $database;

		$database->query('SELECT sender_email FROM emails WHERE id=:emailid', array(':emailid'=>$email_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->sender_email;
	}

	/**
	 * getEmailStatus
	 *
	 * Retreives and returns the status of an email.
	 *
	 * @access	public
	 */
	public function getEmailStatus($email_id)
	{
		global $database;

		$database->query('SELECT status FROM emails WHERE id=:emailid', array(':emailid'=>$email_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->status;
	}

	/**
	 * getPolicies
	 *
	 * Retrieves and returns policies from the `policies` table
	 *
	 * @access	public
	 */
	public function getPolicies()
	{
		global $database;

		$database->query('SELECT button_name, question, response FROM policies WHERE clipboard=2', array());
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * getNewestCreated
	 *
	 * Retrieves the newest `created` date from the `email_message` and `email_response` tables.
	 *
	 * @param	$email_id
	 * @access	public
	 */
	public function getNewestCreated($email_id)
	{
		global $database;

		$database->query('SELECT updated FROM emails WHERE id=:emailid', array(':emailid'=>$email_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result->updated;
	}

	/**
	 * caughtEmail
	 *
	 * Assigns variables from the email and inserts them into the database.
	 *
	 * @access	public
	 */
	public function caughtEmail($full_email)
	{
		global $database;
		global $member;
		global $EmailClass;

		# Include email parser
		require_once('rfc822_addresses.class.php');
		require_once('mime_parser.class.php');

		# Create the email parser class
		$mime=new mime_parser_class;

		$mime->ignore_syntax_errors=1;
		$parameters=array(
			'Data'=>$full_email,
		);

		$mime->Decode($parameters, $decoded);

		for($message=0; $message<count($decoded); $message++)
		{
			$mime->Analyze($decoded[$message], $results);
		}

		# Stop script is it's spam
		if((strpos($results['Subject'], '***SPAM***')!==FALSE) || (preg_match('/\beBay\b/i',$results['From'][0]['name'])))
		{
			exit;
		}

		# If Encoding is not UTF-8 then convert it
		if($results['Encoding']!='utf-8')
		{
			$results['Subject']=$this->convertEncode($results['Subject'], $results['Encoding']);
			$results['Alternative'][0]['Data']=$this->convertEncode($results['Alternative'][0]['Data'], $results['Encoding']);
		}

		# Get the name and email of the sender
		$fromName=$results['From'][0]['name'];
		$fromEmail=$results['From'][0]['address'];

		# If there is no fromName then fromName becomes fromEmail
		if(!isset($fromName) || empty($fromName))
		{
			$fromName1=explode('@', $fromEmail);
			$fromName=$fromName1[0];
		}
		elseif(filter_var($fromName, FILTER_VALIDATE_EMAIL))
		{
			$fromName1=explode('@', $fromName);
			$fromName=$fromName1[0];
		}

		# Get the name and email of the recipient
		foreach($results['To'] as $to)
		{
			$domain=strstr($to['address'], '@');
			$domain_name='@'.DOMAIN_NAME;
			if($domain==$domain_name)
			{
				$toName=$to['name'];
				$toEmail=$to['address'];
			}
		}
		if(!isset($toName)) $toName="";

		# Get the subject
		$subject=trim($results['Subject']);

		# If no subject then make it (No Subject)
		if($subject=="") $subject="(No Subject)";

		# Retrieve Email ID from the subject
		preg_match('/\[#(\d+)\]/U', $subject, $subject_ticket_id);

		# Get the body
		if($results['Type'] != "text")
		{
			$body=$results['Alternative'][0]['Data'];
		}
		else
		{
			$body=$results['Data'];
		}

		# Get rid of any quoted text in the email body and strip signatures
		if(preg_match('/((?:\r|\n||\n\r)--\s*(?:\r|\n||\n\r))/', $body))
		{
			$body_array=explode("\n", $this->stripSignature($body));
		}
		else
		{
			$body_array=explode("\n", $body);
		}

		$new_body="";
		foreach($body_array as $key=>$value)
		{
			# Remove hotmail sig
			if($value=="_________________________________________________________________")
			{
				break;
			}
			# Original message quote
			elseif(preg_match("/^-*(.*)Original Message(.*)-*/i",$value,$matches))
			{
				break;
			}
			# Check for date wrote string
			elseif(preg_match("/^On(.*)wrote:(.*)/i",$value,$matches))
			{
				break;
			}
			# Check for From Name email section
			elseif(preg_match("/^On(.*)$fromName(.*)/i",$value,$matches))
			{
				break;
			}
			# Check for To Name email section
			elseif(preg_match("/^On(.*)$toName(.*)/i",$value,$matches))
			{
				break;
			}
			# Check for To Email email section
			elseif(preg_match("/^(.*)$toEmail(.*)wrote:(.*)/i",$value,$matches))
			{
				break;
			}
			# Check for From Email email section
			elseif(preg_match("/^(.*)$fromEmail(.*)wrote:(.*)/i",$value,$matches))
			{
				break;
			}
			# Check for quoted ">" section
			elseif(preg_match("/^>(.*)/i",$value,$matches))
			{
				break;
			}
			# Check for date wrote string with dashes
			elseif(preg_match("/^---(.*)On(.*)wrote:(.*)/i",$value,$matches))
			{
				break;
			}
			# Add line to body
			else
			{
				$new_body.="$value\n";
			}
		}

		# Retrive the Email ID from the body
		preg_match('/Email ID: (?P<body_ticket_id>\w+)/', $new_body, $body_ticket_id);

		# If there is no ticket ID in the subject line or body then create new ticket.
		if((!isset($subject_ticket_id)) && (!isset($body_ticket_id)))
		{
			$new_email=TRUE;
		}
		else
		{
			if((isset($subject_ticket_id[1]) && (isset($body_ticket_id['body_ticket_id']))) && ($subject_ticket_id[1]==$body_ticket_id['body_ticket_id']))
			{
				$ticket_id=$subject_ticket_id[1];
			}
			elseif(isset($subject_ticket_id[1]))
			{
				$ticket_id=$subject_ticket_id[1];
			}
			elseif(isset($body_ticket_id['body_ticket_id']))
			{
				$ticket_id=$body_ticket_id['body_ticket_id'];
			}
			if(isset($ticket_id))
			{
				$email_id=$this->getEmailID($ticket_id);
				if($this->compareTicket($email_id, $ticket_id, $fromEmail)==TRUE)
				{
					$new_email=FALSE;

					$check_status=$this->getEmailStatus($email_id);

					$last_email_id=$email_id;

					if($check_status=="Closed")
					{
						$database->query('UPDATE emails SET status=:status WHERE id=:id', array(':status'=>2, ':id'=>$email_id));
					}
					$database->query('UPDATE emails SET last_replier=:lastreplier, updated=NOW() WHERE id=:id', array(':lastreplier'=>$fromName, ':id'=>$email_id));
					$database->query('INSERT INTO email_message (email_id, ticket_id, message, created) VALUES (?, ?, ?, NOW())', array($email_id, $ticket_id, trim($new_body)));

					$last_email_message_id=$database->lastInsertId();
				}
				else
				{
					# The ticket ID doesn't match the sender email so create new ticket.
					$new_email=TRUE;
				}
			}
			else
			{
				# $ticket_id is not set so create new ticket.
				$new_email=TRUE;
			}
		}

		# If $new_email is TRUE then create new ticket.
		if($new_email==TRUE)
		{
			$ticket_id=$this->genRandID(6);
			$category1=explode('@',$toEmail);
			$category=ucwords(str_replace('_', ' ', $category1[0]));

			$database->query('INSERT INTO emails (ticket_id, category, recipient_name, recipient_email, sender_name, sender_email, subject, message, last_replier, created, updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())', array($ticket_id, $category, $toName, $toEmail, $fromName, $fromEmail, $subject, trim($new_body), $fromName));

			# Get last email insert ID
			$last_email_id=$database->lastInsertId();

			$subject="[#".$ticket_id."] ".$subject;
			$body_content=$fromName.",\r\n\r\nThank you for contacting us. This is an automated response confirming your email. We will get back to you shortly.\r\nWhen replying, please make sure that the email ID is kept in the subject line to ensure that your replies are tracked appropriately.\r\n\r\nEmail ID: ".$ticket_id."\r\nSubject: ".$subject."\r\nDepartment: ".$category."\r\nStatus: Open";
		}

		# Send email to users with notification
		$database->query('SELECT id, full_name, email, email_notify FROM users WHERE email_notify=1', array());
		$users_notify=$database->statement->fetchAll(PDO::FETCH_OBJ);
		if($database->count() > 0)
		{
			foreach($users_notify as $row)
			{
				if($new_email==TRUE)
				{
					$newest_email_id=$last_email_id;
				}
				else
				{
					$newest_email_id=$last_email_meessage_id;
				}
				$notify_subject=$EmailClass->site_name." - New Email";
				$notify_body_content=$row->full_name.",\r\n\r\nThere is a new email from ".$fromName." on the Hub\r\n\r\nhttp://www.".DOMAIN_NAME."/hub/admin.php?action=view-email&id=".$newest_email_id;
				$EmailClass->sendEmail($row->email, $notify_subject, $notify_body_content, $row->full_name);
			}
		}

		# Check for attachments
		if(isset($results['Related']) || isset($results['Attachments']))
		{
			function walk_func(&$el, $key, $params)
			{
				global $database;

				$email_id=$params[0];
				$last_email_id=$params[1];
				$last_email_message_id=$params[2];
				$fromEmail=$params[3];
				$new_email=$params[4];

				$file_data=$el['Data'];
				$filename=trim($el['FileName']);
				$filename=preg_replace('/[^0-9,a-z,\.,_]*/i', '', str_replace(' ', '_', $filename));

				if($new_email==TRUE)
				{
					$database->query('INSERT INTO email_attachments (email_id, file_name, created) VALUES (?, ?, NOW())', array($last_email_id, $filename));
				}
				else
				{
					$database->query('INSERT INTO email_attachments (email_id, reply_id, file_name, created) VALUES (?, ?, ?, NOW())', array($email_id, $last_email_message_id, $filename));
				}

				$last_attachment_id=$database->lastInsertId();

				# Write the data to the file
				if(!is_dir(ATTACHMENTS.$fromEmail.DS.$last_email_id.DS.$last_attachment_id))
				{
					mkdir(ATTACHMENTS.$fromEmail.DS.$last_email_id.DS.$last_attachment_id.DS, 0755, TRUE);
				}
				$fp=fopen(ATTACHMENTS.$fromEmail.DS.$last_email_id.DS.$last_attachment_id.DS.$filename, 'w');
				$written=fwrite($fp, $file_data);
				fclose($fp);

				$attachment_url=ATTACHMENTS.$fromEmail.DS.$last_email_id.DS.$last_attachment_id.DS.$filename;
				if(filesize($attachment_url) > 4)
				{
					$database->query('UPDATE email_attachments SET file_size=:filesize WHERE id=:lastattachmentid', array(':filesize'=>filesize($attachment_url), ':lastattachmentid'=>$last_attachment_id));
				}
			};

			$data=array($email_id, $last_email_id, $last_email_message_id, $fromEmail, $new_email);
			if(isset($results['Related']))
			{
				array_walk($results['Related'], 'walk_func', $data);
			}

			if(isset($results['Attachments']))
			{
				array_walk($results['Attachments'], 'walk_func', $data);
			}
		}
	}

	/**
	 * listEmails
	 *
	 * Retreives the emails in the `emails` table.
	 *
	 * @access	public
	 */
	public function listEmails()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			if(isset($_POST['addFolder']))
			{
				foreach($_POST['folder'] as $id=>$value)
				{
					$value=trim($value);
					if($value)
					{
						$database->query('INSERT INTO folders (name, type) VALUES (?, 1)', array($value));
					}
				}
				$success[]="Folder(s) added";
				header("Location: ".$member->currentPage());
			}
			elseif(!empty($_POST['folder_id']))
			{
				if(isset($_POST['action']))
				{
					foreach($_POST['action'] as $id=>$value)
					{
						$database->query('UPDATE emails SET folder_id=:folder_id WHERE id=:id', array(':folder_id'=>$_POST['folder_id'], ':id'=>$id));
					}
					$success[]="Email(s) was moved";
					header("Location: ".$member->currentPage());
				}
				else
				{
					$error[]="Select an email to move";
				}
			}
			elseif(!empty($_POST['delete-email']))
			{
				if(isset($_POST['action']))
				{
					foreach($_POST['action'] as $id=>$value)
					{
						# Check if there was an attachment deletion error
						$attachments_deleted=TRUE;

						# If email has attachments
						if($this->countAttachments($id) > 0)
						{
							$sender_email=$this->getSenderEmail($id);

							# Delete attachment files and folders
							$this->rrmdir(ATTACHMENTS.$sender_email.DS.$id);

							# Check if directory still exists
							if(!is_dir(ATTACHMENTS.$sender_email.DS.$id))
							{
								# Delete Attachments
								$database->query('DELETE FROM email_attachments WHERE email_id=:emailid', array(':emailid'=>$id));
							}
							else
							{
								$attachments_deleted=FALSE;
								$error[]="Email attachments failed to delete";
							}
						}

						# Delete emails
						if($attachments_deleted==TRUE)
						{
							$database->query('DELETE FROM emails WHERE id=:id', array(':id'=>$id));
							$database->query('DELETE FROM email_response WHERE email_id=:emailid', array(':emailid'=>$id));
							$database->query('DELETE FROM email_message WHERE email_id=:emailid', array(':emailid'=>$id));

							# Delete Chat messages
							$database->query('DELETE FROM email_chat WHERE email_id=:emailid', array(':emailid'=>$id));

							# Log
							$member->userLogger($user_id, 7);
						}
					}
				}
				else
				{
					$error[]="Select an email to delete";
				}
			}
			elseif(!empty($_POST['merge-email']))
			{
				if(isset($_POST['action']))
				{
					$lowest_id=min($_POST['action']);
					$ticket_id=$this->getTicketID($lowest_id);
					$newest_created=$this->getNewestCreated(max($_POST['action']));

					foreach($_POST['action'] as $id=>$value)
					{
						if($id != $lowest_id)
						{
							$database->query('SELECT ticket_id, sender_email, message, created FROM emails WHERE id=:id', array(':id'=>$id));
							$emails_result=$database->statement->fetch(PDO::FETCH_OBJ);

							# Merge Email Messages
							$database->query('INSERT INTO email_message (email_id, ticket_id, message, created) VALUES (?, ?, ?, ?)', array($lowest_id, $emails_result->ticket_id, $emails_result->message, $emails_result->created));
							$email_message_id=$database->lastInsertId();

							# Update email_message table if exists
							if($this->countEmailMessage($id) > 0)
							{
								$database->query('UPDATE email_message SET email_id=:lowestid, ticket_id=:ticketid WHERE email_id=:id', array(':lowestid'=>$lowest_id, ':ticketid'=>$ticket_id, ':id'=>$id));
							}

							# Update email_response table if exists
							if($this->countEmailResponse($id) > 0)
							{
								$database->query('UPDATE email_response SET email_id=:lowestid, ticket_id=:ticketid WHERE email_id=:id', array(':lowestid'=>$lowest_id, ':ticketid'=>$ticket_id, ':id'=>$id));
							}

							# Update attachments if they exist
							if($this->countAttachments($id) > 0)
							{
								$attachment_url=ATTACHMENTS.$emails_result->sender_email.DS;
								if(is_dir($attachment_url.$lowest_id))
								{
									if(!$this->copy_r($attachment_url.$id, $attachment_url.$lowest_id))
									{
										$error[]="Failed to copy files";
									}
								}
								else
								{
									if(!rename($attachment_url.$id, $attachment_url.$lowest_id))
									{
										$error[]="Directory rename failed";
									}
								}

								if(!$error)
								{
									$database->query('UPDATE email_attachments SET email_id=:lowestid, reply_id=:emailmessageid WHERE email_id=:id', array(':lowestid'=>$lowest_id, ':emailmessageid'=>$email_message_id, ':id'=>$id));
								}
							}

							# Updated `created` field
							$database->query('UPDATE emails SET updated=:updated WHERE id=:lowestid', array(':updated'=>$newest_created, ':lowestid'=>$lowest_id));

							# Delete newest email(s)
							$database->query('DELETE FROM emails WHERE id=:id', array(':id'=>$id));

							# Log
							$member->userLogger($user_id, 8);
						}
					}
				}
			}
			elseif(!empty($_POST['open-email']))
			{
				if(isset($_POST['action']))
				{
					foreach($_POST['action'] as $id=>$value)
					{
						# Open Emails
						$database->query('UPDATE emails SET status=1 WHERE id=:id', array(':id'=>$id));
					}
				}
			}
			elseif(!empty($_POST['close-email']))
			{
				if(isset($_POST['action']))
				{
					foreach($_POST['action'] as $id=>$value)
					{
						# Close Emails
						$database->query('UPDATE emails SET status=3 WHERE id=:id', array(':id'=>$id));
					}
				}
			}

			if(isset($_GET['view-all']))
			{
				$check_view=$member->getUsersEmailView($user_id);

				if($check_view != $_GET['view-all'])
				{
					$view_all=$_GET['view-all'];

					$database->query('UPDATE users SET view_all_email=:viewall WHERE id=:userid', array(':viewall'=>$view_all, ':userid'=>$user_id));
				}
			}

			if(isset($_GET['sort']) && isset($_GET['asc-desc']))
			{
				# Get users sorting options
				$sort_options=$this->getEmailSortOptions($user_id);

				if($sort_options->email_sort_field != $_GET['sort'] || $sort_options->email_sort_order != $_GET['asc-desc'])
				{
					$email_sort_field=$_GET['sort'];
					$email_sort_order=$_GET['asc-desc'];

					$database->query('UPDATE users_sorting SET email_sort_field=:emailsortfield, email_sort_order=:emailsortorder WHERE user_id=:userid', array(':emailsortfield'=>$email_sort_field, ':emailsortorder'=>$email_sort_order, ':userid'=>$user_id));
				}
			}

			# Get users sorting options
			$sort_options=$this->getEmailSortOptions($user_id);
			($sort_options->email_sort_order=="ASC" ? $asc_desc="DESC" : $asc_desc="ASC");

			$users_view=$member->getUsersEmailView($user_id);
			if($users_view=="No")
			{
				$where="WHERE status < 3 ";
			}
			else
			{
				$where="WHERE status >= 1 ";
			}

			$view_all_assigned=$member->getUsersAssignedView($user_id);
			if($view_all_assigned=="No")
			{
				$where.=' AND (assigned_to=0 OR assigned_to='.$user_id.') ';
			}

			(isset($_GET['folder']) ? $where.=' AND folder_id="'.$_GET['folder'].'" ' : $where.=" AND folder_id IS NULL ");
			(isset($_GET['category']) ? $where.=' AND category="'.$_GET['category'].'" ' : $where.="");
			(isset($_GET['assigned']) && $_GET['assigned']=="TRUE" ? $where.=' AND assigned_to="'.$user_id.'" ' : $where.="");

			$database->query('SELECT id, ticket_id, sender_name, sender_email, category, last_replier, subject, status, updated FROM emails '.$where.'ORDER BY '.$sort_options->email_sort_field.' '.$sort_options->email_sort_order, array());
			$results=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			$count_closed=$this->countClosed();

			$view_all="No";
			$hide_show="Hide Resolved Emails";
			if((isset($_GET['view-all']) && ($_GET['view-all']=="No")) || ($users_view=="No"))
			{
				$view_all="Yes";
				$hide_show="View Resolved Emails (".$count_closed.")";
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

			$database->query('SELECT id, name FROM folders WHERE type=1 ORDER BY name', array());
			$folder_results=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			$data.='
	  <form name="delete-email" action="'.$member->currentPage().'" method="post">
	    <div class="boxcontainer">
	      <div class="boxcontainerlabel">
	        <div style="float: right">
	          '.($is_admin ? '<input name="delete-email" class="headerbutton" type="submit" value="Delete">' : '').'
		  <input name="merge-email" class="headerbutton" type="submit" value="Merge">
		  <input name="open-email" class="headerbutton" type="submit" value="Open">
		  <input name="close-email" class="headerbutton" type="submit" value="Close">
		  <select class="folderselect" name="folder_id">
		    <option value=""'.(isset($folde) && $folder=="" ? ' selected' : '').'>Move to...</option>';

			foreach($folder_results as $folder_row)
			{
				$data.='
		    <option value="'.$folder_row['id'].'">'.$folder_row['name'].'</option>';
			}

			$data.='
		  </select>
		  '.($count_closed>0 ? '<div class="headerbuttongreen" onclick="javascript: setGetParameter(\'view-all\', \''.$view_all.'\');">'.$hide_show.'</div>' : '').'
		  <!--div class="headerbuttongreen" onclick="javascript:window.location.href=\'admin.php?action=list-emails&view-all='.$view_all.'\';">'.$hide_show.'</div-->
	        </div>
	        View Emails
	      </div>';

			if(!empty($results))
			{
				$data.='
	      <div class="boxcontainercontent">
	        <table border="0" cellpadding="3" cellspacing="1" width="100%">
	          <tr>
	            <td class="ticketlistheaderrow" align="left" valign="middle" width="20">&nbsp;</td>
	            <td class="ticketlistheaderrow" align="left" valign="middle" width="150">Email ID</td>
	            <td class="ticketlistheaderrow" align="center" valign="middle" width="240"><a href="admin.php?action=list-emails&sort=updated&asc-desc='.$asc_desc.'">Last Update&nbsp;'.($sort_options->email_sort_field=="updated" ? '<img src="assets/images/'.($sort_options->email_sort_order=="ASC" ? 'sortasc.gif' : 'sortdesc.gif').'"' : '').'</a></td>
		    <td class="ticketlistheaderrow" align="center" valign="middle" width=""><a href="admin.php?action=list-emails&sort=last_replier&asc-desc='.$asc_desc.'">Last Replier&nbsp;'.($sort_options->email_sort_field=="last_replier" ? '<img src="assets/images/'.($sort_options->email_sort_order=="ASC" ? 'sortasc.gif' : 'sortdesc.gif').'"' : '').'</a></td>
		    <td class="ticketlistheaderrow" align="center" valign="middle" width="180"><a href="admin.php?action=list-emails&sort=category&asc-desc='.$asc_desc.'">Category&nbsp;'.($sort_options->email_sort_field=="category" ? '<img src="assets/images/'.($sort_options->email_sort_order=="ASC" ? 'sortasc.gif' : 'sortdesc.gif').'"' : '').'</a></td>
		    <td class="ticketlistheaderrow" align="center" valign="middle" width="100">Attachments&nbsp;</td>
		    <td class="ticketlistheaderrow" align="center" valign="middle" width="160">Chatbox Messages&nbsp;</td>
		    <td class="ticketlistheaderrow" align="center" valign="middle" width="120"><a href="admin.php?action=list-emails&sort=status&asc-desc='.$asc_desc.'">Status&nbsp;'.($sort_options->email_sort_field=="status" ? '<img src="assets/images/'.($sort_options->email_sort_order=="ASC" ? 'sortasc.gif' : 'sortdesc.gif').'"' : '').'</a></td>
		  </tr>';

				foreach($results as $row)
				{
					$updated=strtotime($row['updated']);
					$row['updated']=date("d F Y g:i A", $updated);

					$data.='
		  <tr>
		    <td class="ticketlistsubject" align="left" valign="middle" colspan="8"><a href="admin.php?action=view-email&id='.$row['id'].'">'.$row['subject'].'</a></td>
		  </tr>
		  <tr class="ticketlistproperties" style="background: '.($row['status']=="Closed" ? '#5f5f5f' : '#8BB467').';">
		    <td class="ticketlistpropertiescontainer" align="center" valign="middle"><input type="checkbox" name="action['.$row['id'].']" id="checkbox['.$row['id'].']" value="'.$row['id'].'"></td>
		    <td class="ticketlistpropertiescontainer" align="left" valign="middle">'.$row['ticket_id'].'</td>
		    <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.$row['updated'].'</td>
		    <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.$row['last_replier'].'</td>
		    <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.$row['category'].'</td>
		    <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.($this->countAttachments($row['id']) > 0 ? 'Yes' : 'No').'</td>
		    <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.$this->countEmailChat($row['id']).'</td>
		    <td class="ticketlistpropertiescontainer" align="center" valign="middle">'.$row['status'].'</td>
		  </tr>
		  <tr class="ticketlistpropertiesdivider">
		    <td colspan="8">&nbsp;</td>
		  </tr>';
				}
			}
			else
			{
				$data.='  There are no emails';
			}

			$data.='
		</table>
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
	 * viewEmail
	 *
	 * Retreives the email from the `emails` table and displays it.
	 *
	 * @access	public
	 */
	public function viewEmail()
	{
		global $database;
		global $member;
		global $EmailClass;

		$email_id=$_GET['id'];

		if(isset($_POST['ticketstatusid']))
		{
			if(empty($_POST['ticketstatusid']))
			{
				$error[]="Please select a status";
			}
			if(!isset($_POST['assigned_to']))
			{
				$error[]="Assigned to is empty";
			}
			if(!isset($error))
			{
				$ticketstatusid=$_POST['ticketstatusid'];
				$assigned_to=$_POST['assigned_to'];
				$database->query('UPDATE emails SET assigned_to=:assignedto, status=:status WHERE id=:emailid', array(':assignedto'=>$assigned_to, ':status'=>$ticketstatusid, ':emailid'=>$email_id));

				if($assigned_to > 0)
				{
					$admin_full_name=$member->getUsersName($assigned_to);
					$notify_subject=$EmailClass->site_name." - Assigned Email";
					$notify_body_content=$admin_full_name.",\r\n\r\nYou have been assigned an email on the Hub";
					$EmailClass->sendEmail($member->getUsersEmail($assigned_to), $notify_subject, $notify_body_content, $admin_full_name);
				}
			}
		}
		if(isset($_POST['TicketReplyForm']))
		{
			$database->query('SELECT ticket_id, category, recipient_email, sender_name, sender_email, subject, status FROM emails WHERE id=:emailid', array(':emailid'=>$email_id));
			$reply_result=$database->statement->fetch(PDO::FETCH_OBJ);

			if(!empty($_POST['replycontents']))
			{
				$staff_id=$member->getUsersID();
				$staff_name=$member->getUsersName($staff_id);
				$replycontents=$_POST['replycontents'];

				$status="In Progress";
				if(isset($_POST['close']) && $_POST['close']==1)
				{
					$status="Closed";
				}

				$database->query('UPDATE emails SET status=:status, last_replier=:staffname, updated=NOW() WHERE id=:id', array(':status'=>$status, ':staffname'=>$staff_name, ':id'=>$email_id));
				$database->query('INSERT INTO email_response (email_id, ticket_id, staff_id, message, created) VALUES (?, ?, ?, ?, NOW())', array($email_id, $reply_result->ticket_id, $staff_id, $replycontents));

				# Attachments
				if(isset($_FILES['ticketattachments']))
				{
					$ticketattachments=$_FILES['ticketattachments'];

					if(count($ticketattachments['name']) > 0)
					{
						include('upload.class.php');

						$last_email_response_id=$database->lastInsertId();

						$files=array();
						foreach($ticketattachments as $k=>$l)
						{
							foreach($l as $i=>$v)
							{
								if(!array_key_exists($i, $files))
								{
									$files[$i]=array();
								}
								$files[$i][$k]=$v;
							}
						}

						# Create attachment array
						$attachment=array();

						# Now we can loop through $files, and feed each element to the class
						foreach($files as $file)
						{
							$filename=preg_replace('/[^0-9,a-z,\.,_]*/i', '', str_replace(' ', '_', $file['name']));
							$database->query('INSERT INTO email_attachments (email_id, reply_id, file_name, file_size, created) VALUES (?, ?, ?, ?, NOW())', array($email_id, $last_email_response_id, $filename, $file['size']));

							$last_attachment_id=$database->lastInsertId();

							# Add files to the attachment array
							$attachment[]=ATTACHMENTS.$reply_result->sender_email.DS.$email_id.DS.$last_attachment_id.DS.$file['name'];

							# We instanciate the class for each element of $file
							$handle=new upload($file);

							# Then we check if the file has been uploaded properly
							# in its *temporary* location in the server (often, it is /tmp)
							if($handle->uploaded)
							{
								# Now, we start the upload 'process'. That is, to copy the uploaded file
								# from its temporary location to the wanted location
								# It could be something like $handle->Process('/home/www/my_uploads/');
								$handle->Process(ATTACHMENTS.$reply_result->sender_email.DS.$email_id.DS.$last_attachment_id);
							}
						}
					}
				}

				# Send Email
				$subject="[#".$reply_result->ticket_id."] ".$reply_result->subject;
				$body_content=$reply_result->sender_name.",\r\n\r\n".$replycontents."\r\n\r\nEmail ID: ".$reply_result->ticket_id."\r\nSubject: ".$reply_result->subject."\r\nDepartment: ".$reply_result->category."\r\nStatus: ".$status;
				if(isset($_POST['addsignature']) && $_POST['addsignature']==1)
				{
					$staff_name=$member->getUsersName($staff_id);
					$EmailClass->sendEmail($reply_result->sender_email, $subject, $body_content, $reply_result->sender_name, $reply_result->recipient_email, $attachment, $staff_name);
				}
				else
				{
					$EmailClass->sendEmail($reply_result->sender_email, $subject, $body_content, $reply_result->sender_name, $reply_result->recipient_email, $attachment);
				}
			}
		}
		if(isset($_POST['chatform']))
		{
			if(!empty($_POST['chat_input']))
			{
				$staff_id=$member->getUsersID();
				$chat_input=$_POST['chat_input'];

				$database->query('INSERT INTO email_chat (email_id, staff_id, message, created) VALUES (?, ?, ?, NOW())', array($email_id, $staff_id, $chat_input));
			}
		}

		$database->query('SELECT id, ticket_id, category, recipient_email, sender_name, sender_email, subject, message, assigned_to, status, created, updated FROM emails WHERE id=:emailid', array(':emailid'=>$email_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		if(isset($_GET['GetAttachment']))
		{
			$database->query('SELECT file_name, file_size FROM email_attachments WHERE id=:attachmentid', array(':attachmentid'=>$_GET['attachment_id']));
			$attachment_result=$database->statement->fetch(PDO::FETCH_OBJ);

			$attachment_url=$EmailClass->site_url.'hub/assets/attachments/'.$result->sender_email.'/'.$_GET['id'].'/'.$_GET['attachment_id'].'/'.$attachment_result->file_name;

			header("Content-Disposition: attachment; filename=\"".$attachment_result->file_name."\"");
			header("Content-Type: application/force-download");
			header("Content-Length: ".$attachment_result->file_size);
			header("Connection: close");
			readfile($attachment_url);
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

		if(!empty($result))
		{
			$usertype=$member->getUsertype($result->sender_email);
			$created=strtotime($result->created);
			$result->created=date("d F Y g:i A", $created);
			$updated=strtotime($result->updated);
			$result->updated=date("d F Y g:i A", $updated);

			$user_id=$member->getUsersID();

			# Combine Data
			$data.='
		      <div class="boxcontainer">
			<div class="boxcontainerlabel"><div style="float: right">'.($member->isAdmin($user_id) ? '<div class="headerbutton" onclick="javascript: $(\'#ticketpropertiesform\').submit();">Update</div>' : '').'<div class="headerbuttongreen" onclick="javascript: $(\'#postreplycontainer\').show(); $(\'#replycontents\').focus();">Post Reply</div></div>View Email: #'.$result->ticket_id.'</div>
			<div class="boxcontainercontenttight">
			  <form name="ticketpropertiesform" id="ticketpropertiesform" method="post" action="'.$member->currentPage().'">
			    <table width="100%" cellspacing="0" cellpadding="0" border="0">
			      <tbody>
			        <tr>
			          <td>
			            <div class="ticketgeneralcontainer">
			              <div class="ticketgeneraltitlecontainer">
			                <div class="ticketgeneraldepartment">'.$result->category.'</div>
			                <div class="ticketgeneraltitle">'.$result->subject.'</div>
			              </div>
 			              <div class="ticketgeneralinfocontainer">Created: '.$result->created.'&nbsp;&nbsp;&nbsp;&nbsp;Updated: '.$result->updated.'</div>
			            </div>
			          </td>
			        </tr>
			        <tr>
			          <td style="background-color: '.($result->status=="Closed" ? '#5f5f5f' : '#8BB467').';">
			            <div class="ticketgeneralcontainer">
			              <div style="background-color: '.($result->status=="Closed" ? '#5f5f5f' : '#8BB467').';" class="ticketgeneralproperties">
			                <div class="ticketgeneralpropertiesobject"><div class="ticketgeneralpropertiestitle">CATEGORY</div><div class="ticketgeneralpropertiescontent">'.$result->category.'</div></div>
			                <div class="ticketgeneralpropertiesdivider"><img border="0" align="middle" src="assets/images/ticketpropertiesdivider.png"></div>
			                <div class="ticketgeneralpropertiesobject">
			                  <div class="ticketgeneralpropertiestitle">ASSIGNED TO</div>
			                  <div class="ticketgeneralpropertiesselect">
			                    <select class="swiftselect" name="assigned_to">
			                      ';

			# Show Email options (assign / status)
			$admin_result=$member->listAdmins();
			$data.='  <option value="0"'.($result->assigned_to==0 ? ' selected' : '').'>Unassigned</option>';
			foreach($admin_result as $admin)
			{
				$data.='<option value="'.$admin['id'].'"'.($result->assigned_to==$admin['id'] ? ' selected' : '').'>'.$admin['full_name'].'</option>';
			}

			$data.='
			                    </select>
			                  </div>
			                </div>
			                <div class="ticketgeneralpropertiesdivider"><img border="0" align="middle" src="assets/images/ticketpropertiesdivider.png"></div>
			                <div class="ticketgeneralpropertiesobject">
			                    <div class="ticketgeneralpropertiestitle">STATUS</div>';
			if($member->isAdmin($user_id))
			{
				$data.='
			                  <div class="ticketgeneralpropertiesselect">
			                    <select class="swiftselect" name="ticketstatusid">
			                      <option value="1"'.(($result->status=="Open") ? ' selected' : '').'>Open</option>
			                      <option value="2"'.(($result->status=="In Progress") ? ' selected' : '').'>In Progress</option>
			                      <option value="3"'.(($result->status=="Closed") ? ' selected' : '').'>Closed</option>
			                    </select>
			                  </div>';
			}
			else
			{
				$data.='
			                  <div class="ticketgeneralpropertiescontent">'.$result->status.'</div>';
			}
			$data.='
			                </div>
			              </div>
			            </div>
			          </td>
			        </tr>
			      </tbody>
			    </table><br />
			    <div class="viewticketcontentcontainer">
			    </div>
			  </form>
			  <div id="postreplycontainer" style="display: none;">
			    <form method="post" action="'.$member->currentPage().'" name="TicketReplyForm" enctype="multipart/form-data">
			      <div class="ticketpaddingcontainer">
			        <table style="float: left; width: 8%" class="hlineheader"><tr><th rowspan="2" nowrap>Message Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
				<div id="format-buttons" style="display: inline">';

			$policy_result=$this->getPolicies();
			if(!empty($policy_result))
			{
				$js=NULL;

				foreach($policy_result as $policy)
				{
					$button_name=strtolower(str_replace(' ', '_', $policy['button_name']));
					$policy['response']=preg_replace('/[\r\n]+/', '\n', $policy['response']);

					$js.='
$("#'.$button_name.'").click(function () {
        $(\'#replycontents\').val($(\'#replycontents\').val()+\''.$policy['response'].'\');
        $(\'#replycontents\').focus();
});
';
					$data.='
				      <input type="button" class="button2" id="'.$button_name.'" name="'.$button_name.'" value="'.$policy['button_name'].'" style="font-weight:bold; width: 30px" title="'.$policy['response'].'">';
				}
				$data.='
				      <script type="text/javascript">
				      //<![CDATA[
				      $(document).ready(function(){
'.$js.'
				      });//]]>
				      </script>';
			}

			$data.='
				</div>
			        <table width="100%" border="0" cellspacing="1" cellpadding="4">
			          <tr>
			            <td colspan="2" align="left" valign="top"><textarea name="replycontents" cols="25" rows="15" id="replycontents" class="swifttextareawide" placeholder="Do not add a greeting or a signature to this box! A greeting will automatically be added. If you want to use a signature, use the checkbox below."></textarea></td>
			          </tr>
			        </table><br />
			        <table class="hlineheader"><tr><th rowspan="2" nowrap>Upload File(s) [<div class="addplus"><a href="#ticketattachmentcontainer" onclick="javascript: AddTicketFile();">Add File</a></div>]</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
			        <div id="ticketattachmentcontainer">
			        </div><br />
			        <div class="subcontent"><input class="rebuttonwide2" value="Send" type="submit" name="TicketReplyForm" /><span style="display: inline-block; vertical-align: middle; padding-left: 10px;"><input type="checkbox" name="close" id="close" value="1"> <label for="close">Close on send</label><br><input type="checkbox" name="addsignature" id="addsignature" value="1"> <label for="addsignature">Add name to signature</label></span></div>
			      </div>
			    </form>
			  </div>
			  <div class="ticketpostsholder">
			    <div class="ticketpostcontainer">
			      <div class="ticketpostbar">
			        <div class="ticketpostbarname">'.$result->sender_name.'</div>
			        <div class="ticketpostbarbadgeblue"><div class="tpbadgetext">'.(($usertype) ? 'Staff' : 'User').'</div></div>
			      </div>
			      <div style="min-height: 300px;" class="ticketpostcontents">
			        <div class="ticketpostcontentsbar"><div class="ticketbarcontents">Posted on: '.$result->created.'</div><span class="ticketbardatefold"></span></div>
			        <div class="ticketpostcontentsdetails">
			          <div class="ticketpostcontentsattachments">';

			# Show attachments on original email
			$database->query('SELECT id, file_name, file_size FROM email_attachments WHERE email_id=:emailid AND reply_id IS NULL ORDER BY id', array(':emailid'=>$email_id));
			$attachments_result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			if($attachments_result)
			{
				foreach($attachments_result as $attachments_row)
				{
					$data.='
			            <div class="ticketpostcontentsattachmentitem" onclick="javascript: PopupSmallWindow(\''.$member->currentPage().'&GetAttachment=TRUE&attachment_id='.$attachments_row['id'].'\');" style="background-image: URL(\'assets/images/icon_file.gif\');">&nbsp;'.$attachments_row['file_name'].' ('.$this->formatSize($attachments_row['file_size']).')</div>';
				}
			}

			$data.='
			          </div>
			          <div class="ticketpostcontentsholder">
			            <div class="ticketpostcontentsdetailscontainer">
			              '.nl2br($EmailClass->makeClickable($result->message)).'
			            </div>
			          </div>
			        </div>
			        <div class="ticketpostcontentsbottom"><span class="ticketpostbottomright">&nbsp;</span><div class="ticketpostbottomcontents">&nbsp;&nbsp;</div></div>
			        <div class="ticketpostclearer"></div>
			      </div>
			      <div class="ticketpostbarbottom"><div class="ticketpostbottomcontents">&nbsp;&nbsp;</div></div>
			      <div class="ticketpostclearer"></div>
			    </div>';

			# Show responses / messages
			$database->query('(SELECT id, staff_id, message, created FROM email_response WHERE email_id=:emailid) UNION ALL (SELECT id, staff_id, message, created FROM email_message WHERE email_id=:emailid) ORDER BY created', array(':emailid'=>$email_id));
			$response_result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			if($response_result)
			{
				foreach($response_result as $response_row)
				{
					$response_created=strtotime($response_row['created']);
					$response_row['created']=date("d F Y g:i A", $response_created);

					if(isset($response_row['staff_id']))
					{
						$response_name=$member->getUsersName($response_row['staff_id']);
						$response_email=$member->getUsersEmail($response_row['staff_id']);
						$response_usertype='Staff';
					}
					else
					{
						$response_name=$result->sender_name;
						$response_usertype="User";
					}
					$data.='
			    <div class="ticketpostcontainer">
			      <div class="ticketpostbar">
			        <div class="ticketpostbarname">'.$response_name.'</div>
			        <div class="ticketpostbarbadgeblue"><div class="tpbadgetext">'.$response_usertype.'</div></div>
			      </div>
			      <div style="min-height: 300px;" class="ticketpostcontents">
			        <div class="ticketpostcontentsbar"><div class="ticketbarcontents">Posted on: '.$response_row['created'].'</div><span class="ticketbardatefold"></span></div>
			        <div class="ticketpostcontentsdetails">
			          <div class="ticketpostcontentsattachments">';

					# Show attachments on responses / message
					$database->query('SELECT id, file_name, file_size FROM email_attachments WHERE email_id=:emailid AND reply_id=:replyid ORDER BY id', array(':emailid'=>$email_id, ':replyid'=>$response_row['id']));
					$attachments_response_result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

					if($attachments_response_result)
					{
						foreach($attachments_response_result as $attachments_response_row)
						{
							$data.='
			            <div class="ticketpostcontentsattachmentitem" onclick="javascript: PopupSmallWindow(\''.$member->currentPage().'&GetAttachment=TRUE&attachment_id='.$attachments_response_row['id'].'\');" style="background-image: URL(\'assets/images/icon_file.gif\');">&nbsp;'.$attachments_response_row['file_name'].' ('.$this->formatSize($attachments_response_row['file_size']).')</div>';
						}
					}

					$data.='
			          </div>
			          <div class="ticketpostcontentsholder">
			            <div class="ticketpostcontentsdetailscontainer">
			             '.nl2br($EmailClass->makeClickable($response_row['message'])).'
			            </div>
			          </div>
			        </div>
			        <div class="ticketpostcontentsbottom"><span class="ticketpostbottomright">&nbsp;</span><div class="ticketpostbottomcontents">&nbsp;&nbsp;</div></div>
			        <div class="ticketpostclearer"></div>
			      </div>
			      <div class="ticketpostbarbottom"><div class="ticketpostbottomcontents">&nbsp;&nbsp;</div></div>
			      <div class="ticketpostclearer"></div>
			    </div>';
				}
			}

			$data.='
			    <div class="ticketpostcontainer">
			      <div style="min-height: 200px;">
			        <div class="ticketpostcontentsattachments">
			        </div>
			        <div class="ticketpostcontentsholder">
			          <div class="ticketpostcontentsdetailscontainer">';

			# Show chat box
			$database->query('SELECT id, staff_id, message, created FROM email_chat WHERE email_id=:emailid ORDER BY created', array(':emailid'=>$email_id));
			$chat_result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			foreach($chat_result as $chat_row)
			{
				# Get date
				$chat_row['created']=date("n/d/Y g:i A", strtotime($chat_row['created']));

				$user_full_name=$member->getUsersName($chat_row['staff_id']);
				$data.=$chat_row['created'].' <b>'.$user_full_name.':</b> '.$chat_row['message'].'<br>';
			}

			$data.='
			          </div>
			        </div>
			        <div class="ticketpostcontentsbottom">
			          <span class="ticketpostbottomright" style="padding-right: 20px;">
			            <form name="chatform" id="chatform" method="post" action="'.$member->currentPage().'">
			              <span class="counter"></span> / 255 characters&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="chat_input" id="chat_input" class="swifttextlarge" maxlength="255">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="chatform" class="rebuttonwide2" value="Enter">
			            </form>
			          </span>
			          <div class="ticketpostbottomcontents">&nbsp;&nbsp;</div>
			        </div>
			        <div class="ticketpostclearer"></div>
			      </div>
			      <div class="ticketpostbarbottom"></div>
			      <div class="ticketpostclearer"></div>
			    </div>
			  </div>
			  <script>
			  $(document).ready(function(){
			    $(\'#chat_input\').each(function(){
			      // get current number of characters
			      var length=$(this).val().length;

			      // get current number of words
			      //var length=$(this).val().split(/\b[\s,\.-:;]*/).length;
			      // update characters
			      $(this).parent().find(\'.counter\').html(length);

			      // bind on key up event
			      $(this).keyup(function(){
			        // get new length of characters
			        var new_length=$(this).val().length;

			        // get new length of words
			        //var new_length=$(this).val().split(/\b[\s,\.-:;]*/).length;
			        // update
			        $(this).parent().find(\'.counter\').html(new_length);
			      });
			    });
			  });
			  </script>
			</div>
				';

			return $data;
		}
		else
		{
			header("Location: index.php");
		}
	}

	/*** End public methods ***/



	/*** private methods ***/

	/**
	 * convertEncode
	 *
	 * Converts encoding to UTF-8
	 *
	 * @param	$string
	 * @param	$encoding
	 * @access	private
	 */
	private function convertEncode($string, $encoding)
	{
		# What type of encoding?
		switch($encoding)
		{
			case "iso-8859-1":
				$string=utf8_encode($string);
				break;
			case "us-ascii":
				$string=iconv($encoding, 'UTF-8', $string);
				break;
			case "windows-1252":
				$string=iconv($encoding, 'UTF-8', $string);
				break;
		}

		return $string;
	}

	/**
	 * rrmdir
	 *
	 * Recursively remove a directory
	 *
	 * @access	private
	 */
	private function rrmdir($dir)
	{
		foreach(glob($dir.DS.'*') as $file)
		{
			if(is_dir($file))
			{
				$this->rrmdir($file);
			}
			else
			{
				unlink($file);
			}
		}
		rmdir($dir);
	}

	/**
	 * comparekTicket
	 *
	 * Compares the users email with the ticket. If they match return TRUE.
	 *
	 * @access	private
	 */
	private function compareTicket($email_id, $ticket_id, $fromEmail)
	{
		global $database;

		$database->query('SELECT id FROM emails WHERE id=:emailid AND ticket_id=:ticketid AND sender_email=:fromEmail', array(':emailid'=>$email_id, ':ticketid'=>$ticket_id, ':fromEmail'=>$fromEmail));

		if($database->count()>=1)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * countClosed
	 *
	 * Counts how many closed emails there are in the `emails` table.
	 *
	 * @access	private
	 */
	private function countClosed()
	{
		global $database;

		$database->query('SELECT id FROM emails WHERE status=3', array());

		return $database->count();
	}

	/**
	 * countEmailMessage
	 *
	 * Counts the rows in the `email_message` table.
	 *
	 * @access	private
	 */
	private function countEmailMessage($id=NULL)
	{
		global $database;

		($id!==NULL ? $where=' WHERE email_id='.$id : '');
		$database->query('SELECT id FROM email_message'.$where, array());

		return $database->count();
	}

	/**
	 * countEmailResponse
	 *
	 * Counts the rows in the `email_response` table.
	 *
	 * @access	private
	 */
	private function countEmailResponse($id=NULL)
	{
		global $database;

		($id!==NULL ? $where=' WHERE email_id='.$id : '');
		$database->query('SELECT id FROM email_response'.$where, array());

		return $database->count();
	}

	/**
	 * countAttachments
	 *
	 * Counts the attachments in the `email_attachments` table.
	 *
	 * @access	private
	 */
	private function countAttachments($email_id)
	{
		global $database;

		$database->query('SELECT id FROM email_attachments WHERE email_id=:emailid', array(':emailid'=>$email_id));

		return $database->count();
	}

	/**
	 * countEmailChat
	 *
	 * Counts the chat messages in the `email_chat` table.
	 *
	 * @access	private
	 */
	private function countEmailChat($email_id)
	{
		global $database;

		$database->query('SELECT id FROM email_chat WHERE email_id=:emailid', array(':emailid'=>$email_id));

		return $database->count();
	}

	/**
	 * getEmailSortOptions
	 *
	 * Retrieves users sorting options from the `users_sorting` table.
	 *
	 * @access	private
	 */
	private function getEmailSortOptions($user_id)
	{
		global $database;

		$database->query('SELECT email_sort_field, email_sort_order FROM users_sorting WHERE user_id=:userid', array(':userid'=>$user_id));
		$results=$database->statement->fetch(PDO::FETCH_OBJ);

		return $results;
	}

	/**
	 * stripSignature
	 *
	 * Strips the signature from emails.
	 *
	 * @access	private
	 */
	private function stripSignature($strEmailContent)
	{
		$arrParts=preg_split('/((?:\r|\n||\n\r)--\s*(?:\r|\n||\n\r))/', $strEmailContent, -1, PREG_SPLIT_DELIM_CAPTURE);
		array_pop($arrParts);
		array_pop($arrParts);
		$strEmailContent=implode('', $arrParts);

		return $strEmailContent;
	}

	/**
	 * formatSize
	 *
	 * Returns automated unit size of the file.
	 *
	 * @access	private
	 */
	private function formatSize($file)
	{
		$sizes=array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		if($file==0)
		{
			return('n/a');
		}
		else
		{
			return (round($file/pow(1024, ($i=floor(log($file, 1024)))), 2).$sizes[$i]);
		}
	}

	/**
	 * copy_r
	 *
	 * Copies files from source directory to destination
	 *
	 * @param	$path
	 * @param	$dest
	 * @access	private
	 */
	private function copy_r($path, $dest)
	{
		if(is_dir($path))
		{
			if(!is_dir($dest))
			{
				mkdir($dest);
			}

			$objects=scandir($path);
			if(sizeof($objects) > 0)
			{
				foreach($objects as $file)
				{
					if($file=="." || $file=="..")
						continue;

					# Go on
					if(is_dir($path.DS.$file))
					{
						$this->copy_r($path.DS.$file, $dest.DS.$file);
					}
					else
					{
						copy($path.DS.$file, $dest.DS.$file);
					}
				}
			}
			return TRUE;
		}
		elseif(is_file($path))
		{
			return copy($path, $dest);
		}
		else
		{
			return FALSE;
		}
	}

	/*** End private methods ***/
}
?>