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

	private function compareRows($r1, $r2) {
		if ($r1['prt_firstname'] != $r2['prt_firstname'])
			return false;
		if ($r1['prt_lastname'] != $r2['prt_lastname'])
			return false;
		if ($r1['prt_birthday'] != $r2['prt_birthday'])
			return false;
		if ($r1['prt_supervision_firstname'] != $r2['prt_supervision_firstname'])
			return false;
		if ($r1['prt_supervision_lastname'] != $r2['prt_supervision_lastname'])
			return false;
		if ($r1['prt_supervision_cellphone'] != $r2['prt_supervision_cellphone'])
			return false;
		if ($r1['prt_notes'] != $r2['prt_notes'])
			return false;
		return true;
	}

	private function link($target, $reg_part, $i)
	{
		$attr = [ 'class'=>'menu-item', 'onclick'=>'$("#reg_set_part").val('.$i.'); $("#reg_participant_form").submit();' ];
		if ($reg_part == $i)
			$attr['selected'] = null;
		return $attr;
	}

	public function index()
	{
		$this->authorize();

bugout($_POST);
		$this->header('iPad Registrierung', false);
		
		$reg_part = in('reg_part', 1);
		$reg_part->persistent();
		$reg_part_v = $reg_part->getValue();

		$reg_participants = in('reg_participants', [ ]);
		$reg_participants->persistent();
		$reg_participants_v = $reg_participants->getValue();

		if (isset($_POST['prt_firstname']) &&
			isset($_POST['prt_lastname']) &&
			isset($_POST['prt_birthday']) &&
			isset($_POST['prt_supervision_firstname']) &&
			isset($_POST['prt_supervision_lastname']) &&
			isset($_POST['prt_supervision_cellphone']) &&
			isset($_POST['prt_notes'])) {
			// Save the POST data:
			$reg_participants_v[$reg_part_v] = [
				'prt_firstname'=>trim($_POST['prt_firstname']),
				'prt_lastname'=>trim($_POST['prt_lastname']),
				'prt_birthday'=>trim($_POST['prt_birthday']),
				'prt_supervision_firstname'=>trim($_POST['prt_supervision_firstname']),
				'prt_supervision_lastname'=>trim($_POST['prt_supervision_lastname']),
				'prt_supervision_cellphone'=>trim($_POST['prt_supervision_cellphone']),
				'prt_notes'=>trim($_POST['prt_notes']) ];
			$reg_participants->setValue($reg_participants_v);
		}

		$reg_participant_form = new Form('reg_participant_form', 'registration', 2, array('class'=>'input-table'));

		$reg_set_part = $reg_participant_form->addHidden('reg_set_part');
		$reg_set_part_v = $reg_set_part->getValue();
		if ($reg_set_part_v > 0 && $reg_set_part_v <= MAX_PARTICIPANTS) {
			$reg_part_v = $reg_set_part_v;
			$reg_part->setValue($reg_part_v);
			redirect("registration");
		}

		$participant_empty_row = [ 'prt_firstname'=>'', 'prt_lastname'=>'', 'prt_birthday'=>'',
			'prt_supervision_firstname'=>'', 'prt_supervision_lastname'=>'', 'prt_supervision_cellphone'=>'',
			'prt_notes'=>'' ];
		$participant_row = arr_nvl($reg_participants_v, $reg_part_v, $participant_empty_row);

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
		$prt_birthday->setRule('is_valid_date');

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

		$register = submit('register', 'Aufnehmen', [ 'class'=>'button-green', 'style'=>'width: 100%; height: 48px; font-size: 24px;' ]);
		if ($register->submitted()) {
			$edit_part = arr_nvl($reg_participants_v, $reg_part_v, $participant_empty_row);

			is_valid_date
		
			if (!empty($edit_part['prt_firstname']) && !empty($edit_part['prt_lastname'])) {
				$db_part = $this->get_participant_row_by_name($edit_part['prt_firstname'], $edit_part['prt_lastname']);
				if (empty($db_part)) {
					$prt_id_v = $this->insert_participant($edit_part, false);
					if (!empty($prt_id_v)) {
						$this->setSuccess($edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' aufgenommen');
						redirect("registration");
					}
				}
				else {
					if ($db_part['prt_registered'] == REG_NO && empty($db_part['prt_group_number'])) {
						$this->modify_participant($db_part['prt_id'], $db_part, $edit_part, false);
						$this->setSuccess($edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' geändert');
						redirect("registration");
					}
					else
						$this->error = $edit_part['prt_firstname']." ".$edit_part['prt_lastname'].' ist bereits angemeldet';
				}
			}
		}

		$_POST = [ ]; // Clear POST data:

		div(array('class'=>'topnav'));
		table();
		tr();
		td([ 'colspan'=>MAX_PARTICIPANTS+1, 'style'=>'text-align: left;' ]);
		a([ 'href'=>'participant' ], img([ 'src'=>base_url('/img/bf-kids-logo2.png'), 'style'=>'height: 40px; width: auto; position: relative; bottom: -2px;']));
		_td();
		_tr();
		tr();
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		for ($i=1; $i<=MAX_PARTICIPANTS; $i++) {
			if ($this->compareRows(arr_nvl($reg_participants_v, $i, $participant_empty_row), $participant_empty_row))
				$box = div([ 'class'=>'grey-box', 'style'=>'width: 100%;' ], nbsp());
			else {
				$edit_part = arr_nvl($reg_participants_v, $i, $participant_empty_row);
				$box = '';
				if (!empty($edit_part['prt_firstname']) && !empty($edit_part['prt_lastname'])) {
					$db_part = $this->get_participant_row_by_name($edit_part['prt_firstname'], $edit_part['prt_lastname']);
					if (!empty($db_part) && $this->compareRows(arr_nvl($reg_participants_v, $i, $participant_empty_row), $db_part)) {
						// If the kids has a group...
						if ($db_part['prt_registered'] == REG_NO && empty($db_part['prt_group_number']))
							$box = div([ 'class'=>'green-box', 'style'=>'width: 100%;' ], 'Aufgenommen');
						else
							$box = div([ 'class'=>'red-box', 'style'=>'width: 100%;' ], 'Angemeldet');
					}
				}
				if (empty($box))
					$box = div([ 'class'=>'yellow-box', 'style'=>'width: 100%;' ], 'Wird Aufgenommen');
			}
			td([ 'width'=>(100/MAX_PARTICIPANTS).'%', 'style'=>'height: 22px; padding: 0px 2px 5px 2px;' ], $box);
			td([ 'width'=>(100/MAX_PARTICIPANTS).'%', 'style'=>'width: 3px; padding: 0;' ], nbsp());
		}
		_tr();
		_tr();
		tr([ 'style'=>'border-bottom: 1px solid black; padding: 8px 16px;' ]);
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		for ($i=1; $i<=MAX_PARTICIPANTS; $i++) {
			td([ 'width'=>(100/MAX_PARTICIPANTS).'%' ] + $this->link('participant', $reg_part_v, $i), 'Kind '.$i);
			td([ 'width'=>(100/MAX_PARTICIPANTS).'%', 'style'=>'width: 3px; padding: 0;' ], nbsp());
		}
		_tr();
		_table();
		_div();

		$reg_participant_form->open();

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
			td([ 'valign'=>'top', 'style'=>'width: 25%;' ], $register);
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

		$reg_participant_form->close();

		script();
		$this->footer();
	}
}
