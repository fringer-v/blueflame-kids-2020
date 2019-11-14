<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

class GroupsTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'grp_age_level':
				return 'Altersgruppe';
			case 'stf_fullname':
				return 'Leiter';
			case 'workers_column':
				return 'Mitarbeiter';
			case 'grp_count':
				return 'Teiln.';
			case 'button_column':
				return '&nbsp;';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'grp_name':
			case 'loc_name':
			case 'grp_count':
				return $row[$field];
			case 'grp_from_age':
				return if_empty($row['grp_from_age'], '').' - '.if_empty($row['grp_to_age'], '');
			case 'button_column':
				return (new Submit('select', 'Bearbeiten', array('class'=>'button-black',
					'onclick'=>'$("#set_grp_id").val('.$row['grp_id'].');')))->html();
		}
		return nix();
	}
}

class MemberTable extends Table {
	public function columnTitle($field) {
		switch ($field) {
			case 'prt_number':
				return 'Nr.';
			case 'prt_name':
				return 'Name';
			case 'prt_supervision_name':
				return 'Begleitperson';
			//case 'prt_call_status';
			//	return 'Ruf';
			case 'prt_notes':
				return 'Notizen';
		}
		return nix();
	}

	public function cellValue($field, $row) {
		switch ($field) {
			case 'prt_number':
			case 'prt_name':
			case 'prt_supervision_name':
				$value = $row[$field];
				return $value;
			/*
			case 'prt_call_status': {
				$call_status = $row['prt_call_status'];
				if (is_empty($call_status))
					return nbsp();					
				if ($call_status == CALL_CANCELLED || $call_status == CALL_COMPLETED)
					return div(array('class'=>'red-box', 'style'=>'width: 62px; height: 22px;'), '- Ruf');
				return div(array('class'=>'blue-box', 'style'=>'width: 62px; height: 22px;'), how_long_ago($row['prt_call_start_time']));
			}
			*/
			case 'prt_notes':
				$value = $row[$field];
				return $value;
		}
		return nix();
	}
}

class Groups extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	private function get_group_row($grp_id) {
		if (is_empty($grp_id))
			return array('grp_id'=>'', 'grp_name'=>'', 'grp_leader_stf_id'=>'', 'grp_coleader_stf_id'=>'' ,
				'grp_loc_id'=>'', 'grp_notes'=>'', 'grp_from_age'=>'', 'grp_to_age'=>'',
				'stf_leader'=>'', 'stf_coleader'=>'', 'loc_name'=>'');

