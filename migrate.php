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

	// Creates a table only if it is missing
	public function create_table($table, $definition){
		if($this->table_exists($table)){
			$this->log[] = "table $table already there";
			return;
		}
		$this->run("CREATE TABLE `$table` ($definition) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		$this->log[] = "created table $table";
	}

	public function run($sql){
		if(!$this->db->query($sql)){
			throw new Exception($this->db->error);
		}
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
if(PHP_SAPI === 'cli' && isset($argv) && realpath($argv[0]) === realpath(__FILE__)){
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
