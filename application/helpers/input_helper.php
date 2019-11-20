<?php
defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set('CET');
setlocale(LC_ALL, 'de_DE.UTF8', 'de_DE', 'de', 'ge');

class Form {
	private $id;
	private $action;
	private $columns;
	private $attributes; // Assoc. array of attributes for the table
	private $disabled = false;
	private $hiddens = array();	
	private $fields = array();	
	private $buttons = array();	
	private $groups = array();	
	private $openned = false;

	public function __construct($id, $action, $columns = 1, $attributes = array()) {
		$this->id = $id;
		$this->action = $action;
		$this->columns = $columns;
		$this->attributes = $attributes;
	}

	public function getFormAttributes() {
		$attr = array('id'=>$this->id);
		if ($this->disabled)
			$attr['disabled'] = null;
		return $attr;
	}

	function addHidden($name, $default_value = '') {
		$field = hidden($name, $default_value);
		$field->setForm($this);
		$this->hiddens[$name] = $field;
		return $field;
	}

	function addField($label, $value = '') {
		$field = new OutputField();
		$field->setForm($this);
		$this->fields['$'.count($this->fields)] = array($label, $field);
		$field->setValue($value);
		return $field;
	}

	function addSpace() {
		$field = new OutputField();
		$field->setForm($this);
		$this->fields['$'.count($this->fields)] = array('', $field);
		return $field;
	}

	function addRow($value = '') {
		$field = new OutputField();
		$field->setForm($this);
		$this->fields['$'.count($this->fields)] = array('', $field);
		$field->setValue($value);
		$field->setFormat('colspan=*');
		return $field;
	}

	function addText($text) {
		$field = $this->addSpace();
		$field->setValue($text);
		return $field;
	}

	function addTextInput($name, $label, $default_value = '', $attributes = array()) {
		$field = new TextInput($name, $default_value, $attributes);
		$field->setForm($this);
		$this->fields[$name] = array($label, $field);
		return $field;
	}

	function addPassword($name, $label, $default_value = '', $attributes = array()) {
		$field = new Password($name, $default_value, $attributes);
		$field->setForm($this);
		$this->fields[$name] = array($label, $field);
		return $field;
	}

	function addTextArea($name, $label, $default_value = '', $attributes = array()) {
		$field = new TextArea($name, $default_value, $attributes);
		$field->setForm($this);
		$this->fields[$name] = array($label, $field);
		return $field;
	}
	
	function addSelect($name, $label, $values, $default_value = '', $attributes = array()) {
		$field = new Select($name, $values, $default_value, $attributes);
		$field->setForm($this);
		$this->fields[$name] = array($label, $field);
		return $field;
	}

	function addCheckbox($name, $label, $default_value = '', $attributes = array()) {
		$field = new Checkbox($name, $default_value, $attributes);
		$field->setForm($this);
		$this->fields[$name] = array($label, $field);
		return $field;
	}

	function addSubmit($name, $label, $attributes = array()) {
		$button = new Submit($name, $label, $attributes);
		$button->setForm($this);
		$this->buttons[$name] = $button;
		return $button;
	}

	function addButton($name, $label, $attributes = array()) {
		$button = new Button($name, $label, $attributes);
		$button->setForm($this);
		$this->buttons[$name] = $button;
		return $button;
	}

	public function disable() {
		$this->disabled = true;
	}

	private function getFields($group = '') {
		if (is_empty($group))
			 return $this->fields;
		return $this->groups[$group]['fields'];
	}
	
	private function getButtons($group = '') {
		if (is_empty($group))
			return $this->buttons;
		return $this->groups[$group]['buttons'];
	}

	// Turn the exiting fields and buttons into a group:
	public function createGroup($group) {
		foreach ($this->fields as $name => $field_info) {
			$field = $field_info[1];
			$field->setGroup($group);
		}
		$this->groups[$group] = array('fields'=>$this->fields, 'buttons'=>$this->buttons);
		$this->fields = array();
		$this->buttons = array();
	}

