<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class ParticipantTable extends Table {
	private function getOrder($col) {
		if ($col == str_left($this->order_by, ' ')) {
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
				return $this->getOrder('prt_name').'Name';
			case 'prt_supervision_name':
				return 'Begleitperson';
			case 'prt_call_status';
				return $this->getOrder('calling,prt_call_start_time').'Ruf';
			case 'prt_registered';
				return 'Angem.';
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'prt_number':
			case 'prt_name':
			case 'prt_supervision_name':
				$value = $row[$field];
				return $value;
				//return href(url('participant', array('prt_id'=>$row['prt_id'])), $value);
			case 'prt_call_status': {
				$call_status = $row['prt_call_status'];
				if (is_empty($call_status))
					return nbsp();					
				if ($call_status == CALL_CANCELLED || $call_status == CALL_COMPLETED)
					return div(array('class'=>'red-box', 'style'=>'width: 62px; height: 22px;'), '- Ruf');
				return div(array('class'=>'blue-box', 'style'=>'width: 62px; height: 22px;'), how_long_ago($row['prt_call_start_time']));
			}
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
			case 'button_column':
				return (new Submit('select', 'Bearbeiten', array('class'=>'button-black', 'onclick'=>'$("#set_prt_id").val('.$row['prt_id'].');')))->html();
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
				return 'Kommentar';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'hst_action':
				switch ($row[$field]) {
					case CREATED:
						return 'Aufgenommen';
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
					case ENDED:
						return TEXT_COMPLETED;
					case GO_TO_WC:
						return 'Zum WC gegangen';
					case BACK_FROM_WC:
						return 'Von WC zurück';
					case BEING_FETCHED:
						return 'Wird Abgeholt';
				}
				return '';
			case 'hst_timestamp':
				$today = new DateTime();
				$today->setTime(0, 0, 0);
				$date = date_create_from_format('Y-m-d H:i:s', $row[$field]);
				$diff = $today->diff($date);
				$diff_days = (integer) $diff->format("%R%a");
				switch($diff_days) {
				    case 0:
						return 'Heute '.$date->format('H:i');
				    case -1:
						return 'Gestern '.$date->format('H:i');
				    case -2:
						return 'Vorgestern '.$date->format('H:i');
				}
				return $date->format('d.m.Y H:i');
			case 'stf_username':
				return $row[$field];
			case 'hst_notes';
				$val = $row[$field];
				return is_empty($val) ? '' : $val;
		}
		return nix();
	}
}

