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

	// Give every activity that has no project one of its own, named after it
	$m->run("
		INSERT INTO projects (title, description, faculty_id, location, start_date, end_date,
			academic_year, semester, lifecycle_status, implementation_date, created_by, created_at)
		SELECT a.activity_name, a.purpose, a.faculty_id, a.venue, a.activity_date, a.activity_date,
			a.academic_year, a.semester,
			CASE WHEN a.status = 'approved' AND a.activity_date < CURDATE() THEN 'conducted'
			     WHEN a.status = 'approved' THEN 'ready_for_conduct'
			     WHEN a.status = 'rejected' THEN 'draft'
			     ELSE 'for_approval' END,
			CASE WHEN a.status = 'approved' AND a.activity_date < CURDATE() THEN a.activity_date ELSE NULL END,
			a.faculty_id, a.created_at
		FROM activities a
		WHERE a.project_id IS NULL
	");

	// Point each activity at the project just made for it
	$m->run("
		UPDATE activities a
		INNER JOIN projects p
			ON p.title = a.activity_name
			AND p.start_date <=> a.activity_date
			AND p.faculty_id <=> a.faculty_id
		SET a.project_id = p.id
		WHERE a.project_id IS NULL
	");

	$m->note("every existing activity now has its own project");
};
