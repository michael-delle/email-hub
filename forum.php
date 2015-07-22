<?php

# Begin output buffering
ob_start();

# Include Code
include("assets/forum.inc.php");

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
    $title='Forum';
    $content=$member->showPrivHeader().$forum->viewForum().$member->showPrivFooter();
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
	else {
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