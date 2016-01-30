<?php

class Hue {

	private $_apikey;
	private $_ip;
	private $_base;

	private function get_ssl_page($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	public function set_params($bulb, array $params) {
		$ch = curl_init();
		$url = $this->_base . 'lights/' . $bulb . '/state';
		echo $url . PHP_EOL;
		echo json_encode($params) . PHP_EOL;
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept' => 'application/json'));
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	public function __construct($apikey, $ip) {
		$thisapikey = $apikey;
		$this->_base = 'http://' . $ip . '/api/' . $apikey . '/';
		$this->_ip = $ip;
	}

	public function get_current_config() {
		return json_decode($this->get_ssl_page($this->_base . 'lights'), true);
	}

	public function set_many($bulbs, $params) {
		$filtered = array_filter($bulbs, function($k) use ($params) {
//			echo '|' . $k['state']['ct'] . '| |' . $params['ct'] . '|' . PHP_EOL;
			return $k['state']['ct'] != $params['ct'];
		});

		foreach ($filtered as $bulb => $value) {
			echo $this->set_params($bulb, $params) . PHP_EOL;
		}
	}

}


date_default_timezone_set('Europe/Berlin');
$settings = parse_ini_file(getenv('HOME') . '/.lights.conf');

$hue = new Hue($settings['SECRET'], $settings['HUEIP']);
$config = $hue->get_current_config();
$filtered = array_filter($config, function($k) {
	// find lights that are turned on, can change color temperature, where a color temperature is actually set, and which is currently reachable
	return $k['state']['on'] === true;
});
$hue->set_many($filtered, array('alert' => 'lselect'));