	public function getLabel($name, $quote = false, $group = '') {
		$fields = $this->getFields($group);
		$label = $fields[$name][0];
		if ($quote)
			return '"'.$label.'"';
		return $label;
	}

	public function getField($name, $group = '') {
		$fields = $this->getFields($group);
		if (array_key_exists($name, $fields))
			return $fields[$name][1];
		if (array_key_exists($name, $this->hiddens))
			return $this->hiddens[$name];
		return null;
	}

	public function setValues($values) {
		foreach ($this->hiddens as $name => $field) {
			if (isset($values[$name]))
				$field->setValue($values[$name]);
		}
		$this->setFields($this->fields, $values);
		foreach ($this->groups as $group)
			$this->setFields($group['fields'], $values);
	}

	private function setFields($fields, $values) {
		foreach ($fields as $name => $field_info) {
			if (isset($values[$name])) {
				$field = $field_info[1];
				$field->setValue($values[$name]);
			}
		}
	}

	// Return an array of error messages, if no error
	// occurs this returns an empty array.
	public function validate($group = '') {
		$errors = array();
		$fields = $this->getFields($group);
		foreach ($fields as $name => $field_info) {
			$label = $field_info[0];
			$field = $field_info[1];
			$error = $field->validate($this);
			if (!is_empty($error))
				$errors[] = $error;
		}
		return $errors;
	}

	public function open() {
		$attr = $this->getFormAttributes();
		$attr['action'] = $this->action;
		$attr['method'] = 'POST';
		form($attr); 

		foreach ($this->hiddens as $hidden) {
			if (!$hidden->hidden)
				$hidden->show();
		}
		
		$this->openned = true;
	}

	public function close() {
		_form();
	}

	public function show($group = '') {
		$openned = $this->openned;

		if (!$openned)
			$this->open();

		$fields = $this->getFields($group);
		$buttons = $this->getButtons($group);
		if (!is_empty($fields) || !is_empty($buttons)) {
			table($this->attributes);
			
			if (!is_empty($fields)) {
				tr();
				$cols = 0;
				$fields_shown = 0;
				$start_row = false;
				foreach ($fields as $name => $field_info) {
					$label = $field_info[0];
					$field = $field_info[1];

					if ($field->hidden)
						continue;

					if ($this->disabled)
						$field->disable();

					if ($start_row) {
						tr();
						$start_row = false;
					}

					$colspan = 1;
					$haslabel = true;
					$formats = explode(';', $field->format);
					foreach ($formats as $format) {
						if ($format == "nolabel")
							$haslabel = false;
						else if (str_startswith($format, 'colspan=')) {
							$colspan = (integer) str_right($format, 'colspan=');
							if ($colspan == '*')
								$colspan = $this->columns;
							else if ($colspan <= 0)
								$colspan = 1;
						}
					}

					if (!$haslabel || is_empty($label)) {
						td(array('colspan'=>$colspan*2));
						$field->show();
						_td();
					}
					else if ($field instanceof Checkbox) {
						td(array('colspan'=>$colspan*2));
						$field->show();
						label(array('for'=>$name), ' '.$label);
						_td();
					}
					else {
						th(label(array('for'=>$name), $label.':'));
						if ($colspan*2-1 != 1)
							td(array('colspan'=>$colspan*2-1));
						else
							td();
						$field->show();
						_td();
					}
			
					$cols += $colspan;
					$fields_shown++;
			
					//if we have reached the end of this 'row' of the form
					if ($cols >= $this->columns) {
						_tr();
						$cols = 0;
						//open a new row if there are more fields to come
						if ($fields_shown < count($fields))
							$start_row = true;
					}
				}
				// Add blank table cells until we complete this row of the table
				if ($cols < $this->columns && $cols != 0) {
					while ($cols < $this->columns) {
						th(nbsp());
						td(nbsp());
						$cols++;
					}
					_tr();
				}
			}

			if (!is_empty($buttons)) {
				$attr = array('colspan'=>$this->columns*2, 'class'=>'button-row');
				$i = 0;
				$start_row = true;
				foreach ($buttons as $button) {
					if ($button->hidden)
						continue;
					if ($start_row) {
						tr();
						td($attr);
						$start_row = false;
					}
					if ($i != 0)
						nbsp();
					$i++;
					if ($this->disabled)
						$button->disable();
					$button->show();
				}
				if (!$start_row) {
					_td();
					_tr();
				}
			}

			_table();
		}
		
		if (!$openned)
			$this->close();
	}
}

