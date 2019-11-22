<?php

// The current database version:
// 24 - Allow various fields in participant to be NULL
// 25 - Added bf_locations table
// 26 - Added leader and coleader to groups
// 27 - Changed prt_registered field to status
// 39 - Changes for Blueflame 2020
define("DB_VERSION", 39);

class DB_model extends CI_Model {
	private $settings = array();
	private $meta_settings = array(
			"database-version" => array("integer", 0),	// type, default_value
			"current-period" => array("integer", 2)	// type, default_value
		);

	public function __construct() {
		$this->load->database();
	}

	public function get_setting($name) {
		if (empty($this->settings)) {
			if ($this->db->table_exists('bf_setting')) {
				$query = $this->db->query('SELECT stn_name, stn_value, stn_type FROM bf_setting');
				$settings = array();
				while ($row = $query->unbuffered_row()) {
					$val = (string) $row->stn_value;
					switch ($row->stn_type) {
						case "integer":
							$val = (integer) $val;
							break;
						case "boolean":
							$val = (boolean) $val;
							break;
					
					}
					$settings[$row->stn_name] = $val;
				}
				$this->settings = $settings;
			}				
		}
		if (array_key_exists($name, $this->settings))
			return $this->settings[$name];
		if (array_key_exists($name, $this->meta_settings))
			return $this->meta_settings[$name][1];
		fatal_error("Unknown setting: ".$name);
	}
		
	public function set_setting($name, $val) {
		if (!array_key_exists($name, $this->meta_settings))
			fatal_error("Unknown setting: ".$name);
		$type = $this->meta_settings[$name][0];
		if (gettype($val) != $type)
			fatal_error("Setting: incorrect type for: ".$name.", required type: ".$type);
		
		$sql = "INSERT INTO bf_setting (stn_name, stn_type, stn_value) VALUES (?, ? , ?)
			ON DUPLICATE KEY UPDATE stn_value=VALUES(stn_value);";
		$this->db->query($sql, array($name, $type, $val));
		$this->settings[$name] = $val;
	}

	public function up_to_date() {
		return $this->get_setting("database-version") == DB_VERSION;
	}

	public function update_database() {
		$this->load->dbforge();
		
		$fields = array(
			'stn_name VARCHAR(40) NOT NULL PRIMARY KEY',
			'stn_type'=>array('type'=>'VARCHAR', 'constraint'=>'10'),
			'stn_value'=>array('type'=>'VARCHAR', 'constraint'=>'400')
		);
		$this->create_or_update_table('bf_setting', $fields);

		$fields = array(
			'id'=>array('type'=>'VARCHAR', 'constraint'=>'128'),
			'ip_address'=>array('type'=>'VARCHAR', 'constraint'=>'45'),
			'timestamp'=>array('type'=>'INTEGER', 'unsigned'=>true, 'default'=>0),
			'data'=>array('type'=>'BLOB')
		);
		$this->create_or_update_table('bf_sessions', $fields, array('timestamp'));

		$fields = array(
			'stf_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'stf_username'=>array('type'=>'VARCHAR', 'constraint'=>'100', 'unique'=>true),
			'stf_fullname'=>array('type'=>'VARCHAR', 'constraint'=>'100', 'unique'=>true),
			'stf_privs'=>array('type'=>'INTEGER', 'unsigned'=>true, 'default'=>0),
			'stf_password VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL',
			'stf_role'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'default'=>0),
			'stf_registered'=>array('type'=>'BOOLEAN', 'default'=>false),
			'stf_loginallowed'=>array('type'=>'BOOLEAN', 'default'=>true),
			'stf_technician'=>array('type'=>'BOOLEAN', 'default'=>false),
			'stf_reserved_age_level'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'stf_reserved_count'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'INDEX stf_reserved (stf_reserved_age_level, stf_reserved_count)'
		);
		$this->create_or_update_table('bf_staff', $fields);

