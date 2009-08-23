<?php
class MP_Ip
{
	const cache_days 	= 120;			// keep it 120 days
	const provider 	= 'mailpress';
	const no_state	= 'ZZ';

	public static function get_all($ip)
	{
		return self::get_ip_info($ip);
	}

	public static function get_latlng($ip)
	{
		$content = self::get_ip_info($ip);
		if (!$content) return false;
		if (!isset($content['geo']['lat'])) return false;
		return $content['geo'];
	}

	public static function get_country($ip)
	{
		$content = self::get_ip_info($ip);
		if ( (!$content) || (!isset($content['country'])) ||(strlen($content['country']) > 2) ) return self::no_state;
		return $content['country'];
	}

	public static function get_subcountry($ip)
	{
		$content = self::get_ip_info($ip);
		if ( (!$content) || (!isset($content['subcountry'])) || empty($content['subcountry']) ) return self::no_state;
		return $content['subcountry'];
	}

	public static function get_USstate($ip)
	{
		$response = wp_remote_get("http://api.hostip.info/get_html.php?ip=$ip");
		$x = (is_wp_error($response)) ? false : $response['body'];
		if (!$x || empty($x) || (2 < strlen($x))) return self::no_state;

		$USstates = array('AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY');
		$USstate = substr($x, strlen($x)-2, 2);
		return (in_array(substr($USstate, 0, 2), $USstates)) ? substr($USstate, 0, 2) : self::no_state;
	}

	public static function get_ip_info($ip)
	{
		MailPress::require_class('Ip_providers');
		$providers = MP_Ip_providers::get_all();
		foreach($providers as $provider => $pdata)
		{
			list($file, $ip_url, $cache) = self::get_context($pdata, $ip);
			if ($file == $cache) break;
		}

		switch ($file)
		{
			case $cache :
				$content = @file_get_contents($file);
				$valid   = (!empty($content) && $content);
			break;
			default :
				unset($providers[self::provider]);
				do
				{
					$provider	= array_rand($providers);
					$pdata	= $providers[$provider];
					list($file, $ip_url, $cache) = self::get_context($pdata, $ip);

					$response = wp_remote_get($ip_url);
					$content = (is_wp_error($response)) ? false : $response['body'];
					$valid = (!empty($content) && $content);

					if ($valid)
					{
						switch($pdata['type'])
						{
							case 'xml' :
								if (!simplexml_load_string($content)) 	$valid = false;
							break;
							case 'array' :
								if (!is_serialized($content)) 		$valid = false;
							break;
						}
						if ($valid) $valid = apply_filters('MailPress_ip_content_' . $pdata['type'], $valid, $content, $provider);	
					}
					if (!$valid) unset($providers[$provider]);
					if (empty($providers)) break;
				} while (!$valid);

				if ($valid) file_put_contents($cache, $content);
			break;
		}

		if (!$valid) return false;

		switch($provider)
		{
			case self::provider :
				return self::custom($content);
			break;
			default :
				return apply_filters('MailPress_ip_data_' . $provider, false, $content, $ip, $ip_url, $cache, $file);
			break;
		}
	}

	public static function get_context($pdata, $ip)
	{
		$file  = $ip_url = sprintf( $pdata['url'], $ip );
		$cache = MP_TMP . 'tmp/' . ((!isset($pdata['md5'])) ?  md5($ip_url) : $ip_url) . '.spc';


		if (file_exists($cache))
		{
			$cache_days = ( (isset($pdata['cache_days'])) ? $pdata['cache_days'] : self::cache_days )*24*60*60;
			if (filemtime($cache) >= (time() - $cache_days))	$file = $cache;
			else									unlink($cache);
		}
		return array($file, $ip_url, $cache);
	}

	public static function custom($content)
	{
		return (is_serialized($content)) ? unserialize($content) : false;
	}

	public static function get_address($lat, $lng)
	{
		global $mp_general;
		$url = "http://maps.google.com/maps/geo?q=$lat,$lng&output=xml&oe=utf8&sensor=true&key=" . $mp_general['gmapkey'];
//$response = wp_remote_get($url, array('timeout' => 3600));
//$xml = (is_wp_error($response)) ? false : $response['body'];
		$xml = file_get_contents($url);
		if ( !$xml || empty($xml) ) return '';

		$xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

		if (!$xml) return '';
		if (200 != $xml->Response->Status->code) return '';
		if (!isset($xml->Response->Placemark)) return '';

		$i = 0;
		foreach($xml->Response->Placemark as $placemark)
		{
			if (!isset($placemark->address) || !isset($placemark->AddressDetails['Accuracy'])) continue; 

			$accuracy[(int) $placemark->AddressDetails['Accuracy']] = $i;
			$address[$i] = (string) $placemark->address[0];
			$i++;
		}
		if (empty($accuracy)) return '';
		if (isset($accuracy[8])) return $address[$accuracy[8]];
		krsort($accuracy);
		return $address[reset($accuracy)];
	}
}
?>