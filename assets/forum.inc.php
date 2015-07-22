<?php
/*
 **********
 Forum Page
 **********
 */

# Include Class
include("define.php");
include("database.class.php");
include("member.class.php");
include("forum.class.php");

# Start an instance of the Database class
$database=new database(DB_HOST, DB_TABLE, DB_USER, DB_PASS);

# Create the instance of the Member class
$member=new member();

# Create the instance of the Forum claass
$forum=new Forum();

?>