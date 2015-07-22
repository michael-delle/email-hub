<?php
/**
 * Name:	Search
 * Author:	Draven
 *
 * The Search is used to search tables for data.
 *
 */
class Search
{
	/*** public methods ***/

	/**
	 * processSearch
	 *
	 * Checks if the search form has been submitted and processes it, returning the results of the search.
	 *
	 * @access	public
	 */
	public function processSearch()
	{
		global $database;

		# Check if the search form has been submitted.
		if(isset($_POST['searchquery']))
		{
			$keyword=$_POST['searchquery'];

			$database->query("(SELECT id, ticket_id, sender_name, sender_email, subject, message, 'emails_table' AS tableName FROM emails WHERE ticket_id LIKE '%".$keyword."%' OR sender_name LIKE '%".$keyword."%' OR sender_email LIKE '%".$keyword."%' OR subject LIKE '%".$keyword."%' OR message LIKE '%".$keyword."%')
				UNION
				(SELECT id, ticket_id, NULL, NULL, NULL, message, 'email_message_table' AS tableName FROM email_message WHERE ticket_id LIKE '%".$keyword."%' OR message LIKE '%".$keyword."%')
				UNION
				(SELECT id, ticket_id, NULL, NULL, NULL, message, 'email_response_table' AS tableName FROM email_response WHERE ticket_id LIKE '%".$keyword."%' OR message LIKE '%".$keyword."%')
				UNION
				(SELECT id, location, full_name, publication, publication_url, NULL, 'press_apps_table' AS tableName FROM press_apps WHERE location LIKE '%".$keyword."%' OR full_name LIKE '%".$keyword."%' OR publication LIKE '%".$keyword."%' OR publication_url LIKE '%".$keyword."%')
				UNION
				(SELECT id, location, full_name, NULL, NULL, NULL, 'volunteer_apps_table' AS tableName FROM volunteer_apps WHERE location LIKE '%".$keyword."%' OR full_name LIKE '%".$keyword."%')", array());

			$results=$database->statement->fetchAll(PDO::FETCH_ASSOC);

			$data='
	  <div class="boxcontainer">
	    <div class="boxcontainerlabel">Search Results</div>
	    <div class="boxcontainercontent">';

			if(!empty($results))
			{
				foreach($results as $row)
				{
					if($row['tableName']=="emails_table" || $row['tableName']=="email_message_table" || $row['tableName']=="email_response_table")
					{
						$data .= '
	      <div class="ticketsearchcontainer">
		<div class="ticketsearch"><a href="admin.php?action=view-email&id='.$row['id'].'">'.$row['ticket_id'].': '.$row['subject'].'</a></div>
		<div class="ticketsearchtext"> - '.$row['message'].'</div>
	      </div>';
					}
					if($row['tableName'] == "press_apps_table")
					{
						$row['location']=$row['ticket_id'];
						$row['full_name']=$row['sender_name'];
						$row['publication']=$row['sender_email'];
						$row['publication_url']=$row['subject'];

						$data .= '
	      <div class="kbsearchcontainer">
	        <div class="kbsearch"><a href="member.php?action=view-press-apps&id='.$row['id'].'">'.$row['publication'].'</a></div>
	        <div class="kbsearchtext">'.$row['location'].' '.$row['full_name'].' '.$row['publication'].' '.$row['publication_url'].'</div>
	      </div>';
					}
					if($row['tableName'] == "volunteer_apps_table")
					{
						$row['location']=$row['ticket_id'];
						$row['full_name']=$row['sender_name'];

						$data .= '
	      <div class="kbsearchcontainer">
	        <div class="kbsearch"><a href="member.php?action=view-press-apps&id='.$row['id'].'">'.$row['full_name'].'</a></div>
	        <div class="kbsearchtext">'.$row['location'].' '.$row['full_name'].'</div>
	      </div>';
					}
				}
			}
			else
			{
				$data .= '
	      <div class="infotextcontainer">
		No Results Found
	      </div>';
			}

			$data .= '
	    </div>
	  </div>';

			return $data;
		}
	}

	/*** End public methods ***/
}
?>