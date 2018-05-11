<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class CallListTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'prt_number':
				return 'Kinder-Nr';
			case 'prt_call_change_time':
				return 'Lezte Änderung';
			case 'prt_call_status':
				return 'Status';
			case 'prt_call_escalation':
				return 'Eskalation';
			case 'prt_call_start_time':
				return 'Start Zeit';
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'prt_number':
				return $row[$field];
			case 'prt_call_change_time':
				return how_long_ago($row[$field]);
			case 'prt_call_status':
				if ($row[$field] == CALL_PENDING) {
					if ($row['prt_call_escalation'] > 0)
						return div(array('class'=>'green-box', 'style'=>'width: 140px; height: 22px;'), TEXT_ESCALATED);
					return div(array('class'=>'blue-box', 'style'=>'width: 140px; height: 22px;'), TEXT_PENDING);
				}
				if ($row[$field] == CALL_CANCELLED)
					return div(array('class'=>'red-box', 'style'=>'width: 140px; height: 22px;'), TEXT_CANCELLED);
				if ($row[$field] == CALL_COMPLETED)
					return div(array('class'=>'red-box', 'style'=>'width: 140px; height: 22px;'), TEXT_COMPLETED);
				return $row[$field];
			case 'prt_call_escalation':
				return $row[$field];
			case 'prt_call_start_time':
				return how_long_ago($row[$field]);
			case 'button_column':
				if ($row['prt_call_status'] == CALL_CANCELLED)
					return (new Submit('ack_button', 'OK',
						array('class'=>'button-black', 'onclick'=>'$("#cancel_ok").val('.$row['prt_id'].');')))->html();
				if ($row['prt_call_status'] == CALL_PENDING)
					return (new Submit('ack_button', 'Gerufen',
						array('class'=>'button-green', 'onclick'=>'$("#call_done").val('.$row['prt_id'].');')))->html();
				return '&nbsp;';
		}
		return nix();
	}
}

class CallList extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function index()
	{
		$this->authorize();

		$form = new Form('calllist_form', 'calllist', 1, array('class'=>'input-table'));
		$cancel_ok = $form->addHidden('cancel_ok');
		$call_done = $form->addHidden('call_done');

		if (!is_empty($cancel_ok->getValue())) {
			$prt_id = $cancel_ok->getValue();
			$participant_row = $this->get_participant_row($prt_id);
			if ($participant_row['prt_call_status'] == CALL_CANCELLED ||
				$participant_row['prt_call_status'] == CALL_COMPLETED) {
				$sql = 'UPDATE bf_participants SET ';
				$sql .= 'prt_call_status = '.CALL_NOCALL.', ';
				$sql .= 'prt_call_escalation = 0, ';
				$sql .= 'prt_call_change_time = NOW() ';
				$sql .= 'WHERE prt_id = ?';
				$this->db->query($sql, array($prt_id));
			}
		}

		if (!is_empty($call_done->getValue())) {
			$prt_id = $call_done->getValue();
			$participant_row = $this->get_participant_row($prt_id);
			if ($participant_row['prt_call_status'] == CALL_PENDING) {
				$sql = 'UPDATE bf_participants SET ';
				$sql .= 'prt_call_status = '.CALL_CALLED.', ';
				$sql .= 'prt_call_change_time = NOW() ';
				$sql .= 'WHERE prt_id = ?';
				$this->db->query($sql, array($prt_id));

				$this->db->insert('bf_history', array(
					'hst_prt_id'=>$prt_id,
					'hst_stf_id'=>$this->session->stf_id,
					'hst_action'=>CALLED));
				$this->success = $participant_row['prt_supervision_firstname'].' '.$participant_row['prt_supervision_lastname'].' gerufen';
			}
		}

		$async_loader = new AsyncLoader('call_list', 'calllist/getcalls');

		$this->header('Rufliste');
		$form->open();
		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('style'=>'padding: 0px 20px', 'align'=>'center', 'valign'=>'top'));

		$async_loader->html();
	
		_td();
		_tr();
		_table();
		$form->close();
		script();
		out('window.setInterval(call_list, 5000);');
		_script();
		$this->footer();
	}

	public function getCalls() {
		if (!$this->authorize(false)) {
			echo 'Authorization failed';
			return;
		}

		$this->db->where('prt_call_status IN ('.CALL_CANCELLED.', '.CALL_COMPLETED.') AND ADDTIME(prt_call_change_time, "'.CALL_ENDED_DISPLAY_TIME.'") <= NOW()');
		$this->db->update('bf_participants', array('prt_call_status'=>CALL_NOCALL));

		$table = new CallListTable('SELECT prt_id, prt_number, prt_call_change_time, prt_call_status, prt_call_escalation, prt_call_start_time,
			"button_column" FROM bf_participants WHERE prt_call_status >= '.CALL_PENDING, array(), array('class'=>'details-table no-wrap-table'));
		$table->setOrderBy('prt_call_change_time DESC');
		$table->html();
	}
}
