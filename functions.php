<?php
function f_matches($a, $b) // a - haystack, b - needle
{
	return preg_match("/" . preg_quote($b, '/') . "/ui", $a);
}

function tg_api($method, array $query = array())
{
	foreach($query as $param => $value)
	{
		if(is_array($value))
		{
			$query[$param] = implode(',', $value);
		}
	}

	$query['parse_mode'] = "html";
	$url = 'https://'.TG_API.'/bot'.TG_TOKEN.'/'.$method.'?'.http_build_query($query);
	$result = json_decode(curl($url), true);

	if(isset($result['response']))
	{
		return $result['response'];
	}

	return $result;
}

function curl($url, $auth = false)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

	if($auth)
	{
		curl_setopt($ch, CURLOPT_USERPWD, USERNAME . ":" . PASSWORD);
	}

	$result = curl_exec($ch);

	if(!$result)
	{
		return false;
	}

	curl_close($ch);
	return $result;
}
