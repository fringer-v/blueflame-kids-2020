<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

define('MAX_PARTICIPANTS', 5);

class Registration extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function prompted_login($pwd) {
		if ($this->is_logged_in())
			$staff_row = $this->get_staff_row($this->session->stf_login_id);
		else {
			$staff_row = $this->get_staff_row_by_username('Registrierung');
			if (is_empty($staff_row)) {
				$data = array(
					'stf_username' => 'Registrierung',
					'stf_fullname' => 'Registrierung',
					'stf_role' => '',
					'stf_loginallowed' => true,
					'stf_technician' => false,
					'stf_password' => password_hash(strtolower(md5('bf2020129-3026-19-2089')), PASSWORD_DEFAULT)
				);
				$this->db->insert('bf_staff', $data);
				$staff_row = $this->get_staff_row_by_username('Registrierung');
			}
		}
		$pwd = md5($pwd.'129-3026-19-2089');
		if (!password_verify($pwd, $staff_row['stf_password']))
			return false;
		$this->set_logged_in($staff_row);
		$this->stf_login_id = $this->session->stf_login_id;
		$this->stf_login_name = $this->session->stf_login_name;
		return true;
	}

	public function index()
	{
		$this->header('iPad Registrierung', false);

		$reg_top_form = new Form('reg_top_form', 'registration', 2, array('class'=>'input-table'));
		$reg_back = $reg_top_form->addHidden('reg_back');
		if (!empty($reg_back->getValue())) {
			if ($this->prompted_login($reg_back->getValue())) {
				if ($this->session->ses_prev_page == "registration")
					redirect("participant");
				redirect($this->session->ses_prev_page);
			}
			redirect("registration");
		}
		$reg_login = $reg_top_form->addHidden('reg_login');
		if (!empty($reg_login->getValue())) {
			$this->prompted_login($reg_login->getValue());
			redirect('registration');
		}

		$reg_part = in('reg_part', 1);
		$reg_part->persistent();

		$reg_participants = in('reg_participants', [ ]);
		$reg_participants->persistent();

		$reg_supervision = in('reg_supervision', [ ]);
		$reg_supervision->persistent();

		$reg_top_form->open();
		table([ 'style'=>'width: 100%;' ]);
		tr([ 'class'=>'topnav' ]);
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		td([ 'style'=>'width: 200px; text-align: left; padding: 4px 2px;' ]);
		button('back', img([ 'src'=>'../img/bf-kids-logo3.png', 'style'=>'height: 34px; width: auto;']),
			[ 'class'=>'button-box', 'style'=>'border: 1px solid #ffbd4d; height: 40px; font-size: 18px;', 'onclick'=>'do_back(); return false;' ]);
		_td();
		td([ 'style'=>'text-align: center; padding: 4px 2px;' ]);
		button('reload', img([ 'src'=>'../img/reload.png',
			'style'=>'height: 28px; width: auto; position: relative; bottom: -3px; left: -1px']),
			[ 'class'=>'button-box button-lightgrey', 'style'=>'height: 40px; font-size: 18px;', 'onclick'=>'do_reload(); return false;' ]);
		_td();
		td([ 'style'=>'width: 200px; text-align: right; padding: 4px 0px;' ]);
		button('complete', 'Abschließen',
			[ 'class'=>'button-box', 'style'=>'border: 1px solid #ffbd4d; height: 40px; font-size: 18px;', 'onclick'=>'do_complete(); return false;' ]);
		_td();
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		_tr();
		_table();
		$reg_top_form->close();

		if ($this->is_logged_in())
			tag('iframe', [ 'id'=>'content-iframe', 'src'=>'registration/iframe', 'style'=>'width: 100%; height: 400px; border: 0;' ], '');

		script();
		out('
			function do_login_prompt(user) {
				return prompt("Bitte geben Sie das Passwort für "+user+" ein:", "");
			}
			function do_login() {
				do {
					password = do_login_prompt("Registrierung");
				}
				while (password == null);
				$("#reg_login").val(password);
				$("#reg_top_form").submit();
			}
			function do_back() {
				password = do_login_prompt("'.$this->stf_login_name.'");
				if (!password)
					return;
				$("#reg_back").val(password);
				$("#reg_top_form").submit();
			}
			function do_reload() {
				var content = $("#content-iframe").contents();
				var form = content.find("#reg_iframe_form");
				if (form == null || form.length == 0)
					$("#content-iframe").attr("src", "registration/iframe");
				else
					form.submit();
			}
			function do_complete() {
				var content = $("#content-iframe").contents();
				var stat_rest = parseInt(content.find("#reg_before").val().split("|")[0]);
				var stat_part = 0;
				if (stat_rest) {
					var reg_now = content.find("#prt_firstname").val()+"|"+content.find("#prt_lastname").val()+"|"+
						content.find("#prt_birthday").val()+"|"+content.find("#prt_supervision_firstname").val()+"|"+
						content.find("#prt_supervision_lastname").val()+"|"+content.find("#prt_supervision_cellphone").val()+"|"+
						content.find("#prt_notes").val();
					stat_part = iPadStatus(content.find("#reg_before").val(), reg_now);
				}
				if (!stat_rest || stat_part == 2 || stat_part == 4) {
					if (!confirm("Wollen Sie wirklich die Registrierung abschließen, nicht alle Eingaben sind abgeschlossen?"))
						return;
				}

				content.find("#reg_complete").val(1);
				content.find("#reg_iframe_form").submit();
			}
		');
		if (!$this->is_logged_in())
			out('document.getElementsByTagName("body").addEventListener("load", setTimeout(do_login, 500));');
		_script();

		$this->footer();
	}

	private function rows_equal($r1, $r2) {
		if ($r1['prt_firstname'] != $r2['prt_firstname'])
			return false;
		if ($r1['prt_lastname'] != $r2['prt_lastname'])
			return false;
		if ($r1['prt_birthday'] != $r2['prt_birthday'])
			return false;
		if ($r1['prt_notes'] != $r2['prt_notes'])
			return false;
		if (arr_nvl($r1, 'prt_supervision_firstname', '') != $r2['prt_supervision_firstname'])
			return false;
		if (arr_nvl($r1, 'prt_supervision_lastname', '') != $r2['prt_supervision_lastname'])
			return false;
		if (arr_nvl($r1, 'prt_supervision_cellphone', '') != $r2['prt_supervision_cellphone'])
			return false;
		return true;
	}

	private function link($reg_part, $i)
	{
		$attr = [ 'class'=>'menu-item', 'onclick'=>'$("#reg_set_part").val('.$i.'); $("#reg_iframe_form").submit();' ];
		if ($reg_part == $i)
			$attr['selected'] = null;
		return $attr;
	}

	private function get_reg_status($edit_part)
	{
		if (empty($edit_part['prt_firstname'])) {
			if (!empty($edit_part['prt_birthday']))
				return 2;
			return 1;
		}

		if (empty($edit_part['prt_lastname']))
			return 2;

		$db_part = $this->get_participant_row_by_name($edit_part['prt_firstname'], $edit_part['prt_lastname']);
		if (empty($db_part)) {
			return 2;
		}

		if ($db_part['prt_registered'] == REG_NO && empty($db_part['prt_group_number'])) {
			if ($this->rows_equal($edit_part, $db_part))
				return 3;
			return 4;
		}

		return 5;
	}
	
	public function update_supervisor($reg_participants_v, $reg_part_v, $reg_supervision_v)
	{
		for ($i=1; $i<=count($reg_participants_v); $i++) {
			$prt_id = $reg_participants_v[$i]['prt_id'];
			if ($i != $reg_part_v && !empty($prt_id))  {
				$db_part = $this->get_participant_row($prt_id);
				$edit_part = $reg_participants_v[$i] + $reg_supervision_v;
				$this->modify_participant($prt_id, $db_part, $edit_part, false);
			}
		}
	}

	public function any_empty($part_row)
	{
		return empty($part_row['prt_firstname']) ||
			empty($part_row['prt_lastname']) ||
			empty($part_row['prt_birthday']) ||
			empty($part_row['prt_supervision_firstname']) ||
			empty($part_row['prt_supervision_lastname']) ||
			empty($part_row['prt_supervision_cellphone']);
	}

	public function set_default_lastname($reg_participants, $reg_participants_v, $reg_part_v, $participant_empty_row)
	{
		if (!isset($reg_participants_v[$reg_part_v]))
			$reg_participants_v[$reg_part_v] = $participant_empty_row;

		if (empty($reg_participants_v[$reg_part_v]['prt_lastname'])) {
			// Set child default surname:
			$last_name = '';
			for ($i=count($reg_participants_v); $i>=1; $i--) {
				if ($i != $reg_part_v && !empty($reg_participants_v[$i]['prt_lastname']))  {
					$last_name = $reg_participants_v[$i]['prt_lastname'];
					break;
				}
			}
			$reg_participants_v[$reg_part_v]['prt_lastname'] = $last_name;
		}

		// Remove extras:
		for ($i=count($reg_participants_v); $i>$reg_part_v; $i--) {
			if (empty($reg_participants_v[$i]['prt_firstname']) &&
				empty($reg_participants_v[$i]['prt_birthday']) &&
				empty($reg_participants_v[$i]['prt_notes']))
				$reg_participants_v[$i] = $participant_empty_row;
		}

		$reg_participants->setValue($reg_participants_v);
	}

	public function iframe()
	{
		if (!$this->authorize('registration'))
			return;

		$read_only = !is_empty($this->session->stf_login_tech);
		if ($read_only)
			return;

		$this->header('iPad Registrierung', false);
		
		$reg_part = in('reg_part', 1);
		$reg_part->persistent();

		$reg_participants = in('reg_participants', [ ]);
		$reg_participants->persistent();

		$reg_supervision = in('reg_supervision', [ ]);
		$reg_supervision->persistent();

		$participant_empty_row = [ 'prt_id'=>0, 'prt_firstname'=>'', 'prt_lastname'=>'', 'prt_birthday'=>'', 'prt_notes'=>'' ];

		$reg_part_v = $reg_part->getValue();
		$reg_participants_v = $reg_participants->getValue();
		$reg_supervision_v = if_empty($reg_supervision->getValue(),
			[ 'prt_supervision_firstname'=>'', 'prt_supervision_lastname'=>'', 'prt_supervision_cellphone'=>'' ]);

		if (isset($_POST['prt_firstname']) &&
			isset($_POST['prt_lastname']) &&
			isset($_POST['prt_birthday']) &&
			isset($_POST['prt_notes']) &&
			isset($_POST['prt_supervision_firstname']) &&
			isset($_POST['prt_supervision_lastname']) &&
			isset($_POST['prt_supervision_cellphone'])) {
			// Save the POST data:
			// Don't allow the name to be set to a name we already have!:
			$found = false;
			for ($i=1; $i<=count($reg_participants_v); $i++) {
				if ($i != $reg_part_v &&
					strtolower($reg_participants_v[$i]['prt_firstname']) == strtolower(trim($_POST['prt_firstname'])) &&
					strtolower($reg_participants_v[$i]['prt_lastname']) == strtolower(trim($_POST['prt_lastname']))) {
					$found = true;
					break;
				}
			}
			if ($found) {
				if (!isset($reg_participants_v[$reg_part_v]['prt_firstname'])) {
					$reg_participants_v[$reg_part_v]['prt_firstname'] = '';
					$_POST['prt_firstname'] = '';
				}
				if (!isset($reg_participants_v[$reg_part_v]['prt_lastname'])) {
					$reg_participants_v[$reg_part_v]['prt_lastname'] = '';
					$_POST['prt_lastname'] = '';
				}
			}
			else {
				$reg_participants_v[$reg_part_v]['prt_firstname'] = trim($_POST['prt_firstname']);
				$reg_participants_v[$reg_part_v]['prt_lastname'] = trim($_POST['prt_lastname']);
			}
			$reg_participants_v[$reg_part_v]['prt_birthday'] = trim($_POST['prt_birthday']);
			$reg_participants_v[$reg_part_v]['prt_notes'] = trim($_POST['prt_notes']);
			$reg_participants->setValue($reg_participants_v);
			$reg_supervision_v['prt_supervision_firstname'] = trim($_POST['prt_supervision_firstname']);
			$reg_supervision_v['prt_supervision_lastname'] = trim($_POST['prt_supervision_lastname']);
			$reg_supervision_v['prt_supervision_cellphone'] = trim($_POST['prt_supervision_cellphone']);
			$reg_supervision->setValue($reg_supervision_v);
		}

		$reg_iframe_form = new Form('reg_iframe_form', 'iframe', 2, array('class'=>'input-table'));
		$reg_before = $reg_iframe_form->addHidden('reg_before');
		$reg_complete = $reg_iframe_form->addHidden('reg_complete');
		$reg_set_part = $reg_iframe_form->addHidden('reg_set_part');

		if ($reg_complete->getValue() == 1) {
			$reg_part->setValue(1);
			$reg_participants->setValue([ ]);
			$reg_supervision->setValue([ ]);
			redirect("registration/iframe");
		}

		$reg_set_part_v = $reg_set_part->getValue();
		if ($reg_set_part_v < 0 && $reg_set_part_v > MAX_PARTICIPANTS)
			$reg_set_part_v = 0;

		$edit_part = arr_nvl($reg_participants_v, $reg_part_v, $participant_empty_row) + $reg_supervision_v;

		if (!empty($reg_set_part_v) && $this->any_empty($edit_part)) {
			// May leave a partially empty tab:
			$reg_part->setValue($reg_set_part_v);
			$this->set_default_lastname($reg_participants, $reg_participants_v, $reg_set_part_v, $participant_empty_row);
			redirect("registration/iframe");
		}

		$prt_firstname = textinput('prt_firstname', $edit_part['prt_firstname'],
			[ 'placeholder'=>'Vorname', 'style'=>'width: 160px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_firstname->setFormat([ 'clear-box'=>true ]);
		$prt_firstname->setRule('required');
		$prt_lastname = textinput('prt_lastname', $edit_part['prt_lastname'],
			[ 'placeholder'=>'Nachname', 'style'=>'width: 220px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_lastname->setFormat([ 'clear-box'=>true ]);
		$prt_lastname->setRule('required');
		$prt_birthday = new NumericField('prt_birthday', $edit_part['prt_birthday'],
			[ 'placeholder'=>'DD.MM.JJJJ', 'style'=>'font-family: Monospace; width: 120px;', 'onkeyup'=>'dateChanged($(this));' ]);
		$prt_birthday->setFormat([ 'clear-box'=>true ]);
		$prt_birthday->setRule('is_valid_date', 'Geburtstag');

		$prt_supervision_firstname = textinput('prt_supervision_firstname', $edit_part['prt_supervision_firstname'],
			[ 'placeholder'=>'Vorname', 'style'=>'width: 160px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_supervision_firstname->setFormat([ 'clear-box'=>true ]);
		$prt_supervision_lastname = textinput('prt_supervision_lastname', $edit_part['prt_supervision_lastname'],
			[ 'placeholder'=>'Nachname', 'style'=>'width: 220px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_supervision_lastname->setFormat([ 'clear-box'=>true ]);
		$prt_supervision_cellphone = new NumericField('prt_supervision_cellphone', $edit_part['prt_supervision_cellphone'],
			[ 'style'=>'width: 220px; font-family: Monospace;' ]);
		$prt_supervision_cellphone->setFormat([ 'clear-box'=>true ]);

		$prt_notes = textarea('prt_notes', $edit_part['prt_notes'], [ 'style'=>'width: 98%;' ]);

		$register = button('register', 'Registrieren', [ 'class'=>'button-box button-green', 'style'=>'width: 100%; height: 48px; font-size: 24px;' ]);
		$register->disable();

		if ($register->submitted() || !empty($reg_set_part_v)) {
			$this->error = $prt_birthday->validate();
			if (empty($this->error)) {
				$bday = str_to_date($prt_birthday->getValue());
				if (empty($bday))
					$this->error = "Geburtstag ist kein gültiges Datum";
				else {
					$year = (integer) $bday->format('Y');
					if ($year < 2003 || $year > 2017)
						$this->error = "Geburtstag ist kein gültiges Datum";
				}
			}
			if (empty($this->error)) {
				$reg_participants_v[$reg_part_v]['prt_birthday'] = $bday->format('d.m.Y');
				$reg_participants->setValue($reg_participants_v);
				$edit_part = arr_nvl($reg_participants_v, $reg_part_v, $participant_empty_row) + $reg_supervision_v;
				if (!empty($edit_part['prt_firstname']) && !empty($edit_part['prt_lastname'])) {
					$db_part = $this->get_participant_row_by_name($edit_part['prt_firstname'], $edit_part['prt_lastname']);
					if (!empty($edit_part['prt_id'])) {
						$db_part_by_id = $this->get_participant_row($edit_part['prt_id']);
						if (empty($db_part))
							// Name was not found, change the name...
							$db_part = $db_part_by_id;
						else {
							if ($db_part['prt_id'] != $db_part_by_id['prt_id']) {
								// Revert name completely:
								$this->error = $edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' ist bereits registriert';
								$_POST['prt_firstname'] = $db_part_by_id['prt_firstname'];
								$_POST['prt_lastname'] = $db_part_by_id['prt_lastname'];
								$edit_part['prt_firstname'] = $db_part_by_id['prt_firstname'];
								$edit_part['prt_lastname'] = $db_part_by_id['prt_lastname'];
								$reg_participants_v[$reg_part_v]['prt_firstname'] = $db_part_by_id['prt_firstname'];
								$reg_participants_v[$reg_part_v]['prt_lastname'] = $db_part_by_id['prt_lastname'];
								$reg_participants->setValue($reg_participants_v);
								goto end_of_edit;
							}
						}
					}
					unset($edit_part['prt_id']);
					if (empty($db_part)) {
						$prt_id_v = $this->insert_participant($edit_part, false);
						if (!empty($prt_id_v)) {
							$reg_participants_v[$reg_part_v]['prt_id'] = $prt_id_v;
							$reg_participants->setValue($reg_participants_v);
							$this->update_supervisor($reg_participants_v, $reg_part_v, $reg_supervision_v);
							$this->setSuccess($edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' registriert');
							// Move to next tab:
							$reg_part_v++;
							if ($reg_part_v > MAX_PARTICIPANTS)
								$reg_part_v = MAX_PARTICIPANTS;
							$reg_part->setValue($reg_part_v);
							$this->set_default_lastname($reg_participants, $reg_participants_v, $reg_part_v, $participant_empty_row);
							if (!empty($reg_set_part_v)) {
								$reg_part->setValue($reg_set_part_v);
								$this->set_default_lastname($reg_participants, $reg_participants_v,
									$reg_set_part_v, $participant_empty_row);
							}
							redirect("registration/iframe");
						}
						else {
							$reg_participants_v[$reg_part_v]['prt_id'] = 0;
							$reg_participants->setValue($reg_participants_v);
						}
					}
					else {
						if (!empty($reg_set_part_v) && $this->rows_equal($edit_part, $db_part)) {
							// No change, just change tab:
							$reg_part->setValue($reg_set_part_v);
							$this->set_default_lastname($reg_participants, $reg_participants_v,
							$reg_set_part_v, $participant_empty_row);
							redirect("registration/iframe");
						}
						if ($db_part['prt_registered'] == REG_NO && empty($db_part['prt_group_number'])) {
							$this->modify_participant($db_part['prt_id'], $db_part, $edit_part, false);
							$reg_participants_v[$reg_part_v]['prt_id'] = $db_part['prt_id'];
							$reg_participants->setValue($reg_participants_v);
							$this->update_supervisor($reg_participants_v, $reg_part_v, $reg_supervision_v);
							$this->setSuccess($edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' geändert');
							if (!empty($reg_set_part_v)) {
								$reg_part->setValue($reg_set_part_v);
								$this->set_default_lastname($reg_participants, $reg_participants_v,
									$reg_set_part_v, $participant_empty_row);
							}
							redirect("registration/iframe");
						}
						else {
							$reg_participants_v[$reg_part_v]['prt_id'] = 0;
							$reg_participants->setValue($reg_participants_v);
							$this->error = $edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' ist bereits angemeldet';
						}
					}
					end_of_edit:;
				}
			}
		}

		$reg_iframe_form->open();

		div(array('class'=>'topnav'));
		table();
		tr();
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		$stat_part = 1;
		$stat_rest = 1;
		for ($i=1; $i<=MAX_PARTICIPANTS; $i++) {
			$attr = [ 'id'=>'reg_status_'.$i, 'style'=>'width: 100%;' ];
			$status = $this->get_reg_status(arr_nvl($reg_participants_v, $i, $participant_empty_row) + $reg_supervision_v);
			switch ($status) {
				case 1: $box = div($attr + [ 'class'=>'grey-box' ], nbsp()); break;
				case 2: $box = div($attr + [ 'class'=>'yellow-box' ], 'Wird registriert'); break;
				case 3: $box = div($attr + [ 'class'=>'green-box' ], 'Registriert'); break;
				case 4: $box = div($attr + [ 'class'=>'yellow-box' ], 'Wird geändert'); break;
				case 5: $box = div($attr + [ 'class'=>'red-box' ], 'Angemeldet'); break;
			}
			td([ 'width'=>(100/MAX_PARTICIPANTS).'%', 'style'=>'height: 22px; padding: 0px 2px 5px 2px;' ], $box);
			td([ 'width'=>(100/MAX_PARTICIPANTS).'%', 'style'=>'width: 3px; padding: 0;' ], nbsp());
			if ($reg_part_v == $i)
				$stat_part = $status;
			else if ($status == 2 || $status == 4)
				$stat_rest = 0;
				
		}
		$before = $stat_rest.'|'.$stat_part.'|'.$prt_firstname->getValue().'|'. $prt_lastname->getValue().'|'.
			$prt_birthday->getValue().'|'.$prt_supervision_firstname->getValue().'|'.$prt_supervision_lastname->getValue().'|'.
			$prt_supervision_cellphone->getValue().'|'.$prt_notes->getValue();
		$reg_before->setValue($before);

		_tr();
		_tr();
		tr([ 'style'=>'border-bottom: 1px solid black; padding: 8px 16px;' ]);
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		for ($i=1; $i<=MAX_PARTICIPANTS; $i++) {
			$part_row = arr_nvl($reg_participants_v, $i, $participant_empty_row);
			$fname = arr_nvl($part_row, 'prt_firstname', '');
			$lname = arr_nvl($part_row, 'prt_lastname', '');
			if (empty($fname)) {
				$fname = $lname;
				$lname = '';
			}
			if (!empty($fname)) {
				if (strlen($fname) + strlen($lname) > 14) {
					if (empty($lname))
						$tab_title = substr($fname, 0, 12).'...';
					else {
						if (strlen($fname) <= 12)
							$tab_title = $fname.' '.substr($lname, 0, 1).".";
						else
							$tab_title = substr($fname, 0, 9).'... '.substr($lname, 0, 1).".";
					}
				}
				else
					$tab_title = $fname.' '.$lname;
			}
			else
				$tab_title = 'Kind '.$i;
			td([ 'id'=>'reg_tab_'.$i, 'width'=>(100/MAX_PARTICIPANTS).'%' ] + $this->link($reg_part_v, $i), $tab_title);
			td([ 'width'=>(100/MAX_PARTICIPANTS).'%', 'style'=>'width: 3px; padding: 0;' ], nbsp());
		}
		_tr();
		_table();
		_div();

		table([ 'class'=>'ipad-table', 'style'=>'padding-top: 2px;' ]);
		tr();
		td(nbsp().b('Kind '.$reg_part_v.':'));
		td(nbsp().b('Begleitperson:'));
		_tr();
		tr();
		td();
			table([ 'style'=>'border: 1px solid black; border-collapse: separate; border-spacing: 5px;' ]);
			tr();
			td($prt_firstname);
			td($prt_lastname);
			_tr();
			tr();
			td([ 'style'=>'text-align: right;' ], 'Geburtstag:');
			td($prt_birthday);
			_tr();
			_table();
		_td();
		td();
			table([ 'style'=>'border: 1px solid black; border-collapse: separate; border-spacing: 5px;' ]);
			tr();
			td($prt_supervision_firstname);
			td($prt_supervision_lastname);
			_tr();
			tr();
			td([ 'style'=>'text-align: right;' ], 'Handy-Nr:');
			td($prt_supervision_cellphone);
			_tr();
			_table();
		_td();
		_tr();
		tr(td(nbsp().'Allergien und andere Besonderheiten des Kindes:'));
		tr();
		td([ 'colspan'=>2 ]);
			table([ 'style'=>'width: 100%;' ]);
			tr();
			td([ 'style'=>'width: 75%;' ], $prt_notes);
			td([ 'valign'=>'top', 'align'=>'right', 'style'=>'width: 25%;' ], $register);
			_tr();
			_table();
		_td();
		_tr();
		tr();
		td([ 'colspan'=>2 ]);
		$this->printResult();
		_td();
		_tr();
		_table();

		$reg_iframe_form->close();

		script();
		out('
			function reg_changed() {
				var reg_now = $("#prt_firstname").val()+"|"+$("#prt_lastname").val()+"|"+$("#prt_birthday").val()+"|"+
					$("#prt_supervision_firstname").val()+"|"+$("#prt_supervision_lastname").val()+"|"+
					$("#prt_supervision_cellphone").val()+"|"+$("#prt_notes").val();
				iPadRegistrationChanged(
					'.$reg_part_v.',
					$("#reg_before").val(),
					reg_now,
					$("#reg_status_'.$reg_part_v.'"),
					$("#reg_tab_'.$reg_part_v.'"),
					$("#register")
				);
			}
			$("#prt_firstname").keyup(reg_changed);
			$("#prt_lastname").keyup(reg_changed);
			$("#prt_birthday").keyup(reg_changed);
			$("#prt_notes").keyup(reg_changed);
			$("#prt_supervision_firstname").keyup(reg_changed);
			$("#prt_supervision_lastname").keyup(reg_changed);
			$("#prt_supervision_cellphone").keyup(reg_changed);
			reg_changed();
		');
		_script();
		$this->footer();
	}
}
