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
		foreach ($bulbs as $bulb => $value) {
			echo $this->set_params($bulb, $params) . PHP_EOL;
		}
	}

	public static function ct_to_mired($ct) {
		return round(1000000/$ct);
	}

}



date_default_timezone_set('Europe/Berlin');
$settings = parse_ini_file(getenv('HOME') . '/.lights.conf');


$now = new DateTime();
$now_date = $now->format('Y-m-d');

# get sunrise / sunset
$sunrise_start = DateTime::createFromFormat('Y-m-d H:i', $now_date . ' ' . $settings['SUNRISE_START']);
$sunrise_duration = new DateInterval('PT' . $settings['SUNRISE_DURATION'] . 'M');
$sunset_start = DateTime::createFromFormat('Y-m-d H:i', $now_date . ' ' . $settings['SUNSET_START']);
$sunset_duration = new DateInterval('PT' . $settings['SUNSET_DURATION'] . 'M');
$sunrise_end = clone($sunrise_start);
$sunset_end = clone($sunset_start);
$sunrise_end->add($sunrise_duration);
$sunset_end->add($sunset_duration);

# get settings
$hue = new Hue($settings['SECRET'], $settings['HUEIP']);

$hue->set_params(14, array('on' => true, 'ct' => Hue::ct_to_mired(5000), 'bri' => 255, 'transitiontime' => 0));

// uplight red
$hue->set_params(4, array('on' => true, 'hue' => 0, 'sat' => 255, 'bri' => 0, 'transitiontime' => 0));
sleep(1);
// 2000k
$hue->set_params(4, array('ct' => Hue::ct_to_mired(2000), 'bri' => 255, 'transitiontime' => 6000));
sleep(600);
// transition uplight to 2700k -> minimum osram can do
$hue->set_params(4, array('ct' => Hue::ct_to_mired(2700), 'transitiontime' => 3000));
sleep(300);
// transition both to 3500k
$hue->set_params(4, array('ct' => Hue::ct_to_mired(3500), 'transitiontime' => 3000));
$hue->set_params(10, array('on' => true, 'ct' => Hue::ct_to_mired(2700), 'bri' => 10, 'transitiontime' => 60));
sleep(10);
$hue->set_params(10, array('ct' => Hue::ct_to_mired(3500), 'bri' => 80, 'transitiontime' => 3000));
sleep(300);
// both to 4700k
$hue->set_params(4, array('ct' => Hue::ct_to_mired(4700), 'transitiontime' => 6000));
$hue->set_params(10, array('ct' => Hue::ct_to_mired(4700), 'transitiontime' => 6000));
sleep(600);
// gibe bathroom
$hue->set_params(9, array('on' => true, 'ct' => Hue::ct_to_mired(5000), 'bri' => 255, 'transitiontime' => 0));
$hue->set_params(4, array('ct' => Hue::ct_to_mired(6500), 'transitiontime' => 590));
$hue->set_params(4, array('ct' => Hue::ct_to_mired(5000), 'transitiontime' => 590));

