<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class StaffTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'stf_fullname':
				return 'Name';
			case 'group_list':
				return 'Gruppe';
			case 'stf_registered':
				return 'Angem.';
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'stf_fullname':
			case 'group_list':
				return $row[$field];
			case 'stf_registered':
				if ($row[$field] == 1)
					return div(array('class'=>'green-box', 'style'=>'width: 56px; height: 22px;'), 'Ja');
				return div(array('class'=>'red-box', 'style'=>'width: 56px; height: 22px;'), 'Nein');
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
		if (is_empty($stf_id))
			return array('stf_id'=>'', 'stf_username'=>'', 'stf_fullname'=>'', 'stf_password'=>'',
				'confirm_password'=>'', 'stf_registered'=>'', 'stf_loginallowed'=>'', 'stf_technician'=>'');

		$query = $this->db->query('SELECT stf_id, stf_username, stf_fullname, stf_registered,
			stf_loginallowed, stf_technician FROM bf_staff WHERE stf_id=?', array($stf_id));
		return $query->row_array();
	}

	public function index()
	{
		$this->authorize();

		$display_staff = new Form('display_staff', 'staff', 1, array('class'=>'input-table'));
		$set_stf_id = $display_staff->addHidden('set_stf_id');

		$update_staff = new Form('update_staff', 'staff', 1, array('class'=>'input-table'));
		if (!is_empty($this->session->stf_technician))
			$update_staff->disable();
		$stf_id = $update_staff->addHidden('stf_id');
		$stf_id->makeGlobal();

		if ($set_stf_id->submitted()) {
			$stf_id->setValue($set_stf_id->getValue());
			redirect("staff");
		}

		$stf_id_v = $stf_id->getValue();
		$staff_row = $this->get_staff_row($stf_id_v);

		// Fields
		if (!is_empty($stf_id_v)) {
			$stf_registered = $update_staff->addField('Status');
			if ($staff_row['stf_registered'])
				$stf_registered->setValue(div(array('class'=>'green-box'), 'Angemeldet'));
			else
				$stf_registered->setValue(div(array('class'=>'red-box'), 'Abgemeldet'));
		}
		$stf_username = $update_staff->addTextInput('stf_username', 'Login-name', $staff_row['stf_username']);
		$stf_fullname = $update_staff->addTextInput('stf_fullname', 'Name', $staff_row['stf_fullname']);
		$stf_password = $update_staff->addPassword('stf_password', 'Passwort');
		$confirm_password = $update_staff->addPassword('confirm_password', 'Passwort wiederholen');
		$stf_loginallowed = $update_staff->addCheckbox('stf_loginallowed',
			'Die Mitarbeiter darf sich bei dieser Anwendung anmelden', $staff_row['stf_loginallowed']);
		$stf_technician = $update_staff->addCheckbox('stf_technician',
			'Die Mitarbeiter darf nur auf die Rufliste zugreifen', $staff_row['stf_technician']);

		// Rules
		$stf_username->setRule('required|is_unique[bf_staff.stf_username.stf_id]');
		$stf_fullname->setRule('required|is_unique[bf_staff.stf_fullname.stf_id]');
		$confirm_password->setRule('matches[stf_password]');

		// Buttons:
		if (is_empty($stf_id_v)) {
			$save_staff = $update_staff->addSubmit('save_staff', 'Mitarbeiter Hinzufügen', array('class'=>'button-black'));
			$clear_staff = $update_staff->addSubmit('clear_staff', 'Clear', array('class'=>'button-black'));
		}
		else {
			$save_staff = $update_staff->addSubmit('save_staff', 'Änderung Sichern', array('class'=>'button-black'));
			$clear_staff = $update_staff->addSubmit('clear_staff', 'Weiteres Aufnehmen...', array('class'=>'button-black'));
			if ($staff_row['stf_registered'])
				$reg_unregister = $update_staff->addSubmit('reg_unregister', 'Abmelden', array('class'=>'button-red'));
			else
				$reg_unregister = $update_staff->addSubmit('reg_unregister', 'Anmelden', array('class'=>'button-green'));
		}

		if ($clear_staff->submitted()) {
			$stf_id->setValue(0);
			redirect("staff");
		}

		if ($save_staff->submitted()) {
			$pwd = $stf_password->getValue();

			$this->error = $update_staff->validate();
			if (is_empty($this->error) &&
				$stf_loginallowed->getValue() &&
				is_empty($pwd))
				$this->error = '"Passwort" muss vorhanden sein';

			if (is_empty($this->error)) {
				if (!is_empty($pwd))
					$pwd = password_hash(strtolower(md5($pwd."129-3026-19-2089")), PASSWORD_DEFAULT);
				$data = array(
					'stf_username' => $stf_username->getValue(),
					'stf_fullname' => $stf_fullname->getValue(),
					'stf_loginallowed' => $stf_loginallowed->getValue(),
					'stf_technician' => $stf_technician->getValue()
				);
				if (is_empty($stf_id_v)) {
					$data['stf_password'] = $pwd;
					$this->db->insert('bf_staff', $data);
					$stf_id_v = $this->db->insert_id();
					$stf_id->setValue($stf_id_v);
					$this->setSuccess($stf_fullname->getValue().' hinzugefügt');
				}
				else {
					if (!is_empty($pwd))
						$data['stf_password'] = $pwd;
					$this->db->where('stf_id', $stf_id_v);
					$this->db->update('bf_staff', $data);
					$this->setSuccess($stf_fullname->getValue().' geändert');
				}
				redirect("staff");
			}
		}

		if (!is_empty($stf_id_v) && $reg_unregister->submitted()) {
			$registered = !$staff_row['stf_registered'];
			$sql = 'UPDATE bf_staff SET stf_registered = ? WHERE stf_id = ?';
			$this->db->query($sql, array($registered, $stf_id_v));
			$this->setSuccess($stf_fullname->getValue().' '.($registered ? 'angemeldet' : 'abgemeldet'));
			redirect("staff");
		}

		$stf_page = new Hidden('stf_page', 1);
		$stf_page->makeGlobal();
		$stf_page_v = $stf_page->getValue();

		// 
		$table = new StaffTable('SELECT SQL_CALC_FOUND_ROWS stf_id, stf_username, stf_fullname,
		    GROUP_CONCAT(DISTINCT grp_name ORDER BY grp_name DESC SEPARATOR ", ") group_list,
			stf_registered, "button_column" FROM bf_staff
			LEFT JOIN bf_groups ON stf_id = grp_leader_stf_id OR stf_id = grp_coleader_stf_id
			GROUP BY stf_id',
			array(),
			array('class'=>'details-table no-wrap-table', 'style'=>'width: 600px;'));
		$table->setPageQuery('SELECT stf_fullname FROM bf_staff');
		$table->setPagination('staff?stf_page=', 16, $stf_page_v);
		$table->setOrderBy('stf_username');

		// Generate page ------------------------------------------
		$this->header('Mitarbeiter');

		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('class'=>'left-panel', 'align'=>'left', 'valign'=>'top'));
			$display_staff->open();
			table(array('style'=>'border-collapse: collapse;'));
			tr(td($table->paginationHtml()));
			tr(td($table->html()));
			_table();
			$display_staff->close();
		_td();
		td(array('align'=>'left', 'valign'=>'top'));
			table(array('style'=>'border-collapse: collapse; margin-right: 5px; min-width: 640px;'));
			tbody();
			tr();
			td(array('style'=>'border: 1px solid black; padding: 10px 5px;'));
			$update_staff->show();
			_td();
			_tr();
			_tbody();
			_table();
		_td();
		_tr();
		_table();

		$this->footer();
	}
}
