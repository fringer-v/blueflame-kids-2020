<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class GroupsTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'grp_name':
				return 'Name';
			case 'loc_name':
				return 'Raum';
			case 'grp_from_age':
				return 'Altersgruppe';
			case 'grp_count':
				return 'Teiln.';
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'grp_name':
			case 'loc_name':
			case 'grp_count':
				return $row[$field];
			case 'grp_from_age':
				return if_empty($row['grp_from_age'], '').' - '.if_empty($row['grp_to_age'], '');
			case 'button_column':
				return (new Submit('select', 'Bearbeiten', array('class'=>'button-black',
					'onclick'=>'$("#set_grp_id").val('.$row['grp_id'].');')))->html();
		}
		return nix();
	}
}

class MemberTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'prt_number':
				return 'Nr.';
			case 'prt_name':
				return 'Name';
			case 'prt_supervision_name':
				return 'Begleitperson';
			//case 'prt_call_status';
			//	return 'Ruf';
			case 'prt_notes':
				return 'Notizen';
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
			/*
			case 'prt_call_status': {
				$call_status = $row['prt_call_status'];
				if (is_empty($call_status))
					return nbsp();					
				if ($call_status == CALL_CANCELLED || $call_status == CALL_COMPLETED)
					return div(array('class'=>'red-box', 'style'=>'width: 62px; height: 22px;'), '- Ruf');
				return div(array('class'=>'blue-box', 'style'=>'width: 62px; height: 22px;'), how_long_ago($row['prt_call_start_time']));
			}
			*/
			case 'prt_notes':
				$value = $row[$field];
				return $value;
		}
		return nix();
	}
}

class Groups extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	private function get_group_row($grp_id) {
		if (is_empty($grp_id))
			return array('grp_id'=>'', 'grp_name'=>'', 'grp_leader_stf_id'=>'', 'grp_coleader_stf_id'=>'' ,
				'grp_loc_id'=>'', 'grp_notes'=>'', 'grp_from_age'=>'', 'grp_to_age'=>'',
				'stf_leader'=>'', 'stf_coleader'=>'', 'loc_name'=>'');

