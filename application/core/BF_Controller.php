<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// prt_registered
define('REG_NO', 0);
define('REG_YES', 1);
define('REG_BEING_FETCHED', 2);

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
define('BEING_FETCHED', 10);

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

// Staff roles:
define('ROLE_OTHER', 0);
define('ROLE_GROUP_LEADER', 1);
define('ROLE_OFFICIAL', 2);
define('ROLE_TECHNICIAN', 3);

// Extended roles
define('EXT_ROLE_TEAM_LEADER', 100);
define('EXT_ROLE_TEAM_COLEADER', 101);

// Periods:
define('PERIOD_FRIDAY', 0);
define('PERIOD_SAT_MORNING', 1);
define('PERIOD_SAT_AFTERNOON', 2);
define('PERIOD_SAT_EVENING', 3);
define('PERIOD_SUNDAY', 4);
define('PERIOD_COUNT', 5);

define('CURRENT_PERIOD', 0);

$period_names = array (
	PERIOD_FRIDAY => 'Freitag Abend',
	PERIOD_SAT_MORNING => 'Samstag Morgen',
	PERIOD_SAT_AFTERNOON => 'Samstag Nachmittag',
	PERIOD_SAT_EVENING => 'Samstag Abend',
	PERIOD_SUNDAY => 'Sontag Morgen');

$all_roles = array(
	ROLE_OTHER => '',
	ROLE_GROUP_LEADER => 'Gruppenleiter',
	ROLE_OFFICIAL => 'Ordner',	
	ROLE_TECHNICIAN => 'Techniker'	
);

$extended_roles = $all_roles + array(
	EXT_ROLE_TEAM_LEADER => 'Teamleiter',
	EXT_ROLE_TEAM_COLEADER => 'Teammitglieder'	
);

// Age levels:
define('AGE_LEVEL_0', 0);
define('AGE_LEVEL_1', 1);
define('AGE_LEVEL_2', 2);
define('AGE_LEVEL_COUNT', 3);

$age_level_from = array (AGE_LEVEL_0 => 4, AGE_LEVEL_1 => 6, AGE_LEVEL_2 => 9);
$age_level_to = array (AGE_LEVEL_0 => 5, AGE_LEVEL_1 => 8, AGE_LEVEL_2 => 11);


class BF_Controller extends CI_Controller {
	public $stf_login_id = 0;
	public $stf_login_name = '';

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
				'prt_registered'=>REG_NO, 'prt_supervision_firstname'=>'', 'prt_supervision_lastname'=>'',
				'prt_supervision_cellphone'=>'', 'prt_notes'=>'',
				'prt_age_level'=>'', 'prt_group_number'=>'',
				'prt_call_status'=>'', 'prt_call_escalation'=>'', 'prt_call_start_time'=>'', 'prt_call_change_time'=>'',
				'prt_wc_time'=>'', 'prt_age_level'=>'', 'prt_group_number'=>''
			);
		else {
			$query = $this->db->query('SELECT prt_id, prt_number, prt_firstname, prt_lastname,
				DATE_FORMAT(prt_birthday, "%e.%c.%Y") AS prt_birthday,
				prt_registered, prt_supervision_firstname, prt_supervision_lastname,
				prt_supervision_cellphone, prt_notes,
				prt_age_level, prt_group_number,
				prt_call_status, prt_call_escalation, prt_call_start_time, prt_call_change_time,
				prt_wc_time, prt_age_level, prt_group_number
				FROM bf_participants
				WHERE prt_id=?', array($prt_id));
			$participant_row = $query->row_array();
		}
		return $participant_row;
	}

	public function get_staff_row($stf_id) {
		if (is_empty($stf_id))
			return array('stf_id'=>'', 'stf_username'=>'', 'stf_fullname'=>'', 'stf_password'=>'',
				'stf_reserved_age_level'=>0, 'stf_reserved_group_number'=>0, 'stf_reserved_count'=>0,
				'stf_role'=>ROLE_OTHER, 'stf_registered'=>'', 'stf_loginallowed'=>'', 'stf_technician'=>0);

		$query = $this->db->query('SELECT s1.stf_id, s1.stf_username, s1.stf_fullname, s1.stf_password,
			s1.stf_reserved_age_level, s1.stf_reserved_group_number, s1.stf_reserved_count,
			s1.stf_role, s1.stf_registered, s1.stf_loginallowed, s1.stf_technician,
			GROUP_CONCAT(DISTINCT s2.stf_id ORDER BY s2.stf_username SEPARATOR ",") team_ids,
			GROUP_CONCAT(DISTINCT s2.stf_username ORDER BY s2.stf_username SEPARATOR ",") team_names
			FROM bf_staff s1
			LEFT OUTER JOIN bf_period ON s1.stf_id = per_my_leader_id
			LEFT OUTER JOIN bf_staff s2 ON s2.stf_id = per_staff_id
			WHERE s1.stf_id=?
			GROUP BY s1.stf_id',
			array($stf_id));
		return $query->row_array();
	}

	public function authorize($redirect = true) {
		$this->load->library('session');
		if ($this->session->has_userdata('stf_login_id') && $this->session->stf_login_id > 0) {
			$this->stf_login_id = $this->session->stf_login_id;
			$this->stf_login_name = $this->session->stf_login_name;
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

	public function header($title, $print_result = true) {
		if ($title == 'Database update') {
			$prt_count = '-';
			$stf_count = '-';
		}
		else {
			$prt_count = (integer) db_1_value('SELECT COUNT(*) FROM bf_participants WHERE prt_registered != '.REG_NO);
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
		hidden('login_full_name', $this->stf_login_name);
		if ($title != 'Login') {
			$attr = $this->link('login?action=logout', false);
			$attr['id'] = 'logout_menu_item';
			$attr['onmouseover'] = 'mouseOverLogout(this);';
			$attr['onmouseout'] = 'mouseOutLogout(this, $(\'#login_full_name\'));';
			td($attr, $this->stf_login_name);
		}
		else
			td();
		td(array('style'=>'width: 3px; padding: 0;'), nbsp());
		_tr();
		_table();
		_div();

		div(array('class'=>'breadcrumb'));
		if ($print_result)
			$this->printResult();
		_div();
	}

	public function printResult() {
		if (!empty($this->error))
			print_error($this->error);
		if (!empty($this->warning))
			print_warning($this->warning);
		if (!empty($this->session->bf_success)) {
			print_success($this->session->bf_success);
			// Only display this feedback once:
			$this->session->set_userdata('bf_success', '');
		}
	}

	public function footer($js_src = "") {
		_tag('body');
		if (!empty($js_src))
			script($js_src);
		_tag('html');
	}
	
	public function setSuccess($message) {
		$this->session->set_userdata('bf_success', $message);
	}

	// ---------------------------
	public function linkList($page, $ids, $names)
	{
		$out = out('');
		for ($j=0; $j<sizeof($ids); $j++) {
			if ($j > 0)
				$out->add(', ');
			$out->add(a([ 'href'=>$page.$ids[$j] ], $names[$j] ));
		}
		return $out;
	}
}
