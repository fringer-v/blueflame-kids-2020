<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Output Class
 *
 * This class, along with the 'class factory' method out() are used to safely generate
 * HTML code. It works as follows:
 *
 * - The first arg to out() is a format string, followed by any number of parameters
 * - The sequence: [] in the format string will be replaced by the corresponding parameter
 * - A parameter can either be a string or another instance of Output
 * - All string parameters will have special HTML characters escaped
 * - An Output instance can be printed by calling show()
 * - You can append further strings / instances of Output to an existing instance with
 *   the add() method
 *
 * The format string should always be a hardcoded, static string. 
 *
 * Note: if the result string is never requested from the Output instance (with html()),
 * the string will automatically be printed in the __destruct() method.
 *
 * This is so you can print directly by instantiating an instance of Output without assigning
 * it to a variable.
 * (ie. you can use out('hello world');, you dont need to do out('hello world')->show(); )
 * If you ever need to create an instance of Output that never gets used, make sure to
 * explicitly call the destroy() method;
 *
 */
class Output {
	private $output = "";
	private $used = false;
	
	//note that $format should always be a hardcoded, static string
	public function __construct($format, $params = array()) {
		if ($params == null) {
			$params = array();
		}
		else if (!is_array($params)) {
			// Treat a single non-array parameter as an array with one entry
			$params = array($params);
		}

		$fmts = explode("[]", $format);
		if (count($params) !== count($fmts) - 1) {
			fatal_error('Number of placeholders does not match number of parameters, format string: '.$format);
			return;
		}

		$this->output = $fmts[0];
		for ($i = 1; $i < count($fmts); $i++) {
			$param = $params[$i-1];
			if ($param instanceof Output)
				$param = $param->html();
			else
				$param = htmlspecialchars($param, ENT_QUOTES);
			$this->output .= $param;
			$this->output .= $fmts[$i];
		}
	}
	
	public function __destruct() {
		// if this instance has not been 'used', print it (this is so you can print
		// with out("text"); rather than typing out("text")->show(); every time)
		if (!$this->used)
			$this->show();
	}
	
	//destroy the object without printing it
	public function destroy() {
		$this->used = true;
		$this->__destruct();
	}
	
	// This way you can append instances of Output to strings
	public function __toString() {
		return $this->html();
	}

	//append strings or more output instances which will be printed after this one
	public function add(/*item1, item2, ....*/){
		foreach (func_get_args() as $item) {
			$this->output .= $item;
		}

		return $this;
	}
	
	//print
	public function show() {
		echo $this->html();
	}

	public function html() {
		$this->used = true;
		return $this->output;
	}
}

/*
 * note that $format should always be a hardcoded, static string
 * usage: 
 *		out($format [,$param1] [,$param2] ...);
 */
function out($format) {
	return new Output($format, array_slice(func_get_args(), 1));
}

function print_message_box($msg, $class) {
	if (gettype($msg) == "string")
		$msg = explode("\n", $msg);

	if (gettype($msg) == "array") {
		$out = div(array('class'=>'message-box '.$class));
		$i = 0;
		foreach ($msg as $m) {
			if ($i != 0)
				$out->add(br());
			$i++;
			$out->add($m);
		}
		$out->add(_div());
	}
	else
		$out = div(array('class'=>'message-box '.$class), $msg);
	return $out;
}

function print_error($message) {
	return print_message_box($message, "error-box");
}

function print_warning($message) {
	return print_message_box($message, "warning-box");
}

function print_success($message) {
	return print_message_box($message, "success-box");
}

function print_info($message) {
	return print_message_box($message, "info-box");
}

class Nix {
}

function nix() {
	return new Nix();
}

class Table {
	private $sql;
	private $sqlargs;
	private $attributes;
	private $page_url = '';
	private $per_page = 0;
	private $curr_page = 1;
	private $query = null;
	protected $order_by = null;

	public function __construct($sql = '', $sqlargs = array(), $attributes = array()) {
		$this->sql = $sql;
		$this->sqlargs = $sqlargs;
		$this->attributes = $attributes;
	}

