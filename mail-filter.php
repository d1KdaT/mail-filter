<?php
require_once("config.php");
require_once("functions.php");
require_once("actions.php");

define("LOCK_FILE_PATH", __DIR__ . "/.lockfile");

if(file_exists(LOCK_FILE_PATH))
{
	if(time() - filemtime(LOCK_FILE_PATH) > 300)
	{
		$message_error = "Lockfile exist more than 300 seconds";
		$sendMessage = tg_api("sendMessage", ["chat_id" => CHAT_ID, "text" => $message_error]);
	}

	exit(1);
}

file_put_contents(LOCK_FILE_PATH, "1");

$messages = array();
$connection = imap_open("{" . IMAP_HOST . ":993/imap/ssl" . ((defined("CHECK_CERT") && !CHECK_CERT) ? "/novalidate-cert" : "") . "}", USERNAME, PASSWORD, 0, 3, IMAP_OPTIONS);

if($connection)
{
	$date_search = date("d-M-Y", time() - 60 * 60 * 24 * 7);
	$messages_ids = imap_search($connection, "SINCE " . $date_search);
	unset($date_search);

	if(isset($messages_ids) && is_array($messages_ids) && count($messages_ids) > 0)
	{
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
	$messages[] = "Can not connect to IMAP";
}

if(isset($messages) && is_array($messages) && count($messages) > 0)
{
	$sendMessage = tg_api("sendMessage", ["chat_id" => CHAT_ID, "text" => implode("\n", $messages)]);
}

unlink(LOCK_FILE_PATH);
