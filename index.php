<?php
/*
 * Name:          Index Page
 * Author:        Draven
 */

# Begin output buffering
ob_start();

# Include Class
include("assets/member.inc.php");

$member->LoggedIn(TRUE);

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
	$title = 'Hub';
	$content = $member->showPubHeader().'
		<p>Welcome to the Site Name Hub.</p><br>
		<p>This will be the center for press badge, and volunteer applicants (coming soon).</p><br>
		<p>It also controls all emails sent to @domain.com email accounts.</p>
		'.$member->showPubFooter();
}
else
{
	header("Location: index.php");
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
    <meta name="description" content="<?php echo META_DESC; ?>">
    <meta name="keywords" content="<?php echo META_KEYWORDS; ?>">
    <meta name="author" content="Michael Delle (Draven) FromDuskTillCon.com">
    <meta name="designer" content="Michael Delle (Draven) FromDuskTillCon.com">
    <meta name="copyright" content="Copyright &copy; 2010-<?php echo date('Y'); ?> <?php echo SITE_NAME; ?>">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="<?php echo SITE_NAME; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://<?php echo FULL_DOMAIN.HERE; ?>">
    <meta property="og:image" content="<?php echo APPLICATION_URL.ROOT_IMAGES.FB_THUMB; ?>">
    <meta property="og:description" content="<?php echo META_DESC; ?>">
    <meta property="fb:admins" content="draven714">
    <meta property="fb:app_id" content="177566665639096">
    <link rel="shortcut icon" href="<?php echo APPLICATION_URL.ROOT_IMAGES; ?>favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="assets/js/vendor/jquery-1.8.0.min.js"><\/script>')</script>
    <script src="assets/js/plugins.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/vendor/modernizr-2.6.1.min.js"></script>
  </head>

  <body>

<?php echo $content; ?>

  </body>
</html>
<?php

# Send the buffer to the user's browser.
ob_end_flush();

?>