<?php
require_once("config.php");
require_once("functions.php");
require_once("actions.php");

$connection = imap_open("{" . IMAP_HOST . ":993/imap/ssl}", USERNAME, PASSWORD, 0, 3, ['DISABLE_AUTHENTICATOR' => 'GSSAPI', 'NTLM']);

if($connection)
{
	$messages_ids = imap_search($connection, "UNSEEN");

	if(isset($messages_ids) && is_array($messages_ids) && count($messages_ids) > 0)
	{
		$messages = array();

		foreach($messages_ids as $id)
		{
			$headers = imap_headerinfo($connection, $id);

			$pieces = array();

			foreach(["toaddress", "fromaddress", "ccaddress", "bccaddress"] as $v)
			{
				if(isset($headers->$v))
				{
					$pieces[] = im_header_decode($headers->$v);
				}
			}
			unset($v);

			$data = array();
			$data[0] = (count($pieces) > 0) ? implode(", ", $pieces) : "";
			$data[1] = im_header_decode($headers->subject);

			foreach($actions as $v)
			{
				if(f_matches($data[$v[0]], $v[1]))
				{
					imap_mail_move($connection, $id, $v[2]);

					if($v[3])
					{
						$messages[] = $v[2] . ": " . $data[1];
					}

					echo "[" . date("H:i:s") . "] Move \"" . $data[1] . "\" (" . $data[0] . ") [" . $id . "] to " . $v[2] . " folder" . (($v[3]) ? " & sent message" : "") . PHP_EOL;

					break; // do not use other filters
				}
			}

			unset($v, $headers, $pieces, $data);
		}
	}

	imap_expunge($connection);
	imap_close($connection);
	unset($connection, $messages_ids, $id);
}
else
{
	$message = "Can not connect to IMAP";
}

if(isset($message))
{
	$sendMessage = tg_api("sendMessage", ["chat_id" => CHAT_ID, "text" => $message]);
}
elseif(isset($messages) && is_array($messages) && count($messages) > 0)
{
	$sendMessage = tg_api("sendMessage", ["chat_id" => CHAT_ID, "text" => implode("\n", $messages)]);
}
