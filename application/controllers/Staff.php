<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class StaffTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'stf_username':
				return 'Username';
			case 'stf_fullname':
				return 'Name';
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'stf_username':
			case 'stf_fullname':
				return $row[$field];
			case 'button_column':
				return (new Submit('select', 'Bearbeiten', array('class'=>'button-black', 'onclick'=>'$("#set_stf_id").val('.$row['stf_id'].');')))->html();
		}
		return nix();
	}
}

class Staff extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	private function get_staff_row($stf_id) {
		$query = $this->db->query('SELECT stf_id, stf_username, stf_fullname FROM bf_staff WHERE stf_id=?', array($stf_id));
		return $query->row_array();
	}

	public function index()
	{
		$this->authorize();

bugs($_POST);
		$form = new Form('update_staff', 'staff', 1, array('class'=>'input-table'));
		$set_stf_id = $form->addHidden('set_stf_id');
		$stf_id = $form->addHidden('stf_id');
		
		$stf_id_v = $stf_id->getValue();
		$set_stf_id_v = $set_stf_id->getValue();
		if (!empty($set_stf_id_v)) {
			$set_stf_id->setValue('');
			$stf_id_v = $set_stf_id_v;
			$stf_id->setValue($stf_id_v);
		}

		$empty_row = array('stf_id'=>'', 'stf_username'=>'', 'stf_fullname'=>'', 'stf_password'=>'', 'confirm_password'=>'');
		if (empty($stf_id_v))
			$staff_row = $empty_row;
		else
			$staff_row = $this->get_staff_row($stf_id_v);

		// Fields
		$stf_username = $form->addTextInput('stf_username', 'Username', $staff_row['stf_username']);
		$stf_fullname = $form->addTextInput('stf_fullname', 'Name', $staff_row['stf_fullname']);
		$stf_password = $form->addPassword('stf_password', 'Passwort');
		$confirm_password = $form->addPassword('confirm_password', 'Passwort wiederholen');

		// Rules
		$stf_username->setRule('required|is_unique[bf_staff.stf_username.stf_id]');
		$stf_fullname->setRule('required|is_unique[bf_staff.stf_fullname.stf_id]');
		if (empty($stf_id_v)) {
			$stf_password->setRule('required');
			$confirm_password->setRule('required|matches[stf_password]');
		}
		else
			$confirm_password->setRule('matches[stf_password]');
		// Buttons:
		if (empty($stf_id_v))
			$save_staff = $form->addSubmit('submit', 'Mitarbeiter Hinzufügen', array('class'=>'button-black'));
		else
			$save_staff = $form->addSubmit('submit', 'Änderung Sichern', array('class'=>'button-black'));
		$clear_staff = $form->addButton('clear', 'Clear', array('class'=>'button-black', 'onclick'=>'location.href="staff";'));

		if ($clear_staff->submitted())
			$form->setValues($empty_row);

		if ($save_staff->submitted()) {
			$this->error = $form->validate();
			if (empty($this->error)) {
				$pwd = $stf_password->getValue();
				if (!empty($pwd))
					$pwd = password_hash(strtolower(md5($pwd."129-3026-19-2089")), PASSWORD_DEFAULT);
				if (empty($stf_id_v)) {
					//$this->news_model->set_news();
					$data = array(
						'stf_username' => $stf_username->getValue(),
						'stf_fullname' => $stf_fullname->getValue(),
						'stf_password' => $pwd
					);
					$this->db->insert('bf_staff', $data);
					$this->success = $stf_fullname->getValue().' hinzugefügt';
				}
				else {
					$data = array(
						'stf_username' => $stf_username->getValue(),
						'stf_fullname' => $stf_fullname->getValue(),
					);
					if (!is_empty($pwd))
						$data['stf_password'] = $pwd;
					$this->db->where('stf_id', $stf_id_v);
					$this->db->update('bf_staff', $data);
					$this->success = $stf_fullname->getValue().' geändert';
				}
			}
		}

		if (!empty($stf_id_v)) {
			$staff_row = $this->get_staff_row($stf_id_v);
			$form->setValues($staff_row);
		}

		$stf_page = new Hidden('stf_page', 1);
		$stf_page->makeGlobal();
		$stf_page_v = $stf_page->getValue();

		$table = new StaffTable('SELECT SQL_CALC_FOUND_ROWS stf_id, stf_username, stf_fullname,
			"button_column" FROM bf_staff ', array(), array('class'=>'details-table no-wrap-table'));
		$table->setPagination('staff?stf_page=', 16, $stf_page_v);
		$table->setOrderBy('stf_username');

		$this->header('Mitarbeiter');
		$form->open();
		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('class'=>'left-panel', 'align'=>'left', 'valign'=>'top', 'width'=>10));
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
