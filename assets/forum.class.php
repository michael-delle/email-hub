<?php

# TODO: Check for unique category in addCategory() method.
# TODO: Create delete method and create backup tables.

/**
 * Name:	Forum
 * Author:	Draven
 *
 * The Forum class is used to access the forum.
 */

class Forum
{
	/*** public methods ***/

	/**
	 * viewForum
	 *
	 * Retreives the forum categories from the `forum_cat` table and displays them.
	 *
	 * @access	public
	 */
	public function viewForum()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

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

		$forum_cat_results=$this->getAllCategories();

		if(!empty($forum_cat_results))
		{
			# Prepare the table
			$data.='
          <div class="boxcontainer">
            <div class="boxcontainerlabel">
              Forum
            </div>
            <div class="boxcontainercontent">
              <table border="1" id="forum">
                <tr>
		  <th>Category</th>
		  <th width="5%">Topics</th>
		  <th width="5%">Posts</th>
                  <th width="20%">Last Post</th>
                </tr>';

			foreach($forum_cat_results as $forum_cat_row)
			{
				# Check if the topics have been read by the user
				$count_is_read=0;
				$topics_results=$this->getAllTopics($forum_cat_row['cat_id']);
				foreach($topics_results as $topic_row)
				{
					$is_read=$this->checkIsRead($user_id, $topic_row['topic_id']);
					if($is_read==0)
					{
						$count_is_read=$count_is_read+1;
					}
				}

				# Count topic
				$count_topics=$this->countTopics($forum_cat_row['cat_id']);

				# Count posts
				$count_posts=$this->countPosts($forum_cat_row['cat_id']);

				$data.='
                <tr>
                  <td>
                    <h3><a href="member.php?action=forum_topics&cat='.$forum_cat_row['cat_id'].'">'.$forum_cat_row['cat_name'].'</a>'.($count_is_read >= 1 && $count_topics > 0 ? ' <small style="font-size: 8px; color: red; vertical-align: top">*NEW*</small>' : NULL).'</h3>'.$forum_cat_row['cat_desc'].'
		  </td>
                  <td align="center">'.$count_topics.'</td>
		  <td align="center">'.$count_posts.'</td>
                  <td>';

				# Fetch last topic for each category
				$forum_topic_result=$this->getNewestPost($forum_cat_row['cat_id']);
				if($database->count()<=0)
				{
					$data.='No topics';
				}
				else
				{
					$data.='
                    by '.$member->getUsersName($forum_topic_result->post_by).'<br>
                    '.date('D M d, Y g:i a', strtotime($forum_topic_result->post_date));
				}

				$data.='
                   </td>
                 </tr>';
			}
		}
		else
		{
			$data.='There are no categories';
		}

		$data.='
               </table>
             </div>
           </div>';

