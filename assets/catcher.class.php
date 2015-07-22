<?php

/**
 * Name:	Catcher
 * Author:	Draven
 *
 * The Catcher Class is used to check emails and manipulate them.
 *
 */
class Catcher
{
	/*** magic methods ***/

	/**
	 * emailCatcher
	 *
	 * Catches the emails sent.
	 *
	 * @access	public
	 */
	public function __construct()
	{
		global $EmailHandler;

		# Listen to incoming e-mails
		$sock=fopen("php://stdin", 'r');
		$full_email='';

		# Read e-mail into buffer
		while(!feof($sock))
		{
			$full_email.=fread($sock, 1024);
		}

		# Close socket
		fclose($sock);

		$EmailHandler->caughtEmail($full_email);
	}

	/*** End magic methods ***/
}
?>