		$query = $this->db->query('SELECT grp_id, grp_name, grp_leader_stf_id, grp_coleader_stf_id,
				grp_loc_id, grp_notes, grp_from_age, grp_to_age,
				a.stf_fullname stf_leader, b.stf_fullname stf_coleader, loc_name
			FROM bf_groups
			LEFT JOIN bf_locations ON loc_id = grp_loc_id
			LEFT JOIN bf_staff a ON a.stf_id = grp_leader_stf_id
			LEFT JOIN bf_staff b ON b.stf_id = grp_coleader_stf_id
			WHERE grp_id=?',
			array($grp_id));
		return $query->row_array();
	}

	public function index()
	{
		global $period_names;

		$this->authorize();

		$this->header('Kleingruppen');

		table(array('style'=>'border-collapse: collapse;'));
		tr();
		td(array('class'=>'left-panel', 'align'=>'left', 'valign'=>'top'));
			table();
			for ($p=0; $p<PERIOD_COUNT; $p++) {
				if ($p > 0)
					tr(td([ 'style'=>'height: 10px' ]));
				tr();
				td([ 'class'=>'group-header' ], b($period_names[$p]));
				_tr();
				tr();
				$async_loader = new AsyncLoader('group_list_'.$p, 'groups/getgrouplist?period='.$p,
					[ 'age_level', 'args', 'action' ] );
				td($async_loader->html());
				_tr();
			}
			_table();
		_td();
		_tr();
		_table();

		$this->footer();
	}

	public function getGroupList()
	{
		global $age_level_from;
		global $age_level_to;
		global $all_roles;
		global $extended_roles;

		if (!$this->authorize(false)) {
			echo 'Authorization failed';
			return;
		}

		$period = hidden('period');
		$p = $period->getValue();
		if ($p < 0)
			$p = 0;
		if ($p >= PERIOD_COUNT)
			$p = PERIOD_COUNT-1;

		$display_groups = new Form('display_groups_'.$p, 'groups');

		$age_level = $display_groups->addHidden('age_level');
		$age = $age_level->getValue();
		if ($age < 0)
			$age = 0;
		if ($age >= AGE_LEVEL_COUNT)
			$age = AGE_LEVEL_COUNT-1;
	
		$arguments = $display_groups->addHidden('args');
		$args = $arguments->getValue();
		$action = $display_groups->addHidden('action');
		$act = $action->getValue();

		$group_counts = db_array_2('SELECT grp_age_level, grp_count
			FROM bf_groups WHERE grp_period = ? ORDER BY grp_period, grp_age_level', [ $p ]);

		if ($action->submitted()) {
			switch ($act) {
				case "add-group":
					$group_counts[$age] = arr_nvl($group_counts, $age, 0) + 1;
					$data = array(
						'grp_period' => $p,
						'grp_age_level' => $age,
						'grp_count' => $group_counts[$age]
					);
					$this->db->replace('bf_groups', $data);
					break;
				case "remove-group":
					if (arr_nvl($group_counts, $age, 0) > 0) {
						$group_counts[$age] = arr_nvl($group_counts, $age, 0) - 1;
						$data = array(
							'grp_period' => $p,
							'grp_age_level' => $age,
							'grp_count' => $group_counts[$age]
						);
						$this->db->replace('bf_groups', $data);
					}
					break;
				case "set-leader":
					$group_number = str_left($args, '_');
					$staff_id = str_right($args, '_');
					// Remove the current leader of the group:
					$this->db->query('UPDATE bf_period SET per_age_level = 0, per_group_number = 0
						WHERE per_period = ? AND per_age_level = ? AND per_group_number = ?',
						[ $p, $age, $group_number ]);
					// Set the new leader
					$this->db->query('UPDATE bf_period SET per_age_level = ?, per_group_number = ?
						WHERE per_period = ? AND per_staff_id = ?',
						[ $age, $group_number, $p, $staff_id ]);
					break;					
			}
		}

		$group_leaders = db_array_2('SELECT CONCAT(per_age_level, "_", per_group_number), per_staff_id
			FROM bf_period WHERE per_period = ? AND per_is_leader = TRUE', [ $p ]);

		$group_helpers = db_array_2('SELECT CONCAT(p1.per_age_level, "_", p1.per_group_number),
			GROUP_CONCAT(DISTINCT stf_username ORDER BY stf_username SEPARATOR ", ")
			FROM bf_period p1 JOIN bf_period p2 ON
					p2.per_my_leader_id = p1.per_staff_id AND p2.per_period = ? AND p1.per_age_level = p2.per_age_level
				JOIN bf_staff ON p2.per_staff_id = stf_id
			WHERE p1.per_period = ? AND p1.per_is_leader = TRUE
			GROUP BY p1.per_age_level, p1.per_group_number', [ $p, $p ]);
bugs("-->", $group_leaders);
bugs("-->", $group_helpers);

		$display_groups->open();
		table();
		for ($a=0; $a<AGE_LEVEL_COUNT; $a++) {
			tr();
			th([ 'align'=>'right' ], $age_level_from[$a].' - '.$age_level_to[$a].':');
			$count = arr_nvl($group_counts, $a, 0);
			for ($i=1; $i<=$count; $i++) {
				td();
				table(['class'=>'group g-'.$a]);
				tr();
				td(span(['class'=>'group-number'], $i));
				$leaders = [ 0=>'' ] + db_array_2("SELECT stf_id, stf_username FROM bf_staff, bf_period
					WHERE stf_id = per_staff_id AND per_is_leader = TRUE AND
						per_period = $p AND per_age_level_$a = TRUE
						ORDER BY stf_username");
				td(select('select_leader', $leaders, arr_nvl($group_leaders, $a.'_'.$i, 0),
					[ 'onchange'=>'$("#age_level").val('.$a.');
						$("#args").val("'.$i.'_" + $(this).val());
						$("#action").val("set-leader");
						group_list_'.$p.'();' ]));
				_tr();
				tr();
				td();
				td('.AAA');
				_td();
				_tr();
				_table();
				_td();
			}
			td();
			if ($count > 0) {
				span([ 'class'=>'group-add g--'.$a, 'style'=>'width: 39px;',
					'onclick'=>'$("#age_level").val('.$a.'); $("#action").val("remove-group"); group_list_'.$p.'();' ], '-');
				span(['style'=>'display: inline-block; width: 11px;'], '');
			}
			span([ 'class'=>'group-add g--'.$a, 'style'=>'width: 39px;',
				'onclick'=>'$("#age_level").val('.$a.'); $("#action").val("add-group"); group_list_'.$p.'();' ], '+');
			_td();
			_tr();
		}
		_table();
		$display_groups->close();
	}

	public function printable() {
		if (!$this->authorize(false)) {
			echo 'Authorization failed';
			return;
		}

		$grp_id = new Hidden('grp_id');
		$grp_id->makeGlobal();
		$grp_id_v = $grp_id->getValue();
		$group_row = $this->get_group_row($grp_id_v);

		$update_group = new Form('update_group', 'groups', 1, array('class'=>'output-table'));
		$update_group->addField('Gruppe', b($group_row['grp_name']));
		$update_group->addField('Leiter', $group_row['stf_leader']);
		$update_group->addField('Co-Leiter', $group_row['stf_coleader']);
		$update_group->addField('Raum', $group_row['loc_name']);
		$update_group->addField('Altersgruppe', $group_row['grp_from_age'].' - '.$group_row['grp_to_age']);
		$update_group->addField('Notizen', $group_row['grp_notes']);

		$member_table = new MemberTable('SELECT prt_id, prt_number, 
				CONCAT(prt_firstname, " ", prt_lastname) as prt_name,
				CONCAT(prt_supervision_firstname, " ", prt_supervision_lastname) AS prt_supervision_name,
			 	prt_notes
			FROM bf_participants
			WHERE prt_grp_id = ? AND prt_registered != '.REG_NO.' ORDER BY prt_id',
			array($grp_id_v), array('class'=>'printable-table'));

		out('<!DOCTYPE html>');
		tag('html');
		tag('head');
		tag('meta', array('http-equiv'=>'Content-Type', 'content'=>'text/html; charset=utf-8'));
		tag('link', array('href'=>base_url('/css/blue-flame.css'), 'rel'=>'stylesheet', 'type'=>'text/css'));
		tag('title', '');
		_tag('head');
		tag('body', array('style'=>'background: white;'));
		table(array('style' => 'width: 720px; padding: 5px;'));
		tr();
		td();
		$update_group->show();
		_td();
		_tr();
		tr(td($member_table->html()));
		_table();
		_tag('body');
		_tag('html');
	}
}
