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

	public function index()
	{
		if (!$this->authorize())
			return;

		$this->header('iPad Registrierung', false);

		$reg_part = in('reg_part', 1);
		$reg_part->persistent();

		$reg_participants = in('reg_participants', [ ]);
		$reg_participants->persistent();

		$reg_supervision = in('reg_supervision', [ ]);
		$reg_supervision->persistent();

		$reg_top_form = new Form('reg_top_form', 'registration', 2, array('class'=>'input-table'));

		$reg_back = $reg_top_form->addHidden('reg_back');
		if (!empty($reg_back->getValue())) {
			$query = $this->db->query(
				'SELECT stf_id, stf_username, stf_fullname, stf_password, '.
				'stf_registered, stf_loginallowed, stf_technician '.
				'FROM bf_staff WHERE stf_id=?',
				[ $this->session->stf_login_id ]);
			$staff_row = $query->row_array();

			$pwd = $reg_back->getValue();
			$pwd = md5($pwd.'129-3026-19-2089');
			if (password_verify($pwd, $staff_row['stf_password']))
				redirect($this->session->ses_prev_page);
		}

		$reg_top_form->open();
		table([ 'style'=>'width: 100%;' ]);
		tr([ 'class'=>'topnav' ]);
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		td([ 'style'=>'text-align: left; padding: 4px 2px;' ]);
		a([ 'onclick'=>'do_back();' ], img([ 'src'=>base_url('/img/bf-kids-logo2.png'),
			'style'=>'height: 40px; width: auto; position: relative; bottom: -2px;']));
		_td();
		td(nbsp());
		td([ 'style'=>'text-align: right; padding: 4px 2px; width: 48px;' ]);
		div([ 'onclick'=>'do_reload();', 'style'=>
			'border: 1px solid black; background-color: lightgray; padding: 4px 4px 1px 1px; height: 33px; width: 33px;' ],
			img([ 'src'=>'../img/reload.png',
			'style'=>'height: 28px; width: auto; position: relative; bottom: -2px; left: -2px']));
		_td();
		$complete = button('complete', 'Abschließen',
			[ 'style'=>'height: 40px; background-color: lightgray; color: black; border-radius: 0px; font-size: 18px;',
				'onclick'=>'do_complete();' ]);
		td([ 'style'=>'width: 124px; text-align: right; padding: 4px 0px;' ], $complete);
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		_tr();
		tr();
		td( [ 'colspan'=>6, 'style'=>'height: 800px; background-color: #009DDE;' ] );
		//out('x');
		tag('iframe', [ 'id'=>'content-iframe', 'src'=>'registration/iframe', 'style'=>'width: 100%; height: 100%; border: 0;' ], '');
		_td();
		_tr();
		_table();
		$reg_top_form->close();

		script();
		out('
			function do_back() {
				var password = prompt("Please password for '.$this->session->stf_login_name.':", "");
				if (password != null) {
					$("#reg_back").val(password);
					$("#reg_top_form").submit();
				}
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
					if (!confirm("Wollen Sie wirklich die Aufnahme abschließen, nicht alle Eingaben sind abgeschlossen?"))
						return;
				}

				content.find("#reg_complete").val(1);
				content.find("#reg_iframe_form").submit();
			}
		');
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
		if (empty($edit_part['prt_firstname']) && empty($edit_part['prt_lastname']))
			return 1;

		if (empty($edit_part['prt_firstname']) || empty($edit_part['prt_lastname']))
			return 2;

		$db_part = $this->get_participant_row_by_name($edit_part['prt_firstname'], $edit_part['prt_lastname']);
		if (empty($db_part))
			return 2;

		if ($db_part['prt_registered'] == REG_NO && empty($db_part['prt_group_number'])) {
			if ($this->rows_equal($edit_part, $db_part))
				return 3;
			return 4;
		}

		return 5;
	}

	public function iframe()
	{
		if (!$this->authorize())
			return;

		$this->header('iPad Registrierung', false);
		
		$reg_part = in('reg_part', 1);
		$reg_part->persistent();

		$reg_participants = in('reg_participants', [ ]);
		$reg_participants->persistent();

		$reg_supervision = in('reg_supervision', [ ]);
		$reg_supervision->persistent();

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
			$reg_participants_v[$reg_part_v] = [
				'prt_firstname'=>trim($_POST['prt_firstname']),
				'prt_lastname'=>trim($_POST['prt_lastname']),
				'prt_birthday'=>trim($_POST['prt_birthday']),
				'prt_notes'=>trim($_POST['prt_notes']) ];
			$reg_participants->setValue($reg_participants_v);
			$reg_supervision_v = [
				'prt_supervision_firstname'=>trim($_POST['prt_supervision_firstname']),
				'prt_supervision_lastname'=>trim($_POST['prt_supervision_lastname']),
				'prt_supervision_cellphone'=>trim($_POST['prt_supervision_cellphone']) ];
			$reg_supervision->setValue($reg_supervision_v);
		}

		$reg_iframe_form = new Form('reg_iframe_form', 'iframe', 2, array('class'=>'input-table'));
		$reg_before = $reg_iframe_form->addHidden('reg_before');

		$reg_set_part = $reg_iframe_form->addHidden('reg_set_part');
		$reg_set_part_v = $reg_set_part->getValue();
		if ($reg_set_part_v > 0 && $reg_set_part_v <= MAX_PARTICIPANTS) {
			$reg_part_v = $reg_set_part_v;
			$reg_part->setValue($reg_part_v);
			redirect("registration/iframe");
		}

		$reg_complete = $reg_iframe_form->addHidden('reg_complete');
		if ($reg_complete->getValue() == 1) {
			$reg_part->setValue(1);
			$reg_participants->setValue([ ]);
			$reg_supervision->setValue([ ]);
			redirect("registration/iframe");
		}

		$participant_empty_row = [ 'prt_firstname'=>'', 'prt_lastname'=>'', 'prt_birthday'=>'', 'prt_notes'=>'' ];
		$participant_row = arr_nvl($reg_participants_v, $reg_part_v, $participant_empty_row) + $reg_supervision_v;
		$prt_firstname = textinput('prt_firstname', $participant_row['prt_firstname'],
			[ 'placeholder'=>'Vorname', 'style'=>'width: 160px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_firstname->setFormat([ 'clear-box'=>true ]);
		$prt_firstname->setRule('required');
		$prt_lastname = textinput('prt_lastname', $participant_row['prt_lastname'],
			[ 'placeholder'=>'Nachname', 'style'=>'width: 220px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_lastname->setFormat([ 'clear-box'=>true ]);
		$prt_lastname->setRule('required');
		$prt_birthday = new NumericField('prt_birthday', $participant_row['prt_birthday'],
			[ 'placeholder'=>'DD.MM.JJJJ', 'style'=>'font-family: Monospace; width: 120px;', 'onkeyup'=>'dateChanged($(this));' ]);
		$prt_birthday->setFormat([ 'clear-box'=>true ]);
		$prt_birthday->setRule('is_valid_date', 'Geburtstag');

		$prt_supervision_firstname = textinput('prt_supervision_firstname', $participant_row['prt_supervision_firstname'],
			[ 'placeholder'=>'Vorname', 'style'=>'width: 160px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_supervision_firstname->setFormat([ 'clear-box'=>true ]);
		$prt_supervision_lastname = textinput('prt_supervision_lastname', $participant_row['prt_supervision_lastname'],
			[ 'placeholder'=>'Nachname', 'style'=>'width: 220px;', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_supervision_lastname->setFormat([ 'clear-box'=>true ]);
		$prt_supervision_cellphone = new NumericField('prt_supervision_cellphone', $participant_row['prt_supervision_cellphone'],
			[ 'style'=>'width: 220px; font-family: Monospace;' ]);
		$prt_supervision_cellphone->setFormat([ 'clear-box'=>true ]);

		$prt_notes = textarea('prt_notes', $participant_row['prt_notes'], [ 'style'=>'width: 98%;' ]);

		$register = submit('register', 'Aufnehmen', [ 'class'=>'button-green', 'style'=>'width: 96%; height: 48px; font-size: 24px;' ]);
		$register->disable();

		if ($register->submitted()) {
			$edit_part = arr_nvl($reg_participants_v, $reg_part_v, $participant_empty_row) + $reg_supervision_v;
			$this->error = $prt_birthday->validate();
			$reg_participants_v[$reg_part_v]['prt_birthday'] = str_to_date($prt_birthday->getValue())->format('d.m.Y');
			$reg_participants->setValue($reg_participants_v);
			if (empty($this->error) && !empty($edit_part['prt_firstname']) && !empty($edit_part['prt_lastname'])) {
				$db_part = $this->get_participant_row_by_name($edit_part['prt_firstname'], $edit_part['prt_lastname']);
				if (empty($db_part)) {
					$prt_id_v = $this->insert_participant($edit_part, false);
					if (!empty($prt_id_v)) {
						$this->setSuccess($edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' aufgenommen');
						redirect("registration/iframe");
					}
				}
				else {
					if ($db_part['prt_registered'] == REG_NO && empty($db_part['prt_group_number'])) {
						$this->modify_participant($db_part['prt_id'], $db_part, $edit_part, false);
						$this->setSuccess($edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' geändert');
						redirect("registration/iframe");
					}
					else
						$this->error = $edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' ist bereits angemeldet';
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
				case 2: $box = div($attr + [ 'class'=>'yellow-box' ], 'Wird Aufgenommen'); break;
				case 3: $box = div($attr + [ 'class'=>'green-box' ], 'Aufgenommen'); break;
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
			$participant_row = arr_nvl($reg_participants_v, $i, $participant_empty_row);
			$fname = arr_nvl($participant_row, 'prt_firstname', '');
			$lname = arr_nvl($participant_row, 'prt_lastname', '');
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
		tr(td(nbsp().b('Hinweise (Allergien, etc.)')));
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
