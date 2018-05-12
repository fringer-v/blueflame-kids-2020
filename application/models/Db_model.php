<?php

// THe current database version:
define("DB_VERSION", 17);

class DB_model extends CI_Model {
	private $settings = array();
	private $meta_settings = array(
			"database-version" => array("integer", 0)	// type, default_value
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
		$this->create_table('bf_setting', $fields);

		$fields = array(
			'id'=>array('type'=>'VARCHAR', 'constraint'=>'128'),
			'ip_address'=>array('type'=>'VARCHAR', 'constraint'=>'45'),
			'timestamp'=>array('type'=>'INTEGER', 'unsigned'=>true, 'default'=>0),
			'data'=>array('type'=>'BLOB')
		);
		$this->create_table('bf_sessions', $fields, array('timestamp'));

		$fields = array(
			'stf_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'stf_username'=>array('type'=>'VARCHAR', 'constraint'=>'100', 'unique'=>true),
			'stf_fullname'=>array('type'=>'VARCHAR', 'constraint'=>'100', 'unique'=>true),
			'stf_privs'=>array('type'=>'INTEGER', 'unsigned'=>true, 'default'=>0),
			'stf_password VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL'
		);
		$this->create_table('bf_staff', $fields);

		$fields = array(
			'prt_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'prt_number'=>array('type'=>'INTEGER', 'unsigned'=>true, 'unique'=>true),
			'prt_firstname'=>array('type'=>'VARCHAR', 'constraint'=>'50'),
			'prt_lastname'=>array('type'=>'VARCHAR', 'constraint'=>'80'),
			'prt_birthday'=>array('type'=>'DATE'),
			'prt_registered'=>array('type'=>'BOOLEAN', 'default'=>true),
			'prt_supervision_firstname'=>array('type'=>'VARCHAR', 'constraint'=>'50'),
			'prt_supervision_lastname'=>array('type'=>'VARCHAR', 'constraint'=>'80'),
			'prt_supervision_cellphone'=>array('type'=>'VARCHAR', 'constraint'=>'50'),
			'prt_grp_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'prt_createtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
			'prt_modifytime'=>array('type'=>'DATETIME', 'null'=>false),
			'prt_create_stf_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'prt_modify_stf_id'=>array('type'=>'INTEGER', 'unsigned'=>true, 'null'=>true),
			'prt_call_status'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'prt_call_escalation'=>array('type'=>'SMALLINT', 'unsigned'=>true, 'null'=>true),
			'prt_call_start_time'=>array('type'=>'DATETIME', 'null'=>true),
			'prt_call_change_time'=>array('type'=>'DATETIME', 'null'=>true),
			'prt_notes'=>array('type'=>'TEXT'),
			'UNIQUE INDEX prt_name_index (prt_firstname, prt_lastname)',
			'INDEX prt_grp_id_index (prt_grp_id)',
			'INDEX prt_call_status_index (prt_call_status, prt_call_change_time)'
		);
		$this->create_table('bf_participants', $fields);

		$fields = array(
			'grp_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'grp_name'=>array('type'=>'VARCHAR', 'constraint'=>'100', 'unique'=>true),
			'grp_location'=>array('type'=>'VARCHAR', 'constraint'=>'100')
		);
		$this->create_table('bf_groups', $fields); // Kleingruppe

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
		$this->create_table('bf_history', $fields);

		$this->set_setting('database-version', DB_VERSION);
		
		// insert bf_staff (stf_username, stf_fullname, stf_password) values ('Admin', 'Administrator', '$2y$10$pU1PLFCA1BbQPEYPFusiK.WW7WvKLpoiT4QXeRRqwDUcjNigNDL.O');
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
