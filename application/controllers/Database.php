<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');

class Database extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->model('db_model');
	}

	public function index()
	{
		$this->load->library('session');

		$form = new Form('update_database', 'database', 1);
		$update = $form->addSubmit('submit', 'Update Database', array('class'=>'button-black'));

		if ($update->submitted() && !$this->db_model->up_to_date()) {
			if (empty($this->error)) {
				$this->db_model->update_database();
				$this->setSuccess("Database updated");
			}
		}

		$this->header('Database update');
		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('class'=>'left-panel', 'align'=>'left', 'valign'=>'top'));
		if ($this->db_model->up_to_date()) {
			out("The database is up-to-date");
		}
		else {
			out("The database schema must be updated");
			$form->show();
		}
		_td();
		_tr();
		_table();
		$this->footer();
	}

}
