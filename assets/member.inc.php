<?php
/*
**********
Member Page
**********
*/

# Include Class
include("define.php");
include("database.class.php");
include("member.class.php");
include("forum.class.php");
include("press.class.php");
include("volunteer.class.php");
include("admin.class.php");
include("email.class.php");
include("emailhandler.class.php");
include("search.class.php");

# Start an instance of the Database class
$database=new database(DB_HOST, DB_TABLE, DB_USER, DB_PASS);

# Create an instance of the Member class
$member=new member();

# Create an instance of the Forum class
$forum=new Forum();

# Create an instance of the Press class
$press=new Press();

# Create an instance of the Volunteer class
$Volunteer=new Volunteer();

# Create an instance of the Admin class
$admin=new Admin();

# Create an instance of the Email class
$EmailClass=new EmailClass;

# Create an instance of the EmailHandler class
$EmailHandler=new EmailHandler();

# Create an instance of the Search class
$Search=new Search();
?>