		$query = $this->db->query('SELECT grp_id, grp_name, grp_leader_stf_id, grp_coleader_stf_id,
				grp_loc_id, grp_notes, grp_from_age, grp_to_age,
				a.stf_fullname stf_leader, b.stf_fullname stf_coleader, loc_name
			FROM bf_groups
			LEFT JOIN bf_locations ON loc_id = grp_loc_id
			LEFT JOIN bf_staff a ON a.stf_id = grp_leader_stf_id
			LEFT JOIN bf_staff b ON b.stf_id = grp_coleader_stf_id
			WHERE grp_id=?',
			array($grp_id));
		return $query->row_array();
	}

	public function index()
	{
		$this->authorize();

		$display_group = new Form('display_group', 'groups', 1, array('class'=>'input-table'));
		$set_grp_id = $display_group->addHidden('set_grp_id');
		$grp_page = new Hidden('grp_page', 1);
		$grp_page->makeGlobal();

		$update_group = new Form('update_group', 'groups', 2, array('class'=>'input-table'));
		$grp_id = $update_group->addHidden('grp_id');
		$grp_id->makeGlobal();
		$mem_page = new Hidden('mem_page', 1);
		$mem_page->makeGlobal();

		if ($set_grp_id->submitted()) {
			$grp_id->setValue($set_grp_id->getValue());
			$mem_page->setValue(1);
			redirect('groups');
		}

		$grp_id_v = $grp_id->getValue();
		$group_row = $this->get_group_row($grp_id_v);

		$grp_name = $update_group->addTextInput('grp_name', 'Name', $group_row['grp_name']);
		$grp_name->setRule('required|is_unique[bf_groups.grp_name.grp_id]');
		$print_link = $update_group->addSpace();
		if (!is_empty($grp_id_v))
			$print_link->setValue(div(array('style'=>'float: right;'), href('groups/printable',
				tag('img', array('src'=>base_url('/img/print-50.png'), 'style'=>'width: 28px; height: 28px;')))));

		$staff = db_array_2('SELECT stf_id, stf_fullname FROM bf_staff ORDER BY stf_fullname');
		$staff = array(0 => '') + $staff;
		$grp_leader_stf_id = $update_group->addSelect('grp_leader_stf_id', 'Leiter', $staff, $group_row['grp_leader_stf_id']);
		$update_group->addSpace();
		$grp_coleader_stf_id = $update_group->addSelect('grp_coleader_stf_id', 'Co-Leiter', $staff, $group_row['grp_coleader_stf_id']);
		$update_group->addSpace();

		$locations = db_array_2('SELECT loc_id, loc_name FROM bf_locations ORDER BY loc_name');
		$grp_loc_id = $update_group->addSelect('grp_loc_id', 'Raum', $locations, $group_row['grp_loc_id']);
		$update_group->addSpace();
		$age_range_field = $update_group->addField('Altersgruppe');
		$grp_from_age = new TextInput('grp_from_age', if_empty($group_row['grp_from_age'], ''), array('style'=>'width: 20px;'));
		$grp_to_age = new TextInput('grp_to_age', if_empty($group_row['grp_to_age'], ''), array('style'=>'width: 20px;'));
		if (!is_empty($this->session->stf_technician)) {
			$update_group->disable();
			$grp_from_age->disable();
			$grp_to_age->disable();
		}
		$age_range_field->setValue($grp_from_age->html()->add(' - ')->add($grp_to_age->html()));
		$update_group->addSpace();
		$grp_notes = $update_group->addTextArea('grp_notes', 'Notizen', $group_row['grp_notes']);
		$grp_notes->setFormat('colspan=2');

		if (is_empty($grp_id_v)) {
			$save_group = $update_group->addSubmit('submit', 'Kleingruppe Hinzufügen', array('class'=>'button-black'));
			$clear_group = $update_group->addSubmit('clear', 'Clear', array('class'=>'button-black'));
		}
		else {
			$save_group = $update_group->addSubmit('submit', 'Änderung Sichern', array('class'=>'button-black'));
			$clear_group = $update_group->addSubmit('clear', 'Weiteres Aufnehmen...', array('class'=>'button-black'));

			$member_table = new MemberTable('SELECT SQL_CALC_FOUND_ROWS prt_id, prt_number, 
					CONCAT(prt_firstname, " ", prt_lastname) as prt_name,
					CONCAT(prt_supervision_firstname, " ", prt_supervision_lastname) AS prt_supervision_name, prt_call_status,
			 		prt_registered, prt_wc_time, "button_column",
			 		IF(prt_call_status = '.CALL_PENDING.' OR prt_call_status = '.CALL_CALLED.', 0, 1) calling,
			 		prt_call_start_time,
			 		prt_notes
				FROM bf_participants
				WHERE prt_grp_id = ? AND prt_registered != '.REG_NO.' ORDER BY prt_id',
				array($grp_id_v), array('class'=>'details-table member-table'));
			$member_table->setPagination('groups?mem_page=', 10, $mem_page->getValue());
		}

		if ($clear_group->submitted()) {
			$grp_id->setValue(0);
			redirect('groups');
		}

		if ($save_group->submitted()) {
			$this->error = $update_group->validate();
			if (is_empty($this->error)) {
				$data = array(
					'grp_name' => $grp_name->getValue(),
					'grp_leader_stf_id' => $grp_leader_stf_id->getValue(),
					'grp_coleader_stf_id' => $grp_coleader_stf_id->getValue(),
					'grp_loc_id' => $grp_loc_id->getValue(),
					'grp_from_age' => $grp_from_age->getValue(),
					'grp_to_age' => $grp_to_age->getValue(),
					'grp_notes' => $grp_notes->getValue()
				);
				if (is_empty($grp_id_v)) {
					$this->db->insert('bf_groups', $data);
					$this->setSuccess($grp_name->getValue().' hinzugefügt');
				}
				else {
					$this->db->where('grp_id', $grp_id_v);
					$this->db->update('bf_groups', $data);
					$this->setSuccess($grp_name->getValue().' geändert');
				}
				redirect("groups");
			}
		}

		$table = new GroupsTable('SELECT SQL_CALC_FOUND_ROWS grp_id, grp_name, loc_name,
			grp_from_age, grp_to_age, COUNT(prt_id) grp_count,
			"button_column" FROM bf_groups
				LEFT JOIN bf_locations ON loc_id = grp_loc_id 
				LEFT JOIN bf_participants ON prt_grp_id = grp_id AND prt_registered != '.REG_NO.'
			GROUP BY grp_id', array(),
			array('class'=>'details-table no-wrap-table', 'style'=>'width: 600px;'));
		$table->setPagination('groups?grp_page=', 16, $grp_page->getValue());
		$table->setOrderBy('grp_from_age, grp_name');

		$this->header('Kleingruppen');

		table(array('style'=>'border-collapse: collapse;'));
		tr();

		td(array('class'=>'left-panel', 'style'=>'width: 604px;', 'align'=>'left', 'valign'=>'top', 'rowspan'=>3));
			$display_group->open();
			table(array('style'=>'border-collapse: collapse;'));
			tr(td($table->paginationHtml()));
			tr(td($table->html()));
			_table();
			$display_group->close();
		_td();

		td(array('align'=>'left', 'valign'=>'top'));
			table(array('style'=>'border-collapse: collapse; margin-right: 5px; min-width: 640px;'));
			tbody();
			tr();
			td(array('style'=>'border: 1px solid black; padding: 10px 5px;'));
			$update_group->show();
			_td();
			_tr();
			_tbody();
			_table();
		_td();

		_tr();

		if (isset($member_table)) {
			tr(td(array('align'=>'left', 'valign'=>'top'), $member_table->paginationHtml()));
			tr(td(array('align'=>'left', 'valign'=>'top'), $member_table->html()));
		}
		else {
			tr(td(nbsp()));
			tr(td(nbsp()));
		}

		_table();

		$this->footer();
	}

	public function printable() {
		if (!$this->authorize(false)) {
			echo 'Authorization failed';
			return;
		}

		$grp_id = new Hidden('grp_id');
		$grp_id->makeGlobal();
		$grp_id_v = $grp_id->getValue();
		$group_row = $this->get_group_row($grp_id_v);

		$update_group = new Form('update_group', 'groups', 1, array('class'=>'output-table'));
		$update_group->addField('Gruppe', b($group_row['grp_name']));
		$update_group->addField('Leiter', $group_row['stf_leader']);
		$update_group->addField('Co-Leiter', $group_row['stf_coleader']);
		$update_group->addField('Raum', $group_row['loc_name']);
		$update_group->addField('Altersgruppe', $group_row['grp_from_age'].' - '.$group_row['grp_to_age']);
		$update_group->addField('Notizen', $group_row['grp_notes']);

		$member_table = new MemberTable('SELECT prt_id, prt_number, 
				CONCAT(prt_firstname, " ", prt_lastname) as prt_name,
				CONCAT(prt_supervision_firstname, " ", prt_supervision_lastname) AS prt_supervision_name,
			 	prt_notes
			FROM bf_participants
			WHERE prt_grp_id = ? AND prt_registered != '.REG_NO.' ORDER BY prt_id',
			array($grp_id_v), array('class'=>'printable-table'));

		out('<!DOCTYPE html>');
		tag('html');
		tag('head');
		tag('meta', array('http-equiv'=>'Content-Type', 'content'=>'text/html; charset=utf-8'));
		tag('link', array('href'=>base_url('/css/blue-flame.css'), 'rel'=>'stylesheet', 'type'=>'text/css'));
		tag('title', '');
		_tag('head');
		tag('body', array('style'=>'background: white;'));
		table(array('style' => 'width: 720px; padding: 5px;'));
		tr();
		td();
		$update_group->show();
		_td();
		_tr();
		tr(td($member_table->html()));
		_table();
		_tag('body');
		_tag('html');
	}
}
