<?php

# Begin output buffering
ob_start();

# Include Code
include("assets/member.inc.php");

# Is an Action set?
if(isset($_GET['action']))
{
	$action=$_GET['action'];
}
else
{
	$action=NULL;
}

if($action=='')
{
	$member->LoggedIn();
	$title='Secure Page';
	$content=$member->showPrivHeader().
    '<article class="boxcontainer">
      <div class="boxcontainerlabel">ToDo</div>
	    <div class="boxcontainercontent">
	      <ul>
		    <li>Recode chat box to use Ajax</li>
            <li>Change "status" text if email is old</li>
            <li>Add text highlighting in search</li>
            <li>Explode search terms</li>
            <li>Create and store config values in a config table</li>
            <li>Create foreign key relationships between database tables</li>
            <li>Fix "Last Replier" when merging emails</li>
          </ul>
	    </div>
	    <div class="boxcontainerlabel">ChangeLog</div>
	    <div class="boxcontainercontent">
	      <p>Legend:</p>
	      <ul>
            <li>* -> Security Fix</li>
            <li># -> Issue Fix</li>
            <li>+ -> Addition</li>
            <li>^ -> Change</li>
            <li>- -> Removed</li>
            <li>! -> Note</li>
          </ul>
	      <p>19-April-2013 Michael Delle (Draven)  &lt;dravenlon@gmail.com&gt;</p>
	      <ul>
	        <li>+ Users Online to the left side of the Hub.</li>
	        <li>+ Last Active in Users -> Click a user.</li>
          </ul>
	    </div>
	  </article>'.$member->showPrivFooter();
}
elseif($action=='logout')
{
	$member->logout();
}
elseif($action=='preferences')
{
	$member->LoggedIn();
	$title='Preferences';
	$content=$member->showPrivHeader().$member->preferences().$member->showPrivFooter();
}
elseif($action=='change-settings')
{
	$member->LoggedIn();
	$title='Account-Settings';
	$content=$member->showPrivHeader().$member->changeSettings().$member->showPrivFooter();
}
elseif($action=='change-password')
{
	$member->LoggedIn();
	$title='Change Password';
	$content=$member->showPrivHeader().$member->changePassword().$member->showPrivFooter();
}
elseif($action=='register')
{
	$title='Create an account';
	$content=$member->showPubHeader().$member->register().$member->showPubFooter();
}
elseif($action=='recover-password')
{
	$title='Recover your password';
	$content=$member->showPubHeader().$member->recoverPassword().$member->showPubFooter();
}
elseif($action=='reset-password')
{
	$member->LoggedIn();
	$title='Reset your password';
	$content=$member->showPrivHeader().$member->resetPassword().$member->showPrivFooter();
}
elseif($action=='verification')
{
	$title='Your account has been verified';
	$content=$member->showPubHeader().$member->verification().$member->showPubFooter();
}
elseif($action=="forum")
{
	$member->LoggedIn();
	$title='Forum';
	$content=$member->showPrivHeader().$forum->viewForum().$member->showPrivFooter();
}
elseif($action=="forum_topics")
{
	$member->LoggedIn();
	$title='Forum';
	$content=$member->showPrivHeader().$forum->viewTopics().$member->showPrivFooter();
}
elseif($action=="forum_posts" || $action=="forum_edit")
{
	$member->LoggedIn();
	$title='Forum';
	$content=$member->showPrivHeader().$forum->viewPosts().$member->showPrivFooter();
}
elseif($action=="search")
{
	$member->LoggedIn();
	$title='Search';
	$content=$member->showPrivHeader().$Search->processSearch().$member->showPrivFooter();
}
elseif($action=="policies")
{
	$member->LoggedIn();
	$title='Policies';
	$content=$member->showPrivHeader().
	 '<article class="boxcontainer">
	    <div class="boxcontainerlabel">Policies</div>
	    <div class="boxcontainercontent">
	      <strong>***Remember to close the ticket when the question is resolved***</strong><br>
	      <strong>***Be polite***</strong><br><br>';

	$get_policies=$member->getPolicies();
	foreach($get_policies as $policy)
	{
		$content.='
	      <strong>'.nl2br($policy['question']).'</strong><br>
'.($policy['clipboard']=="Yes" ? '<pre class="html">'.$policy['response'].'</pre><br>' : nl2br($policy['response']).'<br><br><br>');
	}

	$content.='
	    </div>
	  </article>
	  <script type="text/javascript" src="assets/js/vendor/jquery.zclip.min.js"></script>
	  <script type="text/javascript" src="assets/js/vendor/jquery.snippet.min.js"></script>
	  <script type="text/javascript">
	  $(document).ready(function(){
		  $("pre.html").snippet("html",{style:\'print\',clipboard:\'assets/js/vendor/ZeroClipboard.swf\',showNum:false});
	  });
	  </script>'.$member->showPrivFooter();
}
else
{
	$title='Please authenticate yourself';
	$content=$member->showPubHeader().$member->login().$member->showPubFooter();
}
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo $title; ?></title>
    <meta name="viewport" content="width=device-width">
<?php
	$arr=$member->removeParameters($_SERVER['REQUEST_URI']);
	if(!isset($arr['action']))
	{
		$arr['action']="";
	}
	else
	{
		$arr['action']="?action=".$arr['action'];
	}

	if($_SERVER['REQUEST_URI']=="/hub/member.php?action=login" || $_SERVER['REQUEST_URI']=="/hub/member.php?action=recover-password" || $_SERVER['PHP_SELF'].$arr['action']=="/hub/member.php?action=verification")
	{
?>
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/style.css">
<?php
	}
	else
	{
?>
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
<?php
	}
?>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="assets/js/vendor/jquery-1.8.0.min.js"><\/script>')</script>
    <script src="assets/js/jquery.badbrowser.js"></script>
    <script src="assets/js/plugins.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/vendor/modernizr-2.6.1.min.js"></script>
  </head>

  <body class="bodymain">
    <!--[if lt IE 7]>
        <p class="chromeframe">You are using an outdated browser. <a href="http://browsehappy.com/">Upgrade your browser today</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to better experience this site.</p>
    <![endif]-->

<?php echo $content; ?>

  </body>
</html>
<?php

# Send the buffer to the user's browser.
ob_end_flush();

?>