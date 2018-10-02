<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// hst_action
define('CREATED', 0);
define('REGISTER', 1);
define('UNREGISTER', 2);
define('CALL', 3);
define('CANCELLED', 4);
define('ESCALATE', 5);
define('CALLED', 6);
define('ENDED', 7);
define('GO_TO_WC', 8);
define('BACK_FROM_WC', 9);

// hst_action && prt_call_status 
define('CALL_NOCALL', 0);
define('CALL_CALLED', 100);		// Operator has executed call
define('CALL_COMPLETED', 200);	// Call completed (if cancelled after called)
define('CALL_PENDING', 300);	// Call request pending
define('CALL_CANCELLED', 400);	// Call withdrawen (no longer required)

/*
define('TEXT_CALLED', 'Called');
define('TEXT_COMPLETED', 'Completed');
define('TEXT_PENDING', 'Pending');
define('TEXT_CANCELLED', 'Cancelled');
*/

define('TEXT_CALLED', 'Gerufen');
define('TEXT_COMPLETED', 'Ruf Beendet');
define('TEXT_PENDING', 'Ruf Bevorstehend');
define('TEXT_CANCELLED', 'Ruf Aufgehoben');
define('TEXT_ESCALATED', 'Ruf Eskaliert');

define('CALL_ENDED_DISPLAY_TIME', '00:00:30'); // Call cancel/end state shown for 30 seconds

class BF_Controller extends CI_Controller {
	public $stf_login_id = 0;
	public $stf_fullname = '';

