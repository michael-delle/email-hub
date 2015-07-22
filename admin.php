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

if($action == 'list-users')
{
	$member->LoggedIn();
	$title='Users';
	$content=$member->showPrivHeader().$admin->listUsers().$member->showPrivFooter();
}
elseif($action=="view-user")
{
	$member->LoggedIn();
	$title="View User";
	$content=$member->showPrivHeader().$admin->viewUser().$member->showPrivFooter();
}
elseif($action=="add-user")
{
	$member->LoggedIn();
	$title="Add User";
	$content=$member->showPrivHeader().$admin->addUser().$member->showPrivFooter();
}
elseif($action=="list-emails")
{
	$member->LoggedIn();
	$title="Emails";
	$content=$member->showPrivHeader().$EmailHandler->listEmails().$member->showPrivFooter();
}
elseif($action=="view-email")
{
	$member->LoggedIn();
	$title="View Email";
	$content=$member->showPrivHeader().$EmailHandler->viewEmail().$member->showPrivFooter();
}
elseif($action=="settings")
{
	$member->LoggedIn();
	$title="Settings";
	$content=$member->showPrivHeader().$admin->viewSettings().$member->showPrivFooter();
}
elseif($action=="site_settings")
{
	$member->LoggedIn();
	$title="Site Settings";
	$content=$member->showPrivHeader().$admin->viewSiteSettings().$member->showPrivFooter();
}
elseif($action=="policy_setting")
{
	$member->LoggedIn();
	$title="Policy Settings";
	$content=$member->showPrivHeader().$admin->viewPolicySettings().$member->showPrivFooter();
}
elseif($action=="policy_add" || $action=="policy_edit")
{
	$member->LoggedIn();
	$title="Add / Edit Policies";
	$content=$member->showPrivHeader().$admin->addPolicy().$member->showPrivFooter();
}
elseif($action=="forum_setting")
{
	$member->LoggedIn();
	$title="Forum Settings";
	$content=$member->showPrivHeader().$forum->viewForumSettings().$member->showPrivFooter();
}
elseif($action=="forum_cat_add" || $action=="forum_cat_edit")
{
	$member->LoggedIn();
	$title="Edit Forum Category";
	$content=$member->showPrivHeader().$forum->addCategory().$member->showPrivFooter();
}
elseif($action=="google_drive")
{
	$member->LoggedIn();
	$title="Google Drive Files";
	$content=$member->showPrivHeader().$admin->googleDrive().$member->showPrivFooter();
}
elseif($action=="file_add" || $action=="file_edit")
{
	$member->LoggedIn();
	$title="Add / Edit Google Drive Files";
	$content=$member->showPrivHeader().$admin->addFile().$member->showPrivFooter();
}
elseif($action=="forum_bbcode_add" || $action=="forum_bbcode_edit")
{
	$member->LoggedIn();
	$title="Add / Edit BBCode";
	$content=$member->showPrivHeader().$forum->addBBCode().$member->showPrivFooter();
}
elseif($action=="forum_smiley_add" || $action=="forum_smiley_edit")
{
	$member->LoggedIn();
	$title="Add / Edit Smiley";
	$content=$member->showPrivHeader().$forum->addSmiley().$member->showPrivFooter();
}
else
{
	$title='Please authenticate yourself';
	$content=$member->login();
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
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
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