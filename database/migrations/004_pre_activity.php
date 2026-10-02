<?php

/*
| PRE-ACTIVITY DOCUMENTS
|
| Six kinds of record, each belonging to one project:
|   assessment_reports      the needs of the community, established first
|   memorandum_agreements   the agreement with the partner
|   capsules                the short summary of the proposed project
|   proposals               the full proposal
|   designations            who is assigned to the project, and as what
|   conduct_preparations    the final arrangements before the activity runs
|
| They share the same review workflow, in the `status` column:
|   draft -> submitted -> under_review -> approved
|                                      -> revision (sent back) -> submitted
|                                      -> rejected
| A designation is an assignment rather than a document, so it only uses
| active / ended.
*/

return function(Migrator $m){

	// Columns every reviewed document has
	$common = "
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
	";

	$m->create_table('assessment_reports', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		title VARCHAR(255) NOT NULL,
		community VARCHAR(255) NULL,
		location VARCHAR(255) NULL,
		assessment_date DATE NULL,
		assessors TEXT NULL,
		identified_needs TEXT NULL,
		findings TEXT NULL,
		recommendations TEXT NULL,
		target_beneficiaries TEXT NULL,
		$common
	");

	$m->create_table('memorandum_agreements', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		title VARCHAR(255) NOT NULL,
		partner VARCHAR(255) NULL,
		description TEXT NULL,
		start_date DATE NULL,
		end_date DATE NULL,
		school_responsibilities TEXT NULL,
		partner_responsibilities TEXT NULL,
		signatories TEXT NULL,
		moa_date DATE NULL,
		$common
	");

	$m->create_table('capsules', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		title VARCHAR(255) NOT NULL,
		rationale TEXT NULL,
		objectives TEXT NULL,
		target_beneficiaries TEXT NULL,
		location VARCHAR(255) NULL,
		major_activities TEXT NULL,
		expected_outputs TEXT NULL,
		expected_outcomes TEXT NULL,
		duration VARCHAR(100) NULL,
		prepared_by VARCHAR(255) NULL,
		date_prepared DATE NULL,
		$common
	");

	$m->create_table('proposals', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		title VARCHAR(255) NOT NULL,
		background TEXT NULL,
		problem_statement TEXT NULL,
		objectives TEXT NULL,
		target_beneficiaries TEXT NULL,
		location VARCHAR(255) NULL,
		methodology TEXT NULL,
		activities TEXT NULL,
		timeline TEXT NULL,
		expected_outputs TEXT NULL,
		expected_outcomes TEXT NULL,
		monitoring_plan TEXT NULL,
		budget DECIMAL(12,2) NULL,
		funding_source VARCHAR(255) NULL,
		sustainability_plan TEXT NULL,
		prepared_by VARCHAR(255) NULL,
		$common
	");

	// One row per assigned person; a project can have many
	$m->create_table('designations', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		personnel_name VARCHAR(255) NOT NULL,
		faculty_id INT NULL,
		role VARCHAR(150) NOT NULL,
		responsibility TEXT NULL,
		start_date DATE NULL,
		end_date DATE NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'active',
		file_name VARCHAR(255) NULL,
		created_by INT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		INDEX idx_project (project_id),
		INDEX idx_faculty (faculty_id)
	");

	// One row per project: the final arrangements before it runs
	$m->create_table('conduct_preparations', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		project_id INT NOT NULL,
		final_date DATE NULL,
		start_time TIME NULL,
		end_time TIME NULL,
		venue VARCHAR(255) NULL,
		expected_participants INT NULL,
		target_beneficiaries TEXT NULL,
		materials TEXT NULL,
		equipment TEXT NULL,
		logistics TEXT NULL,
		assigned_personnel TEXT NULL,
		notes TEXT NULL,
		venue_confirmed TINYINT(1) NOT NULL DEFAULT 0,
		personnel_assigned TINYINT(1) NOT NULL DEFAULT 0,
		materials_prepared TINYINT(1) NOT NULL DEFAULT 0,
		$common
	");

	$m->note("six pre-activity record types created");
};
