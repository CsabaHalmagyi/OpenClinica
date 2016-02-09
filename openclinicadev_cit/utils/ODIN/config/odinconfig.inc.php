<?php
/**
 * Default config file.  
 */


/*
 * Enables LDAP authenthication for ODIN.
 */
$useLDAP = false;

/*
 * A contact email address that is displayed for users who are not able to login.
 */
$adminContactEmail = "admin@example.com";

/*
 * Determines the time the created files (mapping, xml) should be kept on the server (in seconds).
 * Example values:
 * 
 * //will never delete any created files
 * $deleteFilesOlderThan = 0;
 * 
 * //will delete files older than a minute
 * $deleteFilesOlderThan = 60;
 * 
 * //will delete files older than an hour
 * $deleteFilesOlderThan = 3600;
 * 
 * //will delete files older than a day
 * $deleteFilesOlderThan = 84600;
 * 
 * //will delete files older than a month
 * $deleteFilesOlderThan = 2678400;
 * 
 */
$deleteFilesOlderThan = 0;

?>