	public function setPagination($page_url, $per_page, $curr_page = 1) {
		if (!str_startswith($this->sql, "SELECT SQL_CALC_FOUND_ROWS "))
			fatal_error('SQL must begin with SQL_CALC_FOUND_ROWS for pagination');
		if (str_contains($this->sql, "LIMIT"))
			fatal_error('SQL may not include LIMIT for pagination');
		$this->page_url = $page_url;
		$this->per_page = (integer) $per_page;
		$this->curr_page = (integer) $curr_page < 1 ? 1 : (integer) $curr_page;
	}
	
	public function setOrderBy($order_by) {
		$this->order_by = $order_by;
	}

	public function getTableAttributes() {
		return $this->attributes;
	}

	public function columnTitle($field) {
		return $field;
	}

	public function cellValue($field, $row) {
		return $row[$field];
	}

	private function doQuery() {
		if (!is_null($this->query))
			return;

		$cii =& get_instance();
		$cii->load->database();

		$sql = $this->sql;

		if (!empty($this->order_by))
			$sql .= ' ORDER BY '.$this->order_by;

		if ($this->per_page > 0) {
			$offset = $this->per_page * ($this->curr_page-1);
			$sql .= ' LIMIT '.$this->per_page.' OFFSET '.$offset;
		}

		$this->query = $cii->db->query($sql, $this->sqlargs);
	}

	public function html() {

		$this->doQuery();

		$fields = $this->query->list_fields();
		
		$out = table($this->getTableAttributes());
		$out->add(thead());
		$out->add(tr());
		$row_count = 0;
		foreach ($fields as $field) {
			$title = $this->columnTitle($field);
			if (!($title instanceof Nix)) {
				$row_count++;
				$out->add(th($title));
			}
		}
		$out->add(_tr());
		$out->add(_thead());

		$out->add(tbody());
		if ($row = $this->query->unbuffered_row('array')) {
			do {
				$out->add(tr());
				foreach ($fields as $field) {
					$value = $this->cellValue($field, $row);
					if (!($value instanceof Nix))
						$out->add(td($value));
				}
				$out->add(_tr());
			}
			while ($row = $this->query->unbuffered_row('array'));
		}
		else {
			$out->add(tr());
			$out->add(td(array('colspan'=>$row_count), 'Keine Daten gefunden'));
			$out->add(_tr());
		}
		$out->add(_tbody());
		$out->add(_table());
		return $out;
	}

	public function paginationHtml() {
		if (empty($this->per_page))
			return;

		$this->doQuery();

		$max_rows = (integer) db_1_value('SELECT FOUND_ROWS()');
		$max_page = (integer) (($max_rows + $this->per_page - 1) / $this->per_page);
		if ($this->curr_page > $max_page)
			$this->curr_page = $max_page;

		$out = div(array('class'=>'pagination-div'));
		$out->add(href(url($this->page_url.'1'), '|<'));
		$out->add(nbsp());
		$out->add(href(url($this->page_url.max(1, $this->curr_page-1)), '<'));
		for ($i = 1; $i <= $max_page; $i++) {
			$out->add(nbsp());
			if ($this->curr_page == $i)
				$out->add(b(href(url($this->page_url.$i), $i)));
			else
				$out->add(href(url($this->page_url.$i), $i));
		}
		$out->add(nbsp());
		$out->add(href(url($this->page_url.min($max_page, $this->curr_page+1)), '>'));
		$out->add(nbsp());
		$out->add(href(url($this->page_url.$max_page), '>|'));
		$out->add(_div());
		return $out;
	}

}

class AsyncLoader {
	private $id;
	private $page;
	private $params;

	public function __construct($id, $page, $params = array()) {
		$this->id = $id;
		$this->page = $page;
		$this->params = $params;
	}

	public function html() {
		//the div the table will be loaded into
		$out = div(array('id'=>$this->id), table(array('style'=>'width: 100%;'), tr(td('Loading...'))));
		$out->add($this->loadPageHtml());
		return $out;
	}

	function loadPageHtml() {
		$out = script();
		$out->add(out('function []() {', $this->id));
		$out->add(out('loadPage("[]", "[]"', $this->id, $this->page));
		foreach ($this->params as $param) {
			$out->add(out(', "[]"', $param));
		}
		$out->add(');} ');
		$out->add(out('[](); ', $this->id)); // Call the function for the first time!

		// Create triggers for parameters:
		foreach ($this->params as $param) {
			$out->add(out('$("#[]").keyup([]); ', $param, $this->id));
		}

		$out->add(_script());
		return $out;
	}
}


