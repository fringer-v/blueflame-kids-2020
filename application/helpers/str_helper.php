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
	
// This function fixes a bug that empty() has with the results of function calls
function is_empty($val) {
	if (!is_array($val))
		$val = trim($val);
	return empty($val);
}

function is_not_empty($val) {
	return !is_empty($val);
}

function if_empty($val, $def) {
	if (empty($val))
		return $def;
	return $val;
}

function arr_remove_empty($array) {
	return array_filter($array, "is_not_empty");
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