class InputField {
	public $name; // Name and ID of the field
	public $default_value;
	protected $attributes; // Assoc. array of attributes
	protected $disabled = false;
	public $hidden = false;
	protected $form = null;
	protected $rules = '';
	public $format = '';
	public $group = '';
	
	public function __construct($name = '', $default_value = '', $attributes = array()) {
		$this->name = $name;
		$this->default_value = ($default_value instanceof Output) ? $default_value->html() : $default_value;
		$this->attributes = $attributes;

		if (!is_array($attributes))
			fatal_error('InputField attributes, must be an array');
	}

	public function setForm($form) {
		$this->form = $form;
	}

	public function setGroup($group) {
		$this->group = $group;
	}

	public function getLabel($quote = false) {
		if (is_null($this->form))
			return '';
		return $this->form->getLabel($this->name, $quote, $this->group);
	}

	public function addAttribute($name, $value = null) {
		$this->attributes[$name] = $value;
	}
	
	public function getAttributes($type, $include_value = true) {
		if (is_empty($this->name))
			$attr = array();
		else
			$attr = array('name'=>$this->name, 'id'=>$this->name);
		if (!is_empty($type))
			$attr['type'] = $type;
		if ($include_value)
			$attr['value'] = $this->getValue();
		if ($this->disabled)
			$attr['disabled'] = null;
		$attr = array_merge($attr, $this->attributes);
		return $attr;
	}

	public function submitted() {
		if (is_empty($this->name))
			return false;
		if (isset($_POST[$this->name]))
			return true;
		if (isset($_GET[$this->name]))
			return true;
		return false;
	}

	public function setValue($value = '') {
		if (!is_empty($this->name)) {
			if (isset($_POST[$this->name]))
				unset($_POST[$this->name]);
			if (isset($_GET[$this->name]))
				unset($_GET[$this->name]);
			if (isset($_SESSION[$this->name]))
				$_SESSION[$this->name] = $value;
		}
		$this->default_value = $value;
	}

	public function getValue() {
		if (is_empty($this->name))
			return $this->default_value;

		if (isset($_POST[$this->name])) {
			$value = $_POST[$this->name];
			return $value;
		}

		if (isset($_GET[$this->name])) {
			$value = $_GET[$this->name];
			return $value;
		}

		if (isset($_SESSION[$this->name]))
			return $_SESSION[$this->name];

		return $this->default_value;
	}

	public function getDate($fmt = '') {
		$val = $this->getValue();
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
		if (!is_empty($fmt))
			return $ts->format($fmt);
		return $ts;
	}

	public function disable() {
		$this->disabled = true;
	}

	public function hide() {
		$this->hidden = true;
	}

	public function show() {
		$this->html()->show();
	}

	public function html() {
	}

	public function __toString() {
		return $this->html()->html();
	}

	public function setRule($rules) {
		$this->rules = $rules;
	}

	public function setFormat($format) {
		$this->format = $format;
	}

	public function persistent() {
		if (!is_empty($this->name)) {
			$value = $this->getValue();
			$_SESSION[$this->name] = $value;
		}
	}

