<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class Admin extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->model('db_model');
	}

	public function index()
	{
		global $period_names;

		if (!$this->authorize())
			return;

		$current_period = $this->db_model->get_setting('current-period');

		$form = new Form('admin_form', '', 1, [ 'class'=>'input-table' ]);
		$set_current_period = $form->addSelect('set_current_period', '',
			$period_names, $current_period, [ 'onchange'=>'this.form.submit()' ]);

		if ($set_current_period->submitted()) {
			$current_period = $set_current_period->getValue();
			$this->db_model->set_setting('current-period', (integer) $current_period);
		}

		$this->header('Database update');
		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td([ 'class'=>'left-panel', 'align'=>'left', 'valign'=>'top' ]);
		out("Select the current period:");
		$form->show();
		_td();
		_tr();
		_table();
		$this->footer();
	}
}
