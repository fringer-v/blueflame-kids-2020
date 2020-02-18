<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class ParticipantTable extends Table {
	private function getOrder($col) {
		if (str_contains($this->order_by, $col)) {
			if (str_endswith($this->order_by, ' DESC'))
				return '&uarr;';
			return '&darr;';
		}
		return '';
	}

	public function columnTitle($field) {
		switch ($field) {
			case 'prt_number':
				return $this->getOrder('prt_number').'Nr.';
			case 'prt_name':
				return $this->getOrder('prt_firstname').'Name';
			case 'prt_birthday':
				return 'Geburtstag';
			case 'age':
				return 'Alter';
			case 'prt_group_number':
				return 'Gruppe';
			case 'prt_call_status':
				return $this->getOrder('prt_registered').'Status';
//			case 'prt_registered';
//				return 'Angem.';
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function columnAttributes($field) {
		switch ($field) {
			case 'prt_birthday':
			case 'age':
			case 'prt_call_status':
			case 'prt_group_number':
				return [ 'style'=>'text-align: center;' ];
		}
		return null;
	}

	public function cellValue($field, $row) {
		global $age_level_from;
		global $age_level_to;

		switch ($field) {
			case 'prt_number':
			case 'prt_name':
				$value = $row[$field];
				return $value;
			case 'prt_birthday':
				if (empty($row['prt_birthday']))
					return '';
				$ts = DateTime::createFromFormat('Y-m-d', $row['prt_birthday']);
				return $ts->format('d.m.Y');
			case 'age':
				return get_age($row['prt_birthday']);
			case 'prt_group_number':
				$age_level = $row['prt_age_level'];
				$group_nr = $row[$field];
				if (empty($group_nr))
					$group_box = div([ 'style'=>'height: 22px; font-size: 16px' ], nbsp());
				else {
					$ages = $age_level_from[$age_level].' - '.$age_level_to[$age_level];
					$group_box = span([ 'class'=>'group-s g-'.$age_level ],
						span(['class'=>'group-number-s'], $group_nr), " ".$ages);
				}
				return $group_box;
			case 'prt_call_status': {
				if ($row['prt_registered'] == REG_BEING_FETCHED)
					return div(array('class'=>'yellow-box in-col'), 'Wird Abg.');
				if (!is_empty($row['prt_wc_time'])) {
					$out = div(array('class'=>'white-box in-col'), 'WC '.how_long_ago($row['prt_wc_time']));
					return $out;
				}
				$call_status = $row['prt_call_status'];
				if (is_empty($call_status))
					return nbsp();
				if ($call_status == CALL_CANCELLED || $call_status == CALL_COMPLETED)
					return div(array('class'=>'red-box in-col'), 'Ruf Aufh.');
				return div(array('class'=>'blue-box in-col'), 'Ruf '.how_long_ago($row['prt_call_start_time']));
			}
/*
			case 'prt_registered':
				if ($row[$field] == REG_YES) {
					if (is_empty($row['prt_wc_time']))
						return div(array('class'=>'green-box', 'style'=>'width: 56px; height: 22px;'), 'Ja');
					$out = div(array('class'=>'white-box', 'style'=>'width: 25px; height: 22px; font-size: 12px;'), 'WC');
					$out->add(" ");
					$out->add(div(array('class'=>'green-box', 'style'=>'width: 25px; height: 22px;'), 'Ja'));
					return $out;
				}
				if ($row[$field] == REG_BEING_FETCHED) {
					return div(array('class'=>'yellow-box', 'style'=>'width: 56px; height: 22px;'), 'Abg.');
				}
				return div(array('class'=>'red-box', 'style'=>'width: 56px; height: 22px;'), 'Nein');
*/
			case 'button_column':
				return a([ 'class'=>'button-black',
					'style'=>'display: block; color: white; height: 26px; width: 32px; text-align: center; line-height: 26px; border-radius: 6px;',
					'onclick'=>'$("#set_prt_id").val('.$row['prt_id'].'); $("#display_participant").submit();' ], out('&rarr;'))->html();
				//return submit('select', 'Bearbeiten', array('class'=>'button-black', 'onclick'=>'$("#set_prt_id").val('.$row['prt_id'].');'))->html();
		}
		return nix();
	}
}

class HistoryTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'hst_action':
				return 'Art';
			case 'hst_timestamp':
				return 'Zeit';
			case 'stf_username':
				return 'Mitarb.';
			case 'hst_notes':
				return 'Einzelheiten';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		global $age_level_from;
		global $age_level_to;

		switch ($field) {
			case 'hst_action':
				switch ($row[$field]) {
					case CREATED:
						return 'Registriert';
					case REGISTER:
						return 'Angemeldet';
					case UNREGISTER:
						return 'Abgemeldet';
					case CALL:
						return TEXT_PENDING;
					case CANCELLED:
						return TEXT_CANCELLED;
					case ESCALATE:
						return TEXT_ESCALATED.' ('.$row['hst_escalation'].')';
					case CALLED:
						return TEXT_CALLED;
					case CALL_ENDED:
						return TEXT_COMPLETED;
					case GO_TO_WC:
						return 'Zum WC gegangen';
					case BACK_FROM_WC:
						return 'Von WC zurück';
					case BEING_FETCHED:
						return 'Wird abgeholt';
					case CHANGED_GROUP:
						return 'Gruppe geändert';
					case FETCH_CANCELLED:
						return 'Abholen abgebrochen';
					case NAME_CHANGED:
						return 'Name geändert';
					case BIRTHDAY_CHANGED:
						return 'Geburtstag geändert';
					case SUPERVISOR_CHANGED:
						return 'Begleiter gewechselt';
					case CELLPHONE_CHANGED:
						return 'Handy geändert';
					case NOTES_CHANGED:
						return 'Hinweise geändert';
				}
				return '';
			case 'hst_timestamp':
				$date = date_create_from_format('Y-m-d H:i:s', $row[$field]);
				$date->setTime(0, 0, 0);
				$today = new DateTime();
				$today->setTime(0, 0, 0);
				$diff = $today->diff($date);
				$diff = (integer) $diff->format("%R%a");

				$date = date_create_from_format('Y-m-d H:i:s', $row[$field]);
				switch($diff) {
				    case 0:
						return 'Heute '.$date->format('H:i');
				    case -1:
						return 'Gestern '.$date->format('H:i');
				    case -2:
						return 'Vorgestern '.$date->format('H:i');
				}
				return $date->format('d.m.Y H:i');
			case 'stf_username':
				return htmlentities($row[$field]);
			case 'hst_notes':
				$group_nr = $row['hst_group_number'];
				if ($group_nr > 0) {
					$age_level = $row['hst_age_level'];
					if (empty($group_nr))
						$group_box = div([ 'style'=>'height: 22px; font-size: 16px' ], nbsp());
					else {
						$ages = $age_level_from[$age_level].' - '.$age_level_to[$age_level];
						$group_box = span([ 'class'=>'group-s g-'.$age_level ],
							span(['class'=>'group-number-s'], $group_nr), " ".$ages);
					}
					$note = $group_box;
				}
				else
					$note = '';
				$val = $row['hst_notes'];
				if (!empty($val)) {
					if (!empty($note))
						$note .= ' ';
					$note .= str_replace(' -&gt; ', ' &rarr; ', htmlentities($val));
				}
				return trim($note);
		}
		return nix();
	}
}

