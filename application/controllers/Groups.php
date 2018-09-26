<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class GroupsTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'grp_name':
				return 'Name';
			case 'grp_location':
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
			case 'grp_location':
			case 'grp_count':
				return $row[$field];
			case 'grp_from_age':
				return ifempty($row['grp_from_age'], '').' - '.ifempty($row['grp_to_age'], '');
			case 'button_column':
				return (new Submit('select', 'Bearbeiten', array('class'=>'button-black', 'onclick'=>'$("#set_grp_id").val('.$row['grp_id'].');')))->html();
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
		$query = $this->db->query('SELECT g.grp_id, g.grp_name, g.grp_location, g.grp_notes, '.
		'g.grp_from_age, g.grp_to_age '.
		'FROM bf_groups g WHERE g.grp_id=?', array($grp_id));
		return $query->row_array();
	}

	public function index()
	{
		$this->authorize();

		$form = new Form('update_group', 'groups', 1, array('class'=>'input-table'));
		$set_grp_id = $form->addHidden('set_grp_id');
		$grp_id = $form->addHidden('grp_id');
		$grp_id->makeGlobal();

		if ($set_grp_id->submitted() && !empty($set_grp_id->getValue())) {
			$grp_id->setValue($set_grp_id->getValue());
			redirect('groups');
		}

		$grp_id_v = $grp_id->getValue();

		$empty_row = array('grp_id'=>'', 'grp_name'=>'', 'grp_location'=>'', 'grp_notes'=>'', 'grp_from_age'=>'', 'grp_to_age'=>'');
		if (empty($grp_id_v))
			$group_row = $empty_row;
		else
			$group_row = $this->get_group_row($grp_id_v);

		$grp_name = $form->addTextInput('grp_name', 'Name', $group_row['grp_name']);
		$grp_location = $form->addTextInput('grp_location', 'Raum', $group_row['grp_location']);
		$grp_name->setRule('required|is_unique[bf_groups.grp_name.grp_id]');
		$age_range_field = $form->addField('Altersgruppe');
		$grp_from_age = new TextInput('grp_from_age', ifempty($group_row['grp_from_age'], ''), array('style'=>'width: 20px;'));
		$grp_to_age = new TextInput('grp_to_age', ifempty($group_row['grp_to_age'], ''), array('style'=>'width: 20px;'));
		$age_range_field->setValue($grp_from_age->html()->add(' - ')->add($grp_to_age->html()));
		$grp_notes = $form->addTextArea('grp_notes', 'Notizen', $group_row['grp_notes']);

		if (empty($grp_id_v))
			$save_group = $form->addSubmit('submit', 'Kleingruppe Hinzufügen', array('class'=>'button-black'));
		else
			$save_group = $form->addSubmit('submit', 'Änderung Sichern', array('class'=>'button-black'));
		$clear_group = $form->addButton('clear', 'Clear', array('class'=>'button-black', 'onclick'=>'location.href="groups";'));

		if ($clear_group->submitted())
			$form->setValue($empty_row);

		if ($save_group->submitted()) {
			$this->error = $form->validate();
			if (empty($this->error)) {
				$data = array(
					'grp_name' => $grp_name->getValue(),
					'grp_location' => $grp_location->getValue(),
					'grp_from_age' => $grp_from_age->getValue(),
					'grp_to_age' => $grp_to_age->getValue(),
					'grp_notes' => $grp_notes->getValue()
				);
				if (empty($grp_id_v)) {
					$this->db->insert('bf_groups', $data);
					$this->setSuccess($grp_location->getValue().' hinzugefügt');
				}
				else {
					$this->db->where('grp_id', $grp_id_v);
					$this->db->update('bf_groups', $data);
					$this->setSuccess($grp_location->getValue().' geändert');
				}
				redirect("groups");
			}
		}

		if (!empty($grp_id_v)) {
			$group_row = $this->get_group_row($grp_id_v);
			$form->setValues($group_row);
		}

		$grp_page = new Hidden('grp_page', 1);
		$grp_page->makeGlobal();
		$grp_page_v = $grp_page->getValue();

		$table = new GroupsTable('SELECT SQL_CALC_FOUND_ROWS g.grp_id, g.grp_name, g.grp_location,'.
			'g.grp_from_age, g.grp_to_age, COUNT(p.prt_id) grp_count, '.
			'"button_column" FROM bf_groups g '.
			'LEFT JOIN bf_participants p ON p.prt_grp_id = g.grp_id '.
			'AND p.prt_registered = 1 GROUP BY g.grp_id', array(), array('class'=>'details-table no-wrap-table', 'style'=>'width: 600px;'));
		$table->setPagination('groups?grp_page=', 16, $grp_page_v);
		$table->setOrderBy('grp_name');

		$this->header('Kleingruppen');
		$form->open();

		table(array('style'=>'border-collapse: collapse;'));
		tr();

		td(array('class'=>'left-panel', 'style'=>'width: 604px;', 'align'=>'left', 'valign'=>'top'));
			table(array('style'=>'border-collapse: collapse;'));
			tr(td($table->paginationHtml()));
			tr(td($table->html()));
			_table();
		_td();

		td(array('align'=>'left', 'valign'=>'top'));
			table(array('style'=>'border-collapse: collapse; margin-right: 5px;'));
			tbody();
			tr();
			td(array('style'=>'border: 1px solid black; padding: 10px 5px;'));
			$form->show();
			_td();
			_tr();
			_tbody();
			_table();
		_td();

		_tr();
		_table();

		$form->close();
		$this->footer();
	}
}
