<?php
/**
 * Catcher Config
 */

# Include Code
include("define.php");
include("database.class.php");
include("email.class.php");
include("emailhandler.class.php");
include("catcher.class.php");

# Start an instance of the Database class
$database=new database(DB_HOST, DB_TABLE, DB_USER, DB_PASS);

# Create an instance of the Email class
$EmailClass=new EmailClass;

# Create an instance of the EmailHandler Class
$EmailHandler=new EmailHandler();

# Start an instance of the EmailCatcher class
$Catcher=new Catcher();

?>