<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');

class Login extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function index()
	{
		$now = (integer) time();
		$form = new Form('user_login', url('login'), 1, array('class'=>'input-table'));
		$stf_username = $form->addTextInput('stf_username', 'Username');
		$stf_password = $form->addPassword('stf_password', 'Password');
		$stf_md5_pwd = $form->addHidden('stf_md5_pwd', 'Password');
		$login = $form->addSubmit('login', 'Login', array('class'=>'button-black', 'onclick'=>'doLogin();'));

		$stf_username->setRule('required');
		$stf_md5_pwd->setRule('required');

		if ($login->submitted()) {
			$this->error = $form->validate();
			if (empty($this->error)) {
				$query = $this->db->query(
					'SELECT stf_id, stf_username, stf_fullname, stf_password, '.
					'stf_registered, stf_loginallowed, stf_technician '.
					'FROM bf_staff WHERE stf_username=?',
					array($stf_username->getValue()));
				$staff_row = $query->row_array();
				if (empty($staff_row))
					$this->error = "Unbekannter Benutzer: ".$stf_username->getValue();
				else if (empty($staff_row['stf_loginallowed']) &&
					strtolower($staff_row['stf_username']) != 'andrea' &&
					strtolower($staff_row['stf_username']) != 'jessica' &&
					strtolower($staff_row['stf_username']) != 'paul' &&
					strtolower($staff_row['stf_username']) != 'admin') {
					$this->error = "Zugangsberechtigung verweigert: ".$stf_username->getValue();
				}
				else {
					if (password_verify($stf_md5_pwd->getValue(), $staff_row['stf_password'])) {
						$this->load->library('session');
						$this->session->set_userdata('stf_login_id', $staff_row['stf_id']);
						$this->session->set_userdata('stf_fullname', $staff_row['stf_fullname']);
						$this->session->set_userdata('stf_technician', $staff_row['stf_technician']);
						if (empty($staff_row['stf_loginallowed']))
							redirect("participant");
						redirect("calllist");
					}
					$this->error = "Passwort falsch";
				}
			}
		}
		$stf_password->setValue('');
		$stf_md5_pwd->setValue('');

		$this->header('Login');

		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('class'=>'left-panel', 'align'=>'left', 'valign'=>'top'));
		$form->show();
		_td();
		_tr();
		_table();

		$this->footer(base_url('/js/js-md5.js'));
	}
}
