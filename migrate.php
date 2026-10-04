<?php

/*
|--------------------------------------------------------------------------
| DATABASE UPDATES
|--------------------------------------------------------------------------
| Each file in database/migrations/ is one update step. A step runs once:
| after it finishes, its name is written to the schema_migrations table and
| it is skipped from then on.
|
| Steps only add tables, columns and data. Nothing is deleted or emptied,
| so running an update on a live database is safe.
|
| The admin page "System Update" (index.php?page=system_update) runs the
| pending steps. From the command line:  php migrate.php
*/

class Migrator {

	private $db;
	public $log = array();

	public function __construct($db){
		$this->db = $db;
		$this->db->query("
			CREATE TABLE IF NOT EXISTS schema_migrations (
				id INT AUTO_INCREMENT PRIMARY KEY,
				filename VARCHAR(255) NOT NULL UNIQUE,
				applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
		");
	}

	/* ---------- helpers the step files use ---------- */

	public function table_exists($table){
		return $this->db->query("
			SELECT 1 FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$this->db->real_escape_string($table)."'
		")->num_rows > 0;
	}

	public function column_exists($table, $column){
		return $this->db->query("
			SELECT 1 FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = '".$this->db->real_escape_string($table)."'
			AND COLUMN_NAME = '".$this->db->real_escape_string($column)."'
		")->num_rows > 0;
	}

	public function index_exists($table, $index){
		return $this->db->query("
			SELECT 1 FROM information_schema.STATISTICS
			WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = '".$this->db->real_escape_string($table)."'
			AND INDEX_NAME = '".$this->db->real_escape_string($index)."'
		")->num_rows > 0;
	}

	// Adds a column only if it is missing
	public function add_column($table, $column, $definition){
		if(!$this->table_exists($table)){
			$this->log[] = "skipped $table.$column (no such table)";
			return;
		}
		if($this->column_exists($table, $column)){
			$this->log[] = "$table.$column already there";
			return;
		}
		$this->run("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
		$this->log[] = "added column $table.$column";
	}

	// Adds an index only if it is missing
	public function add_index($table, $index, $columns){
		if(!$this->table_exists($table) || $this->index_exists($table, $index)){
			return;
		}
		$this->run("ALTER TABLE `$table` ADD INDEX `$index` ($columns)");
		$this->log[] = "added index $table.$index";
	}

	/*
	| The text collation the existing tables already use. New tables are made to
	| match, otherwise comparing text between an old table and a new one fails
	| with "Illegal mix of collations". Newer MariaDB picks a different default
	| for new tables than older versions did, so this cannot be assumed.
	*/
	public function base_collation(){

		if($this->collation !== null){
			return $this->collation;
		}

		foreach(array('activities', 'users', 'faculty_list') as $table){

			$row = $this->db->query("
				SELECT TABLE_COLLATION FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
			")->fetch_assoc();

			if($row && !empty($row['TABLE_COLLATION'])){
				return $this->collation = $row['TABLE_COLLATION'];
			}
		}

		return $this->collation = 'utf8mb4_general_ci';
	}

	private $collation = null;

	private function charset_of($collation){
		return substr($collation, 0, strpos($collation, '_'));
	}

	// Brings a table that was made with the wrong collation into line
	public function match_collation($table){

		if(!$this->table_exists($table)){
			return;
		}

		$want = $this->base_collation();

		$row = $this->db->query("
			SELECT TABLE_COLLATION FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$this->db->real_escape_string($table)."'
		")->fetch_assoc();

		if(!$row || $row['TABLE_COLLATION'] === $want){
			return;
		}

		$this->run("ALTER TABLE `$table` CONVERT TO CHARACTER SET ".$this->charset_of($want)." COLLATE $want");
		$this->log[] = "put $table on the same collation as the rest ($want)";
	}

	// Creates a table only if it is missing
	public function create_table($table, $definition){

		if($this->table_exists($table)){
			$this->log[] = "table $table already there";
			$this->match_collation($table);
			return;
		}

		$collation = $this->base_collation();

		$this->run("CREATE TABLE `$table` ($definition) ENGINE=InnoDB DEFAULT CHARSET=".$this->charset_of($collation)." COLLATE=$collation");
		$this->log[] = "created table $table";
	}

	public function run($sql){
		if(!$this->db->query($sql)){
			throw new Exception($this->db->error);
		}
	}

	// Runs a statement and says how many rows it changed
	public function run_count($sql){
		$this->run($sql);
		return $this->db->affected_rows;
	}

	// The first column of the first row, e.g. a COUNT(*)
	public function query_value($sql){
		$result = $this->db->query($sql);
		if(!$result){
			throw new Exception($this->db->error);
		}
		$row = $result->fetch_row();
		return $row ? $row[0] : null;
	}

	// Every row a query returns, as an array
	public function rows($sql){
		$result = $this->db->query($sql);
		if(!$result){
			throw new Exception($this->db->error);
		}
		$rows = array();
		while($row = $result->fetch_assoc()){
			$rows[] = $row;
		}
		return $rows;
	}

	// Inserts one row from a column => value list and returns its new id.
	// A null value is stored as NULL rather than an empty string.
	public function insert($table, $values){

		$columns = array();
		$parts = array();

		foreach($values as $column => $value){
			$columns[] = "`$column`";
			$parts[] = $value === null ? 'NULL' : "'".$this->db->real_escape_string((string)$value)."'";
		}

		$this->run("INSERT INTO `$table` (".implode(', ', $columns).") VALUES (".implode(', ', $parts).")");

		return (int)$this->db->insert_id;
	}

	public function note($message){
		$this->log[] = $message;
	}

	/* ---------- running the steps ---------- */

	public function pending(){
		$done = array();
		$qry = $this->db->query("SELECT filename FROM schema_migrations");
		while($row = $qry->fetch_assoc()){
			$done[$row['filename']] = true;
		}

		$files = glob(__DIR__.'/database/migrations/*.php');
		sort($files);

		$pending = array();
		foreach($files as $file){
			if(!isset($done[basename($file)])){
				$pending[] = $file;
			}
		}
		return $pending;
	}

	// Runs every step that has not run yet. Returns array(applied, errors).
	public function migrate(){
		$applied = array();
		$errors = array();

		foreach($this->pending() as $file){
			$name = basename($file);
			$before = count($this->log);
			try {
				$step = require $file;
				$step($this);
				$stmt = $this->db->prepare("INSERT INTO schema_migrations (filename) VALUES (?)");
				$stmt->bind_param('s', $name);
				$stmt->execute();
				$applied[] = array('file' => $name, 'details' => array_slice($this->log, $before));
			} catch (Throwable $e) {
				$errors[] = array('file' => $name, 'message' => $e->getMessage());
				break;   // stop at the first problem; later steps may depend on this one
			}
		}

		return array($applied, $errors);
	}
}

// Command line use: php migrate.php
//
// Anything that includes this file defines MIGRATOR_INCLUDED first, so the
// block below runs only when the file itself was started from a terminal.
// (Testing PHP_SAPI for 'cli' is not reliable: some hosts' php command reports
// itself as cgi-fcgi, and the script would then do nothing and say nothing.)
if(!defined('MIGRATOR_INCLUDED')){
	require __DIR__.'/db_connect.php';
	$migrator = new Migrator($conn);
	list($applied, $errors) = $migrator->migrate();

	if(!$applied && !$errors){
		echo "Database is already up to date.\n";
	}
	foreach($applied as $step){
		echo "applied {$step['file']}\n";
		foreach($step['details'] as $line){
			echo "   $line\n";
		}
	}
	foreach($errors as $error){
		echo "FAILED {$error['file']}: {$error['message']}\n";
	}
	exit($errors ? 1 : 0);
}