	public $error = "";
	public $warning = "";

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->db->query("SET time_zone = '+02:00'");
		$this->load->helper('url_helper');
	}

	public function get_participant_row($prt_id) {
		if (empty($prt_id))
			$participant_row = array('prt_id'=>'', 'prt_number'=>'', 'prt_firstname'=>'', 'prt_lastname'=>'',
				'prt_birthday'=>'',
				'prt_registered'=>null, 'prt_supervision_firstname'=>'', 'prt_supervision_lastname'=>'',
				'prt_supervision_cellphone'=>'', 'prt_notes'=>'', 'prt_grp_id'=>'',
				'prt_call_status'=>'', 'prt_call_escalation'=>'', 'prt_call_start_time'=>'', 'prt_call_change_time'=>'',
				'prt_wc_time'=>'', 'grp_name'=>'', 'loc_name'=>''
			);
		else {
			$query = $this->db->query('SELECT prt_id, prt_number, prt_firstname, prt_lastname,
				DATE_FORMAT(prt_birthday, "%e.%c.%Y") AS prt_birthday,
				prt_registered, prt_supervision_firstname, prt_supervision_lastname,
				prt_supervision_cellphone, prt_notes, prt_grp_id,
				prt_call_status, prt_call_escalation, prt_call_start_time, prt_call_change_time,
				prt_wc_time, grp_name, loc_name
				FROM bf_participants
					LEFT JOIN bf_groups ON grp_id = prt_grp_id
					LEFT JOIN bf_locations ON loc_id = grp_loc_id
				WHERE prt_id=?', array($prt_id));
			$participant_row = $query->row_array();
		}
		return $participant_row;
	}

	public function authorize($redirect = true) {
		$this->load->library('session');
		if ($this->session->has_userdata('stf_login_id') && $this->session->stf_login_id > 0) {
			$this->stf_login_id = $this->session->stf_login_id;
			$this->stf_fullname = $this->session->stf_fullname;
			return true;
		}
		if ($redirect)
			redirect("login");
		return false;
	}

	private function link($target, $selected)
	{
		$attr = array('class'=>'menu-item', 'onclick'=>'window.location=\''.$target.'\';');
		if ($selected)
			$attr['selected'] = null;
		return $attr;
	}

	public function header($title) {
		if ($title == 'Database update') {
			$prt_count = '-';
			$stf_count = '-';
		}
		else {
			$prt_count = (integer) db_1_value('SELECT COUNT(*) FROM bf_participants WHERE prt_registered = 1');
			$stf_count = (integer) db_1_value('SELECT COUNT(*) FROM bf_staff WHERE stf_registered = 1');
		}

		out('<!DOCTYPE html>');
		tag('html');
		tag('head');
		tag('meta', array('http-equiv'=>'Content-Type', 'content'=>'text/html; charset=utf-8'));
		tag('link', array('href'=>base_url('/css/blue-flame.css'), 'rel'=>'stylesheet', 'type'=>'text/css'));
		tag('title', "BlueFlame Kids: ".$title);
		//script('https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js');
		script(base_url('/js/jquery.js'));
		script(base_url('/js/blue-flame.js'));
		_tag('head');
		tag('body'/*, array('onload'=>'setHeaderSizesOfScrollableTables();')*/);
		
		/*
		div(array('class'=>'header'));
		tag('img', array('src'=>base_url('/img/bf-kids-logo.png')));
		if ($title == 'Kinder')
			span($title.' ('.$prt_count.')');
		else if ($title == 'Mitarbeiter')
			span($title.' ('.$stf_count.')');
 		else
			span($title);
		div(array('class'=>'header_name'), $this->stf_fullname);
		_div();
		*/
		div(array('class'=>'topnav'));
		table();
		tr(array('style'=>'height: 12px;'));
		td(array('colspan'=>'8'));
		td(array('rowspan'=>'2', 'valign'=>'bottom', 'style'=>'width: 100%; border-bottom: 1px solid black;'));
		if ($title != 'Login')
			tag('img', array('src'=>base_url('/img/bf-kids-logo2.png'), 'style'=>'height: 40px; width: auto; position: relative; bottom: -2px;'));
		_td();
		td(array('colspan'=>'2'));
		_tr();
		tr(array('style'=>'border-bottom: 1px solid black; padding: 8px 16px;'));
		td(array('style'=>'width: 3px; padding: 0;'), nbsp());
		td($this->link('participant', $title == 'Kinder'), 'Kinder ('.$prt_count.')');
		td(array('style'=>'width: 3px; padding: 0;'), nbsp());
		td($this->link('groups', $title == 'Kleingruppen'), 'Kleingruppen');
		td(array('style'=>'width: 3px; padding: 0;'), nbsp());
		td($this->link('staff', $title == 'Mitarbeiter'), 'Mitarbeiter ('.$stf_count.')');
		td(array('style'=>'width: 3px; padding: 0;'), nbsp());
		td($this->link('calllist', $title == 'Rufliste'), 'Rufliste');
		if ($title != 'Login')
			td($this->link('login?action=logout', false), 'Logout: '.$this->stf_fullname);
		else
			td();
		td(array('style'=>'width: 3px; padding: 0;'), nbsp());
		_tr();
		_table();
		/*
		href('participant', 'Kinder ('.$prt_count.')', $title == 'Kinder' ? array('selected') : array());
		href('groups', 'Kleingruppen', $title == 'Kleingruppen' ? array('selected') : array());
		href('staff', 'Mitarbeiter ('.$stf_count.')', $title == 'Mitarbeiter' ? array('selected') : array());
		href('calllist', 'Rufliste', $title == 'Rufliste' ? array('selected') : array());
		href(url('login', array('action'=>'logout')), 'Logout', array('style'=>'float:right'));
		*/
		_div();
		div(array('class'=>'breadcrumb'));
		if (!empty($this->error))
			print_error($this->error);
		if (!empty($this->warning))
			print_warning($this->warning);
		if (!empty($this->session->bf_success)) {
			print_success($this->session->bf_success);
			// Only display this feedback once:
			$this->session->set_userdata('bf_success', '');
		}
		_div();
	}
	
	public function footer($js_src = "") {

		//div(array('class'=>'footer'));
		//em('&copy; ', 2018);
		//_div();
		_tag('body');

		if (!empty($js_src))
			script($js_src);
		//script();
		//out('$(window).resize(function() {
		//	setHeaderSizesOfScrollableTables();
		//}).resize();');
		//_script();

		_tag('html');
	}
	
	public function setSuccess($message) {
		$this->session->set_userdata('bf_success', $message);
	}
}
