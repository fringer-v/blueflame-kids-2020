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
define('CALL_ENDED', 7);
define('GO_TO_WC', 8);
define('BACK_FROM_WC', 9);
define('BEING_FETCHED', 10);
define('CHANGED_GROUP', 11);
define('FETCH_CANCELLED', 12);
define('NAME_CHANGED', 13);
define('BIRTHDAY_CHANGED', 14);
define('SUPERVISOR_CHANGED', 15);
define('CELLPHONE_CHANGED', 16);
define('NOTES_CHANGED', 17);

// hst_action && prt_call_status 
define('CALL_NOCALL', 0);
define('CALL_CALLED', 100);		// Operator has executed call
define('CALL_COMPLETED', 200);	// Call completed (if cancelled after called)
define('CALL_PENDING', 300);	// Call request pending
define('CALL_CANCELLED', 400);	// Call withdrawen (no longer required)

define('TEXT_CALLED', 'Gerufen');
define('TEXT_COMPLETED', 'Ruf beendet');
define('TEXT_PENDING', 'Ruf bevorstehend');
define('TEXT_CANCELLED', 'Ruf aufgehoben');
define('TEXT_ESCALATED', 'Ruf eskaliert');

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

define('DEFAULT_GROUP_SIZE', 8);

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
				DATE_FORMAT(prt_birthday, "%d.%m.%Y") AS prt_birthday,
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

	public function get_participant_row_by_name($prt_firstname, $prt_lastname) {
		$query = $this->db->query('SELECT prt_id, prt_number, prt_firstname, prt_lastname,
			DATE_FORMAT(prt_birthday, "%d.%m.%Y") AS prt_birthday,
			prt_registered, prt_supervision_firstname, prt_supervision_lastname,
			prt_supervision_cellphone, prt_notes,
			prt_age_level, prt_group_number,
			prt_call_status, prt_call_escalation, prt_call_start_time, prt_call_change_time,
			prt_wc_time, prt_age_level, prt_group_number
			FROM bf_participants
			WHERE prt_firstname=? AND prt_lastname=?', [ $prt_firstname, $prt_lastname ]);
		$participant_row = $query->row_array();
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

	public function get_staff_row_by_username($stf_username) {
		$query = $this->db->query(
			'SELECT stf_id, stf_username, stf_fullname, stf_password, '.
			'stf_registered, stf_loginallowed, stf_technician '.
			'FROM bf_staff WHERE stf_username=?', [ $stf_username ]);
		$staff_row = $query->row_array();
		return $staff_row;
	}

	public function reserve_group($age, $num)
	{
		$this->db->set('stf_reserved_count',
			'IF(stf_reserved_age_level = '.$age.' AND stf_reserved_group_number = '.$num.', stf_reserved_count+1, 1)', false);
		$this->db->set('stf_reserved_age_level', $age);
		$this->db->set('stf_reserved_group_number', $num);
		$this->db->where('stf_id', $this->session->stf_login_id);
		$this->db->update('bf_staff');
	}

	public function unreserve_groups($age, $num)
	{
		$this->db->set('stf_reserved_age_level', null);
		$this->db->set('stf_reserved_group_number', null);
		$this->db->set('stf_reserved_count', 0);
		$this->db->where('stf_id', $this->session->stf_login_id);
		$this->db->where('stf_reserved_age_level', $age);
		$this->db->where('stf_reserved_group_number', $num);
		$this->db->update('bf_staff');
	}

	public function unreserve_group($age, $num)
	{
		$this->db->set('stf_reserved_age_level', 'IF (stf_reserved_count = 1, NULL, stf_reserved_age_level)', false);
		$this->db->set('stf_reserved_group_number', 'IF (stf_reserved_count = 1, NULL, stf_reserved_group_number)', false);
		$this->db->set('stf_reserved_count', 'stf_reserved_count-1', false);
		$this->db->where('stf_id', $this->session->stf_login_id);
		$this->db->where('stf_reserved_age_level', $age);
		$this->db->where('stf_reserved_group_number', $num);
		$this->db->update('bf_staff');
	}

	public function insert_participant($after_row, $group_reserved) {
		$insert_row = $after_row;
		$insert_row['prt_registered'] = $group_reserved ? REG_YES : REG_NO;
		$insert_row['prt_birthday'] = str_to_date($after_row['prt_birthday'])->format('Y-m-d');
		$insert_row['prt_create_stf_id'] = $this->session->stf_login_id;

		do {
			$prt_number = (integer) db_1_value('SELECT MAX(prt_number) FROM bf_participants');
			$prt_number = $prt_number < 100 ? 100 : $prt_number+1;
			$insert_row['prt_number'] = $prt_number;
			$prt_id_v = db_insert('bf_participants', $insert_row, 'prt_modifytime');
		}
		while (empty($prt_id_v));

		$history = [ 'hst_stf_id'=> $this->session->stf_login_id ];
		$history['hst_prt_id'] = $prt_id_v;
		if ($group_reserved) {
			$history['hst_action'] = REGISTER;
			$history['hst_age_level'] = $after_row['prt_age_level'];
			$history['hst_group_number'] = $after_row['prt_group_number'];
			$this->db->insert('bf_history', $history);
			$this->unreserve_group($after_row['prt_age_level'], $after_row['prt_group_number']);
		}
		else {
			$history['hst_action'] = CREATED;
			$this->db->insert('bf_history', $history);
		}
		return $prt_id_v;
	}

	public function modify_participant($prt_id_v, $before_row, $after_row, $group_reserved) {
		$update_row = $after_row;
		if (isset($after_row['prt_birthday']))
			$update_row['prt_birthday'] = str_to_date($after_row['prt_birthday'])->format('Y-m-d');
		$update_row['prt_modify_stf_id'] = $this->session->stf_login_id;

		$this->db->set('prt_modifytime', 'NOW()', false);
		$this->db->where('prt_id', $prt_id_v);
		$this->db->update('bf_participants', $update_row);

		$history = [ 'hst_stf_id'=> $this->session->stf_login_id ];
		$history['hst_prt_id'] = $prt_id_v;

		if ($before_row['prt_firstname'] != $after_row['prt_firstname'] ||
			$before_row['prt_lastname'] != $after_row['prt_lastname']) {
			$history['hst_action'] = NAME_CHANGED;
			$history['hst_notes'] = $before_row['prt_firstname'].' '.$before_row['prt_lastname'].
				' -> '.$after_row['prt_firstname'].' '.$after_row['prt_lastname'];
			$this->db->insert('bf_history', $history);
		}

		if ($before_row['prt_birthday'] != $after_row['prt_birthday']) {
			$history['hst_action'] = BIRTHDAY_CHANGED;
			$history['hst_notes'] = $before_row['prt_birthday'].' -> '.$after_row['prt_birthday'];
			$this->db->insert('bf_history', $history);
		}

		if ($before_row['prt_supervision_firstname'] != $after_row['prt_supervision_firstname'] ||
			$before_row['prt_supervision_lastname'] != $after_row['prt_supervision_lastname']) {
			$history['hst_action'] = SUPERVISOR_CHANGED;
			$history['hst_notes'] = $before_row['prt_supervision_firstname'].' '.$before_row['prt_supervision_lastname'].
				' -> '.$after_row['prt_supervision_firstname'].' '.$after_row['prt_supervision_lastname'];
			$this->db->insert('bf_history', $history);
		}

		if ($before_row['prt_supervision_cellphone'] != $after_row['prt_supervision_cellphone']) {
			$history['hst_action'] = CELLPHONE_CHANGED;
			$history['hst_notes'] = $before_row['prt_supervision_cellphone'].' -> '.$after_row['prt_supervision_cellphone'];
			$this->db->insert('bf_history', $history);
		}

		if ($before_row['prt_notes'] != $after_row['prt_notes']) {
			$history['hst_action'] = NOTES_CHANGED;
			if (empty($before_row['prt_notes']))
				$history['hst_notes'] = ' -> "'.$after_row['prt_notes'].'"';
			else {
				if (empty(trim($after_row['prt_notes'])))
					$history['hst_notes'] = '"'.$before_row['prt_notes'].'" -> ""';
				else
					$history['hst_notes'] = '"'.$before_row['prt_notes'].'" -> "..."';
			}
			$this->db->insert('bf_history', $history);
		}

		if ($group_reserved) {
			$history['hst_action'] = ($before_row['prt_registered'] != REG_NO || $before_row['prt_group_number'] > 0) ? CHANGED_GROUP : REGISTER;
			$history['hst_age_level'] = $after_row['prt_age_level'];
			$history['hst_group_number'] = $after_row['prt_group_number'];
			unset($history['hst_notes']);
			$this->db->insert('bf_history', $history);
			$this->unreserve_group($after_row['prt_age_level'], $after_row['prt_group_number']);
		}
	}

	public function get_period_data($p = 0)
	{
		$current_period = $this->db_model->get_setting('current-period');
		if ($p == 0)
			$p = $current_period;

		$nr_of_groups = [];
		$total_limit = 0;
		$total_count = 0;
		$total_limits = [];
		$total_counts = [];
		$group_limits = [];
		$group_counts = [];

		$groups = db_row_array('SELECT grp_age_level, grp_count, grp_size_hints
			FROM bf_groups WHERE grp_period = ? ORDER BY grp_period, grp_age_level', [ $p ]);

bugout("========");
		foreach ($groups as $group) {
			$nr_of_groups[$group['grp_age_level']] = $group['grp_count'];
			$limits = explode(',', $group['grp_size_hints']);
bugout($group['grp_size_hints'], $limits);
			if (!empty($limits)) {
				for ($i=1; $i<=count($limits); $i++)
					$group_limits[$group['grp_age_level'].'_'.$i] = if_empty($limits[$i-1], 0);
			}
		}
bugout($group_limits);

		// Number of kids in each group:
		if ($p == $current_period) {
			$group_counts = db_array_2('SELECT CONCAT(prt_age_level, "_", prt_group_number),
				COUNT(DISTINCT prt_id)
				FROM bf_participants WHERE prt_group_number > 0 GROUP BY prt_age_level, prt_group_number');
			foreach ($group_counts as $group=>$count) {
				$age = str_left($group, '_');
				$num = str_right($group, '_');
				if (arr_nvl($nr_of_groups, $age, 0) < $num)
					$nr_of_groups[$age] = $num;
			}
		}

		for ($a=0; $a<AGE_LEVEL_COUNT; $a++) {
			$a_limit = 0;
			$a_count = 0;
			$max_group_nr = arr_nvl($nr_of_groups, $a, 0);
			for ($i=1; $i<=$max_group_nr; $i++) {
				$a_limit += if_empty(arr_nvl($group_limits, $a.'_'.$i, 0), DEFAULT_GROUP_SIZE);
				$a_count += if_empty(arr_nvl($group_counts, $a.'_'.$i, 0), 0);
			}
			$total_limits[$a] = $a_limit;
			$total_counts[$a] = $a_count;
			$total_limit += $a_limit;
			$total_count += $a_count;
		}
		return [ $current_period, $nr_of_groups,
			$total_limit, $total_count, $total_limits, $total_counts,
			$group_limits, $group_counts ];
	}

	public function is_logged_in() {
		$this->load->library('session');

		$page = str_left($this->uri->uri_string(), "/");
		if (!$this->session->has_userdata('ses_prev_page'))
			$this->session->set_userdata('ses_prev_page', $page);
		if (!$this->session->has_userdata('ses_curr_page') || $this->session->ses_curr_page != $page) {
			$this->session->set_userdata('ses_prev_page', $this->session->ses_curr_page);
			$this->session->set_userdata('ses_curr_page', $page);
		}

		if ($this->session->has_userdata('stf_login_id') && $this->session->stf_login_id > 0) {
			$this->stf_login_id = $this->session->stf_login_id;
			$this->stf_login_name = $this->session->stf_login_name;
			return true;
		}
		return false;
	}

	public function set_logged_in($staff_row) {
		$this->load->library('session');
		$this->session->set_userdata('stf_login_id', $staff_row['stf_id']);
		$this->session->set_userdata('stf_login_name', $staff_row['stf_fullname']);
		$this->session->set_userdata('stf_login_tech', $staff_row['stf_technician']);
		$this->db->query('UPDATE bf_staff SET stf_registered = 1 WHERE stf_id = ?',
			array($staff_row['stf_id']));
	}

	public function authorize($redirect_page = 'login') {
		if ($this->is_logged_in())
			return true;

		$this->header('Redirect');
		script();
		out('window.parent.location = "'.site_url($redirect_page).'";');
		_script();
		$this->footer();

		return false;
	}

	private function link($target, $selected)
	{
		$attr = array('class'=>'menu-item', 'onclick'=>'window.location=\''.$target.'\';');
		if ($selected)
			$attr['selected'] = null;
		return $attr;
	}

	public function header($title, $menu = true) {
		out('<!DOCTYPE html>');
		tag('html');
		tag('head');
		tag('meta', [ 'http-equiv'=>'Content-Type', 'content'=>'text/html; charset=utf-8' ]);
		tag('meta', [ 'name'=>'apple-mobile-web-app-capable', 'content'=>'yes' ]);
		tag('meta', [ 'name'=>'apple-mobile-web-app-status-bar-style', 'content'=>'black' ]);
		tag('link', array('href'=>base_url('/css/blue-flame.css'), 'rel'=>'stylesheet', 'type'=>'text/css'));
		tag('title', "BlueFlame Kids: ".$title);
		script(base_url('/js/jquery.js'));
		script(base_url('/js/blue-flame.js'));
		_tag('head');
		tag('body');

		table([ 'style'=>'width: 100%; border-collapse: collapse; border: 0px;' ]);
		tr();
		td([ 'style'=>'padding: 0px;' ]);

		if ($menu) {
			if ($title == 'Database update') {
				$prt_count = '-';
				$stf_count = '-';
			}
			else {
				$prt_count = (integer) db_1_value('SELECT COUNT(*) FROM bf_participants WHERE prt_registered != '.REG_NO);
				$stf_count = (integer) db_1_value('SELECT COUNT(*) FROM bf_staff WHERE stf_registered = 1');
			}

			div(array('class'=>'topnav'));
			table();
			tr(array('style'=>'height: 12px;'));
			if ($title != 'Login') {
				td(array('rowspan'=>'2', 'valign'=>'bottom', 'style'=>'padding: 0px 3px 2px 10px; border-bottom: 1px solid black;'));
				img([ 'src'=>base_url('/img/bf-kids-logo2.png'), 'style'=>'height: 40px; width: auto; position: relative; bottom: -2px;']);
				_td();
			}
			td(array('colspan'=>'10'));
			td(array('rowspan'=>'2', 'valign'=>'bottom', 'style'=>'width: 100%; border-bottom: 1px solid black;'));
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
			td(array('style'=>'width: 3px; padding: 0;'), nbsp());
			td($this->link('registration', $title == 'iPad Registrierung'), 'iPad Registrierung');
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

			// Little padding before content:
			div([ 'style'=>'height: 20px;' ], nbsp());
		}
	}

	public function footer($js_src = "") {
		_td();
		_tr();
		_table();

		_tag('body');
		if (!empty($js_src))
			script($js_src);
		_tag('html');
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
