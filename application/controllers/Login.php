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
		$login = $form->addSubmit('login', 'Login', array('class'=>'button-black', 'onclick'=>'doLogin();'));

		$stf_username->setRule('required');
		$stf_password->setRule('required');

		if ($login->submitted()) {
			$this->error = $form->validate();
			if (empty($this->error)) {
				$query = $this->db->query('SELECT stf_id, stf_fullname, stf_password FROM bf_staff WHERE stf_username=?', array($stf_username->getValue()));
				$staff_row = $query->row_array();
				if (empty($staff_row))
					$this->error = "Unknown user: ".$stf_username->getValue();
				else {
					if (password_verify($stf_password->getValue(), $staff_row['stf_password'])) {
						$this->load->library('session');
						$this->session->set_userdata('stf_id', $staff_row['stf_id']);
						$this->session->set_userdata('stf_fullname', $staff_row['stf_fullname']);
						redirect("participant");
					}
					$this->error = "Password incorrect";
				}
			}
		}
		$stf_password->setValue('');

		$this->header('Login');

		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('style'=>'padding: 0px 20px', 'align'=>'right', 'valign'=>'top', 'width'=>10));
		$form->show();
		_td();
		_tr();
		_table();

		$this->footer(base_url('/js/js-md5.js'));
	}
}
