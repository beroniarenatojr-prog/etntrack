<?php

/*
| The extension PROJECT, the record every other document hangs off.
|
| A project is the whole piece of work: its assessment, MOA, capsule,
| proposal, designations and reports all belong to it, and it can hold one or
| more activities (the actual events that get a QR code and are evaluated).
|
| Existing activities keep working exactly as before. Each one is given its
| own project here so nothing is orphaned and the lifecycle view has
| something to show from day one.
*/

return function(Migrator $m){

	$m->create_table('projects', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		title VARCHAR(255) NOT NULL,
		description TEXT NULL,
		category VARCHAR(50) NOT NULL DEFAULT 'Extension',
		faculty_id INT NULL,
		location VARCHAR(255) NULL,
		target_beneficiaries TEXT NULL,
		start_date DATE NULL,
		end_date DATE NULL,
		academic_year VARCHAR(20) NULL,
		semester VARCHAR(20) NULL,
		lifecycle_status VARCHAR(30) NOT NULL DEFAULT 'pre_activity',
		implementation_date DATE NULL,
		created_by INT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		INDEX idx_faculty (faculty_id),
		INDEX idx_status (lifecycle_status),
		INDEX idx_year (academic_year)
	");

	// Activities belong to a project. Existing ones are matched up below.
	$m->add_column('activities', 'project_id', 'INT NULL DEFAULT NULL');
	$m->add_index('activities', 'idx_project', '`project_id`');

	/*
	| If an earlier attempt at this step stopped halfway it may have left
	| projects behind that nothing points at. Clear those out first, but only
	| while no activity is linked yet, so a real project is never touched.
	*/
	$linked = (int)$m->query_value("SELECT COUNT(*) FROM activities WHERE project_id IS NOT NULL");

	if($linked === 0){
		$removed = $m->run_count("
			DELETE FROM projects
			WHERE id NOT IN (SELECT DISTINCT project_id FROM activities WHERE project_id IS NOT NULL)
		");
		if($removed){
			$m->note("cleared $removed leftover project(s) from an earlier attempt");
		}
	}

	/*
	| Give every activity that has no project one of its own, named after it.
	| Done one at a time so each activity is linked to the project just made for
	| it. (Matching them up afterwards by title would compare text between two
	| tables, which fails when they were created with different collations.)
	*/
	$activities = $m->rows("
		SELECT id, activity_name, purpose, faculty_id, venue, activity_date,
			academic_year, semester, status, created_at
		FROM activities
		WHERE project_id IS NULL
	");

	foreach($activities as $row){

		$past = $row['activity_date'] !== null && $row['activity_date'] < date('Y-m-d');

		if($row['status'] === 'approved'){
			$status = $past ? 'conducted' : 'ready_for_conduct';
		}elseif($row['status'] === 'rejected'){
			$status = 'draft';
		}else{
			$status = 'for_approval';
		}

		$project_id = $m->insert('projects', array(
			'title' => $row['activity_name'],
			'description' => $row['purpose'],
			'faculty_id' => $row['faculty_id'],
			'location' => $row['venue'],
			'start_date' => $row['activity_date'],
			'end_date' => $row['activity_date'],
			'academic_year' => $row['academic_year'],
			'semester' => $row['semester'],
			'lifecycle_status' => $status,
			'implementation_date' => ($row['status'] === 'approved' && $past) ? $row['activity_date'] : null,
			'created_by' => $row['faculty_id'],
			'created_at' => $row['created_at']
		));

		$m->run("UPDATE activities SET project_id = $project_id WHERE id = ".(int)$row['id']);
	}

	$m->note(count($activities)." existing activit".(count($activities) === 1 ? "y" : "ies")." now ha"
		.(count($activities) === 1 ? "s" : "ve")." a project");
};
