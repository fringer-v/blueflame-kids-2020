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
		$login_form = new Form('user_login', url('login'), 1, array('class'=>'input-table'));
		$stf_username = $login_form->addTextInput('stf_username', 'Username');
		$stf_password = $login_form->addPassword('stf_password', 'Password');
		$stf_md5_pwd = $login_form->addHidden('stf_md5_pwd', 'Password');
		$login = $login_form->addSubmit('login', 'Login', array('class'=>'button-black'/*, 'onclick'=>'doLogin();'*/));

		$stf_username->setRule('required');
		$stf_md5_pwd->setRule('required');

		$logout_action = in('action');
		if ($logout_action->getValue() == "logout") {
			$this->load->library('session');
			if ($this->session->has_userdata('stf_login_id') && $this->session->stf_login_id > 0) {
				$this->db->query('UPDATE bf_staff SET stf_registered = 0 WHERE stf_id = ?',
					array($this->session->stf_login_id));
			}
			$this->session->sess_destroy();
		}

		if ($login->submitted()) {
			$this->error = $login_form->validate();
			if (is_empty($this->error)) {
				$query = $this->db->query(
					'SELECT stf_id, stf_username, stf_fullname, stf_password, '.
					'stf_registered, stf_loginallowed, stf_technician '.
					'FROM bf_staff WHERE stf_username=?',
					array($stf_username->getValue()));
				$staff_row = $query->row_array();
				if (is_empty($staff_row))
					$this->error = "Unbekannter Benutzer: ".$stf_username->getValue();
				else if (is_empty($staff_row['stf_loginallowed']) &&
					strtolower($staff_row['stf_username']) != 'andrea' &&
					strtolower($staff_row['stf_username']) != 'jessica' &&
					strtolower($staff_row['stf_username']) != 'paul' &&
					strtolower($staff_row['stf_username']) != 'admin') {
					$this->error = "Zugangsberechtigung verweigert: ".$stf_username->getValue();
				}
				else {
					$pwd = $stf_password->getValue();
					$pwd = md5($pwd.'129-3026-19-2089');
					if (password_verify($pwd, $staff_row['stf_password'])) {
						$this->load->library('session');
						$this->session->set_userdata('stf_login_id', $staff_row['stf_id']);
						$this->session->set_userdata('stf_login_name', $staff_row['stf_fullname']);
						$this->session->set_userdata('stf_login_tech', $staff_row['stf_technician']);

						$this->db->query('UPDATE bf_staff SET stf_registered = 1 WHERE stf_id = ?',
							array($staff_row['stf_id']));

						if (is_empty($staff_row['stf_technician']))
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

		table(array('style'=>'border-collapse: collapse; width: 100%'));
		tr();
		td(array('align'=>'center'));
		img([ 'src'=>base_url('/img/bf-kids-logo.png'), 'style'=>'width: 200px; height: auto' ]);
		_td();
		tr(td(array('height'=>'5'), ''));
		_tr();
		tr();
		td(array('align'=>'center'));

		table();
		tr();
		td();		
		$login_form->show();
		_td();
		_tr();
		tr();
		td(array('align'=>'center'));
		$this->printResult();
		_td();
		_tr();
		_table();

		_td();
		_tr();
		_table();
		_div();

		$this->footer(base_url('/js/js-md5.js'));
	}
}
