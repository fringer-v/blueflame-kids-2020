<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function str_startswith($haystack, $needle){
	return !strncmp($haystack, $needle, strlen($needle));
}

function str_endswith($haystack, $needle) {
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}
	
	return (substr($haystack, -$length) === $needle);
}

function str_right($str, $value, $search_backwards = false) {
	if ($search_backwards)
		$pos = strrpos($str, $value);
	else
		$pos = strpos($str, $value);
	if ($pos === false) {
		if ($search_backwards)
			return $str;
		return '';
	}
	return substr($str, $pos + strlen($value));
}

function str_left($str, $value, $search_backwards = false) {
	if ($search_backwards)
		$pos = strrpos($str, $value);
	else
		$pos = strpos($str, $value);
	if ($pos === false) {
		if ($search_backwards)
			return '';
		return $str;
	}
	$retValue = substr($str, 0, $pos);
	return $retValue;
}

function str_contains($haystack, $needle) {
	return stripos($haystack, $needle) !== false;
}

function str_listappend($list, $value, $sep) {
	if (empty($list))
		return '';
	return $list.$sep.$value;
}

function str_to_date($val)
{
	if (is_empty($val))
		return null;
	if (str_contains($val, '.'))
		$ts = DateTime::createFromFormat('d.m.Y', $val);
	else
		$ts = DateTime::createFromFormat('d-m-Y', $val);
	if ($ts === false)
		return null;
	$year = (integer) $ts->format('Y');
	$month = (integer) $ts->format('m');
	$day = (integer) $ts->format('d');
	if ($year < 100)
		$ts->setDate($year+2000, $month, $day);
	return $ts;
}

function str_from_date($ts, $fmt)
{
	return $ts->format($fmt);
}
	
// This function fixes a bug that empty() has with the results of function calls
function is_empty($val) {
	if (!is_array($val))
		$val = trim($val);
	return empty($val);
}

function is_not_empty($val) {
	return !is_empty($val);
}

function is_int_val($data) {
	if (is_int($data))
		return true;
	if (is_string($data) && is_numeric($data))
		return strpos($data, '.') === false;
	return false;
}

function if_empty($val, $def) {
	if (empty($val))
		return $def;
	return $val;
}

function arr_remove_empty($array) {
	return array_filter($array, "is_not_empty");
}

function arr_nvl($array, $index, $default = null) {
	if (isset($array[$index]))
		return $array[$index];
	return $default;
}

function arr_is_assoc($array) {
	return array_keys($array) !== range(0, count($array) - 1);
}

function get_age($dob) {
	if (empty($dob))
		return null;

	if ($dob instanceof DateTime)
		return $dob->diff(new DateTime())->format('%y');

    //calculate years of age (input string: YYYY-MM-DD)
    list($year, $month, $day) = explode("-", $dob);

    $year_diff  = date("Y") - $year;
    $month_diff = date("m") - $month;
    $day_diff   = date("d") - $day;

    if ($month_diff < 0 || ($month_diff == 0 && $day_diff < 0))
        $year_diff--;

    return $year_diff;
}

function str_get_age($dob) {
	$age = get_age($dob);
	if (empty($age))
		return '';
	return $age.' Jahre';
}

function format_seconds($totalseconds) {
	$hours = 0;
	$minutes = 0;
	$seconds = 0;
	
	if ($totalseconds > 60) {
		$seconds = $totalseconds % 60;
		$totalMinutes = ($totalseconds - ($totalseconds % 60) ) / 60;
		
		if ($totalMinutes > 60) {
			$minutes = $totalMinutes % 60;
			$hours = ($totalMinutes - ($totalMinutes % 60) ) / 60;
		}
		else
			$minutes = $totalMinutes;
	}
	else
		$seconds = $totalseconds;


	if ($hours == 0 && $minutes == 0)
		return $seconds.'s';

	$result = ':'.str_pad($seconds, 2, '0', STR_PAD_LEFT);

	if ($hours == 0)
		return $minutes.$result.'m';

	return $hours.':'.str_pad($minutes, 2, '0', STR_PAD_LEFT).$result;
}

function how_long_ago($then) {
	if (is_empty($then))
		return '';
	$start_time = strtotime($then);
	return format_seconds(time() - $start_time);
}

function csv_to_array($filename='', $delimiter=';') {
	if (!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== false) {
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
			if (!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}