		return $data;
	}

	/**
	 * viewTopics
	 *
	 * Retreives the topics of the chosen category
	 *
	 * @access  public
	 */
	public function viewTopics()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		$topic_subject=NULL;
		$topic_content=NULL;

		if(isset($_POST['AddTopicForm']))
		{
			$topic_cat=$_GET['cat'];
			if(isset($topic_cat) && $this->getCategory($topic_cat) > 0)
			{
				$topic_subject=ucfirst(strip_tags($_POST['topic_subject']));
				$topic_by=$member->getUsersID();
				$topic_content=strip_tags($_POST['topic_content']);

				# Check topic subject length
				$topic_subject_length=strlen($topic_subject);
				if($topic_subject_length < 8)
				{
					$error[]="Please enter a topic subject with 8 or more characters";
					$topic_subject=$topic_subject;
				}

				$topic_content_length=strlen($topic_content);
				if($topic_content_length <= 0)
				{
					$topic_content="(No Body)";
				}
			}
			else
			{
				$error[]="Error while selecting from database. Please try again later";
			}

			if(!isset($error))
			{
				# Insert topic
				$database->query('INSERT INTO forum_topics (topic_subject, topic_date, topic_updated, topic_cat, topic_by) VALUES (?, NOW(), NOW(), ?, ?)', array($topic_subject, $topic_cat, $topic_by));

				# Get last insert ID
				$topic_id=$database->lastInsertId();

				if(isset($topic_id))
				{
					# Insert comment into forum_posts
					$database->query('INSERT INTO forum_posts (post_content, post_date, post_topic, post_by) VALUES (?, NOW(), ?, ?)', array($topic_content, $topic_id, $topic_by));
					$success[]='You have succesfully created <a href="member.php?action=forum_posts&topic='.$topic_id.'">your new topic</a>';

					# Automatically mark this topic as read for the user
					$database->query('INSERT INTO forum_is_read SET user_id=:userid, topic_id=:topicid, read_date=NOW()', array(':userid' => $user_id, ':topicid' => $topic_id));

					# Automatically Subscribe user to topic
					$forum_notify=$this->getForumNotify($user_id);
					if($forum_notify=="Automatic")
					{
						$database->query('INSERT INTO forum_subscribers (user_id, topic_id) VALUES (?, ?)', array($user_id, $topic_id));
					}

					# Reset topic subject and content
					$topic_subject=NULL;
					$topic_content=NULL;
				}
				else
				{
					$error[]="An error occured while inserting your data. Please try again later";
				}
			}
		}
		elseif(!empty($_POST['move-cat_id']))
		{
			if(isset($_POST['action']))
			{
				foreach($_POST['action'] as $id => $value)
				{
					$database->query('UPDATE forum_topics SET topic_cat=:topiccat WHERE topic_id=:topicid', array(':topiccat' => $_POST['move-cat_id'], ':topicid' => $id));
				}
				$success[]="Topic(s) was moved";
			}
			else
			{
				$error[]="Select a topic to move";
			}
		}
		elseif(!empty($_POST['delete-email']))
		{
			if(isset($_POST['action']))
			{
				foreach($_POST['action'] as $id => $value)
				{
					# Delete topic
					$database->query('DELETE FROM forum_topics WHERE topic_id=:topicid', array(':topicid' => $id));
				}
			}
			else
			{
				$error[]="Select a topic to delete";
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
          '.($is_admin ? '<form name="delete-topic" action="'.$member->currentPage().'" method="post">' : NULL).'
          <div class="boxcontainer">
            <div class="boxcontainerlabel">';

		if($is_admin)
		{
			$data.='
	      <div style="float: right">
	        <input name="delete-email" class="headerbutton" type="submit" value="Delete">
                <select class="folderselect" name="move-cat_id">
                  <option value=""'.(isset($cat) && $cat=="" ? ' selected' : '').'>Move to...</option>';

			$cat_results=$this->getAllCategories();
			foreach($cat_results as $cat_row)
			{
				$data.='
		  <option value="'.$cat_row['cat_id'].'">'.$cat_row['cat_name'].'</option>';
			}

			$data.='
		</select>
              </div>';
		}

		$data.='
              '.$this->breadcrumbs($_GET['cat']).'
            </div>
            <div class="boxcontainercontent">';

		# Get topics
		$forum_topic_results=$this->getAllTopics($_GET['cat']);
		if(!empty($forum_topic_results))
		{
			$forum_cat_result=$this->getCategory($_GET['cat']);

			# Prepare the table
			$data.='
              <table border="1" id="forum">
	        <tr>
                  <th width="20"></th>
		  <th>Topics</th>
                  <th width="5%">Posts</th>
                  <th width="20%">Last Post</th>
                </tr>';

			foreach($forum_topic_results as $forum_topic_row)
			{
				# Check if topic has been read by the user
				$is_read=$this->checkIsRead($user_id, $forum_topic_row['topic_id']);

				# Get newest post information
				$newest_post=$this->getNewestPost(NULL, $forum_topic_row['topic_id']);

				# Count posts
				$count_posts=$this->countPosts(NULL, $forum_topic_row['topic_id']);

				$data.='
                <tr>
                  <td class="ticketlistpropertiescontainer" align="center" valign="middle"><input type="checkbox" name="action['.$forum_topic_row['topic_id'].']" id="checkbox['.$forum_topic_row['topic_id'].']" value="'.$forum_topic_row['topic_id'].'"></td>
                  <td class="leftpart">
		    <h3><a href="member.php?action=forum_posts&topic='.$forum_topic_row['topic_id'].'">'.$forum_topic_row['topic_subject'].'</a>'.(!$is_read ? ' <small style="font-size: 8px; color: red; vertical-align: top">*NEW*</small>' : NULL).'</h3>
                    by '.$member->getUsersName($forum_topic_row['topic_by']).' on '.date('D M d, Y g:i a', strtotime($forum_topic_row['topic_date'])).'
		  </td>
                  <td align="center">'.$count_posts.'</td>
		  <td class="rightpart">
		    by '.$member->getUsersName($newest_post->post_by).'<br>
		    '.date('D M d, Y g:i a', strtotime($newest_post->post_date)).'
		  </td>
                </tr>';
			}
			$data.='
              </table>';
		}
		else
		{
			$data.='There are no topics<br><br>';
		}

		$data.='<br>
	      <form name="AddTopicForm" method="post" action="'.$member->currentPage().'">
	        <div class="panel" id="postingbox">
		  <fieldset class="fields1">
                    <dl style="clear: left;">
                      <dt><label for="topic_subject">Subject:</label></dt>
                      <dd><input id="topic_subject" name="topic_subject" type="text" size="20" maxlength="30" tabindex="2" class="swifttextlarge" value="'.$topic_subject.'"></dd>
                    </dl>

		    <div id="format-button">';

		$js=NULL;

		$bbcode_result=$this->getAllBBCode();
		if(!empty($bbcode_result))
		{
			$button_name=NULL;

			foreach($bbcode_result as $bbcode)
			{
				$button_name=strtolower(str_replace(' ', '_', $bbcode['bbcode_name']));

				$js.='
$("#'.$button_name.'").click(function () {
       $(\'#topic_content\').val($(\'#topic_content\').val()+\''.$bbcode['bbcode'].'\');
       $(\'#topic_content\').focus();
});';
				$data.='
                      <input type="button" class="button2" id="'.$button_name.'" name="'.$button_name.'" value="'.$bbcode['bbcode_name'].'" style="font-weight:bold; width: 30px" title="'.$bbcode['bbcode_example'].'">';
			}
		}

		$data.='
                    </div>
		    <div id="smiley-box">
		      <strong>Smilies</strong><br>';

		$smilies_result=$this->getSmilies();
		if(!empty($smilies_result))
		{
			$button_name=NULL;

			foreach($smilies_result as $smiley)
			{
				$button_name=strtolower(str_replace(' ', '_', $smiley['smiley_name']));

				$js.='
$("#'.$button_name.'").click(function () {
       $(\'#topic_content\').val($(\'#topic_content\').val()+\''.$smiley['smiley_code'].'\');
       $(\'#topic_content\').focus();
});';

				$data.='
                      <a href="javascript:;" id="'.$button_name.'"><img src="assets/images/smilies/'.$smiley['smiley_img'].'" height="15" alt="'.$smiley['smiley_code'].'" title="'.$smiley['smiley_name'].'"></a>';
			}
		}

		$data.='
                    </div>
		    <div id="message-box">
		      <textarea name="topic_content" id="topic_content" cols="25" rows="5" tabindex="3" class="swifttextarea">'.$topic_content.'</textarea>
		    </div>
		  </fieldset>
		  <input class="rebuttonwide2" value="Post" type="submit" name="AddTopicForm">
		</div>
                <script type="text/javascript">
                //<![CDATA[
                    $(document).ready(function(){
'.$js.'
                    });
                //]]>
		</script>
              </form>
            </div>
            </div>
	    '.($is_admin ? '</form>' : NULL);

		return $data;
	}

	/**
	 * viewPosts
	 *
	 * Retreives the topics of the chosen category
	 *
	 * @access	public
	 */
	public function viewPosts()
	{
		global $database;
		global $member;
		global $EmailClass;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		# Check if topic is read
		$is_read=$this->checkIsRead($user_id, $_GET['topic']);
		if(!$is_read)
		{
			$database->query('INSERT INTO forum_is_read SET user_id=:userid, topic_id=:topicid, read_date=NOW()', array(':userid' => $user_id, ':topicid' => $_GET['topic']));
		}

		# Get topic information
		$forum_topic_result=$this->getTopic($_GET['topic']);

		$post_id=NULL;
		$post_content=NULL;

		if(isset($_GET['post_id']))
		{
			$post_result=$this->getPost($_GET['post_id']);
			if($post_result->post_by!=$user_id && $is_admin!="Webmaster")
			{
				$error[]="You can not edit this post";
			}

			if(!$error)
			{
				$post_id=$post_result->post_id;
				$post_content=$post_result->post_content;
			}
		}
		if(isset($_POST['AddReplyForm']))
		{
			$topic_id=$_GET['topic'];
			if(!isset($topic_id) && $this->getTopic($topic_id) <= 0)
			{
				$error[]="Error while selecting from database. Please try again later";
			}
			else
			{
				$post_by=$member->getUsersID();
				$post_content=ucfirst(strip_tags($_POST['post_content']));

				# Check if the user inserted a post and check length
				$post_content_length=strlen($post_content);
				if($post_content_length < 4)
				{
					$error[]="Please enter a post with 4 or more characters";
					$post_content=$post_content;
				}
			}

			if(!isset($error))
			{
				# Insert post into `forum_posts`
				if(!isset($post_id))
				{
					$database->query('INSERT INTO forum_posts (post_content, post_date, post_topic, post_by) VALUES (?, NOW(), ?, ?)', array($post_content, $topic_id, $post_by));

					# Automatically Subscribe user to topic
					$forum_notify=$this->getForumNotify($user_id);
					if($forum_notify=="Automatic")
					{
						$database->query('INSERT INTO forum_subscribers (user_id, topic_id) VALUES (?, ?)', array($user_id, $topic_id));
					}

					$success[]='Post has been added';
				}
				elseif(isset($post_id))
				{
					$database->query('UPDATE forum_posts SET post_content=:postcontent, post_edited=2, post_edit_date=NOW(), post_edit_by=:posteditby WHERE post_id=:postid', array(':postid' => $post_id, ':postcontent' => $post_content, ':posteditby' => $user_id));

					header("Location: member.php?action=forum_posts&topic=".$topic_id);
				}

				# Make topic unread by everyone
				$database->query('DELETE FROM forum_is_read WHERE user_id!=:userid AND topic_id=:topicid', array(':userid' => $user_id, ':topicid' => $topic_id));

				# Update `forum_topics`
				$database->query('UPDATE forum_topics SET topic_updated=NOW() WHERE topic_id=:topicid', array(':topicid' => $topic_id));

				# Get subscribers to this topic
				$get_subscribers=$this->getSubscribers($user_id, $topic_id);
				$count_subscribed=$database->count();

				# If there are subscribers
				if($count_subscribed > 0)
				{
					# Loop through subscribers
					foreach($get_subscribers as $subscriber)
					{
						# Send subscribers an email notification
						$notify_subject=$EmailClass->site_name." - Topic Reply Notification";
						$notify_body_content=$subscriber->full_name.",\r\n\r\nYou are receiving this notification because you are subscribed to the topic \"".$forum_topic_result->topic_subject."\". This topic has recieved a reply since you last viewed it.\r\n\r\nIf you want to view the topic, click the following link:\r\n\r\n".$member->currentPage();
						$EmailClass->sendEmail($subscriber->email, $notify_subject, $notify_body_content, $subscriber->full_name);
					}
				}

				# Reset post_content
				$post_content=NULL;
			}
		}
		elseif(isset($_POST['DeletePost']))
		{
			if(!isset($_POST['post_id']))
			{
				$error[]="Please choose a post to delete";
			}

			if(!$error)
			{
				$database->query('DELETE FROM forum_posts WHERE post_id=:postid', array(':postid' => $_POST['post_id']));
				$get_posts=$this->getAllPosts($_GET['topic']);
				if($database->count() <= 0)
				{
					$topic_result=$this->getTopic($_GET['topic']);

					$database->query('DELETE FROM forum_topics WHERE topic_id=:topicid', array(':topicid' => $_GET['topic']));
					header('Location: member.php?action=forum_topics&cat='.$topic_result->topic_cat);
				}
			}

		}
		elseif(!empty($_POST['sub_topic']))
		{
			if($_POST['sub_topic']!="Off" && $_POST['sub_topic']!="On")
			{
				$error[]="There was an error subscribing to this topic";
			}

			if(!$error)
			{
				if($_POST['sub_topic']=="Off")
				{
					$database->query('DELETE FROM forum_subscribers WHERE user_id=:userid AND topic_id=:topicid', array(':userid' => $user_id, ':topicid' => $_GET['topic']));
					$success[]="You have unsubscribed from this topic";
				}
				elseif($_POST['sub_topic']=="On")
				{
					$database->query('INSERT INTO forum_subscribers (user_id, topic_id) VALUES (?, ?)', array($user_id, $_GET['topic']));
					$success[]="You have subscribed to this topic";
				}
			}
		}

		# Check if user is subscribed to topic
		$check_subscribed=$this->checkSubscribed($user_id, $_GET['topic']);

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
          <form name="subscribe-topic" action="'.$member->currentPage().'" method="post">
          <div class="boxcontainer">
            <div class="boxcontainerlabel">
              <div style="float: right">
                <select class="folderselect" name="sub_topic">
                  <option value="Off"'.($check_subscribed <= 0 ? ' selected' : '').'>Do Not Subscribe</option>
                  <option value="On"'.($check_subscribed > 0 ? ' selected' : '').'>Subscribe to Topic</option>
                </select>
              </div>
	    '.$this->breadcrumbs(NULL, $_GET['topic']).'
            </div>
            <div class="boxcontainercontent">
              <table class="topic" border="1" id="forum">';

		if(!empty($forum_topic_result))
		{
			$data.='
	        <tr>
		  <th colspan="2">'.$forum_topic_result->topic_subject.'</th>
		</tr>';

			# Get posts
			$forum_posts_result=$this->getAllPosts($_GET['topic']);
			foreach($forum_posts_result as $forum_post_row)
			{
				$post_edited_by=$member->getUsersName($forum_post_row['post_edit_by']);

				$data.='
                <tr class="topic-post">
		  <td class="user-post" valign="top">
		    '.$forum_post_row['full_name'].'<br>
		    '.date('D M d, Y g:i a', strtotime($forum_post_row['post_date'])).'
		  </td>
		  <td>
		    <div class="post-icons">
		      '.($forum_post_row['post_by']==$user_id || $is_admin=="Webmaster" ? '<a href="member.php?action=forum_edit&topic='.$_GET['topic'].'&post_id='.$forum_post_row['post_id'].'#postingbox" title="Edit"><img src="assets/images/icon_edit.gif" alt="Edit"></a>' : NULL).'
		      <!--'.($forum_post_row['post_by']==$user_id || $is_admin ? '<a href="member.php?action=forum_posts&post_id='.$forum_post_row['post_id'].'" title="Delete"><img src="assets/images/icon_trash.gif" alt="Delete"></a>' : NULL).'-->';
				if($forum_post_row['post_by']==$user_id || $is_admin)
				{
					$data.='
		      <form style="display: inline" name="DeletePost" id="DeletePost" method="post" action="'.$member->currentPage().'">
                        <input type="hidden" name="post_id" value="'.$forum_post_row['post_id'].'">
                        <input name="DeletePost" class="trashbutton" type="submit" value="">
                      </form>';
				}

				$data.='
		    </div>
		    <div class="post-content">'.nl2br($this->convertBBCode($forum_post_row['post_content'])).'</div>
		    '.($forum_post_row['post_edited']=="Yes" ? '<div class="post-edit"><small>Last edited by '.$post_edited_by.' on '.date('D M d, Y g:i a', strtotime($forum_post_row['post_edit_date'])).'</small></div>' : NULL).'
		  </td>
		</tr>';
			}
		}

		$data.='
              </table><br>
              <form name="AddReplyForm" method="post" action="'.$member->currentPage().'">
                <div class="panel" id="postingbox">
                  <fieldset class="fields1">
                    <div id="format-buttons">';

		$js=NULL;

		$bbcode_result=$this->getAllBBCode();
		if(!empty($bbcode_result))
		{
			$button_name=NULL;

			foreach($bbcode_result as $bbcode)
			{
				$button_name=strtolower(str_replace(' ', '_', $bbcode['bbcode_name']));

				$js.='
$("#'.$button_name.'").click(function () {
       $(\'#post_content\').val($(\'#post_content\').val()+\''.$bbcode['bbcode'].'\');
       $(\'#post_content\').focus();
});';
				$data.='
                      <input type="button" class="button2" id="'.$button_name.'" name="'.$button_name.'" value="'.$bbcode['bbcode_name'].'" style="font-weight:bold; width: 30px" title="'.$bbcode['bbcode_example'].'">';
			}
		}

		$data.='
<!--
                    <input type="button" class="button2" accesskey="q" name="addbbcode6" value="Quote" style="width: 50px" onclick="bbstyle(6)" title="Quote text: [quote]text[/quote]" />
                    <input type="button" class="button2" accesskey="c" name="addbbcode8" value="Code" style="width: 40px" onclick="bbstyle(8)" title="Code display: [code]code[/code]" />
                    <input type="button" class="button2" accesskey="l" name="addbbcode10" value="List" style="width: 40px" onclick="bbstyle(10)" title="List: [list]text[/list]" />
                    <input type="button" class="button2" accesskey="o" name="addbbcode12" value="List=" style="width: 40px" onclick="bbstyle(12)" title="Ordered list: [list=]text[/list]" />
                    <input type="button" class="button2" accesskey="y" name="addlistitem" value="[*]" style="width: 40px" onclick="bbstyle(-1)" title="List item: [*]text[/*]" />
                    <select name="addbbcode20" onchange="bbfontstyle(\'[size=\' + this.form.addbbcode20.options[this.form.addbbcode20.selectedIndex].value + \']\', \'[/size]\');this.form.addbbcode20.selectedIndex=2;" title="Font size: [size=85]small text[/size]">
                      <option value="50">Tiny</option>
                      <option value="85">Small</option>
                      <option value="100" selected="selected">Normal</option>
                      <option value="150">Large</option>
                      <option value="200">Huge</option>
                    </select>
-->
                    </div>
                    <div id="smiley-box">
                      <strong>Smilies</strong><br>';

		$smilies_result=$this->getSmilies();
		if(!empty($smilies_result))
		{
			$button_name=NULL;

			foreach($smilies_result as $smiley)
			{
				$button_name=strtolower(str_replace(' ', '_', $smiley['smiley_name']));

				$js.='
$("#'.$button_name.'").click(function () {
       $(\'#post_content\').val($(\'#post_content\').val()+\''.$smiley['smiley_code'].'\');
       $(\'#post_content\').focus();
});';

				$data.='
                      <a href="javascript:;" id="'.$button_name.'"><img src="assets/images/smilies/'.$smiley['smiley_img'].'" height="15" alt="'.$smiley['smiley_code'].'" title="'.$smiley['smiley_name'].'"></a>';
			}
		}

		$data.='
                    </div>
                    <div id="message-box">
                      <textarea name="post_content" id="post_content" cols="25" rows="5" class="swifttextarea">'.$post_content.'</textarea>
                    </div>
		  </fieldset>
		  <input class="rebuttonwide2" value="Post" type="submit" name="AddReplyForm">
		</div>
                <script type="text/javascript">
                //<![CDATA[
                    $(document).ready(function(){
'.$js.'
                    });
                //]]>
                </script>
              </form>
            </div>
          </div>
          </form>';

		return $data;
	}

	/**
	 * viewForumSettings
	 *
	 * Displays the websites forum settings
	 *
	 * @access	public
	 */
	public function viewForumSettings()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			if(isset($_POST['UpdateCatOrder']))
			{
				foreach($_POST['cat_order'] as $key => $value)
				{
					$database->query('UPDATE forum_cat SET cat_order=:catorder WHERE cat_id=:catid', array(':catorder' => $value, ':catid' => $key));
				}
				$success[]="Categories have been updated";
			}

			if(isset($_POST['DeleteCat']))
			{
				if(!isset($_POST['cat_id']))
				{
					$error[]="Please choose a category to delete";
				}

				if(!$error)
				{
					$database->query('DELETE FROM forum_cat WHERE cat_id=:catid', array(':catid' => $_POST['cat_id']));
				}
			}
			elseif(isset($_POST['DeleteBBCode']))
			{
				if(!isset($_POST['bbcode_id']))
				{
					$error[]="Please choose a BBCode to delete";
				}

				if(!$error)
				{
					$database->query('DELETE FROM forum_bbcode WHERE bbcode_id=:bbcodeid', array(':bbcodeid' => $_POST['bbcode_id']));
				}
			}
			elseif(isset($_POST['DeleteSmiley']))
			{
				if(!isset($_POST['smiley_id']))
				{
					$error[]="Please choose a smiley to delete";
				}

				if(!$error)
				{
					$database->query('DELETE FROM forum_smilies WHERE smiley_id=:smileyid', array(':smileyid' => $_POST['smiley_id']));
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
          <form name="UpdateCatOrder" id="UpdateCatOrder" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
              <div class="boxcontainerlabel">
                <div style="float: right">
                  <input name="UpdateCatOrder" class="headerbutton" type="submit" value="Update"><div class="headerbuttongreen" onclick="javascript:window.location.href=\'admin.php?action=forum_cat_add\';">Add Category</div><div class="headerbuttongreen" onclick="javascript:window.location.href=\'admin.php?action=forum_bbcode_add\';">Add BBCode</div><div class="headerbuttongreen" onclick="javascript:window.location.href=\'admin.php?action=forum_smiley_add\';">Add Smiley</div>
                </div>
                Forum Settings
              </div>
              <div class="boxcontainercontent">
                <table class="hlineheader"><tr><th rowspan="2" nowrap>Categories</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table><br>
                <table width="100%" border="1" cellspacing="1" cellpadding="4">
                  <tr>
                    <th width="20px">Edit</th>
                    <th width="20px">Delete</th>
                    <th width="20px">Order</th>
                    <th width="255px">Category</th>
                    <th>Description</th>
                  </tr>';

			$get_categories=$this->getAllCategories();
			foreach($get_categories as $category)
			{
				$data.='
                  <tr>
                    <td align="center"><a style="display: block; background-image: url(assets/images/icon_edit.gif); width: 14px; height: 14px;" href="admin.php?action=forum_cat_edit&cat_id='.$category['cat_id'].'"></a></td>
		    <td align="center">
                      <form name="DeleteCat" id="DeleteCat" method="post" action="'.$member->currentPage().'">
                        <input type="hidden" name="cat_id" value="'.$category['cat_id'].'">
                        <input name="DeleteCat" class="trashbutton" type="submit" value="">
                      </form>
                    </td>
                    <td><input type="text" name="cat_order['.$category['cat_id'].']" id="cat_order" size="1" maxlength="3" value="'.$category['cat_order'].'" style="text-align:center"></td>
                    <td>'.$category['cat_name'].'</td>
                    <td>'.$category['cat_desc'].'</td>
                  </tr>';
			}

			$data.='
                </table><br>
                <table class="hlineheader"><tr><th rowspan="2" nowrap>BBCode</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table><br>
                <table width="100%" border="1" cellspacing="1" cellpadding="4">
	          <tr>
                    <th width="20px">Edit</th>
                    <th width="20px">Delete</th>
		    <th width="160px">BBCode Name</th>
		    <th width="160px">BBCode</th>
                    <th>Example</th>
                  </tr>';

			$get_bbcode=$this->getAllBBCode();
			foreach($get_bbcode as $bbcode)
			{
				$data.='
                  <tr>
                    <td align="center"><a style="display: block; background-image: url(assets/images/icon_edit.gif); width: 14px; height: 14px;" href="admin.php?action=forum_bbcode_edit&bbcode_id='.$bbcode['bbcode_id'].'"></a></td>
		    <td align="center">
                      <form name="DeleteBBCode" id="DeleteBBCode" method="post" action="'.$member->currentPage().'">
                        <input type="hidden" name="bbcode_id" value="'.$bbcode['bbcode_id'].'">
                        <input name="DeleteBBCode" class="trashbutton" type="submit" value="">
                      </form>
                    </td>
                    <td>'.$bbcode['bbcode_name'].'</td>
                    <td>'.$bbcode['bbcode'].'</td>
                    <td>'.$bbcode['bbcode_example'].'</td>
                  </tr>';
			}

			$data.='
                </table><br>
                <table class="hlineheader"><tr><th rowspan="2" nowrap>Smilies</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table><br>
                <table width="100%" border="1" cellspacing="1" cellpadding="4">
	          <tr>
                    <th width="20px">Edit</th>
                    <th width="20px">Delete</th>
		    <th width="160px">Smiley Name</th>
		    <th width="160px">Smiley Code</th>
                    <th>Smiley Image</th>
                  </tr>';

			$get_smilies=$this->getSmilies();
			foreach($get_smilies as $smiley)
			{
				$data.='
                  <tr>
                    <td align="center"><a style="display: block; background-image: url(assets/images/icon_edit.gif); width: 14px; height: 14px;" href="admin.php?action=forum_smiley_edit&smiley_id='.$smiley['smiley_id'].'"></a></td>
		    <td align="center">
                      <form name="DeleteSmiley" id="DeleteSmiley" method="post" action="'.$member->currentPage().'">
                        <input type="hidden" name="smiley_id" value="'.$smiley['smiley_id'].'">
                        <input name="DeleteSmiley" class="trashbutton" type="submit" value="">
                      </form>
                    </td>
                    <td>'.$smiley['smiley_name'].'</td>
                    <td>'.$smiley['smiley_code'].'</td>
                    <td>'.$smiley['smiley_img'].'</td>
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
	 * addCategory
	 *
	 * Adds or Edits a forum category.
	 *
	 * @access	public
	 */
	public function addCategory()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			$cat_id=NULL;
			$cat_name=NULL;
			$cat_desc=NULL;

			if(isset($_GET['cat_id']))
			{
				$cat_result=$this->getCategory($_GET['cat_id']);
				$cat_id=$cat_result->cat_id;
				$cat_name=$cat_result->cat_name;
				$cat_desc=$cat_result->cat_desc;
			}
			if(isset($_POST['AddCategoryForm']))
			{
				$cat_name=ucwords($_POST['cat_name']);
				$cat_desc=$_POST['cat_desc'];

				# Check the Category Name length
				$cat_name_length=strlen($cat_name);
				if($cat_name_length >= 8)
				{
					# Is the category name alphabetic?
					if(strcspn($cat_name, '0123456789')!=strlen($cat_name))
					{
						$error[]="Please enter a valid alphabetic category name";
					}
				}
				else
				{
					$error[]="Please enter a category name with 8 or more characters";
					$cat_name=$cat_name;
				}

				if(!isset($error))
				{
					# Insert category
					if(!isset($cat_id))
					{
						$database->query('INSERT INTO forum_cat (cat_name, cat_desc) VALUES (?, ?)', array($cat_name, $cat_desc));
						$success[]=$cat_name.' has been added';
					}
					elseif(isset($cat_id))
					{
						$database->query('UPDATE forum_cat SET cat_name=:catname, cat_desc=:catdesc WHERE cat_id=:catid', array(':catid' => $cat_id, ':catname' => $cat_name, ':catdesc' => $cat_desc));
						$success[]=$cat_name.' has been updated';
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
          <form name="AddCategoryForm" method="post" action="'.$member->currentPage().'">
	    <div class="boxcontainer">
              <div class="boxcontainerlabel">Add Category</div>
	      <div class="boxcontainercontent">
	        <table class="hlineheader"><tr><th rowspan="2" nowrap>Category Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
                <table width="100%" border="0" cellspacing="1" cellpadding="4">
                  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="cat_name">Category Name:</label></td>
		    <td><input name="cat_name" type="text" size="20" maxlength="30" class="swifttextlarge" value="'.$cat_name.'"></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="cat_desc">Category Description:</label></td>
		    <td><textarea name="cat_desc" cols="25" rows="6" id="cat_desc" class="swifttextarea">'.$cat_desc.'</textarea></td>
		  </tr>
		</table><br />
                <div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="AddCategoryForm"></div>
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
	 * addBBCode
	 *
	 * Adds or Edits a forum BBCode.
	 *
	 * @access	public
	 */
	public function addBBCode()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			$bbcode_id=NULL;
			$bbcode_name=NULL;
			$bbcode=NULL;
			$bbcode_example=NULL;

			if(isset($_GET['bbcode_id']))
			{
				$bbcode_result=$this->getBBCode($_GET['bbcode_id']);
				$bbcode_id=$bbcode_result->bbcode_id;
				$bbcode_name=$bbcode_result->bbcode_name;
				$bbcode=$bbcode_result->bbcode;
				$bbcode_example=$bbcode_result->bbcode_example;
			}
			if(isset($_POST['AddBBCodeForm']))
			{
				$bbcode_name=ucwords($_POST['bbcode_name']);
				$bbcode=$_POST['bbcode'];
				$bbcode_example=$_POST['bbcode_example'];

				# Check the BBCode Name length
				$bbcode_name_length=strlen($bbcode_name);
				if($bbcode_name_length >= 3)
				{
					# Is the bbcode name alphabetic?
					if(strcspn($bbcode_name, '0123456789')!=strlen($bbcode_name))
					{
						$error[]="Please enter a valid alphabetic bbcode name";
					}
				}
				else
				{
					$error[]="Please enter a bbcode name with 3 or more characters";
					$bbcode_name=$bbcode_name;
				}

				if(!isset($error))
				{
					# Insert bbcode
					if(!isset($bbcode_id))
					{
						$database->query('INSERT INTO forum_bbcode (bbcode_name, bbcode, bbcode_example) VALUES (?, ?, ?)', array($bbcode_name, $bbcode, $bbcode_example));
						$success[]=$bbcode_name.' has been added';
					}
					elseif(isset($bbcode_id))
					{
						$database->query('UPDATE forum_bbcode SET bbcode_name=:bbcodename, bbcode=:bbcode, bbcode_example=:bbcodeexample WHERE bbcode_id=:bbcodeid', array(':bbcodeid' => $bbcode_id, ':bbcodename' => $bbcode_name, ':bbcode' => $bbcode, ':bbcodeexample' => $bbcode_example));
						$success[]=$bbcode_name.' has been updated';
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
          <form name="AddBBCodeForm" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
	      <div class="boxcontainerlabel">Add BBCode</div>
              <div class="boxcontainercontent">
                <table class="hlineheader"><tr><th rowspan="2" nowrap>BBCode Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
                <table width="100%" border="0" cellspacing="1" cellpadding="4">
                  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="bbcode_name">BBCode Name:</label></td>
		    <td><input name="bbcode_name" type="text" size="20" maxlength="30" class="swifttextlarge" value="'.$bbcode_name.'"></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="bbcode">BBCode:</label></td>
		    <td><input id="bbcode" name="bbcode" type="text" size="30" maxlength="150" class="swifttextlarge" value="'.$bbcode.'"></td>
		  </tr>
                  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="bbcode_example">BBCode Example:</label></td>
		    <td><input id="bbcode_example" name="bbcode_example" type="text" size="30" maxlength="255" class="swifttextlarge" value="'.$bbcode_example.'"></td>
		  </tr>
		</table><br />
		<div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="AddBBCodeForm"></div>
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
	 * addSmiley
	 *
	 * Adds or Edits a forum smiley.
	 *
	 * @access	public
	 */
	public function addSmiley()
	{
		global $database;
		global $member;

		$user_id=$member->getUsersID();
		$is_admin=$member->isAdmin($user_id);

		if($is_admin)
		{
			$smiley_id=NULL;
			$smiley_name=NULL;
			$smiley_code=NULL;
			$smiley_img=NULL;

			if(isset($_GET['smiley_id']))
			{
				$smiley_result=$this->getSmiley($_GET['smiley_id']);
				$smiley_id=$smiley_result->smiley_id;
				$smiley_name=$smiley_result->smiley_name;
				$smiley_code=$smiley_result->smiley_code;
				$smiley_img=$smiley_result->smiley_img;
			}
			if(isset($_POST['AddSmileyForm']))
			{
				$smiley_name=ucwords($_POST['smiley_name']);
				$smiley_code=$_POST['smiley_code'];
				$smiley_img=$_POST['smiley_img'];

				# Check the Smiley Name length
				$smiley_name_length=strlen($smiley_name);
				if($smiley_name_length >= 3)
				{
					# Is the smiley name alphabetic?
					if(strcspn($smiley_name, '0123456789')!=strlen($smiley_name))
					{
						$error[]="Please enter a valid alphabetic smiley name";
					}
				}
				else
				{
					$error[]="Please enter a smiley name with 3 or more characters";
					$smiley_name=$smiley_name;
				}

				if(!isset($error))
				{
					# Insert smiley
					if(!isset($smiley_id))
					{
						$database->query('INSERT INTO forum_smilies (smiley_name, smiley_code, smiley_img) VALUES (?, ?, ?)', array($smiley_name, $smiley_code, $smiley_img));
						$success[]=$smiley_name.' has been added';
					}
					elseif(isset($smiley_id))
					{
						$database->query('UPDATE forum_smilies SET smiley_name=:smileyname, smiley_code=:smileycode, smiley_img=:smileyimg WHERE smiley_id=:smileyid', array(':smileyid' => $smiley_id, ':smileyname' => $smiley_name, ':smileycode' => $smiley_code, ':smileyimg' => $smiley_img));
						$success[]=$smiley_name.' has been updated';
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
          <form name="AddSmileyForm" method="post" action="'.$member->currentPage().'">
            <div class="boxcontainer">
              <div class="boxcontainerlabel">Add Smiley</div>
              <div class="boxcontainercontent">
                <table class="hlineheader"><tr><th rowspan="2" nowrap>Smiley Details</th><td>&nbsp;</td></tr><tr><td class="hlinelower">&nbsp;</td></tr></table>
		<table width="100%" border="0" cellspacing="1" cellpadding="4">
                  <tr>
                    <td align="left" valign="middle" class="zebraodd"><label for="smiley_name">Smiley Name:</label></td>
		    <td><input name="smiley_name" type="text" size="20" maxlength="30" class="swifttextlarge" value="'.$smiley_name.'"></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="smiley_code">Smiley Code:</label></td>
		    <td><input id="smiley_code" name="smiley_code" type="text" size="30" maxlength="255" class="swifttextlarge" value="'.$smiley_code.'"></td>
		  </tr>
		  <tr>
		    <td align="left" valign="middle" class="zebraodd"><label for="smiley_img">Smiley Image:</label></td>
		    <td><input id="smiley_img" name="smiley_img" type="text" size="30" maxlength="255" class="swifttextlarge" placeholder="smiley_file_name.gif" value="'.$smiley_img.'"></td>
		  </tr>
		</table><br />
                <div class="subcontent"><input class="rebuttonwide2" value="Update" type="submit" name="AddSmileyForm"></div>
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
	 * getCategory
	 *
	 * Retreives the category from the `forum_cat` table.
	 *
	 * @param	$cat_id
	 * @access	public
	 */
	public function getCategory($cat_id=NULL)
	{
		global $database;

		$database->query('SELECT cat_id, cat_name, cat_desc FROM forum_cat WHERE cat_id=:catid', array(':catid' => $cat_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getAllCategories
	 *
	 * Retrieves all categories from the `forum_cat` table.
	 *
	 * @access	public
	 */
	public function getAllCategories()
	{
		global $database;

		$database->query('SELECT cat_id, cat_name, cat_desc, cat_order FROM forum_cat ORDER BY cat_order', array());
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * getTopic
	 *
	 * Retreives the topic from the `forum_topics` table.
	 *
	 * @param	$topic_id
	 * @access	public
	 */
	public function getTopic($topic_id=NULL)
	{
		global $database;

		$database->query('SELECT topic_id, topic_subject, topic_cat FROM forum_topics WHERE topic_id=:topicid', array(':topicid' => $topic_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getAllTopics
	 *
	 * Retreives the topics of the chosen category from the `forum_topics` table.
	 *
	 * @param	$cat_id
	 * @access	public
	 */
	public function getAllTopics($cat_id=NULL)
	{
		global $database;

		$database->query('SELECT topic_id, topic_subject, topic_date, topic_by FROM forum_topics WHERE topic_cat=:catid ORDER BY topic_updated DESC', array(':catid' => $cat_id));
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * getPost
	 *
	 * Retreives the post from the `forum_posts` table.
	 *
	 * @param	$post_id
	 * @access	public
	 */
	public function getPost($post_id=NULL)
	{
		global $database;

		$database->query('SELECT post_id, post_content, post_by FROM forum_posts WHERE post_id=:postid', array(':postid' => $post_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getAllPosts
	 *
	 * Retreives the psts from the `forum_posts` table.
	 *
	 * @param	$topic_id
	 * @access	public
	 */
	public function getAllPosts($topic_id=NULL)
	{
		global $database;

		$database->query('SELECT forum_posts.post_id, forum_posts.post_topic, forum_posts.post_content, forum_posts.post_date, forum_posts.post_by, forum_posts.post_edited, forum_posts.post_edit_date, forum_posts.post_edit_by, users.id, users.full_name FROM forum_posts LEFT JOIN users ON forum_posts.post_by=users.id WHERE forum_posts.post_topic=:topicid', array(':topicid' => $topic_id));
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * getNewestPost
	 *
	 * Retreives the newest post information from `forum_topics` and `forum_posts` tables.
	 *
	 * @param	$cat_id
	 * @param	$topic_id
	 * @acess	public
	 */
	public function getNewestPost($cat_id=NULL, $topic_id=NULL)
	{
		global $database;

		if($cat_id!==NULL)
		{
			$database->query('SELECT forum_posts.post_date, forum_posts.post_by FROM forum_topics LEFT JOIN forum_posts ON forum_topics.topic_id=forum_posts.post_topic WHERE forum_topics.topic_cat=:catid ORDER BY forum_posts.post_date DESC LIMIT 1', array(':catid' => $cat_id));
		}
		elseif($topic_id!==NULL)
		{
			$database->query('SELECT forum_posts.post_date, forum_posts.post_by FROM forum_topics LEFT JOIN forum_posts ON forum_topics.topic_id=forum_posts.post_topic WHERE forum_posts.post_topic=:topicid ORDER BY forum_posts.post_date DESC LIMIT 1', array(':topicid' => $topic_id));
		}

		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getBBCode
	 *
	 * Retreives the BBCode from the `forum_bbcode` table.
	 *
	 * @param	$bbcode_id
	 * @access	public
	 */
	public function getBBCode($bbcode_id=NULL)
	{
		global $database;

		$database->query('SELECT bbcode_id, bbcode_name, bbcode, bbcode_example FROM forum_bbcode WHERE bbcode_id=:bbcodeid', array(':bbcodeid' => $bbcode_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getAllBBCode
	 *
	 * Retrieves and returns the BBCode from the `forum_bbcode` table.
	 *
	 * @access	public
	 */
	public function getAllBBCode()
	{
		global $database;

		$database->query('SELECT bbcode_id, bbcode_name, bbcode, bbcode_example FROM forum_bbcode', array());
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * getSmiley
	 *
	 * Retreives the Smiley from the `forum_smilies` table.
	 *
	 * @param	$smiley_id
	 * @access	public
	 */
	public function getSmiley($smiley_id=NULL)
	{
		global $database;

		$database->query('SELECT smiley_id, smiley_name, smiley_code, smiley_img FROM forum_smilies WHERE smiley_id=:smileyid', array(':smileyid' => $smiley_id));
		$result=$database->statement->fetch(PDO::FETCH_OBJ);

		return $result;
	}

	/**
	 * getSmilies
	 *
	 * Retrieves and returns the Smilies from the `forum_smilies` table.
	 *
	 * @access	public
	 */
	public function getSmilies()
	{
		global $database;

		$database->query('SELECT smiley_id, smiley_name, smiley_code, smiley_img FROM forum_smilies', array());
		$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);

		return $result;
	}

	/**
	 * convertBBCode
	 *
	 * Converts the BBCode to HTML tags.
	 *
	 * @param	$str
	 * @access	public
	 */
	public function convertBBCode($str)
	{
		# Delete 'http://' because will be added when convert the code
		$str=str_replace('[url=http://', '[url=', $str);
		$str=str_replace('[url]http://', '[url]', $str);

		# Array with RegExp to recognize the code that must be converted
		$bbcode_smiles=array(
			# RegExp for [b]...[/b], [i]...[/i], [u]...[/u], [block]...[/block], [color=code]...[/color], [br]
			'/\[b\](.*?)\[\/b\]/is',
			'/\[i\](.*?)\[\/i\]/is',
			'/\[u\](.*?)\[\/u\]/is',
			'/\[block\](.*?)\[\/block\]/is',
			'/\[color=(.*?)\](.*?)\[\/color\]/is',
			'/\[br\]/is',

			# RegExp for [url=link_address]..link_name..[/url], or [url]..link_address..[/url]
			'/\[url\=(.*?)\](.*?)\[\/url\]/is',
			'/\[url\](.*?)\[\/url\]/is',

			# RegExp for [img=image_address]..image_title[/img], or [img]..image_address..[/img]
			'/\[img\=(.*?)\](.*?)\[\/img\]/is',
			'/\[img\](.*?)\[\/img\]/is',

			# RegExp for sets of characters for smiles: :), :(, :P, :P, ...
			'/:D/i', '/:\)/i', '/;\)/i', '/:\(/i', '/:o/i', '/:shock:/i', '/:\?/i', '/8-\)/i', '/:x/i', '/:P/i', '/:oops:/i', '/:cry:/i', '/:evil:/i', '/:roll:/i', '/:\|/i', '/:bow:/i'
		);

		# Array with HTML that will replace the bbcode tags, defined inthe same order
		$html_tags=array(
			# <b>...</b>, <i>...</i>, <u>...</u>, <blockquote>...</blockquote>, <span>...</span>, <br/>
			'<b>$1</b>',
			'<i>$1</i>',
			'<u>$1</u>',
			'<blockquote>$1</blockquote>',
			'<span style="color:$1;">$2</span>',
			'<br/>',

			# a href...>...</a>, and <img />
			'<a target="_blank" href="http://$1">$2</a>',
			'<a target="_blank" href="http://$1">$1</a>',

			# <img src... alt=...>
			'<img src="$1" alt="$2" />',
			'<img src="$1" alt="$1" />',

			# The HTML to replace smiles. Here you must add the address of the images with smiles
			'<img src="assets/images/smilies/icon_e_biggrin.gif" alt="Very Happy" border="0" />',
			'<img src="assets/images/smilies/icon_e_smile.gif" alt="Smile" border="0" />',
			'<img src="assets/images/smilies/icon_e_wink.gif" alt="Wink" border="0" />',
			'<img src="assets/images/smilies/icon_e_sad.gif" alt="Sad" border="0" />',
			'<img src="assets/images/smilies/icon_e_surprised.gif" alt="Surprised" border="0" />',
			'<img src="assets/images/smilies/icon_eek.gif" alt="Shocked" border="0" />',
			'<img src="assets/images/smilies/icon_e_confused.gif" alt="Confused" border="0" />',
			'<img src="assets/images/smilies/icon_cool.gif" alt="Cool" border="0" />',
			'<img src="assets/images/smilies/icon_mad.gif" alt="Mad" border="0" />',
			'<img src="assets/images/smilies/icon_razz.gif" alt="Razz" border="0" />',
			'<img src="assets/images/smilies/icon_redface.gif" alt="Embarrassed" border="0" />',
			'<img src="assets/images/smilies/icon_cry.gif" alt="Crying or Very Sad" border="0" />',
			'<img src="assets/images/smilies/icon_evil.gif" alt="Evil or Very Mad" border="0" />',
			'<img src="assets/images/smilies/icon_roll.gif" alt="Rolling Eyes" border="0" />',
			'<img src="assets/images/smilies/icon_neutral.gif" alt="Neutral" border="0" />',
			'<img src="assets/images/smilies/icon_bow.gif" alt="Bow or Respect" border="0" />'
		);

		# Replace the bbcode
		$str=preg_replace($bbcode_smiles, $html_tags, $str);

		return $str;
	}

	/**
	 * getForumNotify
	 *
	 * Retreives the if the user wants to be notified on topics in the `forum_notify` table.
	 *
	 * @access	public
	 */
	public function getForumNotify($user_id=NULL)
	{
		global $database;

		if($user_id!==NULL)
		{
			$database->query('SELECT forum_notify FROM users WHERE id=:userid', array(':userid' => $user_id));
			$result=$database->statement->fetch(PDO::FETCH_ASSOC);
			return $result['forum_notify'];
		}
		else
		{
			$database->query('SELECT full_name, email FROM users WHERE forum_notify!=:notifyoff', array(':notifyoff' => "Off"));
			$result=$database->statement->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		}
	}

	/*** End public methods ***/



	/*** protected  methods ***/

	/**
	 * countTopics
	 *
	 * Count the number of topics in the category ($cat_id)
	 *
	 * @param	$cat_id
	 * @access	private
	 */
	private function countTopics($cat_id=NULL)
	{
		global $database;

		$database->query('SELECT topic_id FROM forum_topics WHERE topic_cat=:catid', array(':catid' => $cat_id));
		$result=$database->count();

		return $result;
	}

	/**
	 * countPosts
	 *
	 * Counts the number of posts in the topic
	 *
	 * @param	$cat_id
	 * @param	$topic_id
	 * @access	private
	 */
	private function countPosts($cat_id=NULL, $topic_id=NULL)
	{
		global $database;

		if($cat_id!==NULL)
		{
			$database->query('SELECT forum_posts.post_id FROM forum_posts LEFT JOIN forum_topics ON forum_posts.post_topic=forum_topics.topic_id WHERE forum_topics.topic_cat=:catid', array(':catid' => $cat_id));
		}
		elseif($topic_id!==NULL)
		{
			$database->query('SELECT post_id FROM forum_posts WHERE post_topic=:topicid', array(':topicid' => $topic_id));
		}

		$result=$database->count();

		return $result;
	}

	/**
	 * checkSubscribed
	 *
	 * Checks if the user is subscribed to the topic.
	 *
	 * @param	$user_id
	 * @param	$topic_id
	 * @param	private
	 */
	private function checkSubscribed($user_id=NULL, $topic_id=NULL)
	{
		global $database;

		$database->query('SELECT user_id FROM forum_subscribers WHERE user_id=:userid AND topic_id=:topicid', array(':userid' => $user_id, ':topicid' => $topic_id));
		$result=$database->count();

		return $result;
	}

	/**
	 * getSubscribers
	 *
	 * Retreive the topic subscribers from the `forum_subscribers` table.
	 *
	 * @param	$user_id
	 * @param	$topic_id
	 * @access	private
	 */
	private function getSubscribers($user_id=NULL, $topic_id=NULL)
	{
		global $database;

		if($topic_id!==NULL)
		{
			$database->query('SELECT users.full_name, users.email FROM users LEFT JOIN forum_subscribers ON users.id=forum_subscribers.user_id WHERE user_id!=:userid AND topic_id=:topicid', array(':userid' => $user_id, ':topicid' => $topic_id));
			$result=$database->statement->fetchAll(PDO::FETCH_OBJ);

			return $result;
		}
		else
		{
			echo "Error";
		}
	}

	/**
	 * checkIsRead
	 *
	 * Checks if the topic is read by the user.
	 *
	 * @param	$user_id
	 * @param	$topic_id
	 * @param	private
	 */
	private function checkIsRead($user_id=NULL, $topic_id=NULL)
	{
		global $database;

		$database->query('SELECT user_id FROM forum_is_read WHERE user_id=:userid AND topic_id=:topicid', array(':userid' => $user_id, ':topicid' => $topic_id));
		$result=$database->count();

		return $result;
	}

	/**
	 * breadcrumbs
	 *
	 * Creates a trail of breadcrumbs.
	 *
	 * @param	$cat_id
	 * @param	$topic_id
	 * @access	private
	 */
	private function breadcrumbs($cat_id=NULL, $topic_id=NULL)
	{
		global $database;

		if($cat_id!==NULL)
		{
			# Get Category
			$cat_result=$this->getCategory($cat_id);
			$cat_url=$cat_result->cat_name;
		}
		elseif($cat_id===NULL && $topic_id!==NULL)
		{
			$topic_result=$this->getTopic($topic_id);
			$cat_result=$this->getCategory($topic_result->topic_cat);

			$cat_url='<a href="member.php?action=forum_topics&cat='.$cat_result->cat_id.'" title="'.$cat_result->cat_name.'">'.$cat_result->cat_name.'</a> &raquo '.$topic_result->topic_subject;
		}

		$data='
              <div id="breadcrumbs">
                <a href="member.php?action=forum" title="Forum">Forum</a> &raquo '.$cat_url.'
              </div>';

		return $data;
	}

	/*** End protected methods ***/
}
?>