	public function validate($form) {
		$rules = explode('|', $this->rules);
		$value = $this->getValue();
		$error = '';
		foreach ($rules as $rule) {
			if (str_startswith($rule, 'required')) {
				if (is_empty($value))
					$error = $this->getLabel(true).' muss vorhanden sein';
			}
			else if (str_startswith($rule, 'is_number')) {
				if (!is_numeric($value) || ((integer) $value) <= 0)
					$error = $this->getLabel(true).' muss eine Zahl sein';
			}
			else if (str_startswith($rule, 'is_unique')) {
				$cii =& get_instance();
				$cii->load->database();

				$arg = str_left(str_right($rule, '['), ']');
				$dots = explode('.', $arg);
				$table = $dots[0];
				$column = $dots[1];
				$id = $dots[2];
				$sql = 'SELECT COUNT(*) AS count FROM '.$table.' WHERE '.$column.' = ?';
				if (!is_empty($id))
					$sql .= ' AND '.$id.' != ?';
				$query = $cii->db->query($sql, array($value, $form->getField($id)->getValue()));
				$row = $query->row_array()['count'];
				if ($row != 0)
					$error = $this->getLabel(true).' muss eindeutig sein';
			}
			else if (str_startswith($rule, 'matches')) {
				$arg = str_left(str_right($rule, '['), ']');
				if ($value != $form->getField($arg)->getValue())
					$error = $this->getLabel(true).' ist nicht gleich '.$form->getLabel($arg, true);
			}
			else if (str_startswith($rule, 'is_valid_date')) {
				if (!is_empty($value)) {
					if (date_create_from_format('d.m.Y', $value) === false)
						$error = $this->getLabel(true).' ist kein gültiges Datum';
				}
			}
			else if (str_startswith($rule, 'maxlength')) {
				$arg = str_left(str_right($rule, '['), ']');
				if (strlen($value) > $arg)
					$error = $this->getLabel(true)." darf nicht länger als $arg Zeichen sein";
			}
			if (!is_empty($error))
				break;		
		}
		return $error;
	}
}

class Submit extends InputField {
	public function html() {
		if ($this->hidden)
			return out('');
		return tag('input', $this->getAttributes('submit'));
	}
}

class Button extends InputField {
	public function html() {
		if ($this->hidden)
			return out('');
		return tag('input', $this->getAttributes('button'));
	}
}

class Hidden extends InputField {
	public function html() {
		if ($this->hidden)
			return out('');
		return tag('input', $this->getAttributes('hidden'));
	}
}

class OutputField extends InputField {
	public function __construct($default_value = '') {
		parent::__construct('', $default_value);
	}

	public function html() {
		if ($this->hidden)
			return out('');
		return out($this->default_value);
	}
}

class TextInput extends InputField {
	public function html() {
		if ($this->hidden)
			return out('');
		$out = tag('input', $this->getAttributes('text'));
		return $out;
	}
}

class Password extends InputField {
	public function html() {
		if ($this->hidden)
			return out('');
		return tag('input', $this->getAttributes('password'));
	}
}

class TextArea extends InputField {
	public function html() {
		if ($this->hidden)
			return out('');
		$out = tag('textarea', $this->getAttributes('', false));
		$out->add(out('[]', $this->getValue()));
		$out->add(_tag('textarea'));
		return $out;
	}
}

class Select extends TextArea {
	private $values = array();

	public function __construct($name = '', $values = array(), $default_value = '', $attributes = array()) {
		$this->values = $values;
		parent::__construct($name, $default_value, $attributes);
	}

	public function html() {
		if ($this->hidden)
			return out('');
		$current_value = $this->getValue();
		$out = tag('select', $this->getAttributes('', true));
		foreach ($this->values as $value => $text) {
			$attr = array('value'=>$value);
			if ($value == $current_value)
				$attr['selected'] = null;
			$out->add(tag('option', $attr, $text));
		}
		$out->add(_tag('select'));
		return $out;
	}
}

class Checkbox extends InputField {
	public function html() {
		if ($this->hidden)
			return out('');
		$out = tag('input', array('type'=>'hidden', 'name'=>$this->name, 'value'=>'0'));
		$attr = $this->getAttributes('checkbox', false);
		$attr['value'] = '1';
		$value = $this->getValue();
		if (!is_empty($value))
			$attr['checked'] = null;
		$out->add(tag('input', $attr));
		return $out;
	}
}

function submit($name, $label, $attributes = [])
{
	return new Submit($name, $label, $attributes);
}

function select($name, $values, $default_value = '', $attributes = [])
{
	return new Select($name, $values, $default_value, $attributes);
}

function hidden($name, $default_value = '')
{
	return new Hidden($name, $default_value);
}