class Participant extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->model('db_model');
	}

	public function index()
	{
		global $age_level_to;
		global $age_level_from;
		global $group_colors;

		if (!$this->authorize())
			return;

		$display_participant = new Form('display_participant', 'participant', 1, [ 'class'=>'input-table' ]);
		$set_prt_id = $display_participant->addHidden('set_prt_id');
		$prt_filter = $display_participant->addTextInput('prt_filter', '', '', [ 'placeholder'=>'Suchfilter' ]);
		$prt_filter->persistent();
		$prt_page = in('prt_page', 1);
		$prt_page->persistent();
		$clear_filter = $display_participant->addSubmit('clear_filter', 'Clear',
			[ 'class'=>'button-black', 'onclick'=>'$("#prt_filter").val(""); participants_list(); return false;' ]);
		$also_reg_filter = $display_participant->addSubmit('also_reg_filter', 'Mit Registriert',
			[ 'class'=>'button-black', 'onclick'=>'get_supervisor_parts(); return false;' ]);

		$update_participant = new Form('update_participant', 'participant', 2, [ 'class'=>'input-table', 'style'=>'width: 100%;' ]);
		if (!is_empty($this->session->stf_login_tech))
			$update_participant->disable();
		$prt_id = $update_participant->addHidden('prt_id');
		$prt_id->persistent();
		$prt_id_v = $prt_id->getValue();
		$hst_page = in('hst_page', 1);
		$hst_page->persistent();

		if ($set_prt_id->submitted()) {
			$prt_id->setValue($set_prt_id->getValue());
			$hst_page->setValue(1);
			redirect("participant");
		}
		
		$participant_row = $this->get_participant_row($prt_id_v);

		// Registrierung u. Ändern
		$number1 = $update_participant->addField('Kinder-Nr');
		$number1->setFormat([ 'colspan'=>'2' ]);
		$prt_firstname = $update_participant->addTextInput('prt_firstname', 'Name',
			$participant_row['prt_firstname'], [ 'placeholder'=>'Vorname', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_firstname->setRule('required');
		$prt_lastname = $update_participant->addTextInput('prt_lastname', '',
			$participant_row['prt_lastname'], [ 'placeholder'=>'Nachname', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_lastname->setRule('required');
		$prt_birthday = $update_participant->addTextInput('prt_birthday', 'Geburtstag',
			$participant_row['prt_birthday'], [ 'placeholder'=>'DD.MM.JJJJ' ]);
		$prt_birthday->setRule('is_valid_date');
		$age_field = $update_participant->addSpace();

		$group_list = new AsyncLoader('modify_group_list', 'participant/getgroups?tab=modify', [ 'grp_arg'=>'""', 'action'=>'""' ] );
		$update_participant->addRow($group_list->html());

		$prt_supervision_firstname = $update_participant->addTextInput('prt_supervision_firstname', 'Begleitperson',
			$participant_row['prt_supervision_firstname'], [ 'placeholder'=>'Vorname', 'onkeyup'=>'capitalize($(this));' ]);
		$prt_supervision_lastname = $update_participant->addTextInput('prt_supervision_lastname', '',
			$participant_row['prt_supervision_lastname'], [ 'placeholder'=>'Nachname', 'onkeyup'=>'capitalize($(this));' ]);
		//$prt_supervision_lastname->setFormat([ 'nolabel'=>true ]);
		$prt_supervision_cellphone = $update_participant->addTextInput('prt_supervision_cellphone', 'Handy-Nr',
			$participant_row['prt_supervision_cellphone']);
		$update_participant->addSpace();
		$prt_notes = $update_participant->addTextArea('prt_notes', 'Hinweise', $participant_row['prt_notes'],
			[ 'style'=>'height: 24px;' ]);
		$prt_notes->setFormat([ 'colspan'=>'2' ]);

		$save_participant = $update_participant->addSubmit('save_participant', 'Änderung Sichern', [ 'class'=>'button-green' ]);
		$new_participant = $update_participant->addSubmit('new_participant', 'Kind Registrieren', [ 'class'=>'button-green' ]);
		$clear_nr_name = $update_participant->addSubmit('clear_no_name', 'Geschwister Aufnehmen...', [ 'class'=>'button-blue' ]); // DEFUKT
		$clear_participant = $update_participant->addSubmit('clear_participant', '', [ 'class'=>'button-black' ]);

		$update_participant->createGroup('tab_modify');

		// An u. Abmeldung
		$register_data = $update_participant->addRow('');
		$group_list = new AsyncLoader('register_group_list', 'participant/getgroups?tab=register', [ 'grp_arg'=>'""', 'action'=>'""' ] );
		$update_participant->addRow($group_list->html());

		// NO LONGER USED:
		//$register_comment = $update_participant->addTextInput('register_comment', 'Kommentar', '', [ 'style'=>'width: 494px;' ]);
		//$register_comment->setFormat([ 'colspan'=>'2' ]);

		$go_to_wc = $update_participant->addSubmit('go_to_wc', 'WC', [ 'class'=>'button-white wc' ]);
		$back_from_wc = $update_participant->addSubmit('back_from_wc', 'WC', [ 'class'=>'button-white wc strike-thru' ]);
		$being_fetched = $update_participant->addSubmit('being_fetched', 'Wird Abgeholt', [ 'class'=>'button-yellow register' ]);
		$cancel_fetch = $update_participant->addSubmit('cancel_fetch', 'Abholen Abbrechen', [ 'class'=>'button-yellow register' ]);
		$unregister = $update_participant->addSubmit('unregister', 'Abmelden', [ 'class'=>'button-red register' ]);
		$register = $update_participant->addSubmit('register', 'Anmelden', [ 'class'=>'button-green register' ]);

		$update_participant->createGroup('tab_register');

		// Eltern Ruf
		$number3 = $update_participant->addField('Kinder-Nr');
		$number3->setFormat([ 'colspan'=>'2' ]);
		$f1 = $update_participant->addTextInput('prt_firstname', 'Name', $participant_row['prt_firstname'], [ 'placeholder'=>'Vorname' ]);
		$f1->disable();
		$f2 = $update_participant->addTextInput('prt_lastname', '', $participant_row['prt_lastname'], [ 'placeholder'=>'Nachname' ]);
		$f2->disable();
		$f3 = $update_participant->addTextInput('prt_supervision_firstname', 'Begleitperson',
			$participant_row['prt_supervision_firstname'], [ 'placeholder'=>'Vorname' ]);
		$f3->disable();
		$f4 = $update_participant->addTextInput('prt_supervision_lastname', '',
			$participant_row['prt_supervision_lastname'], [ 'placeholder'=>'Nachname' ]);
		$f4->disable();
		$supervisor_comment = $update_participant->addTextInput('supervisor_comment', 'Kommentar', '', [ 'style'=>'width: 494px;' ]);
		$supervisor_comment->setFormat([ 'colspan'=>'2' ]);

		$escallate = $update_participant->addSubmit('escallate', 'Eskalieren', [ 'class'=>'button-blue' ]);
		$call_super = $update_participant->addSubmit('call_super', 'Ruf Eltern', [ 'class'=>'button-blue' ]);
		$cancel_super = $update_participant->addSubmit('cancel_super', 'Ruf Aufheben ', [ 'class'=>'button-red' ]);

		$update_participant->createGroup('tab_supervisor');

		if ($clear_participant->submitted()) {
			$prt_id->setValue(0);
			redirect("participant");
		}

		if ($clear_nr_name->submitted()) {
			$participant_row['prt_id'] = '';
			$participant_row['prt_number'] = '';
			$participant_row['prt_firstname'] = '';
			$participant_row['prt_birthday'] = null;
			$prt_id->setValue('');
			$prt_firstname->setValue('');
			$prt_birthday->setValue('');
			$prt_id_v = 0;
		}

		if ($new_participant->submitted() || $save_participant->submitted()) {
			$this->error = $update_participant->validate('tab_modify');
			if (is_empty($this->error)) {
				$staff_row = $this->get_staff_row($this->session->stf_login_id);
				$group_reserved = if_empty($staff_row['stf_reserved_count'], 0) > 0;

				$data = [
					'prt_firstname' => $prt_firstname->getValue(),
					'prt_lastname' => $prt_lastname->getValue(),
					'prt_birthday' => $prt_birthday->getDate('d.m.Y'),
					'prt_supervision_firstname' => $prt_supervision_firstname->getValue(),
					'prt_supervision_lastname' => $prt_supervision_lastname->getValue(),
					'prt_supervision_cellphone' => $prt_supervision_cellphone->getValue(),
					'prt_notes' => $prt_notes->getValue()
				];
				if ($group_reserved) {
					$data['prt_age_level'] = $staff_row['stf_reserved_age_level'];
					$data['prt_group_number'] = $staff_row['stf_reserved_group_number'];
					if ((integer) $participant_row['prt_registered'] != REG_BEING_FETCHED)
						$data['prt_registered'] = REG_YES;
				}

				if (is_empty($prt_id_v)) {
					$count = (integer) db_1_value('SELECT COUNT(*) FROM bf_participants WHERE prt_firstname = ? '.
						'AND prt_lastname = ?',
						[ $prt_firstname->getValue(), $prt_lastname->getValue() ]);
					if ($count == 0) {
						$prt_id_v = $this->insert_participant($data, $group_reserved);
						if (!empty($prt_id_v)) {
							$prt_filter->setValue('');
							$prt_page->setValue(1);
							$prt_id->setValue($prt_id_v);
							$this->setSuccess($prt_firstname->getValue()." ".$prt_lastname->getValue().' aufgeno');
							redirect("participant");
						}
					}
					else
						$this->error = $prt_firstname->getValue()." ".$prt_lastname->getValue().' ist bereits registriert';
				}
				else {
					$this->modify_participant($prt_id_v, $participant_row, $data, $group_reserved);
					$this->setSuccess($prt_firstname->getValue()." ".$prt_lastname->getValue().' geändert');
					redirect("participant");
				}
			}
		}

		if (!is_empty($prt_id_v)) {
			if ($register->submitted() || $unregister->submitted() ||
				$being_fetched->submitted() || $cancel_fetch->submitted()) {
				
				$data = [ ];
				//$history = [
				//	'hst_prt_id'=>$prt_id_v,
				//	'hst_stf_id'=> $this->session->stf_login_id,
				//	'hst_notes'=>$register_comment->getValue() ];
				$history = [
					'hst_prt_id'=>$prt_id_v,
					'hst_stf_id'=> $this->session->stf_login_id ];

				$staff_row = $this->get_staff_row($this->session->stf_login_id);
				$group_reserved = if_empty($staff_row['stf_reserved_count'], 0) > 0;
				
				if ($register->submitted()) {
					if (!$group_reserved)
						$this->error = 'Bitte wählen sie eine Gruppe aus';
					else if ($participant_row['prt_registered'] == REG_NO) {
						$data['prt_age_level'] = $staff_row['stf_reserved_age_level'];
						$data['prt_group_number'] = $staff_row['stf_reserved_group_number'];
						$data['prt_registered'] = REG_YES;
						$history['hst_action'] = REGISTER;
						$history['hst_age_level'] = $staff_row['stf_reserved_age_level'];
						$history['hst_group_number'] = $staff_row['stf_reserved_group_number'];
						$comment = 'angemeldet';
					}
				}
				else if ($unregister->submitted()) {
					if ($participant_row['prt_registered'] == REG_YES ||
						$participant_row['prt_registered'] == REG_BEING_FETCHED) {
						$data['prt_age_level'] = null;
						$data['prt_group_number'] = null;
						$data['prt_registered'] = REG_NO;
						$history['hst_action'] = UNREGISTER;
						$history['hst_age_level'] = $staff_row['stf_reserved_age_level'];
						$history['hst_group_number'] = $staff_row['stf_reserved_group_number'];
						$comment = 'abgemeldet';
					}
				}
				else if ($being_fetched->submitted()) {
					if ($participant_row['prt_registered'] == REG_YES) {
						$data['prt_registered'] = REG_BEING_FETCHED;
						$history['hst_action'] = BEING_FETCHED;
						$comment = 'wird abgeholt';
					}
				}
				else if ($cancel_fetch->submitted()) {
					if ($participant_row['prt_registered'] == REG_BEING_FETCHED) {
						$data['prt_registered'] = REG_YES;
						$history['hst_action'] = FETCH_CANCELLED;
						$comment = 'erneut angemeldet';
					}
				}

				if (!empty($data)) {					
					if (!is_empty($participant_row['prt_call_status'])) {
						// Cancel the call!
						$this->db->insert('bf_history', array(
							'hst_prt_id'=>$prt_id_v,
							'hst_stf_id'=>$this->session->stf_login_id,
							'hst_action'=>CANCELLED,
							'hst_escalation'=>0));

						$this->db->set('prt_call_status', CALL_NOCALL);
						$this->db->set('prt_call_escalation', 0);
						$this->db->set('prt_call_start_time', 'NOW()', false);
					}
					if (!is_empty($participant_row['prt_wc_time']))
						$this->db->set('prt_wc_time', null);

					$this->db->set('prt_modifytime', 'NOW()', false);
					$this->db->where('prt_id', $prt_id_v);
					$this->db->update('bf_participants', $data);

					$this->db->insert('bf_history', $history);
					if ($group_reserved && $history['hst_action'] == REGISTER)
						$this->unreserve_group($staff_row['stf_reserved_age_level'], $staff_row['stf_reserved_group_number']);

					$this->setSuccess($prt_firstname->getValue()." ".$prt_lastname->getValue().' '.$comment);
					redirect("participant");
				}
			}

			$call_status = $participant_row['prt_call_status'];
			if ($call_super->submitted() || $cancel_super->submitted()) {
				$sql = 'UPDATE bf_participants SET prt_call_escalation = 0, ';
				if (is_empty($call_status) || $call_status == CALL_CANCELLED || $call_status == CALL_COMPLETED) {
					$action = CALL;
					$sql .= 'prt_call_status = '.CALL_PENDING.', ';
					$sql .= 'prt_call_start_time = NOW(), ';
					$sql .= 'prt_call_change_time = NOW() ';
					$msg = 'gerufen';
				}
				else {
					if ($call_status == CALL_PENDING) {
						$action = CANCELLED;
						$sql .= 'prt_call_status = '.CALL_CANCELLED.', ';
					}
					else { // CALL_CALLED
						$action = CALL_ENDED;
						$sql .= 'prt_call_status = '.CALL_COMPLETED.', ';
					}
					$sql .= 'prt_call_change_time = NOW() ';
					$msg = 'ruf aufgehoben';
				}
				$sql .= 'WHERE prt_id = ?';
				$this->db->query($sql, [ $prt_id_v ]);

				$this->db->insert('bf_history', array(
					'hst_prt_id'=>$prt_id_v,
					'hst_stf_id'=>$this->session->stf_login_id,
					'hst_action'=>$action,
					'hst_escalation'=>0,
					'hst_notes'=>$supervisor_comment->getValue()));

				$this->setSuccess($prt_supervision_firstname->getValue()." ".$prt_supervision_lastname->getValue().' '.$msg);
				$supervisor_comment->setValue('');
				redirect("participant");
			}
			
			if ($escallate->submitted()) {
				$call_esc = $participant_row['prt_call_escalation']+1;
				if (!is_empty($call_status) && $call_status != CALL_CANCELLED && $call_status != CALL_COMPLETED) {
					$sql = 'UPDATE bf_participants SET
						prt_call_status = '.CALL_PENDING.',
						prt_call_escalation = ?,
						prt_call_change_time = NOW()
					WHERE prt_id = ?'; 
					$this->db->query($sql, array($call_esc, $prt_id_v));

					$this->db->insert('bf_history', array(
						'hst_prt_id'=>$prt_id_v,
						'hst_stf_id'=>$this->session->stf_login_id,
						'hst_action'=>ESCALATE,
						'hst_escalation'=>$call_esc,
						'hst_notes'=>$supervisor_comment->getValue()));

					$this->setSuccess($prt_supervision_firstname->getValue()." ".$prt_supervision_lastname->getValue().' ruf eskaliert');
					$supervisor_comment->setValue('');
					redirect("participant");
				}
			}
			
			if ($go_to_wc->submitted() || $back_from_wc->submitted()) {
				if (is_empty($participant_row['prt_wc_time'])) {
					$action = GO_TO_WC;
					$msg = 'ging nach WC';
					$sql = 'UPDATE bf_participants SET prt_wc_time = NOW() WHERE prt_id = ?';
				}
				else {
					$action = BACK_FROM_WC;
					$msg = 'zurück von WC';
					$sql = 'UPDATE bf_participants SET prt_wc_time = NULL WHERE prt_id = ?';
				}
				$this->db->query($sql, array($prt_id_v));

				//$this->db->insert('bf_history', array(
				//	'hst_prt_id'=>$prt_id_v,
				//	'hst_stf_id'=>$this->session->stf_login_id,
				//	'hst_action'=>$action,
				//	'hst_notes'=>$register_comment->getValue()));
				$this->db->insert('bf_history', array(
					'hst_prt_id'=>$prt_id_v,
					'hst_stf_id'=>$this->session->stf_login_id,
					'hst_action'=>$action));

				$this->setSuccess($prt_supervision_firstname->getValue()." ".$prt_supervision_lastname->getValue().' '.$msg);
				redirect("participant");
			}
		}

		if (is_empty($prt_id_v)) {
			$clear_participant->setValue('Clear');
			$clear_nr_name->hide();
			$save_participant->hide();
			$go_to_wc->hide();
			$back_from_wc->hide();
			$being_fetched->hide();
			$cancel_fetch->hide();
			$unregister->hide();
			$register->hide();
			$cancel_super->hide();
			$call_super->hide();
			$escallate->hide();
			$reg_field = '';
			$call_field = '';
			$age_field->setValue(div(array('id' => 'prt_age'), ''));
			$curr_age = null;
		}
		else {
			$clear_participant->setValue('Kind Registrieren...');
			// Not using this button, so always hidden!
			$clear_nr_name->hide();

			$new_participant->hide();
			if ($participant_row['prt_registered'] == REG_YES) {
				if (is_empty($participant_row['prt_wc_time'])) {
					$reg_field = out("");
					$back_from_wc->hide();
				}
				else {
					$reg_field = div(array('class'=>'white-box'), 'WC '.how_long_ago($participant_row['prt_wc_time']));
					$reg_field->add(" ");
					$go_to_wc->hide();
				}
				$reg_field->add(div(array('class'=>'green-box'), 'Angemeldet'));
				$cancel_fetch->hide();
				$register->hide();
			}
			else if ($participant_row['prt_registered'] == REG_BEING_FETCHED) {
				$reg_field = div(array('class'=>'yellow-box'), 'Wird abgeholt');
				$go_to_wc->hide();
				$back_from_wc->hide();
				$being_fetched->hide();
				$register->hide();
			}
			else {
				$reg_field = div(array('class'=>'red-box'), 'Abgemeldet');
				$go_to_wc->hide();
				$back_from_wc->hide();
				$being_fetched->hide();
				$cancel_fetch->hide();
				$unregister->hide();
			}

			$call_status = $participant_row['prt_call_status'];
			if (is_empty($call_status) || $call_status == CALL_CANCELLED || $call_status == CALL_COMPLETED) {
				if ($call_status == CALL_CANCELLED)
					$call_field = div(array('class'=>'red-box'), TEXT_CANCELLED);
				else if ($call_status == CALL_COMPLETED)
					$call_field = div(array('class'=>'red-box'), TEXT_COMPLETED);
				else
					$call_field = '';
				$escallate->hide();
				$cancel_super->hide();
			}
			else {
				$str = how_long_ago($participant_row['prt_call_start_time']);
				if (!is_empty($participant_row['prt_call_escalation']))
					$str .= ' ('.$participant_row['prt_call_escalation'].')';
				if ($participant_row['prt_call_status'] == CALL_CALLED) {
					$cancel_super->setValue('Ruf Beenden');
					$call_field = div(array('class'=>'blue-box', 'style'=>'width: 210px;'), TEXT_CALLED.': '.$str);
				}
				else {
					$cancel_super->setValue('Ruf Aufheben');
					if (!is_empty($participant_row['prt_call_escalation']))
						$call_field = div(array('class'=>'blue-box', 'style'=>'width: 210px;'), TEXT_ESCALATED.': '.$str);
					else
						$call_field = div(array('class'=>'blue-box', 'style'=>'width: 210px;'), TEXT_PENDING.': '.$str);
				}
				$call_super->hide();
			}

			$history_list_loader = new AsyncLoader('history_list', 'participant/gethistory');

			$curr_age = get_age($prt_birthday->getDate());
			$age_field->setValue(div(array('id' => 'prt_age'), is_null($curr_age) ? '&nbsp;-' : b(nbsp().$curr_age." Jahre")));
		}
			

		$status_line = table([ 'width'=>'100%' ],
			tr(td($participant_row['prt_number']),
			td([ 'align'=>'center' ], $call_field),
			td([ 'align'=>'right', 'style'=>'white-space: nowrap;' ], $reg_field)));
		$number1->setValue($status_line);
		$number3->setValue($status_line);

		$bday = '';
		$group = ''; // Info for Abholkarte
		if (!is_null($curr_age)) {
			$attr = [ 'class'=>'group-s',
				'style'=>'width: 32px; min-width: 32px; height: 28px; font-size: 24px; text-align: center;' ];
			if ($participant_row['prt_registered'] == REG_NO || empty($participant_row['prt_group_number'])) {
				if ($curr_age <= $age_level_to[AGE_LEVEL_0])
					$attr['class'] .= ' g-'.AGE_LEVEL_0;
				else if ($curr_age >= $age_level_from[AGE_LEVEL_2])
					$attr['class'] .= ' g-'.AGE_LEVEL_2;
				else
					$attr['class'] .= ' g-'.AGE_LEVEL_1;
			}
			else {
				$attr['style'] .= ' background-color: lightgrey; color: white;';
				$group = span([ 'class'=>'group-s',
					'style'=>'width: 40px; min-width: 40px; height: 28px; font-size: 24px; text-align: center; color: black;' ],
					substr($group_colors[$participant_row['prt_age_level']], 0, 1).$participant_row['prt_group_number']);
			}
			$bday = span($attr, $curr_age);
			$bday .= ' '.$participant_row['prt_birthday'];
		}
		$child_data = table([ 'width'=>'100%', 'style'=>
			'border: 1px solid black; font-weight: bold; background-color: white;' ]);
		$child_data->add(tr(td([ 'colspan'=>2, 'style'=>'padding: 5px 5px 5px 10px; font-size: 20px; '],
			'# '.$participant_row['prt_number'])));
		$child_data->add(tr(td([ 'colspan'=>2, 'style'=>'padding: 5px 5px 5px 10px; font-size: 20px; '],
			$participant_row['prt_firstname'].' '.$participant_row['prt_lastname'])));
		$child_data->add(tr());
		$child_data->add(td([ 'style'=>'padding: 5px 5px 5px 10px; font-size: 18px;'], $bday));
		$child_data->add(td([ 'align'=>'right', 'style'=>'padding-right: 10px; ' ], $group));
		$child_data->add(_tr());
		$child_data->add(_table());
		
		$status_line = table([ 'width'=>'100%' ],
			td([ 'align'=>'left', 'style'=>'white-space: nowrap; padding: 0px;' ], $call_field),
			td([ 'align'=>'right', 'style'=>'white-space: nowrap; padding: 0px;' ], $reg_field));
		$reg_data = table([ 'width'=>'100%' ]);
		$reg_data->add(tr());
		$perc = empty($call_field) ? 48 : 40;
		$reg_data->add(td([ 'rowspan'=>3, 'valign'=>'top', 'style'=>'width: '.$perc.'%;' ], $child_data));
		$reg_data->add(td([ 'colspan'=>2, 'valign'=>'top', 'style'=>'padding-left: 10px;'  ], $status_line));
		$reg_data->add(_tr());
		$reg_data->add(tr(th(['align'=>'right', 'style'=>'padding-left: 10px;' ], 'Begleitperson:'),
			td($participant_row['prt_supervision_firstname'].' '.$participant_row['prt_supervision_lastname'])));
		$reg_data->add(tr(th(['align'=>'right' ], 'Handy-Nr:'),
			td($participant_row['prt_supervision_cellphone'])));
		$reg_data->add(_table());
		$register_data->setValue($reg_data);

		$participants_list_loader = new AsyncLoader('participants_list', 'participant/getkids?prt_page='.$prt_page->getValue(), [ 'prt_filter' ]);


		$prt_tab = in('prt_tab', 'register');
		$prt_tab->persistent();

		// Generate page ------------------------------------------
		$this->header('Kinder');

		table([ 'style'=>'border-collapse: collapse;' ]);
		tr();

		td(array('class'=>'left-panel', 'style'=>'width: 604px;', 'align'=>'left', 'valign'=>'top', 'rowspan'=>2));
			$display_participant->open();
			table([ 'class'=>'input-table' ]);
			tr(td(table(tr(td($prt_filter), td(nbsp()), td($clear_filter), td(nbsp()), td($also_reg_filter)))));
			tr(td($participants_list_loader->html()));
			_table(); // 
			$display_participant->close();
		_td();

		td([ 'align'=>'left', 'valign'=>'top', 'style'=>'height: 100px;' ]);
			table([ 'style'=>'border-collapse: collapse; margin-right: 5px;' ]);
			tbody();
			tr();

			td(array('width'=>'33.33%'), div($this->tabAttr($prt_tab, 'register', 'margin-left: 2px; margin-right: 2px;'), 'An u. Abmeldung'));
			td(array('width'=>'33.33%'), div($this->tabAttr($prt_tab, 'modify', 'margin-right: 2px;'), 'Registrierung u. Ändern'));
			td(array('width'=>'33.33%'), div($this->tabAttr($prt_tab, 'supervisor', 'margin-left: 2px;'), 'Eltern Ruf'));
			_tr();
			tr();
			td(array('colspan'=>3, 'align'=>'left'));
				$update_participant->open();
				table(array('style'=>'border-collapse: collapse; min-width:638px;'));
				tbody();
					tr();
					td(array('style'=>'border: 1px solid black; padding: 10px 5px;'));
					div($this->tabContentAttr($prt_tab, 'register'));
					$update_participant->show('tab_register');
					_div();
					div($this->tabContentAttr($prt_tab, 'modify'));
					$update_participant->show('tab_modify');
					_div();
					div($this->tabContentAttr($prt_tab, 'supervisor'));
					$update_participant->show('tab_supervisor');
					_div();
					_td();
					_tr();
				_tbody();
				_table();
				$update_participant->close();
			_td();
			_tr();
			tr();
			td([ 'colspan'=>3 ]);
			$this->printResult();
			_td();
			_tr();
			_tbody();
			_table();
		_td();
		_tr();
		if (isset($history_list_loader)) {
			tr(td([ 'align'=>'left', 'valign'=>'top' ], $history_list_loader->html()));
		}
		else {
			tr(td(nbsp()));
		}
		_table();

		script();
		// Dummy function, because this tab does not have a load function:
		out('
			function get_supervisor_parts() {
				var fname = $("#prt_supervision_firstname").val().trim();
				var lname = $("#prt_supervision_lastname").val().trim();
				if (fname.length > 0 || lname.length > 0) {
					$("#prt_filter").val("@"+fname+" "+lname);
					participants_list();
				}
			}
		');
		out('
			function supervisor_group_list() {
			}
		');
		out('
			function birthday_changed() {
				var new_value = dateChanged($("#prt_birthday"));
				var age = getAge(new_value);
				if (age < 0)
					$("#prt_age").html("&nbsp;-");
				else
					$("#prt_age").html("&nbsp;<b>"+age+" Jahre</b>");
			}
			$("#prt_birthday").keyup(birthday_changed);
		');
		out('
			function poll_groups() {
				var tab = "";
				if ($("#tab_content_modify").css("display") == "block")
					tab = "modify";
				else if ($("#tab_content_register").css("display") == "block")
					tab = "register";
				else
					return;
				var args = "tab="+tab+"&gpa="+$("#"+tab+"_groups_per_age").val()+"&cnt="+$("#history_count").val();
				$.getScript("participant/pollgroups?"+args);
			}
		');
		out('window.setInterval(poll_groups, 5000);');
		_script();
		$this->footer();
	}

	private function tabAttr($prt_tab, $tab, $style) {
		$attr = array('id'=>'tab_selector_'.$tab);
		if ($prt_tab->getValue() == $tab)
			$attr['class'] = 'participant-tabs active';
		else
			$attr['class'] = 'participant-tabs';
		$attr['onclick'] = 'showTab("'.$tab.'"); '.$tab.'_group_list();';
		$attr['style'] = $style;
		return $attr;
	}

	private function tabContentAttr($prt_tab, $tab) {
		$attr = array('id'=>'tab_content_'.$tab);
		if ($prt_tab->getValue() == $tab)
			$attr['style'] = 'display: block;';
		else
			$attr['style'] = 'display: none';
		return $attr;
	}

	public function getkids() {
		if (!$this->authorize())
			return;

		$this->db->where('prt_call_status IN ('.CALL_CANCELLED.', '.CALL_COMPLETED.') AND ADDTIME(prt_call_change_time, "'.CALL_ENDED_DISPLAY_TIME.'") <= NOW()');
		$this->db->update('bf_participants', array('prt_call_status'=>CALL_NOCALL));

		$prt_filter = in('prt_filter');
		$prt_filter->persistent();
		$prt_last_filter = in('prt_last_filter');
		$prt_last_filter->persistent();
		$prt_page = in('prt_page', 1);
		$prt_page->persistent();
		$prt_tab = in('prt_tab', 'modify');
		$prt_tab->persistent();
		
		$prt_filter_v = $prt_filter->getValue();
		$qtype = 0;
		if (is_empty($prt_filter_v)) {
			$prt_filter_v = '%';
			$order_by = 'prt_modifytime DESC';
		}
		else {
			$order_by = 'prt_firstname,prt_lastname';
			if (preg_match('/^[0-9]{1,2}\.([0-9]{1,2}(\.[0-9]{0,4})?)?$/', $prt_filter_v)) {
				$qtype = 1;
				$args = explode('.', $prt_filter_v);
				for ($i=sizeof($args)-1; $i>=0; $i--) {
					if (empty($args[$i]))
						array_pop($args);
					else
						$args[$i] = (integer) $args[$i];
				}
			}
			else if (preg_match('/^[A-Za-z]+ [A-Za-z]+$/', $prt_filter_v)) {
				$qtype = 2;
				$args = explode(' ', $prt_filter_v);
				$args[0] .= '%';
				$args[1] .= '%';
			}
			else if (str_startswith($prt_filter_v, '@')) {
				$qtype = 3;
				$prt_filter_v = str_right($prt_filter_v, '@');
			}
			else
				$prt_filter_v = '%'.$prt_filter_v.'%';
		}
		if ($prt_tab->getValue() == 'supervisor')
			$order_by = 'prt_registered DESC,calling,prt_call_start_time DESC';

		$prt_page_v = $prt_page->getValue();
		if ($prt_filter_v.'|'.$order_by != $prt_last_filter->getValue()) {
			$prt_page->setValue(1);
			$prt_page_v = $prt_page->getValue();
		}

		$prt_last_filter->setValue($prt_filter_v.'|'.$order_by);

		$sql = 'SELECT SQL_CALC_FOUND_ROWS prt_id, prt_number, CONCAT(prt_firstname, " ", prt_lastname) as prt_name,
			prt_birthday, "age", prt_age_level, prt_group_number, prt_call_status, prt_registered, prt_wc_time, "button_column",
			IF(prt_call_status = '.CALL_PENDING.' OR prt_call_status = '.CALL_CALLED.', 0, 1) calling, prt_call_start_time
			FROM bf_participants WHERE ';
		if ($qtype == 1) {
			// Date
			$sql .= 'DAY(prt_birthday) = ? ';
			if (count($args) > 1)
				$sql .= 'AND MONTH(prt_birthday) = ? ';
			if (count($args) > 2) {
				if ((integer) $args[2])
					$args[2] = 2000 + (integer) $args[2];
				$sql .= 'AND YEAR(prt_birthday) = ? ';
			}
		}
		else if ($qtype == 2) {
			// First_Last
			$sql .= 'prt_firstname LIKE ? AND prt_lastname LIKE ?';
		}
		else if ($qtype == 3) {
			if (str_contains($prt_filter_v, ' ')) {
				$fname = trim(str_left($prt_filter_v, ' '));
				$lname = trim(str_right($prt_filter_v, ' '));
				if (empty($fname)) {
					$sql .= 'prt_supervision_lastname = ?';
					$args = [ $lname ];
				}
				else {
					$sql .= 'prt_supervision_firstname = ? AND prt_supervision_lastname = ?';
					$args = [ $fname, $lname ];
				}
			}
			else {
				$sql .= 'prt_supervision_firstname = ?';
				$args = [ $prt_filter_v ];
			}
		}
		else {
			$sql .= 'CONCAT(prt_number, "$", prt_firstname, " ", prt_lastname, "$",
					prt_supervision_firstname, " ", prt_supervision_lastname) LIKE ?';
			$args = [ $prt_filter_v ];
		}
//bugout($sql);
//bugout($args);
		$participant_table = new ParticipantTable($sql, $args,
			array('class'=>'details-table participant-table', 'style'=>'width: 600px;'));
		$participant_table->setPagination('participant?prt_page=', 18, $prt_page_v);
		$participant_table->setOrderBy($order_by);
		table(array('style'=>'border-collapse: collapse;'));
		tr(td($participant_table->paginationHtml()));
		tr(td($participant_table->html()));
		_table();
	}

	private function get_group_data($prt_id_v)
	{
		list($current_period, $nr_of_groups,
			$total_limit, $total_count, $total_limits, $total_counts,
			$group_limits, $group_counts) = $this->get_period_data();

		$reserve_counts = db_array_2('SELECT CONCAT(stf_reserved_age_level, "_", stf_reserved_group_number),
			SUM(stf_reserved_count)
			FROM bf_staff WHERE stf_reserved_count > 0 AND stf_reserved_group_number > 0
			GROUP BY stf_reserved_age_level, stf_reserved_group_number');
		foreach ($reserve_counts as $group=>$count) {
			$age = str_left($group, '_');
			$num = str_right($group, '_');
			if (arr_nvl($nr_of_groups, $age, 0) < $num)
				$nr_of_groups[$age] = $num;
		}
		
		$participant_row = $this->get_participant_row($prt_id_v);

		$staff_row = $this->get_staff_row($this->session->stf_login_id);

		return [ $current_period, $nr_of_groups,
			$total_limit, $total_count, $total_limits, $total_counts,
			$group_limits, $group_counts,
			$reserve_counts, $participant_row, $staff_row ];
	}

	public function getgroups() {
		global $age_level_from;
		global $age_level_to;

		if (!$this->authorize())
			return;

		$grp_arg = in('grp_arg');
		$grp_arg_v = $grp_arg->getValue();
		$prt_age_level = str_left($grp_arg_v, '_');
		$prt_group_number = str_right($grp_arg_v, '_');
	
		$action = in('action');
		$action_v = $action->getValue();

		if (!empty($action_v)) {
			switch ($action_v) {
				case 'reserve':
					$this->reserve_group($prt_age_level, $prt_group_number);
					break;
				case 'unreserve':
					$this->unreserve_groups($prt_age_level, $prt_group_number);
					break;
			}
		}

		$prt_id = in('prt_id');
		$prt_id->persistent();
		$prt_id_v = $prt_id->getValue();

		$tab = in('tab');
		$tab_v = $tab->getValue();

		list($current_period, $nr_of_groups,
			$total_limit, $total_count, $total_limits, $total_counts,
			$group_limits, $group_counts,
			$reserve_counts, $participant_row, $staff_row) = $this->get_group_data($prt_id_v);

		table([ 'style'=>'border-spacing: 0;' ]);
		$groups_per_age = ''; 
		for ($a=0; $a<AGE_LEVEL_COUNT; $a++) {
			$group_nr = arr_nvl($nr_of_groups, $a, 0);
			$groups_per_age .= $a.'_'.$group_nr.':';
			if (empty($group_nr))
				continue;
			tr();
			th([ 'style'=>'padding: 0px 2px;', 'align'=>'right' ], $age_level_from[$a].' - '.$age_level_to[$a].':');
			for ($i=1; $i<=$group_nr; $i++) {
				td( [ 'style'=>'padding: 0px 2px;' ] );

				$reserve_onclick = $tab_v.'_group_list("'.$a.'_'.$i.'", "reserve");';
				$my_reserve_count = if_empty($staff_row['stf_reserved_count'], 0);
				if ($staff_row['stf_reserved_age_level'] == $a &&
					$staff_row['stf_reserved_group_number'] == $i &&
					$my_reserve_count > 0) {
					$table_onclick = '';
					$onclick = $tab_v.'_group_list("'.$a.'_'.$i.'", "unreserve");';
					$vis = '';
				}
				else {
					$table_onclick = $reserve_onclick;
					$onclick = '';
					$reserve_onclick = '';
					$vis = ' visibility: hidden;';
				}
				$reserve_box = span([ 'class'=>'group-number',
					'style'=>'background-color: white; border-radius: 0px; width: 18px;'.$vis ],
					$staff_row['stf_reserved_count']);

				$opa = '';
				if (!empty($vis) &&
					($participant_row['prt_age_level'] != $a || $participant_row['prt_group_number'] != $i))
					$opa = 'opacity: 0.5;';

				$r_count = arr_nvl($reserve_counts, $a.'_'.$i, 0);
				$count = arr_nvl($group_counts, $a.'_'.$i, 0) + $r_count;
				$r_count -= $my_reserve_count;
				$limit = if_empty(arr_nvl($group_limits, $a.'_'.$i, 0), DEFAULT_GROUP_SIZE);

				// GROUP BOX:
				table([ 'class'=>'participant-group g-'.$a, 'onclick'=>$table_onclick, 'style'=>$opa ]);
				tr();
				$row_style = 'padding: 2px 0px 0px 0px; text-align: center;';
				td([ 'onclick'=>$onclick, 'style'=>$row_style ], span([ 'class'=>'group-number' ], $i));
				td([ 'id'=>$tab_v.'_group_c_'.$a.'_'.$i, 'onclick'=>$onclick, 'style'=>$row_style.' min-width: 24px;' ], $count > 0 ? $count : '-');
				td([ 'onclick'=>$reserve_onclick, 'style'=>$row_style ], $reserve_box);
				_tr();
				tr();
				$row_style = 'padding: 0px 0px 2px 0px; font-size: 14px; text-align: center;';
				td([ 'onclick'=>$onclick, 'style'=>$row_style  ], nbsp());
				td([ 'id'=>$tab_v.'_group_l_'.$a.'_'.$i, 'onclick'=>$onclick, 'style'=>$row_style  ], $limit);
				td([ 'id'=>$tab_v.'_group_r_'.$a.'_'.$i, 'onclick'=>$reserve_onclick, 'style'=>$row_style  ], $r_count > 0 ? $r_count : '');
				_tr();
				_table();
				_td();
			}
			_tr();
		}
		_table();
		if ($participant_row['prt_group_number'] > 0)
			$groups_per_age .= $participant_row['prt_age_level'].'_'.$participant_row['prt_group_number'];
		hidden($tab_v.'_groups_per_age', $groups_per_age);
	}

	public function gethistory() {
		if (!$this->authorize())
			return;

		$prt_id = in('prt_id');
		$prt_id->persistent();
		$prt_id_v = $prt_id->getValue();
		$hst_page = in('hst_page', 1);
		$hst_page->persistent();

		$history_table = new HistoryTable('SELECT SQL_CALC_FOUND_ROWS hst_action, hst_timestamp,
			stf_username, hst_escalation, hst_age_level, hst_group_number, hst_notes
			FROM bf_history LEFT JOIN bf_staff ON stf_id = hst_stf_id
			WHERE hst_prt_id = ? ORDER BY hst_timestamp DESC',
			[ $prt_id_v ], [ 'class'=>'details-table history-table' ]);
		$history_table->setPagination('participant?hst_page=', 10, $hst_page->getValue());

		table(array('style'=>'border-collapse: collapse;'));
		tr(td([ 'align'=>'left', 'valign'=>'top' ], $history_table->paginationHtml()));
		tr(td([ 'align'=>'left', 'valign'=>'top', 'style'=>'padding: 0px 20px 20px 0px;' ], $history_table->html()));
		_table();
		hidden('history_count', $history_table->getRowCount());
	}

	public function pollgroups() {
		if (!$this->authorize())
			return;

		$prt_id = in('prt_id');
		$prt_id->persistent();
		$prt_id_v = $prt_id->getValue();

		$tab = in('tab');
		$tab_v = $tab->getValue();

		$gpa = in('gpa');
		$gpa_v = $gpa->getValue();

		$cnt = in('cnt');
		$cnt_v = $cnt->getValue();

		list($current_period, $nr_of_groups,
			$total_limit, $total_count, $total_limits, $total_counts,
			$group_limits, $group_counts,
			$reserve_counts, $participant_row, $staff_row) = $this->get_group_data($prt_id_v);

		$groups_per_age = ''; 
		for ($a=0; $a<AGE_LEVEL_COUNT; $a++) {
			$group_nr = arr_nvl($nr_of_groups, $a, 0);
			$groups_per_age .= $a.'_'.$group_nr.':';
			if (empty($group_nr))
				continue;
			for ($i=1; $i<=$group_nr; $i++) {
				$my_reserve_count = if_empty($staff_row['stf_reserved_count'], 0);
				$r_count = arr_nvl($reserve_counts, $a.'_'.$i, 0);
				$count = arr_nvl($group_counts, $a.'_'.$i, 0) + $r_count;
				$r_count -= $my_reserve_count;
				$limit = if_empty(arr_nvl($group_limits, $a.'_'.$i, 0), DEFAULT_GROUP_SIZE);
				out('$("#'.$tab_v.'_group_c_'.$a.'_'.$i.'").html("'.($count > 0 ? $count : '-').'");');
				out('$("#'.$tab_v.'_group_r_'.$a.'_'.$i.'").html("'.($r_count > 0 ? $r_count : '').'");');
				out('$("#'.$tab_v.'_group_l_'.$a.'_'.$i.'").html("'.$limit.'");');
			}
		}
		if ($participant_row['prt_group_number'] > 0)
			$groups_per_age .= $participant_row['prt_age_level'].'_'.$participant_row['prt_group_number'];
		if ($gpa_v != $groups_per_age) {
			out($tab_v.'_group_list();');
			out('history_list();');
		}
		else {
			$history_count = (integer) db_1_value('SELECT COUNT(*) FROM bf_history WHERE hst_prt_id = ?', [ $prt_id_v ]);
			if ($cnt_v != $history_count)
				out('history_list();');
		}
	}
}