class Participant extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function index()
	{
		$this->authorize();

		$display_participant = new Form('display_participant', 'participant', 1, array('class'=>'input-table'));
		$set_prt_id = $display_participant->addHidden('set_prt_id');
		$prt_filter = $display_participant->addTextInput('prt_filter', '', '', array('placeholder'=>'Suchfilter'));
		$prt_filter->makeGlobal();
		$prt_page = new Hidden('prt_page', 1);
		$prt_page->makeGlobal();
		$clear_filter = $display_participant->addSubmit('clear_filter', 'Clear',
			array('class'=>'button-black', 'onclick'=>'$("#prt_filter").val(""); participants_list(); return false;'));

		$update_participant = new Form('update_participant', 'participant', 2, array('class'=>'input-table'));
		if (!is_empty($this->session->stf_technician))
			$update_participant->disable();
		$prt_id = $update_participant->addHidden('prt_id');
		$prt_id->makeGlobal();
		$hst_page = new Hidden('hst_page', 1);
		$hst_page->makeGlobal();

		if ($set_prt_id->submitted()) {
			$prt_id->setValue($set_prt_id->getValue());
			$hst_page->setValue(1);
			redirect("participant");
		}
		
		$participant_row = $this->get_participant_row($prt_id->getValue());

		$number1 = $update_participant->addField('Kinder-Nr');
		$number1->setFormat('colspan=2');
		$prt_firstname = $update_participant->addTextInput('prt_firstname', 'Name', $participant_row['prt_firstname'], array('placeholder'=>'Vorname'));
		$prt_firstname->setRule('required');
		$prt_lastname = $update_participant->addTextInput('prt_lastname', 'Nachname', $participant_row['prt_lastname'], array('placeholder'=>'Nachname'));
		$prt_lastname->setFormat('nolabel');
		$prt_lastname->setRule('required');
		$prt_birthday = $update_participant->addTextInput('prt_birthday', 'Geburtsdatum',
			$participant_row['prt_birthday'], array('placeholder'=>'DD.MM.JJJJ'));
		$prt_birthday->setRule('is_valid_date');
		$age_field = $update_participant->addSpace();
		$prt_supervision_firstname = $update_participant->addTextInput('prt_supervision_firstname', 'Begleitperson',
			$participant_row['prt_supervision_firstname'], array('placeholder'=>'Vorname'));
		$prt_supervision_lastname = $update_participant->addTextInput('prt_supervision_lastname', 'Begleitperson Nachname',
			$participant_row['prt_supervision_lastname'], array('placeholder'=>'Nachname'));
		$prt_supervision_lastname->setFormat('nolabel');
		$prt_supervision_cellphone = $update_participant->addTextInput('prt_supervision_cellphone', 'Handy-Nr', $participant_row['prt_supervision_cellphone']);
		$update_participant->addSpace();
		$groups = db_array_2('SELECT g.grp_id, CONCAT(g.grp_name, ", ", '.
			'IF(g.grp_from_age IS NULL OR g.grp_from_age = 0, "", g.grp_from_age), "-",  '.
			'IF(g.grp_to_age IS NULL OR g.grp_to_age = 0, "", g.grp_to_age), " (", COUNT(p.prt_id), ")") grp_name '.
			'FROM bf_groups g '.
			'LEFT JOIN bf_participants p ON p.prt_grp_id = g.grp_id AND prt_registered != '.REG_NO.' GROUP BY g.grp_id ORDER BY grp_from_age, grp_name');
		$groups = array(0 => '') + $groups;
		$prt_grp_id = $update_participant->addSelect('prt_grp_id', 'Kleingruppe', $groups, $participant_row['prt_grp_id']);
		$update_participant->addText($participant_row['loc_name']);
		$prt_notes = $update_participant->addTextArea('prt_notes', 'Notizen', $participant_row['prt_notes']);
		$prt_notes->setFormat('colspan=2');

		$save_participant = $update_participant->addSubmit('save_participant', 'Änderung Sichern', array('class'=>'button-green'));
		$new_participant = $update_participant->addSubmit('new_participant', 'Kind Aufnehmen ', array('class'=>'button-green'));
		$clear_nr_name = $update_participant->addSubmit('clear_no_name', 'Geschwister Aufnehmen...', array('class'=>'button-blue'));
		$clear_participant = $update_participant->addSubmit('clear_participant', '', array('class'=>'button-black'));

		$update_participant->createGroup('tab_modify');

		$number2 = $update_participant->addField('Kinder-Nr');
		$number2->setFormat('colspan=2');
		$f1 = $update_participant->addTextInput('prt_firstname', 'Name', $participant_row['prt_firstname'], array('placeholder'=>'Vorname'));
		$f1->disable();
		$f2 = $update_participant->addTextInput('prt_lastname', 'Nachname', $participant_row['prt_lastname'], array('placeholder'=>'Nachname'));
		$f2->disable();
		$f2->setFormat('nolabel');
		$register_group = $update_participant->addSelect('register_group', 'Kleingruppe', $groups, $participant_row['prt_grp_id']);
		$update_participant->addSpace();
		$register_comment = $update_participant->addTextArea('register_comment', 'Kommentar');
		$register_comment->setFormat('colspan=2');

		$go_to_wc = $update_participant->addSubmit('go_to_wc', 'WC', array('class'=>'button-white wc'));
		$back_from_wc = $update_participant->addSubmit('back_from_wc', 'WC', array('class'=>'button-white wc strike-thru'));
		$being_fetched = $update_participant->addSubmit('being_fetched', 'Wird Abgeholt', array('class'=>'button-yellow register'));
		$cancel_fetch = $update_participant->addSubmit('cancel_fetch', 'Abholen Abbrechen', array('class'=>'button-yellow register'));
		$unregister = $update_participant->addSubmit('unregister', 'Abmelden', array('class'=>'button-red register'));
		$register = $update_participant->addSubmit('register', 'Anmelden', array('class'=>'button-green register'));

		$update_participant->createGroup('tab_register');

		$number3 = $update_participant->addField('Kinder-Nr');
		$number3->setFormat('colspan=2');
		$f1 = $update_participant->addTextInput('prt_firstname', 'Name', $participant_row['prt_firstname'], array('placeholder'=>'Vorname'));
		$f1->disable();
		$f2 = $update_participant->addTextInput('prt_lastname', 'Nachname', $participant_row['prt_lastname'], array('placeholder'=>'Nachname'));
		$f2->disable();
		$f2->setFormat('nolabel');
		$f3 = $update_participant->addTextInput('prt_supervision_firstname', 'Begleitperson',
			$participant_row['prt_supervision_firstname'], array('placeholder'=>'Vorname'));
		$f3->disable();
		$f4 = $update_participant->addTextInput('prt_supervision_lastname', 'Begleitperson Nachname',
			$participant_row['prt_supervision_lastname'], array('placeholder'=>'Nachname'));
		$f4->disable();
		$f4->setFormat('nolabel');
		$supervisor_comment = $update_participant->addTextArea('supervisor_comment', 'Kommentar');
		$supervisor_comment->setFormat('colspan=2');

		$escallate = $update_participant->addSubmit('escallate', 'Eskalieren', array('class'=>'button-blue'));
		$call_super = $update_participant->addSubmit('call_super', 'Ruf Eltern', array('class'=>'button-blue'));
		$cancel_super = $update_participant->addSubmit('cancel_super', 'Ruf Aufheben ', array('class'=>'button-red'));

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
		}

		$prt_id_v = $prt_id->getValue();
		if ($new_participant->submitted() || $save_participant->submitted()) {
			$this->error = $update_participant->validate('tab_modify');
			if (is_empty($this->error)) {
				$data = array(
					'prt_firstname' => $prt_firstname->getValue(),
					'prt_lastname' => $prt_lastname->getValue(),
					'prt_birthday' => $prt_birthday->getDate('Y-m-d'),
					'prt_supervision_firstname' => $prt_supervision_firstname->getValue(),
					'prt_supervision_lastname' => $prt_supervision_lastname->getValue(),
					'prt_supervision_cellphone' => $prt_supervision_cellphone->getValue(),
					'prt_grp_id' => $prt_grp_id->getValue(),
					'prt_notes' => $prt_notes->getValue()
				);
				if (is_empty($prt_id_v)) {
					$prt_number = (integer) db_1_value('SELECT MAX(prt_number) FROM bf_participants');
					$prt_number = $prt_number < 100 ? 100 : $prt_number+1;

					$data['prt_number'] = $prt_number;
					$data['prt_create_stf_id'] = $this->session->stf_login_id;
					$this->db->set('prt_modifytime', 'NOW()', FALSE);

					$this->db->insert('bf_participants', $data);
					$prt_id_v = $this->db->insert_id();

					$this->db->insert('bf_history', array(
						'hst_prt_id'=>$prt_id_v,
						'hst_stf_id'=>$this->session->stf_login_id,
						'hst_action'=>CREATED));

					$prt_filter->setValue('');
					$prt_page->setValue(1);
					$prt_id->setValue($prt_id_v);
					$this->setSuccess($prt_firstname->getValue()." ".$prt_lastname->getValue().' angemeldet');
				}
				else {
					$data['prt_modify_stf_id'] = $this->session->stf_login_id;
					$this->db->set('prt_modifytime', 'NOW()', FALSE);

					$this->db->where('prt_id', $prt_id_v);
					$this->db->update('bf_participants', $data);
					$this->setSuccess($prt_firstname->getValue()." ".$prt_lastname->getValue().' geändert');
				}
				redirect("participant");
			}
		}

		if (!is_empty($prt_id_v)) {
			$reg_toggled = $unregister->submitted() || $register->submitted();
			$fetch_toggled = $being_fetched->submitted() || $cancel_fetch->submitted();
			if ($reg_toggled || $fetch_toggled) {
				if ($reg_toggled) {
					if ($participant_row['prt_registered'] == REG_NO) {
						$registered = REG_YES;
						$action = REGISTER;
						$comment = 'angemeldet';
					}
					else {
						$registered = REG_NO;
						$action = UNREGISTER;
						$comment = 'abgemeldet';
					}
				}
				else { // fetch_toggled
					if ($participant_row['prt_registered'] == REG_BEING_FETCHED) {
						$registered = REG_YES;
						$action = REGISTER;
						$comment = 'erneut angemeldet';
					}
					else {
						$registered = REG_BEING_FETCHED;
						$action = BEING_FETCHED;
						$comment = 'wird abgeholt';
					}
				}

				$sql = 'UPDATE bf_participants SET prt_registered = ?, prt_modifytime = NOW()';
				if (!is_empty($participant_row['prt_call_status'])) {
					$sql .= ', prt_call_status = '.CALL_NOCALL;
					$sql .= ', prt_call_escalation = 0';
					$sql .= ', prt_call_start_time = NOW()';

					$this->db->insert('bf_history', array(
						'hst_prt_id'=>$prt_id_v,
						'hst_stf_id'=>$this->session->stf_login_id,
						'hst_action'=>CANCELLED,
						'hst_escalation'=>0));
				}
				if (!is_empty($participant_row['prt_wc_time'])) {
					$sql .= ', prt_wc_time = NULL';
				}
				$sql .= ', prt_grp_id = '.$register_group->getValue();
				$sql .= ' WHERE prt_id = ?';
				$this->db->query($sql, array($registered, $prt_id_v));

				$this->db->insert('bf_history', array(
					'hst_prt_id'=>$prt_id_v,
					'hst_stf_id'=>$this->session->stf_login_id,
					'hst_action'=>$action,
					'hst_notes'=>$register_comment->getValue()));

				$this->setSuccess($prt_firstname->getValue()." ".$prt_lastname->getValue().' '.$comment);
				redirect("participant");
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
						$action = ENDED;
						$sql .= 'prt_call_status = '.CALL_COMPLETED.', ';
					}
					$sql .= 'prt_call_change_time = NOW() ';
					$msg = 'ruf aufgehoben';
				}
				$sql .= 'WHERE prt_id = ?';
				$this->db->query($sql, array($prt_id_v));

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

				$this->db->insert('bf_history', array(
					'hst_prt_id'=>$prt_id_v,
					'hst_stf_id'=>$this->session->stf_login_id,
					'hst_action'=>$action,
					'hst_notes'=>$register_comment->getValue()));

				$this->setSuccess($prt_supervision_firstname->getValue()." ".$prt_supervision_lastname->getValue().' '.$msg);
				redirect("participant");
			}
		}

		//if (!is_empty($this->success)) {
		//	$participant_row = $this->get_participant_row($prt_id_v);
		//	$update_participant->setValues($participant_row);
		//}

		//$prt_id_v = $prt_id->getValue();
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
		}
		else {
			$clear_participant->setValue('Weiteres Aufnehmen...');

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

			$history_table = new HistoryTable('SELECT SQL_CALC_FOUND_ROWS hst_action, hst_timestamp,
				stf_username, hst_escalation, hst_notes
				FROM bf_history LEFT JOIN bf_staff ON stf_id = hst_stf_id
				WHERE hst_prt_id = ? ORDER BY hst_timestamp DESC',
				array($prt_id_v), array('class'=>'details-table history-table'));
			$history_table->setPagination('participant?hst_page=', 10, $hst_page->getValue());

			$curr_age = get_age($prt_birthday->getDate());
			$age_field->setValue(div(array('id' => 'prt_age'), is_null($curr_age) ? '&nbsp;-' : b(nbsp().$curr_age." Jahre alt")));
		}

		$status_line = table(array('width'=>'100%'),
			tr(td($participant_row['prt_number']), td(array('align'=>'center'), $call_field),
			td(array('align'=>'right', 'nowrap'=>''), $reg_field)));
		$number1->setValue($status_line);
		$number2->setValue($status_line);
		$number3->setValue($status_line);

		$async_loader = new AsyncLoader('participants_list', 'participant/getkids?prt_page='.$prt_page->getValue(), array('prt_filter'));

		$prt_tab = new Hidden('prt_tab', 'modify');
		$prt_tab->makeGlobal();

		// Generate page ------------------------------------------
		$this->header('Kinder');

		table(array('style'=>'border-collapse: collapse;'));
		tr();

		td(array('class'=>'left-panel', 'style'=>'width: 604px;', 'align'=>'left', 'valign'=>'top', 'rowspan'=>3));
			$display_participant->open();
			table(array('style'=>'border-collapse: collapse;'));
			tr(td($prt_filter->html(), $clear_filter->html()));
			tr(td($async_loader->html()));
			_table(); // 
			$display_participant->close();
		_td();

		td(array('align'=>'left', 'valign'=>'top'));
			table(array('style'=>'border-collapse: collapse; margin-right: 5px;'));
			tbody();
			tr();

			td(array('width'=>'33.33%'), div($this->tabAttr($prt_tab, 'modify', 'margin-right: 2px;'), 'Aufnehmen u. Ändern'));
			td(array('width'=>'33.33%'), div($this->tabAttr($prt_tab, 'register', 'margin-left: 2px; margin-right: 2px;'), 'An u. Abmeldung'));
			td(array('width'=>'33.33%'), div($this->tabAttr($prt_tab, 'supervisor', 'margin-left: 2px;'), 'Eltern Ruf'));
			_tr();
			tr();
			td(array('colspan'=>3, 'align'=>'left'));
				$update_participant->open();
				table(array('style'=>'border-collapse: collapse;'));
				tbody();
					tr();
					td(array('style'=>'border: 1px solid black; padding: 10px 5px;'));
					div($this->tabContentAttr($prt_tab, 'modify'));
					$update_participant->show('tab_modify');
					_div();
					div($this->tabContentAttr($prt_tab, 'register'));
					$update_participant->show('tab_register');
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
			_tbody();
			_table();
		_td();
		_tr();
		if (isset($history_table)) {
			tr(td(array('align'=>'left', 'valign'=>'top'), $history_table->paginationHtml()));
			tr(td(array('align'=>'left', 'valign'=>'top'), $history_table->html()));
		}
		else {
			tr(td(nbsp()));
			tr(td(nbsp()));
		}
		_table();

		script();
		out('
			function birthday_changed() {
				var value = $("#prt_birthday").val();
				var age = getAge(value);
				if (age < 0)
					$("#prt_age").html("&nbsp;-");
				else
					$("#prt_age").html("&nbsp;<b>"+age+" Jahre alt</b>");
				console.log(value);
			}
			$("#prt_birthday").keyup(birthday_changed);
		');
		_script();

		$this->footer();
	}

	private function tabAttr($prt_tab, $tab, $style) {
		$attr = array('id'=>'tab_selector_'.$tab);
		if ($prt_tab->getValue() == $tab)
			$attr['class'] = 'participant-tabs active';
		else
			$attr['class'] = 'participant-tabs';
		$attr['onclick'] = 'return showTab("'.$tab.'");';
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
		if (!$this->authorize(false)) {
			echo 'Authorization failed';
			return;
		}

		$this->db->where('prt_call_status IN ('.CALL_CANCELLED.', '.CALL_COMPLETED.') AND ADDTIME(prt_call_change_time, "'.CALL_ENDED_DISPLAY_TIME.'") <= NOW()');
		$this->db->update('bf_participants', array('prt_call_status'=>CALL_NOCALL));

		$prt_filter = new TextInput('prt_filter');
		$prt_filter->makeGlobal();
		$prt_last_filter = new TextInput('prt_last_filter');
		$prt_last_filter->makeGlobal();
		$prt_page = new Hidden('prt_page', 1);
		$prt_page->makeGlobal();
		$prt_tab = new Hidden('prt_tab', 'modify');
		$prt_tab->makeGlobal();
		
		$prt_filter_v = $prt_filter->getValue();

		if (is_empty($prt_filter_v)) {
			$prt_filter_v = '%';
			$order_by = 'prt_number DESC';
		}
		else {
			$prt_filter_v = '%'.$prt_filter_v.'%';
			$order_by = 'prt_name';
		}
		if ($prt_tab->getValue() == 'supervisor')
			$order_by = 'calling,prt_call_start_time DESC';

		$prt_page_v = $prt_page->getValue();
		if ($prt_filter_v.'|'.$order_by != $prt_last_filter->getValue()) {
			$prt_page->setValue(1);
			$prt_page_v = $prt_page->getValue();
		}

		$prt_last_filter->setValue($prt_filter_v.'|'.$order_by);

		$participant_table = new ParticipantTable('SELECT SQL_CALC_FOUND_ROWS prt_id, prt_number, CONCAT(prt_firstname, " ", prt_lastname) as prt_name,
			CONCAT(prt_supervision_firstname, " ", prt_supervision_lastname) AS prt_supervision_name, prt_call_status,
			 prt_registered, prt_wc_time, "button_column", IF(prt_call_status = '.CALL_PENDING.' OR prt_call_status = '.CALL_CALLED.', 0, 1) calling, prt_call_start_time
			FROM bf_participants
			WHERE CONCAT(prt_number, "$", prt_firstname, " ", prt_lastname, "$",
				prt_supervision_firstname, " ", prt_supervision_lastname) LIKE ?',
			array($prt_filter_v), array('class'=>'details-table participant-table', 'style'=>'width: 600px;'));
		$participant_table->setPagination('participant?prt_page=', 18, $prt_page_v);
		$participant_table->setOrderBy($order_by);
		table(array('style'=>'border-collapse: collapse;'));
		tr(td($participant_table->paginationHtml()));
		tr(td($participant_table->html()));
		_table();
	}
}
