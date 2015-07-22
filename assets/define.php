<?php

/*
 *---------------------------------------------------------------
 * APPLICATION CONSTANTS
 *---------------------------------------------------------------
 *
 * Constants defining the application
 *
 */
# Define backslash or forward slash for *NIX and IIS systems.
define('DS', DIRECTORY_SEPARATOR);

# Attempt to determine the full-server path to the 'root' folder in order to reduce the possibility of path problems. (ends with a slash)
define('BASE_PATH', realpath(dirname(__FILE__)).DS);

# Define the path to the modules folder. (ends with a slash)
define('ATTACHMENTS', BASE_PATH.'attachments'.DS);

# Extrapolate the domain name (ie. sub.domain.com)
$fulldomain=$_SERVER['SERVER_NAME'];
# Force lowercase.
$fulldomain=strtolower($fulldomain);
# Define the full domain name (ie. sub.domain.com)-(ends with a slash)
define('FULL_DOMAIN', $fulldomain.'/');

# Create array and fill with sub-domains (ie. www., admin., www.admin.)
$dn_prefixes=array('www.');
# Remove sub domain.
$domain=str_replace($dn_prefixes, '', $fulldomain);
# The domain name that we use (ie. domain.com) (does not end with a slash)
define('DOMAIN_NAME', $domain);

# Define the url that points to our application. (ends with a slash)
define('APPLICATION_URL', 'http://'.DOMAIN_NAME.'/');

# Define the rest of the URL where we are at. (ie. folder/file.php)
define('HERE', ltrim($_SERVER['PHP_SELF'], '/'));

# Check if there is a GET query attached to the url we are at and define it as a constant.
define('GET_QUERY', ((!empty($_SERVER['QUERY_STRING'])) ? '?'.$_SERVER['QUERY_STRING'] : ''));

# Define the complete URL where we are at. (ie subdomain.example.com/file.php?data=1)
define('FULL_URL', FULL_DOMAIN.HERE.GET_QUERY);

# Define the path to the root CSS folder
define('ROOT_CSS', 'css'.DS);

# Define the path to the root images folder
define('ROOT_IMAGES', 'assets/images'.DS);

# Define the path to the root JS folder
define('ROOT_JS', 'js'.DS);

# Reply email.
define('REPLY_EMAIL', 'noreply@'.DOMAIN_NAME);
# Set to TRUE to use SMTP to send out emails, FALSE to use PHP's mail() function.
define('USE_SMTP', TRUE);
# Define the port to use for SMTP (only needed if SMTP is set to TRUE)
define('SMTP_PORT', '587');
# Define the host to use for SMTP (only needed if SMTP is set to TRUE)
define('SMTP_HOST', '');
# Define the user to use for SMTP (only needed if SMTP is set to TRUE)
define('SMTP_USER', '');
# Define the password to use for SMTP (only needed if SMTP is set to TRUE)
define('SMTP_PASS', '');
# Define the email to use in the "from" field for SMTP (only needed if SMTP is set to TRUE)
define('SMTP_FROM', SMTP_USER);
# Define the type of security to use for SMTP (only needed if SMTP is set to TRUE. Options are 'tls', 'ssl', or '')
define('SMTP_SECURE', 'tls');

# Set to TRUE to send emails as html and FALSE to send as text.
define('MAIL_IS_HTML', TRUE);

# Set the database host.
define('DB_HOST', '');
# Set the database table.
define('DB_TABLE', '');
# Set the database username.
define('DB_USER', '');
# Set the database password.
define('DB_PASS', '');

########## DO NOT EDIT - END ############

# Site Name
define('SITE_NAME', 'Site Name');

# Facebook thumbnail
define('FB_THUMB', 'facebook'.DS.'facebook_thumb.jpg');

### Meta Tags ###
# Description
define('META_DESC', SITE_NAME.' Meta Description.');
# Keywords
define('META_KEYWORDS', '');

?>