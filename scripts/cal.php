<?php

class Ical {

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

	public function icsToArray($paramUrl) {
		$icsFile = $this->get_ssl_page($paramUrl);

		$icsData = explode("BEGIN:", $icsFile);

		foreach($icsData as $key => $value) {
			$icsDatesMeta[$key] = explode("\n", $value);
		}

		foreach($icsDatesMeta as $key => $value) {
			foreach($value as $subKey => $subValue) {
				$subValue = trim($subValue);
				if ($subValue != "") {
					if ($key != 0 && $subKey == 0) {
						$icsDates[$key]["BEGIN"] = $subValue;
					} else {
						$subValueArr = explode(":", $subValue, 2);
						$icsDates[$key][$subValueArr[0]] = (string)$subValueArr[1];
					}
				}
			}
		}

		return $icsDates;
	}

}

date_default_timezone_set('Europe/Berlin');
$ical = new Ical();
$holidays = $ical->icsToArray('https://a.rndt.co/feiertage/RP.ics');
$vacations = $ical->icsToArray('https://calendar.picotronic.de/vacation-picotronic.ics');
$today = new DateTime();
$today_fmt = $today->format('Ymd');

// wohooooo, weekend
if ($today->format('N') >= 6) exit(0);

foreach ($holidays as $holiday) {
	if (!array_key_exists('DTSTART;VALUE=DATE', $holiday)) continue;
	// woohooooo, holiday
	if ($holiday['DTSTART;VALUE=DATE'] == $today_fmt) exit(0);
}
foreach ($vacations as $vacation) {
	if (!array_key_exists('DTSTART;VALUE=DATE', $vacation)) continue;
	if (!array_key_exists('DTEND;VALUE=DATE', $vacation)) continue;
	if (strpos($vacation['SUMMARY'], 'Daniel Arndt') === false) continue;
	$start = DateTime::createFromFormat('Ymd', (string)$vacation['DTSTART;VALUE=DATE']);
	$end = DateTime::createFromFormat('Ymd', (string)$vacation['DTEND;VALUE=DATE']);
	if ($start <= $today && $end > $today) {
		// woohoooo, vacation
		exit(0);
	}
}

// gotta work m(
exit(1);