		$fields = array(
			'per_staff_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>false),
			'per_period'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>false),
			'per_age_level'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'per_group_number'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'per_location_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'per_present'=>array('type'=>'BOOLEAN', 'default'=>false, 'null'=>false),
			'per_is_leader'=>array('type'=>'BOOLEAN', 'default'=>false, 'null'=>false),
			'per_my_leader_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'per_age_level_0'=>array('type'=>'BOOLEAN', 'default'=>false, 'null'=>false),
			'per_age_level_1'=>array('type'=>'BOOLEAN', 'default'=>false, 'null'=>false),
			'per_age_level_2'=>array('type'=>'BOOLEAN', 'default'=>false, 'null'=>false),
			'PRIMARY KEY per_primary_key (per_staff_id, per_period)'
		);
		$this->create_or_update_table('bf_period', $fields);

		$fields = array(
			'prt_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'prt_number'=>array('type'=>'INTEGER', 'unsigned'=>true, 'unique'=>true),
			'prt_firstname'=>array('type'=>'VARCHAR', 'constraint'=>'50', 'null'=>true),
			'prt_lastname'=>array('type'=>'VARCHAR', 'constraint'=>'80', 'null'=>true),
			'prt_birthday'=>array('type'=>'DATE', 'null'=>true),
			'prt_registered'=>array('type'=>'TINYINT', 'unsigned'=>true, 'null'=>false, 'default'=>'1'),
			'prt_supervision_firstname'=>array('type'=>'VARCHAR', 'constraint'=>'50', 'null'=>true),
			'prt_supervision_lastname'=>array('type'=>'VARCHAR', 'constraint'=>'80', 'null'=>true),
			'prt_supervision_cellphone'=>array('type'=>'VARCHAR', 'constraint'=>'50', 'null'=>true),
			'prt_age_level'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'prt_group_number'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'prt_createtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
			'prt_modifytime'=>array('type'=>'DATETIME', 'null'=>false),
			'prt_create_stf_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'prt_modify_stf_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'prt_call_status'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'prt_call_escalation'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'prt_call_start_time'=>array('type'=>'DATETIME', 'null'=>true),
			'prt_call_change_time'=>array('type'=>'DATETIME', 'null'=>true),
			'prt_wc_time'=>array('type'=>'DATETIME', 'null'=>true), // Not null means WC!
			'prt_notes'=>array('type'=>'TEXT'),
			'UNIQUE INDEX prt_name_index (prt_firstname, prt_lastname)',
			'INDEX prt_group_index (prt_age_level, prt_group_number)',
			'INDEX prt_call_status_index (prt_call_status, prt_call_change_time)'
		);
		$this->create_or_update_table('bf_participants', $fields);

		$fields = array(
			'grp_period'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>false),
			'grp_age_level'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'grp_count'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'PRIMARY KEY grp_primary_key (grp_period, grp_age_level)'
		);
		$this->create_or_update_table('bf_groups', $fields); // Kleingruppe

		$fields = array(
			'hst_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'hst_prt_id'=>array('type'=>'INTEGER', 'unsigned'=>true),
			'hst_stf_id'=>array('type'=>'INTEGER', 'unsigned'=>true),
			'hst_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
			'hst_action'=>array('type'=>'SMALLINT', 'unsigned'=>true),
			'hst_escalation'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'hst_grp_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'hst_notes'=>array('type'=>'TEXT'),
			'INDEX hst_prt_id_timestamp_index (hst_prt_id, hst_timestamp)'
		);
		$this->create_or_update_table('bf_history', $fields);

		$fields = array(
			'loc_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'loc_name'=>array('type'=>'VARCHAR', 'constraint'=>'100', 'unique'=>true)
		);
		$this->create_or_update_table('bf_locations', $fields);
		
		$this->add_staff('Admin', 'Administrator', '$2y$10$orVZz8QD6iuSqg7G//Rvm.OFWFxFEQ1fSFFuc8H2Kn5bJYqRZ7FZW');

		$this->add_location('Buurndeel');
		$this->add_location('Thronsaal');
		$this->add_location('Raum A (alter Thronsaal)');
		$this->add_location('Raum B (Catering Zelt)');
		$this->add_location('Ranger');

		$this->set_setting('database-version', DB_VERSION);

		$tables = db_array_2('SHOW TABLES');
		foreach ($tables as $table) {
			if (str_startswith($table, 'old_'.DB_VERSION.'_')) {
				$this->dbforge->drop_table($table);
			}
		}
	}

	public function add_staff($username, $fullname, $password) {
		$count = (integer) db_1_value('SELECT COUNT(*) FROM bf_staff WHERE stf_username = ?', array($username));
		if ($count == 0)
			$this->db->query('INSERT bf_staff (stf_username, stf_fullname, stf_password) VALUES (?, ?, ?)',
				array($username, $fullname, $password));
	}

	public function add_location($loc_name) {
		$count = (integer) db_1_value('SELECT COUNT(*) FROM bf_locations WHERE loc_name = ?', array($loc_name));
		if ($count == 0)
			$this->db->query('INSERT bf_locations (loc_name) VALUES (?)',
				array($loc_name));
	}

	public function create_or_update_table($table_name, $fields, $keys = array()) {
		$new_table = 'new_'.DB_VERSION.'_'.$table_name;
		$old_table = 'old_'.DB_VERSION.'_'.$table_name;
		
		$current_exists = $this->db->table_exists($table_name);
		$new_exists = $this->db->table_exists($new_table);
		$old_exists = $this->db->table_exists($old_table);

		if (!$current_exists && !$old_exists && !$new_exists)
			// New table:
			$this->create_table($table_name, $fields, $keys);
		else {
			if (!$old_exists) {
				$this->create_table($new_table, $fields, $keys);
				$this->db->truncate($new_table);
				$new_exists = true;
			
				// Copy data:
				$fields = $this->db->list_fields($table_name);
				$new_fields = $this->db->list_fields($new_table);
				$fields = array_intersect($fields, $new_fields);

				$sql = 'INSERT INTO '.$new_table.' ('.implode(",", $fields).') ';
				$sql .= 'SELECT '.implode(",", $fields).' FROM '.$table_name;
				$this->db->query($sql);

				// Current to old:
				$this->dbforge->rename_table($table_name, $old_table);
			}
			
			if ($new_exists) {
				// New to current:
				$this->dbforge->rename_table($new_table, $table_name);
			}
		}
	}

	public function create_table($table_name, $fields, $keys = array()) {
		foreach ($fields as $field => $details) {
			if (is_array($details))
				$this->dbforge->add_field(array($field=>$details));
			else
				$this->dbforge->add_field($details);
		}	
		foreach ($keys as $key) {
			$this->dbforge->add_key($key);
		}
		$attributes = array('ENGINE' => 'InnoDB');
		$this->dbforge->create_table($table_name, true, $attributes);
	}
}

?>
