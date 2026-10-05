<?php

/*
| PROJECT-CENTERED RECORDS
|
| Activities and reports become records that belong to a project, and the
| last stage of a project, the Impact Assessment, gets a record of its own.
| Only additions: nothing is dropped, deleted or guessed.
|
|   activities         start and end time; who reviewed it and when
|                      (project_id is already there, from 003_projects)
|   uploaded_reports   the project it belongs to, a description, the period
|                      it reports on, and the review: remarks, by whom, when
|   projects           how many years after completion the impact is
|                      assessed (3 unless changed) and the completion date
|   impact_assessments the impact assessment itself, reviewed like the
|                      pre-activity documents (draft -> submitted -> approved,
|                      or sent back for revision, or rejected)
|
| Existing reports carry no clue to their project, so they are left
| unassigned; the admin links each one from the Reports page. Existing
| activities keep the project they already have.
*/

return function(Migrator $m){

	/* ---------------------------------------------------------------
	   ACTIVITIES
	--------------------------------------------------------------- */

	$m->add_column('activities', 'start_time', 'TIME NULL DEFAULT NULL');
	$m->add_column('activities', 'end_time', 'TIME NULL DEFAULT NULL');
	$m->add_column('activities', 'reviewed_by', 'INT NULL DEFAULT NULL');
	$m->add_column('activities', 'reviewed_at', 'DATETIME NULL DEFAULT NULL');
	$m->add_index('activities', 'idx_project', '`project_id`');

	/* ---------------------------------------------------------------
	   REPORTS
	--------------------------------------------------------------- */

	// A database set up without the Reports table gets it here (an existing one is left as it is)
	if(!$m->table_exists('uploaded_reports')){
		$m->create_table('uploaded_reports', "
			id INT AUTO_INCREMENT PRIMARY KEY,
			report_title VARCHAR(255) NOT NULL,
			report_type ENUM('Terminal Report','Progress Report') NOT NULL DEFAULT 'Terminal Report',
			file_name VARCHAR(255) NOT NULL,
			uploaded_by INT NOT NULL,
			uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			status VARCHAR(20) NOT NULL DEFAULT 'Pending',
			Category ENUM('Research','Extension','Training') NOT NULL DEFAULT 'Extension'
		");
	}

	// The report type was added by a hand-run file (database/add_report_type.sql); make sure it is there
	$m->add_column('uploaded_reports', 'report_type', "ENUM('Terminal Report','Progress Report') NOT NULL DEFAULT 'Terminal Report' AFTER `report_title`");

	$m->add_column('uploaded_reports', 'project_id', 'INT NULL DEFAULT NULL');
	$m->add_column('uploaded_reports', 'description', 'TEXT NULL');
	$m->add_column('uploaded_reports', 'period_start', 'DATE NULL DEFAULT NULL');
	$m->add_column('uploaded_reports', 'period_end', 'DATE NULL DEFAULT NULL');
	$m->add_column('uploaded_reports', 'admin_remarks', 'TEXT NULL');
	$m->add_column('uploaded_reports', 'reviewed_by', 'INT NULL DEFAULT NULL');
	$m->add_column('uploaded_reports', 'reviewed_at', 'DATETIME NULL DEFAULT NULL');
	$m->add_column('uploaded_reports', 'updated_at', 'DATETIME NULL DEFAULT NULL');
	$m->add_index('uploaded_reports', 'idx_project', '`project_id`');

	$orphans = (int)$m->query_value("SELECT COUNT(*) FROM uploaded_reports WHERE project_id IS NULL");
	if($orphans > 0){
		$m->note("$orphans existing report(s) are not linked to a project yet; link them from the Reports page");
	}

	/* ---------------------------------------------------------------
	   PROJECTS
	--------------------------------------------------------------- */

	$m->add_column('projects', 'impact_years', 'INT NOT NULL DEFAULT 3');
	$m->add_column('projects', 'completed_at', 'DATE NULL DEFAULT NULL');

	/* ---------------------------------------------------------------
	   IMPACT ASSESSMENT
	--------------------------------------------------------------- */

	$m->create_table('impact_assessments', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		title VARCHAR(255) NOT NULL,
		period_start DATE NULL,
		period_end DATE NULL,
		assessment_date DATE NULL,
		findings TEXT NULL,
		outcomes TEXT NULL,
		beneficiary_impact TEXT NULL,
		sustainability TEXT NULL,
		recommendations TEXT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'draft',
		admin_remarks TEXT NULL,
		file_name VARCHAR(255) NULL,
		reviewed_by INT NULL,
		reviewed_at DATETIME NULL,
		created_by INT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		INDEX idx_project (project_id),
		INDEX idx_status (status)
	");
};
