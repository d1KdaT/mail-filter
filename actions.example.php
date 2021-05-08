<?php
$actions = array(
	array(0, "mail@site", "folder1", true), // check "mail@site" contains in from, to, bcc, cc, if so - move to "folder1" and send message to user
	array(1, "some words", "folder2", false), // check "some words" contains in subject, if so - move to "folder2" without sending message to user
);
