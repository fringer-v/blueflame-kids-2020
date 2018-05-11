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
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'grp_name':
			case 'grp_location':
				return $row[$field];
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
		$query = $this->db->query('SELECT grp_id, grp_name, grp_location FROM bf_groups WHERE grp_id=?', array($grp_id));
		return $query->row_array();
	}

	public function index()
	{
		$this->authorize();

		$form = new Form('update_group', 'groups', 1, array('class'=>'input-table'));
		$set_grp_id = $form->addHidden('set_grp_id');
		$grp_id = $form->addHidden('grp_id');

		$grp_id_v = $grp_id->getValue();
		$set_grp_id_v = $set_grp_id->getValue();
		if (!empty($set_grp_id_v)) {
			$set_grp_id->setValue('');
			$grp_id_v = $set_grp_id_v;
			$grp_id->setValue($grp_id_v);
		}

		$empty_row = array('grp_id'=>'', 'grp_name'=>'', 'grp_location'=>'');
		if (empty($grp_id_v))
			$group_row = $empty_row;
		else
			$group_row = $this->get_group_row($grp_id_v);

		$grp_name = $form->addTextInput('grp_name', 'Name', $group_row['grp_name']);
		$grp_location = $form->addTextInput('grp_location', 'Raum', $group_row['grp_location']);
		$grp_name->setRule('required|is_unique[bf_groups.grp_name.grp_id]');

		$clear_group = $form->addButton('clear', 'Clear', array('class'=>'button-black', 'onclick'=>'location.href="groups";'));
		if (empty($grp_id_v))
			$save_group = $form->addSubmit('submit', 'Kleingruppe Hinzufügen', array('class'=>'button-black'));
		else
			$save_group = $form->addSubmit('submit', 'Änderung Sichern', array('class'=>'button-black'));

		if ($clear_group->submitted())
			$form->setValue($empty_row);

		if ($save_group->submitted()) {
			$this->error = $form->validate();
			if (empty($this->error)) {
				if (empty($grp_id_v)) {
					//$this->news_model->set_news();
					$data = array(
						'grp_name' => $grp_name->getValue(),
						'grp_location' => $grp_location->getValue()
					);
					$this->db->insert('bf_groups', $data);
					$this->success = $grp_location->getValue().' hinzugefügt';
				}
				else {
					$data = array(
						'grp_name' => $grp_name->getValue(),
						'grp_location' => $grp_location->getValue(),
					);
					$this->db->where('grp_id', $grp_id_v);
					$this->db->update('bf_groups', $data);
					$this->success = $grp_location->getValue().' geändert';
				}
			}
		}

		if (!empty($grp_id_v)) {
			$group_row = $this->get_group_row($grp_id_v);
			$form->setValues($group_row);
		}

		$grp_page = new Hidden('grp_page', 1);
		$grp_page->makeGlobal();
		$grp_page_v = $grp_page->getValue();

		$table = new GroupsTable('SELECT SQL_CALC_FOUND_ROWS grp_id, grp_name, grp_location,
			"button_column" FROM bf_groups ', array(), array('class'=>'details-table no-wrap-table'));
		$table->setPagination('groups?grp_page=', 16, $grp_page_v);
		$table->setOrderBy('grp_name');

		$this->header('Kleingruppen');
		$form->open();
		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('style'=>'padding: 0px 20px', 'align'=>'right', 'valign'=>'top', 'width'=>10));
		table(array('style'=>'border-collapse: collapse;'));
		tr(td($table->paginationHtml()));
		tr(td($table->html()));
		_table();
		_td();
		td(array('align'=>'left', 'valign'=>'top'));
		$form->show();
		_td();
		_tr();
		_table();

		$form->close();
		$this->footer();
	}
}
