<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function db_array_2($sql, $sqlargs = array()) {
	$cii =& get_instance();
	$cii->load->database();

	$query = $cii->db->query($sql, $sqlargs);
	$fields = $query->list_fields();
	$result = array();
	while ($row = $query->unbuffered_row('array')) {
		if (count($fields) == 1)
			$result[$row[$fields[0]]] = $row[$fields[0]];
		else
			$result[$row[$fields[0]]] = $row[$fields[1]];
	}
	return $result;
}

function db_1_value($sql, $sqlargs = array()) {
	$cii =& get_instance();
	$cii->load->database();

	$query = $cii->db->query($sql, $sqlargs);
	$row = $query->row_array();
	if (isset($row))
		return reset($row);
	return null;
}

// Returns an array or rows, indexed by the first column
function db_array_n($sql, $sqlargs = array()) {
	$cii =& get_instance();
	$cii->load->database();

	$query = $cii->db->query($sql, $sqlargs);
	$result = array();
	while ($row = $query->unbuffered_row('array')) {
		$result[reset($row)] = $row;
	}
	return $result;
}

// Return an array or rows
function db_row_array($sql, $sqlargs = array()) {
	$cii =& get_instance();
	$cii->load->database();

	$query = $cii->db->query($sql, $sqlargs);
	$result = array();
	while ($row = $query->unbuffered_row('array')) {
		$result[] = $row;
	}
	return $result;
}

