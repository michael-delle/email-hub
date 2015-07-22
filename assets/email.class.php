<?php

/**
 * Name:	EmailClass
 * Author:	Draven
 *
 * The Email class is used to send emails.
 */

class EmailClass
{
	/*** data members ***/

	public $site_name=SITE_NAME;
	public $site_url=APPLICATION_URL;
	public $email_master=REPLY_EMAIL;
	public $email_welcome=FALSE;
	public $email_verification=TRUE;

	/*** End data members ***/



	/*** public methods ***/

	/**
	 * sendEmail
	 *
	 * Sends email
	 *
	 * @access	public
	 */
	public function sendEmail($toEmail, $subject, $body, $toName=NULL, $fromEmail=NULL, $userfile=NULL, $addNameToSignature=NULL)
	{
		$body.="\r\n\r\n--\r\n".(($addNameToSignature !== NULL) ? $addNameToSignature."\r\n" : "").$this->site_name."\r\n".$this->site_url."\r\n";

		# Get the PHPMailer class.
		require_once 'phpMailer'.DS.'class.phpmailer.php';

		# Instantiate a new PHPMailer object.
		$mail=new PHPMailer(TRUE);

		$mail->IsSMTP(TRUE);

		try
		{
			$mail->SMTPAuth=TRUE;
			$mail->Host=SMTP_HOST;
			$mail->Port=SMTP_PORT;
			$mail->Password=SMTP_PASS;

			if(isset($fromEmail))
			{
				$mail->Username=$fromEmail;
				$mail->SetFrom($fromEmail, $this->site_name);
				$mail->AddReplyTo($fromEmail, $this->site_name);
			}
			else
			{
				$mail->Username=$this->email_master;
				$mail->SetFrom($this->email_master, $this->site_name);
				$mail->AddReplyTo($this->email_master, $this->site_name);
			}

			$mail->AddAddress($toEmail, $toName);

			$mail->Subject=$subject;
			$mail->Body=$body;
			$mail->WordWrap=100;

			# Check if there is an attachment.
			if($userfile!==NULL)
			{
				for($i=0; $i<count($userfile); $i++)
				{
					$mail->AddAttachment($userfile[$i]);
				}
			}
			if($mail->Send())
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		catch (phpmailerException $e)
		{
			echo $e->errorMessage(); # Pretty error messages from PHPMailer
		}
		catch (Exception $e)
		{
			echo $e->getMessage(); # Boring error messages from anything else!
		}
	}

	/**
	 * makeClickable
	 */
	public function makeClickable($ret)
	{
		$ret=' '.$ret;

		# In testing, using arrays here was found to be faster
		$ret=preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', array('EmailClass', 'makeURLClickableCB'), $ret);
		$ret=preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', array('EmailClass', 'makeWebFTPClickableCB'), $ret);
		$ret=preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', array('EmailClass', 'makeEmailClickableCB'), $ret);

		# This one is not in an array because we need it to run last, for cleanup of accidental links within links
		$ret=preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
		$ret=trim($ret);

		return $ret;
	}

	/**
	 * HTML2TXT
	 *
	 * Converts passed HTML to text
	 *
	 * @param	$document				An HTML document or a string of html.
	 * @param	$utf8					True will return UTF-8 encoded text, FALSE returns ASCII text.
	 * @param	$remove_white_space		True will remove whitespace, tabs, carriage returns, and line breaks.
	 * @access	public
	 * @return	string
	 */
	public function HTML2TXT($document, $utf8=TRUE, $remove_white_space=FALSE)
	{
		$search=array();

		# Strip out javascript
		$js_search="'<(script[^>]*?)>.*?</script>'si";
		$search[]=$js_search;

		# Strip out HTML tags
		$html_search="'<([\/\!]*?[^<>]*?)>'si";
		$search[]=$html_search;

		# Strip out white space
		if($remove_white_space===TRUE)
		{
			$ws_search1="'([\r\n])[\s]+'";
			$search[]=$ws_search1;
			$ws_search2="'@<![\s\S]*?ñ[ \t\n\r]*>@'";
			$search[]=$ws_search2;
		}
		if($utf8===TRUE)
		{
			# Replace unacceptable characters with acceptable characters or HTML entities
			$dblquote_search="'(\“|\”)'i";
			$search[]=$dblquote_search;
			$snglquote_search="'(\‘|\’)'i";
			$search[]=$snglquote_search;
		}
		else
		{
			# Replace HTML entities
			$dblquote_search="'&(ldquo|#8220|rdquo|#8221|quot|#34|#034|#x22);'i";
			$search[]=$dblquote_search;
			$snglquote_search="'&(lsquo|#8216|rsquo|#8217);'i";
			$search[]=$snglquote_search;
			$dash_search="'&(ndash|#x2013|#8211|mdash|#x2014|#8212|#150);'i";
			$search[]=$dash_search;
			$ampersand_search="'&(amp|#38|#038|#x26);'i";
			$search[]=$ampersand_search;
			$lessthan_search="'&(lt|#60|#060|#x3c);'i";
			$search[]=$lessthan_search;
			$greaterthan_search="'&(gt|#62|#062|#x3e);'i";
			$search[]=$greaterthan_search;
			$space_search="'&(nbsp|#160|#xa0);'i";
			$search[]=$space_search;
			$inverted_exclamation_mark_search="'&(iexcl|#161);'i";
			$search[]=$inverted_exclamation_mark_search;
			$inverted_question_mark_search="'&(iquest|#191);'i";
			$search[]=$inverted_question_mark_search;
			$cent_search="'&(cent|#162);'i";
			$search[]=$cent_search;
			$pound_search="'&(pound|#163);'i";
			$search[]=$pound_search;
			$copyright_search="'&(copy|#169);'i";
			$search[]=$copyright_search;
			$registered_search="'&(reg|#174);'i";
			$search[]=$registered_search;
			$degrees_search="'&(deg|#176);'i";
			$search[]=$degrees_search;
			$apostrophe_search="'&(apos|#39|#039|#x27);'";
			$search[]=$apostrophe_search;
			$euro_search="'&(euro|#8364);'i";
			$search[]=$euro_search;
			$umlaut_a_search="'&a(uml|UML);'";
			$search[]=$umlaut_a_search;
			$umlaut_o_search="'&o(uml|UML);'";
			$search[]=$umlaut_o_search;
			$umlaut_u_search="'&u(uml|UML);'";
			$search[]=$umlaut_u_search;
			$umlaut_y_search="'&y(uml|UML);'";
			$search[]=$umlaut_y_search;
			$umlaut_A_search="'&A(uml|UML);'";
			$search[]=$umlaut_A_search;
			$umlaut_O_search="'&O(uml|UML);'";
			$search[]=$umlaut_O_search;
			$umlaut_U_search="'&U(uml|UML);'";
			$search[]=$umlaut_U_search;
			$umlaut_Y_search="'&Y(uml|UML);'";
			$search[]=$umlaut_Y_search;
			$latin_small_letter_sharp_s_search="'&(szlig|#xdf|#223);'i";
			$search[]=$latin_small_letter_sharp_s_search;
		}

		$replace=array();

		# Strip out javascript
		$js_replace="";
		$replace[]=$js_replace;

		# Strip out HTML tags
		$html_replace="";
		$replace[]=$html_replace;

		# Strip out white space
		if($remove_white_space===TRUE)
		{
			$ws_replace1=" ";
			$replace[]=$ws_replace1;
			$ws_replace2=" ";
			$replace[]=$ws_replace2;
		}
		if($utf8===TRUE)
		{
			# Replace HTML entities
			$dblquote_replace="\"";
			$replace[]=$dblquote_replace;
			$snglquote_replace="'";
			$replace[]=$snglquote_replace;
		}
		else
		{
			# Replace HTML entities
			$dblquote_replace=chr(34);
			$replace[]=$dblquote_replace;
			$snglquote_replace="'";
			$replace[]=$snglquote_replace;
			$dash_replace=chr(45);
			$replace[]=$dash_replace;
			$ampersand_replace=chr(38);
			$replace[]=$ampersand_replace;
			$lessthan_replace=chr(60);
			$replace[]=$lessthan_replace;
			$greaterthan_replace=chr(62);
			$replace[]=$greaterthan_replace;
			$space_replace=' ';
			$replace[]=$space_replace;
			$inverted_exclamation_mark_replace='¡';
			$replace[]=$inverted_exclamation_mark_replace;
			$inverted_question_mark_replace='¿';
			$replace[]=$inverted_question_mark_replace;
			$cent_replace='¢';
			$replace[]=$cent_replace;
			$pound_replace='£';
			$replace[]=$pound_replace;
			$copyright_replace='©';
			$replace[]=$copyright_replace;
			$registered_replace='®';
			$replace[]=$registered_replace;
			$degrees_replace='°';
			$replace[]=$degrees_replace;
			$apostrophe_replace=chr(39);
			$replace[]=$apostrophe_replace;
			$euro_replace='€';
			$replace[]=$euro_replace;
			$umlaut_a_replace='ä';
			$replace[]=$umlaut_a_replace;
			$umlaut_o_replace="ö";
			$replace[]=$umlaut_o_replace;
			$umlaut_u_replace="ü";
			$replace[]=$umlaut_u_replace;
			$umlaut_y_replace="ÿ";
			$replace[]=$umlaut_y_replace;
			$umlaut_A_replace="Ä";
			$replace[]=$umlaut_A_replace;
			$umlaut_O_replace="Ö";
			$replace[]=$umlaut_O_replace;
			$umlaut_U_replace="Ü";
			$replace[]=$umlaut_U_replace;
			$umlaut_Y_replace="Ÿ";
			$replace[]=$umlaut_Y_replace;
			$latin_small_letter_sharp_s_replace="ß";
			$replace[]=$latin_small_letter_sharp_s_replace;
		}

		$text=preg_replace($search, $replace, $document);

		if($utf8===TRUE)
		{
			$text=htmlentities($text, ENT_NOQUOTES, 'UTF-8', FALSE);
		}

		return trim($text);
	}

	/*** End public methods ***/



	/*** protected  methods ***/

	/**
	 * makeURLClickableCB
	 */
	private function makeURLClickableCB($matches)
	{
		$ret='';
		$url=$matches[2];

		if(empty($url))
			return $matches[0];

		# Removed trailing [.,;:] from URL
		if(in_array(substr($url, -1), array('.', ',', ';', ':')) === true)
		{
			$ret=substr($url, -1);
			$url=substr($url, 0, strlen($url)-1);
		}

		return $matches[1]."<a href=\"$url\" rel=\"nofollow\" target=\"_blank\">$url</a>".$ret;
	}

	/**
	 * makeWebFTPClickableCB
	 */
	private function makeWebFTPClickableCB($matches)
	{
		$ret='';
		$dest=$matches[2];
		$dest='http://' . $dest;

		if(empty($dest))
			return $matches[0];

		# removed trailing [,;:] from URL
		if(in_array(substr($dest, -1), array('.', ',', ';', ':')) === true)
		{
			$ret=substr($dest, -1);
			$dest=substr($dest, 0, strlen($dest)-1);
		}

		return $matches[1]."<a href=\"$dest\" rel=\"nofollow\">$dest</a>".$ret;
	}

	/**
	 * makeEmailClickableCB
	 */
	private function makeEmailClickableCB($matches)
	{
		$email=$matches[2].'@'.$matches[3];

		return $matches[1]."<a href=\"mailto:$email\">$email</a>";
	}

	/*** End protected methods ***/
}
?>