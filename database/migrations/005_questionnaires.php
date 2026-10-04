<?php

/*
| QUESTIONNAIRES
|
| Until now a "questionnaire" was simply the questions saved against an
| academic year. This gives questionnaires a record of their own, with
| sections, question types, answer options, a lifecycle status and proper
| response records, so one can be built, previewed, published, closed and
| archived.
|
| Existing data is carried across, never deleted:
|   - each academic year's questions become one questionnaire, ACTIVE, so the
|     QR evaluation form keeps working exactly as before
|   - the criteria those questions sit under become the questionnaire's sections
|   - every existing answer is linked to its questionnaire, and each submission
|     gets a response record numbered the same as before
|   - the questions are also copied into the criteria library, so the same
|     form can be rebuilt quickly for another semester
*/

return function(Migrator $m){

	/* ---------------------------------------------------------------
	   NEW TABLES
	--------------------------------------------------------------- */

	$m->create_table('questionnaires', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		title VARCHAR(255) NOT NULL,
		description TEXT NULL,
		instructions TEXT NULL,
		evaluation_type VARCHAR(30) NOT NULL DEFAULT 'project',
		target_respondent VARCHAR(30) NOT NULL DEFAULT 'community',
		project_id INT NULL,
		academic_id INT NULL,
		academic_year VARCHAR(20) NULL,
		semester VARCHAR(20) NULL,
		start_date DATE NULL,
		end_date DATE NULL,
		status VARCHAR(12) NOT NULL DEFAULT 'draft',
		likert_scale TEXT NULL,
		created_by INT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		ready_at DATETIME NULL,
		ready_by INT NULL,
		published_at DATETIME NULL,
		published_by INT NULL,
		closed_at DATETIME NULL,
		closed_by INT NULL,
		archived_at DATETIME NULL,
		archived_by INT NULL,
		INDEX idx_status (status),
		INDEX idx_project (project_id),
		INDEX idx_year (academic_year),
		INDEX idx_academic (academic_id)
	");

	$m->create_table('questionnaire_sections', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		questionnaire_id INT NOT NULL,
		criteria_id INT NULL,
		title VARCHAR(255) NOT NULL,
		description TEXT NULL,
		order_by INT NOT NULL DEFAULT 0,
		is_active TINYINT(1) NOT NULL DEFAULT 1,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		INDEX idx_questionnaire (questionnaire_id),
		INDEX idx_criteria (criteria_id)
	");

	// Answer choices for single choice, multiple choice and yes / no questions
	$m->create_table('question_options', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		question_id INT NOT NULL,
		label VARCHAR(255) NOT NULL,
		value DECIMAL(6,2) NULL,
		order_by INT NOT NULL DEFAULT 0,
		INDEX idx_question (question_id)
	");

	// Template questions kept with each criterion, for building new questionnaires
	$m->create_table('criteria_questions', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		criteria_id INT NOT NULL,
		question TEXT NOT NULL,
		question_type VARCHAR(20) NOT NULL DEFAULT 'likert',
		order_by INT NOT NULL DEFAULT 0,
		INDEX idx_criteria (criteria_id)
	");

	// One row per submitted evaluation; its answers are in evaluation_answers
	$m->create_table('evaluation_responses', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		questionnaire_id INT NULL,
		activity_id INT NULL,
		project_id INT NULL,
		respondent_name VARCHAR(150) NULL,
		respondent_type VARCHAR(50) NULL,
		barangay VARCHAR(150) NULL,
		device_token CHAR(64) NULL,
		submitted_at DATETIME NULL,
		INDEX idx_questionnaire (questionnaire_id),
		INDEX idx_activity (activity_id),
		INDEX idx_device (questionnaire_id, activity_id, device_token)
	");

	// Who did what, and when
	$m->create_table('audit_log', "
		id INT AUTO_INCREMENT PRIMARY KEY,
		user_id INT NULL,
		user_type TINYINT NULL,
		action VARCHAR(60) NOT NULL,
		entity VARCHAR(40) NOT NULL,
		entity_id INT NULL,
		details TEXT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_entity (entity, entity_id),
		INDEX idx_created (created_at)
	");

	/* ---------------------------------------------------------------
	   NEW COLUMNS ON EXISTING TABLES (only ever added, never removed)
	--------------------------------------------------------------- */

	$m->add_column('question_list', 'questionnaire_id', 'INT NULL DEFAULT NULL');
	$m->add_column('question_list', 'section_id', 'INT NULL DEFAULT NULL');
	$m->add_column('question_list', 'question_type', "VARCHAR(20) NOT NULL DEFAULT 'likert'");
	$m->add_column('question_list', 'is_required', 'TINYINT(1) NOT NULL DEFAULT 1');
	$m->add_column('question_list', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
	$m->add_column('question_list', 'settings', 'TEXT NULL');
	$m->add_index('question_list', 'idx_questionnaire', '`questionnaire_id`');
	$m->add_index('question_list', 'idx_section', '`section_id`');

	// A question in a section that did not come from a criterion has neither
	$m->make_nullable('question_list', 'academic_id', 'INT(30)');
	$m->make_nullable('question_list', 'criteria_id', 'INT(30)');

	$m->add_column('criteria_list', 'description', 'TEXT NULL');
	$m->add_column('criteria_list', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');

	$m->add_column('evaluation_answers', 'questionnaire_id', 'INT NULL DEFAULT NULL');
	$m->add_column('evaluation_answers', 'option_id', 'INT NULL DEFAULT NULL');
	$m->add_column('evaluation_answers', 'answer_text', 'TEXT NULL');
	$m->add_index('evaluation_answers', 'idx_questionnaire', '`questionnaire_id`');
	$m->add_index('evaluation_answers', 'idx_evaluation', '`evaluation_id`');

	// A written answer has no rating
	$m->make_nullable('evaluation_answers', 'rate', 'INT(20)');

	/* ---------------------------------------------------------------
	   CARRY THE EXISTING QUESTIONNAIRE ACROSS
	--------------------------------------------------------------- */

	// The answer scale printed on the ISU Extension evaluation form
	$official_scale = json_encode(array(
		array('value' => 5, 'label' => 'Lubos na Sumasang-ayon (Strongly Agree)'),
		array('value' => 4, 'label' => 'Sumasang-ayon (Agree)'),
		array('value' => 3, 'label' => 'Bahagyang Sumasang-ayon (Slightly Agree)'),
		array('value' => 2, 'label' => 'Hindi Sumasang-ayon (Disagree)'),
		array('value' => 1, 'label' => 'Lubos na Hindi Sumasang-ayon (Strongly Disagree)')
	), JSON_UNESCAPED_UNICODE);

	$official_instructions = "Sagutin kung gaano kayo sumasang-ayon o hindi sumasang-ayon sa mga sumusunod na pahayag. "
		."(Indicate the extent to which you agree or disagree with the following statements.)";

	$semesters = array(1 => '1st Semester', 2 => '2nd Semester', 3 => 'Midyear');

	// Each academic year that has questions not yet in a questionnaire
	$years = $m->rows("
		SELECT q.academic_id, a.year, a.semester, COUNT(*) AS questions
		FROM question_list q
		LEFT JOIN academic_list a ON a.id = q.academic_id
		WHERE q.questionnaire_id IS NULL
		GROUP BY q.academic_id, a.year, a.semester
		ORDER BY q.academic_id
	");

	foreach($years as $year){

		$academic_id = $year['academic_id'] !== null ? (int)$year['academic_id'] : null;

		$questionnaire_id = $m->insert('questionnaires', array(
			'title' => 'Extension Project Evaluation',
			'description' => 'Evaluates the relevance and usefulness, quality of delivery, benefits and impact of the '
				.'extension project, and the overall satisfaction of its beneficiaries.',
			'instructions' => $official_instructions,
			'evaluation_type' => 'project',
			'target_respondent' => 'community',
			'academic_id' => $academic_id,
			'academic_year' => $year['year'],
			'semester' => $semesters[(int)$year['semester']] ?? null,
			// Already in use on the QR evaluation form, so it stays open
			'status' => 'active',
			'likert_scale' => $official_scale,
			'published_at' => date('Y-m-d H:i:s')
		));

		$where = $academic_id === null ? "academic_id IS NULL" : "academic_id = $academic_id";

		// One section per criterion its questions use, in the criteria's order
		$criteria = $m->rows("
			SELECT DISTINCT q.criteria_id, c.criteria, c.order_by
			FROM question_list q
			LEFT JOIN criteria_list c ON c.id = q.criteria_id
			WHERE q.$where AND q.questionnaire_id IS NULL
			ORDER BY c.order_by IS NULL, c.order_by, q.criteria_id
		");

		$position = 0;

		foreach($criteria as $criterion){

			$criteria_id = $criterion['criteria_id'] !== null ? (int)$criterion['criteria_id'] : null;

			$section_id = $m->insert('questionnaire_sections', array(
				'questionnaire_id' => $questionnaire_id,
				'criteria_id' => $criteria_id,
				'title' => $criterion['criteria'] !== null ? $criterion['criteria'] : 'General',
				'order_by' => $position++
			));

			$in_section = $criteria_id === null ? "criteria_id IS NULL" : "criteria_id = $criteria_id";

			$m->run("
				UPDATE question_list
				SET questionnaire_id = $questionnaire_id,
					section_id = $section_id,
					question_type = 'likert',
					is_required = 1,
					is_active = 1
				WHERE $where AND $in_section AND questionnaire_id IS NULL
			");
		}

		$m->note("{$year['questions']} question(s) of ".($year['year'] ?: 'an unknown year')
			." became an ACTIVE questionnaire with ".count($criteria)." section(s)");
	}

	/* ---------------------------------------------------------------
	   EXISTING ANSWERS AND SUBMISSIONS
	--------------------------------------------------------------- */

	// Each answer belongs to the questionnaire its question is in (whole numbers only, so no text comparison)
	$m->run("
		UPDATE evaluation_answers ea
		INNER JOIN question_list q ON q.id = ea.question_id
		SET ea.questionnaire_id = q.questionnaire_id
		WHERE ea.questionnaire_id IS NULL
	");

	// One response record per earlier submission, keeping the same number
	$submissions = $m->rows("
		SELECT ea.evaluation_id,
			MIN(ea.questionnaire_id) AS questionnaire_id,
			MIN(ea.activity_id) AS activity_id,
			MAX(ea.Evaluator_name) AS respondent_name,
			MIN(ea.submitted_at) AS submitted_at
		FROM evaluation_answers ea
		LEFT JOIN evaluation_responses r ON r.id = ea.evaluation_id
		WHERE r.id IS NULL
		GROUP BY ea.evaluation_id
	");

	foreach($submissions as $row){

		$activity_id = $row['activity_id'] !== null ? (int)$row['activity_id'] : null;
		$project_id = null;

		if($activity_id){
			$project_id = $m->query_value("SELECT project_id FROM activities WHERE id = $activity_id");
		}

		$m->insert('evaluation_responses', array(
			'id' => (int)$row['evaluation_id'],
			'questionnaire_id' => $row['questionnaire_id'],
			'activity_id' => $activity_id,
			'project_id' => $project_id,
			'respondent_name' => $row['respondent_name'],
			'submitted_at' => $row['submitted_at']
		));
	}

	if($submissions){
		$m->note(count($submissions)." earlier submission(s) now have a response record");
	}

	/* ---------------------------------------------------------------
	   CRITERIA LIBRARY
	   The questions already written under each criterion, kept as
	   templates for the next questionnaire.
	--------------------------------------------------------------- */

	$seeded = 0;

	foreach($m->rows("SELECT id FROM criteria_list") as $criterion){

		$criteria_id = (int)$criterion['id'];

		if((int)$m->query_value("SELECT COUNT(*) FROM criteria_questions WHERE criteria_id = $criteria_id") > 0){
			continue;
		}

		$questions = $m->rows("
			SELECT question FROM question_list
			WHERE criteria_id = $criteria_id
			ORDER BY order_by, id
		");

		$position = 0;

		foreach($questions as $question){
			$m->insert('criteria_questions', array(
				'criteria_id' => $criteria_id,
				'question' => $question['question'],
				'question_type' => 'likert',
				'order_by' => $position++
			));
			$seeded++;
		}
	}

	if($seeded){
		$m->note("$seeded question(s) added to the criteria library");
	}
};
