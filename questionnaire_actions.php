<?php

/*
|--------------------------------------------------------------------------
| QUESTIONNAIRES (admin)
|--------------------------------------------------------------------------
| Building, checking and running questionnaires. Used by the Action class in
| admin_class.php (see `use QuestionnaireActions`). Every action here is for
| administrators only (ajax.php), and every action that changes something also
| needs the page's security token.
|
| Lifecycle:  draft -> ready -> active -> closed -> archived
|             (ready can go back to draft while it is still being edited)
|
| Only a draft or ready questionnaire can be changed. Once it is active its
| questions are locked, so every response answers the same questions.
|
| Each action answers with JSON: {"ok": true, ...} or {"ok": false, "error": "..."}.
*/

require_once __DIR__.'/questionnaire_lib.php';

trait QuestionnaireActions {

	/* =====================================================================
	   VOCABULARY
	===================================================================== */

	// The names live in questionnaire_lib.php so pages and actions share them
	private function qn_types(){ return questionnaire_types(); }
	private function qn_evaluation_types(){ return questionnaire_evaluation_types(); }
	private function qn_respondents(){ return questionnaire_respondents(); }
	private function qn_semesters(){ return questionnaire_semesters(); }
	private function qn_status_labels(){ return questionnaire_status_labels(); }

	// Where each status may go next
	private function qn_transitions(){
		return array(
			'draft' => array('ready'),
			'ready' => array('draft', 'active'),
			'active' => array('closed'),
			'closed' => array('archived'),
			'archived' => array()
		);
	}

	/* =====================================================================
	   SMALL HELPERS
	===================================================================== */

	private function qn_ok($extra = array()){
		return json_encode(array_merge(array('ok' => true), $extra), JSON_UNESCAPED_UNICODE);
	}

	private function qn_fail($message, $extra = array()){
		return json_encode(array_merge(array('ok' => false, 'error' => $message), $extra), JSON_UNESCAPED_UNICODE);
	}

	private function qn_find($id){
		$id = (int)$id;
		if($id <= 0){
			return null;
		}
		return $this->db->query("SELECT * FROM questionnaires WHERE id = $id")->fetch_assoc() ?: null;
	}

	private function qn_editable($questionnaire){
		return in_array($questionnaire['status'], array('draft', 'ready'), true);
	}

	// Why a questionnaire cannot be changed
	private function qn_locked_message($questionnaire){
		switch($questionnaire['status']){
			case 'active':
				return "This questionnaire is already active and cannot be edited.";
			case 'closed':
				return "This questionnaire is closed. Its questions can no longer be changed.";
			case 'archived':
				return "This questionnaire is archived and read-only.";
		}
		return "This questionnaire cannot be edited.";
	}

	/*
	| Inserts or updates one row with a prepared statement.
	| $values is column => value; null is stored as NULL. Returns the row id.
	*/
	private function qn_write($table, $values, $id = null){

		$columns = array_keys($values);
		$types = '';
		$params = array();

		foreach($values as $value){
			$types .= is_int($value) ? 'i' : 's';
			$params[] = $value;
		}

		if($id === null){
			$sql = "INSERT INTO `$table` (`".implode('`, `', $columns)."`) VALUES ("
				.implode(', ', array_fill(0, count($columns), '?')).")";
		}else{
			$sql = "UPDATE `$table` SET ".implode(', ', array_map(function($column){ return "`$column` = ?"; }, $columns))
				." WHERE id = ?";
			$types .= 'i';
			$params[] = (int)$id;
		}

		$stmt = $this->db->prepare($sql);
		$stmt->bind_param($types, ...$params);
		$stmt->execute();

		return $id === null ? (int)$this->db->insert_id : (int)$id;
	}

	private function qn_count_responses($questionnaire_id){
		return (int)$this->db->query("
			SELECT COUNT(*) AS c FROM evaluation_responses WHERE questionnaire_id = ".(int)$questionnaire_id
		)->fetch_assoc()['c'];
	}

	// Checks a section belongs to the questionnaire; returns the section row
	private function qn_section_of($questionnaire_id, $section_id){
		return $this->db->query("
			SELECT * FROM questionnaire_sections
			WHERE id = ".(int)$section_id." AND questionnaire_id = ".(int)$questionnaire_id
		)->fetch_assoc() ?: null;
	}

	// Checks a question belongs to the questionnaire; returns the question row
	private function qn_question_of($questionnaire_id, $question_id){
		return $this->db->query("
			SELECT * FROM question_list
			WHERE id = ".(int)$question_id." AND questionnaire_id = ".(int)$questionnaire_id
		)->fetch_assoc() ?: null;
	}

	/* =====================================================================
	   READING A QUESTIONNAIRE
	===================================================================== */

	// Sections, questions and options in order (questionnaire_lib.php)
	private function qn_structure($questionnaire_id){
		return questionnaire_structure($this->db, $questionnaire_id);
	}

	/*
	| The Review & Publish checklist. Each check says what it looked at,
	| whether it passed, and what to fix when it did not. A 'warning' never
	| blocks publishing; an 'error' does.
	*/
	private function qn_checks($questionnaire, $structure){

		$checks = array();

		$add = function($key, $label, $ok, $message, $severity = 'error') use (&$checks){
			$checks[] = array(
				'key' => $key,
				'label' => $label,
				'ok' => (bool)$ok,
				'message' => $ok ? '' : $message,
				'severity' => $severity
			);
		};

		$add('year', 'Academic year selected',
			preg_match('/^\d{4}-\d{4}$/', (string)$questionnaire['academic_year']) === 1,
			'Choose the academic year.');

		$add('semester', 'Semester selected',
			in_array($questionnaire['semester'], $this->qn_semesters(), true),
			'Choose the semester.');

		$add('title', 'Questionnaire title provided',
			trim((string)$questionnaire['title']) !== '',
			'Questionnaire title is required.');

		$add('description', 'Description provided',
			trim((string)$questionnaire['description']) !== '',
			'Add a short description of what this questionnaire evaluates.');

		// Only active sections and active questions reach respondents
		$sections = array_values(array_filter($structure['sections'], function($s){ return $s['is_active'] === 1; }));

		$add('sections', 'At least one section exists', count($sections) > 0, 'Add at least one section.');

		$questions = array();
		$empty_sections = array();
		$section_number = 0;
		$question_number = 0;
		$bad_types = array();
		$missing_options = array();
		$bad_ranges = array();
		$required = 0;
		$types = $this->qn_types();

		foreach($sections as $section){

			$section_number++;
			$active = array_values(array_filter($section['questions'], function($q){ return $q['is_active'] === 1; }));

			if(!$active){
				$empty_sections[] = "Section $section_number (".$section['title'].") has no questions.";
			}

			foreach($active as $question){

				$question_number++;
				$questions[] = $question;

				if(!isset($types[$question['question_type']])){
					$bad_types[] = "Question $question_number has an unknown type.";
				}

				if(in_array($question['question_type'], array('single_choice', 'multiple_choice'), true)
					&& count($question['options']) < 2){
					$missing_options[] = "Question $question_number needs at least two options.";
				}

				if($question['question_type'] === 'rating'){
					$settings = (array)$question['settings'];
					$min = isset($settings['min']) ? (int)$settings['min'] : 1;
					$max = isset($settings['max']) ? (int)$settings['max'] : 5;
					if($min >= $max){
						$bad_ranges[] = "Question $question_number needs a rating range from a lower to a higher number.";
					}
				}

				if($question['is_required'] === 1){
					$required++;
				}
			}
		}

		$add('questions', 'At least one question exists', count($questions) > 0, 'At least one question is required.');

		$add('section_questions', 'Every section has questions', !$empty_sections,
			'Please add questions to all sections. '.implode(' ', $empty_sections));

		$add('types', 'Questions have valid types', !$bad_types, implode(' ', $bad_types));

		$add('options', 'Choice questions have their options', !$missing_options, implode(' ', $missing_options));

		$add('ratings', 'Rating questions have a valid range', !$bad_ranges, implode(' ', $bad_ranges));

		$add('required', 'Required questions are configured correctly', count($questions) === 0 || $required > 0,
			'Mark at least one question as required, so an empty evaluation cannot be submitted.');

		$add('order', 'Question order is valid', !$structure['unsectioned'],
			count($structure['unsectioned'])." question(s) are not in any section.");

		$scale = questionnaire_scale($questionnaire);
		$labelled = count(array_filter($scale, function($step){ return trim((string)$step['label']) !== ''; }));
		$uses_likert = count(array_filter($questions, function($q){ return $q['question_type'] === 'likert'; })) > 0;

		$add('scale', 'Likert scale is set up', !$uses_likert || ($labelled >= 2 && $labelled === count($scale)),
			'Every step of the Likert scale needs a label, and the scale needs at least two steps.');

		$start = $questionnaire['start_date'];
		$end = $questionnaire['end_date'];

		$add('dates', 'Evaluation period is valid', !($start && $end && $start > $end),
			'The end date must be on or after the start date.');

		// Another questionnaire already running for the same people
		$other = $this->qn_competitor($questionnaire);

		if($other){
			$add('overlap', 'No other active questionnaire covers the same evaluations', false,
				'"'.$other['title'].'" is already active for the same '
				.($questionnaire['project_id'] ? 'project' : 'academic year and semester')
				.'. Publishing this one means new evaluations will use it instead.', 'warning');
		}

		return $checks;
	}

	// An ACTIVE questionnaire (other than this one) that would serve the same evaluations
	private function qn_competitor($questionnaire){

		$id = (int)$questionnaire['id'];

		if(!empty($questionnaire['project_id'])){
			return $this->db->query("
				SELECT id, title FROM questionnaires
				WHERE status = 'active' AND id <> $id AND project_id = ".(int)$questionnaire['project_id']."
				LIMIT 1
			")->fetch_assoc() ?: null;
		}

		$year = $this->db->real_escape_string((string)$questionnaire['academic_year']);
		$semester = $this->db->real_escape_string((string)$questionnaire['semester']);

		return $this->db->query("
			SELECT id, title FROM questionnaires
			WHERE status = 'active' AND id <> $id AND project_id IS NULL
			AND academic_year = '$year' AND semester = '$semester'
			LIMIT 1
		")->fetch_assoc() ?: null;
	}

	private function qn_passes($checks){
		foreach($checks as $check){
			if(!$check['ok'] && $check['severity'] === 'error'){
				return false;
			}
		}
		return true;
	}

	// Everything the builder and the preview need about one questionnaire
	function qn_get(){

		$questionnaire = $this->qn_find($_GET['id'] ?? 0);

		if(!$questionnaire){
			return $this->qn_fail('Questionnaire not found.');
		}

		$id = (int)$questionnaire['id'];
		$structure = $this->qn_structure($id);
		$checks = $this->qn_checks($questionnaire, $structure);

		$questions = 0;
		foreach($structure['sections'] as $section){
			$questions += count($section['questions']);
		}

		$project = null;
		if(!empty($questionnaire['project_id'])){
			$project = $this->db->query("SELECT id, title FROM projects WHERE id = ".(int)$questionnaire['project_id'])->fetch_assoc();
		}

		$criteria = array();
		$result = $this->db->query("
			SELECT c.id, c.criteria, c.description, c.is_active,
				(SELECT COUNT(*) FROM criteria_questions t WHERE t.criteria_id = c.id) AS templates,
				(SELECT COUNT(*) FROM questionnaire_sections s WHERE s.criteria_id = c.id AND s.questionnaire_id = $id) AS used
			FROM criteria_list c
			ORDER BY c.order_by, c.id
		");
		while($row = $result->fetch_assoc()){
			$criteria[] = array(
				'id' => (int)$row['id'],
				'criteria' => $row['criteria'],
				'description' => (string)$row['description'],
				'is_active' => (int)$row['is_active'],
				'templates' => (int)$row['templates'],
				'used' => (int)$row['used'] > 0
			);
		}

		$labels = $this->qn_status_labels();

		// What was done to it, newest first (the audit log)
		$history = array();
		$result = $this->db->query("
			SELECT a.action, a.details, a.created_at,
				CASE WHEN u.id IS NULL THEN NULL ELSE CONCAT(u.firstname, ' ', u.lastname) END AS name
			FROM audit_log a
			LEFT JOIN users u ON u.id = a.user_id AND a.user_type = 1
			WHERE a.entity = 'questionnaire' AND a.entity_id = $id
			ORDER BY a.id DESC
			LIMIT 30
		");
		while($row = $result->fetch_assoc()){
			$history[] = $row;
		}

		return $this->qn_ok(array(
			'questionnaire' => array(
				'id' => $id,
				'title' => $questionnaire['title'],
				'description' => (string)$questionnaire['description'],
				'instructions' => (string)$questionnaire['instructions'],
				'evaluation_type' => $questionnaire['evaluation_type'],
				'target_respondent' => $questionnaire['target_respondent'],
				'project_id' => $questionnaire['project_id'] !== null ? (int)$questionnaire['project_id'] : null,
				'project_title' => $project ? $project['title'] : '',
				'academic_year' => (string)$questionnaire['academic_year'],
				'semester' => (string)$questionnaire['semester'],
				'start_date' => $questionnaire['start_date'],
				'end_date' => $questionnaire['end_date'],
				'status' => $questionnaire['status'],
				'status_label' => $labels[$questionnaire['status']] ?? $questionnaire['status'],
				'scale' => questionnaire_scale($questionnaire),
				'published_at' => $questionnaire['published_at'],
				'closed_at' => $questionnaire['closed_at']
			),
			'structure' => $structure,
			'checks' => $checks,
			'ready' => $this->qn_passes($checks),
			'editable' => $this->qn_editable($questionnaire),
			'locked_message' => $this->qn_editable($questionnaire) ? '' : $this->qn_locked_message($questionnaire),
			'transitions' => $this->qn_transitions()[$questionnaire['status']] ?? array(),
			'counts' => array('questions' => $questions, 'responses' => $this->qn_count_responses($id)),
			'criteria' => $criteria,
			'types' => $this->qn_types(),
			'history' => $history
		));
	}

	/* =====================================================================
	   BASIC INFORMATION
	===================================================================== */

	function qn_save(){

		$id = (int)($_POST['id'] ?? 0);
		$existing = $id ? $this->qn_find($id) : null;

		if($id && !$existing){
			return $this->qn_fail('Questionnaire not found.');
		}

		if($existing && !$this->qn_editable($existing)){
			return $this->qn_fail($this->qn_locked_message($existing));
		}

		$title = trim((string)($_POST['title'] ?? ''));

		if($title === ''){
			return $this->qn_fail('Questionnaire title is required.');
		}

		$year = trim((string)($_POST['academic_year'] ?? ''));

		if($year !== ''){
			if(!preg_match('/^(\d{4})-(\d{4})$/', $year, $m) || (int)$m[2] !== (int)$m[1] + 1){
				return $this->qn_fail('Write the academic year like 2026-2027.');
			}
		}

		$semester = trim((string)($_POST['semester'] ?? ''));

		if($semester !== '' && !in_array($semester, $this->qn_semesters(), true)){
			return $this->qn_fail('Choose a valid semester.');
		}

		$dates = array();
		foreach(array('start_date', 'end_date') as $field){
			$value = trim((string)($_POST[$field] ?? ''));
			$dates[$field] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
		}

		if($dates['start_date'] && $dates['end_date'] && $dates['start_date'] > $dates['end_date']){
			return $this->qn_fail('The end date must be on or after the start date.');
		}

		$project_id = (int)($_POST['project_id'] ?? 0);

		if($project_id > 0){
			$exists = $this->db->query("SELECT id FROM projects WHERE id = $project_id")->num_rows;
			if(!$exists){
				return $this->qn_fail('The chosen project no longer exists.');
			}
		}

		// The answer scale: its labels, highest first; the values count down to 1
		$labels = isset($_POST['scale']) && is_array($_POST['scale']) ? $_POST['scale'] : array();
		$labels = array_values(array_map(function($label){ return trim((string)$label); }, $labels));

		if($labels){
			if(count($labels) < 2 || count($labels) > 7){
				return $this->qn_fail('The Likert scale needs between two and seven steps.');
			}
			foreach($labels as $label){
				if($label === ''){
					return $this->qn_fail('Every step of the Likert scale needs a label.');
				}
			}
			$scale = array();
			foreach($labels as $position => $label){
				$scale[] = array('value' => count($labels) - $position, 'label' => mb_substr($label, 0, 120));
			}
		}else{
			$scale = $existing ? questionnaire_scale($existing) : questionnaire_default_scale();
		}

		$types = $this->qn_evaluation_types();
		$respondents = $this->qn_respondents();

		$values = array(
			'title' => mb_substr($title, 0, 255),
			'description' => trim((string)($_POST['description'] ?? '')),
			'instructions' => trim((string)($_POST['instructions'] ?? '')),
			'evaluation_type' => isset($types[$_POST['evaluation_type'] ?? '']) ? $_POST['evaluation_type'] : 'project',
			'target_respondent' => isset($respondents[$_POST['target_respondent'] ?? '']) ? $_POST['target_respondent'] : 'community',
			'project_id' => $project_id > 0 ? $project_id : null,
			'academic_year' => $year !== '' ? $year : null,
			'semester' => $semester !== '' ? $semester : null,
			'start_date' => $dates['start_date'],
			'end_date' => $dates['end_date'],
			'likert_scale' => json_encode($scale, JSON_UNESCAPED_UNICODE)
		);

		if($existing){
			$this->qn_write('questionnaires', $values, $id);
			$this->audit('questionnaire_updated', 'questionnaire', $id, $title);
			return $this->qn_ok(array('id' => $id));
		}

		$values['status'] = 'draft';
		$values['created_by'] = (int)$_SESSION['login_id'];

		$this->db->begin_transaction();

		try {
			$id = $this->qn_write('questionnaires', $values);

			// Starting from a copy of an earlier questionnaire
			$copy_from = (int)($_POST['copy_from'] ?? 0);
			if($copy_from > 0){
				if(!$this->qn_find($copy_from)){
					throw new Exception('The questionnaire to copy from was not found.');
				}
				$this->qn_copy_structure($copy_from, $id);
			}

			$this->db->commit();

		} catch (Throwable $e) {
			$this->db->rollback();
			error_log('qn_save: '.$e->getMessage());
			return $this->qn_fail('The questionnaire could not be created. Please try again.');
		}

		$this->audit('questionnaire_created', 'questionnaire', $id, $title.($copy_from ? " (copied from #$copy_from)" : ''));

		return $this->qn_ok(array('id' => $id));
	}

	// Copies every section, question and option of one questionnaire into another
	private function qn_copy_structure($from, $to){

		$from = (int)$from;
		$to = (int)$to;

		$sections = $this->db->query("SELECT * FROM questionnaire_sections WHERE questionnaire_id = $from ORDER BY order_by, id");

		while($section = $sections->fetch_assoc()){

			$new_section = $this->qn_write('questionnaire_sections', array(
				'questionnaire_id' => $to,
				'criteria_id' => $section['criteria_id'] !== null ? (int)$section['criteria_id'] : null,
				'title' => $section['title'],
				'description' => $section['description'],
				'order_by' => (int)$section['order_by'],
				'is_active' => (int)$section['is_active']
			));

			$questions = $this->db->query("SELECT * FROM question_list WHERE section_id = ".(int)$section['id']." ORDER BY order_by, id");

			while($question = $questions->fetch_assoc()){
				$this->qn_copy_question($question, $to, $new_section, (int)$question['order_by'], false);
			}
		}
	}

	// Copies one question (and its options); returns the new question's id
	private function qn_copy_question($question, $questionnaire_id, $section_id, $order, $mark_copy){

		$section = $this->db->query("SELECT criteria_id FROM questionnaire_sections WHERE id = ".(int)$section_id)->fetch_assoc();

		$new_id = $this->qn_write('question_list', array(
			'questionnaire_id' => (int)$questionnaire_id,
			'section_id' => (int)$section_id,
			'criteria_id' => ($section && $section['criteria_id'] !== null) ? (int)$section['criteria_id'] : null,
			'question' => $mark_copy ? $question['question'].' (copy)' : $question['question'],
			'question_type' => $question['question_type'],
			'is_required' => (int)$question['is_required'],
			'is_active' => (int)$question['is_active'],
			'settings' => $question['settings'],
			'order_by' => (int)$order
		));

		$options = $this->db->query("SELECT * FROM question_options WHERE question_id = ".(int)$question['id']." ORDER BY order_by, id");

		while($option = $options->fetch_assoc()){
			$this->qn_write('question_options', array(
				'question_id' => $new_id,
				'label' => $option['label'],
				'value' => $option['value'],
				'order_by' => (int)$option['order_by']
			));
		}

		return $new_id;
	}

	// Only a questionnaire that was never published, and has no responses
	function qn_delete(){

		$questionnaire = $this->qn_find($_POST['id'] ?? 0);

		if(!$questionnaire){
			return $this->qn_fail('Questionnaire not found.');
		}

		if(!$this->qn_editable($questionnaire) || $this->qn_count_responses($questionnaire['id']) > 0){
			return $this->qn_fail('Only a draft or ready questionnaire with no responses can be deleted. Close or archive it instead.');
		}

		$id = (int)$questionnaire['id'];

		$this->db->query("DELETE o FROM question_options o INNER JOIN question_list q ON q.id = o.question_id WHERE q.questionnaire_id = $id");
		$this->db->query("DELETE FROM question_list WHERE questionnaire_id = $id");
		$this->db->query("DELETE FROM questionnaire_sections WHERE questionnaire_id = $id");
		$this->db->query("DELETE FROM questionnaires WHERE id = $id");

		$this->audit('questionnaire_deleted', 'questionnaire', $id, $questionnaire['title']);

		return $this->qn_ok();
	}

	/* =====================================================================
	   SECTIONS
	===================================================================== */

	// Loads the questionnaire named in the request and checks it may be changed
	private function qn_editable_from_post(&$error){

		$questionnaire = $this->qn_find($_POST['questionnaire_id'] ?? 0);

		if(!$questionnaire){
			$error = 'Questionnaire not found.';
			return null;
		}

		if(!$this->qn_editable($questionnaire)){
			$error = $this->qn_locked_message($questionnaire);
			return null;
		}

		return $questionnaire;
	}

	function qn_section_save(){

		$questionnaire = $this->qn_editable_from_post($error);

		if(!$questionnaire){
			return $this->qn_fail($error);
		}

		$qid = (int)$questionnaire['id'];
		$id = (int)($_POST['id'] ?? 0);
		$title = trim((string)($_POST['title'] ?? ''));

		if($title === ''){
			return $this->qn_fail('Section title is required.');
		}

		$values = array(
			'title' => mb_substr($title, 0, 255),
			'description' => trim((string)($_POST['description'] ?? '')),
			'is_active' => !isset($_POST['is_active']) || $_POST['is_active'] === '1' ? 1 : 0
		);

		if($id){
			if(!$this->qn_section_of($qid, $id)){
				return $this->qn_fail('That section is not part of this questionnaire.');
			}
			$this->qn_write('questionnaire_sections', $values, $id);
			$this->audit('section_updated', 'questionnaire', $qid, $title);
			return $this->qn_ok(array('id' => $id));
		}

		$values['questionnaire_id'] = $qid;
		$values['order_by'] = (int)$this->db->query("
			SELECT COALESCE(MAX(order_by), -1) + 1 AS n FROM questionnaire_sections WHERE questionnaire_id = $qid
		")->fetch_assoc()['n'];

		$id = $this->qn_write('questionnaire_sections', $values);
		$this->audit('section_added', 'questionnaire', $qid, $title);

		return $this->qn_ok(array('id' => $id));
	}

	function qn_section_delete(){

		$questionnaire = $this->qn_editable_from_post($error);

		if(!$questionnaire){
			return $this->qn_fail($error);
		}

		$qid = (int)$questionnaire['id'];
		$section = $this->qn_section_of($qid, $_POST['id'] ?? 0);

		if(!$section){
			return $this->qn_fail('That section is not part of this questionnaire.');
		}

		$sid = (int)$section['id'];

		$answered = (int)$this->db->query("
			SELECT COUNT(*) AS c FROM evaluation_answers a
			INNER JOIN question_list q ON q.id = a.question_id
			WHERE q.section_id = $sid
		")->fetch_assoc()['c'];

		if($answered > 0){
			return $this->qn_fail("This section's questions have already been answered, so it can't be deleted.");
		}

		$this->db->query("DELETE o FROM question_options o INNER JOIN question_list q ON q.id = o.question_id WHERE q.section_id = $sid");
		$this->db->query("DELETE FROM question_list WHERE section_id = $sid");
		$this->db->query("DELETE FROM questionnaire_sections WHERE id = $sid");

		$this->qn_renumber_sections($qid);
		$this->audit('section_deleted', 'questionnaire', $qid, $section['title']);

		return $this->qn_ok();
	}

	private function qn_renumber_sections($questionnaire_id){
		$result = $this->db->query("SELECT id FROM questionnaire_sections WHERE questionnaire_id = ".(int)$questionnaire_id." ORDER BY order_by, id");
		$position = 0;
		while($row = $result->fetch_assoc()){
			$this->db->query("UPDATE questionnaire_sections SET order_by = ".$position++." WHERE id = ".(int)$row['id']);
		}
	}

	/*
	| A section made from an evaluation criterion, with the criterion's
	| template questions copied in as Likert questions.
	*/
	function qn_section_from_criteria(){

		$questionnaire = $this->qn_editable_from_post($error);

		if(!$questionnaire){
			return $this->qn_fail($error);
		}

		$qid = (int)$questionnaire['id'];
		$criteria_id = (int)($_POST['criteria_id'] ?? 0);

		$criterion = $this->db->query("SELECT * FROM criteria_list WHERE id = $criteria_id")->fetch_assoc();

		if(!$criterion){
			return $this->qn_fail('That criterion was not found.');
		}

		if((int)$criterion['is_active'] !== 1){
			return $this->qn_fail('That criterion is inactive. Activate it in Evaluation Criteria first.');
		}

		$exists = $this->db->query("
			SELECT id FROM questionnaire_sections WHERE questionnaire_id = $qid AND criteria_id = $criteria_id
		")->num_rows;

		if($exists){
			return $this->qn_fail('This criterion is already a section of this questionnaire.');
		}

		$this->db->begin_transaction();

		try {
			$section_id = $this->qn_write('questionnaire_sections', array(
				'questionnaire_id' => $qid,
				'criteria_id' => $criteria_id,
				'title' => mb_substr($criterion['criteria'], 0, 255),
				'description' => (string)$criterion['description'],
				'order_by' => (int)$this->db->query("
					SELECT COALESCE(MAX(order_by), -1) + 1 AS n FROM questionnaire_sections WHERE questionnaire_id = $qid
				")->fetch_assoc()['n']
			));

			$templates = $this->db->query("SELECT * FROM criteria_questions WHERE criteria_id = $criteria_id ORDER BY order_by, id");
			$position = 0;

			while($template = $templates->fetch_assoc()){
				$type = isset($this->qn_types()[$template['question_type']]) ? $template['question_type'] : 'likert';
				$question_id = $this->qn_write('question_list', array(
					'questionnaire_id' => $qid,
					'section_id' => $section_id,
					'criteria_id' => $criteria_id,
					'question' => $template['question'],
					'question_type' => $type,
					'is_required' => 1,
					'is_active' => 1,
					'order_by' => $position++
				));
				$this->qn_default_options($question_id, $type, array());
			}

			$this->db->commit();

		} catch (Throwable $e) {
			$this->db->rollback();
			error_log('qn_section_from_criteria: '.$e->getMessage());
			return $this->qn_fail('The section could not be added. Please try again.');
		}

		$this->audit('section_added', 'questionnaire', $qid, $criterion['criteria']." (from criteria, $position question(s))");

		return $this->qn_ok(array('id' => $section_id, 'questions' => $position));
	}

	/* =====================================================================
	   QUESTIONS
	===================================================================== */

	// Yes / No questions get their two (or three) answers automatically
	private function qn_default_options($question_id, $type, $settings){
		if($type !== 'yes_no'){
			return;
		}
		$this->qn_write('question_options', array('question_id' => (int)$question_id, 'label' => 'Yes', 'value' => '1', 'order_by' => 0));
		$this->qn_write('question_options', array('question_id' => (int)$question_id, 'label' => 'No', 'value' => '0', 'order_by' => 1));
		if(!empty($settings['allow_na'])){
			$this->qn_write('question_options', array('question_id' => (int)$question_id, 'label' => 'Not Applicable', 'value' => null, 'order_by' => 2));
		}
	}

	function qn_question_save(){

		$questionnaire = $this->qn_editable_from_post($error);

		if(!$questionnaire){
			return $this->qn_fail($error);
		}

		$qid = (int)$questionnaire['id'];
		$id = (int)($_POST['id'] ?? 0);
		$existing = null;

		if($id){
			$existing = $this->qn_question_of($qid, $id);
			if(!$existing){
				return $this->qn_fail('That question is not part of this questionnaire.');
			}
		}

		$section = $this->qn_section_of($qid, $_POST['section_id'] ?? 0);

		if(!$section){
			return $this->qn_fail('Choose the section this question belongs to.');
		}

		$text = trim((string)($_POST['question'] ?? ''));

		if($text === ''){
			return $this->qn_fail('Question text is required.');
		}

		$type = (string)($_POST['question_type'] ?? '');

		if(!isset($this->qn_types()[$type])){
			return $this->qn_fail('Choose a valid question type.');
		}

		// Settings that only some types use
		$settings = array();

		if($type === 'rating'){
			$min = (int)($_POST['rating_min'] ?? 1);
			$max = (int)($_POST['rating_max'] ?? 5);
			if($min < 0 || $max > 10 || $min >= $max){
				return $this->qn_fail('The rating range must go from a lower to a higher number, between 0 and 10.');
			}
			$settings = array('min' => $min, 'max' => $max);
		}

		if($type === 'yes_no'){
			$settings = array('allow_na' => !empty($_POST['allow_na']) && $_POST['allow_na'] !== '0');
		}

		// The answer choices for choice questions
		$options = array();

		if(in_array($type, array('single_choice', 'multiple_choice'), true)){

			$posted = isset($_POST['options']) && is_array($_POST['options']) ? $_POST['options'] : array();

			foreach($posted as $label){
				$label = trim((string)$label);
				if($label !== ''){
					$options[] = mb_substr($label, 0, 255);
				}
			}

			if(count($options) < 2){
				return $this->qn_fail('Add at least two options for this question.');
			}

			if(count(array_unique(array_map('mb_strtolower', $options))) !== count($options)){
				return $this->qn_fail('Two options have the same text.');
			}
		}

		$values = array(
			'section_id' => (int)$section['id'],
			'criteria_id' => $section['criteria_id'] !== null ? (int)$section['criteria_id'] : null,
			'question' => $text,
			'question_type' => $type,
			'is_required' => !isset($_POST['is_required']) || $_POST['is_required'] === '1' ? 1 : 0,
			'is_active' => !isset($_POST['is_active']) || $_POST['is_active'] === '1' ? 1 : 0,
			'settings' => $settings ? json_encode($settings) : null
		);

		$this->db->begin_transaction();

		try {

			if($existing){

				// Moved to another section: it goes to the end of that one
				if((int)$existing['section_id'] !== (int)$section['id']){
					$values['order_by'] = $this->qn_next_question_order((int)$section['id']);
				}

				$this->qn_write('question_list', $values, $id);

			}else{

				$values['questionnaire_id'] = $qid;
				$values['order_by'] = $this->qn_next_question_order((int)$section['id']);
				$id = $this->qn_write('question_list', $values);
			}

			// Options are replaced wholesale; an editable questionnaire has no responses
			$this->db->query("DELETE FROM question_options WHERE question_id = $id");

			foreach($options as $position => $label){
				$this->qn_write('question_options', array(
					'question_id' => $id,
					'label' => $label,
					'value' => null,
					'order_by' => $position
				));
			}

			$this->qn_default_options($id, $type, $settings);

			$this->db->commit();

		} catch (Throwable $e) {
			$this->db->rollback();
			error_log('qn_question_save: '.$e->getMessage());
			return $this->qn_fail('The question could not be saved. Please try again.');
		}

		$this->audit($existing ? 'question_updated' : 'question_added', 'questionnaire', $qid, mb_substr($text, 0, 200));

		return $this->qn_ok(array('id' => $id));
	}

	private function qn_next_question_order($section_id){
		return (int)$this->db->query("
			SELECT COALESCE(MAX(order_by), -1) + 1 AS n FROM question_list WHERE section_id = ".(int)$section_id
		)->fetch_assoc()['n'];
	}

	function qn_question_delete(){

		$questionnaire = $this->qn_editable_from_post($error);

		if(!$questionnaire){
			return $this->qn_fail($error);
		}

		$qid = (int)$questionnaire['id'];
		$question = $this->qn_question_of($qid, $_POST['id'] ?? 0);

		if(!$question){
			return $this->qn_fail('That question is not part of this questionnaire.');
		}

		$id = (int)$question['id'];
		$answered = (int)$this->db->query("SELECT COUNT(*) AS c FROM evaluation_answers WHERE question_id = $id")->fetch_assoc()['c'];

		if($answered > 0){
			return $this->qn_fail('This question has already been answered, so it cannot be deleted.');
		}

		$this->db->query("DELETE FROM question_options WHERE question_id = $id");
		$this->db->query("DELETE FROM question_list WHERE id = $id");

		$this->audit('question_deleted', 'questionnaire', $qid, mb_substr($question['question'], 0, 200));

		return $this->qn_ok();
	}

	// A copy placed straight after the original
	function qn_question_duplicate(){

		$questionnaire = $this->qn_editable_from_post($error);

		if(!$questionnaire){
			return $this->qn_fail($error);
		}

		$qid = (int)$questionnaire['id'];
		$question = $this->qn_question_of($qid, $_POST['id'] ?? 0);

		if(!$question){
			return $this->qn_fail('That question is not part of this questionnaire.');
		}

		$section_id = (int)$question['section_id'];
		$position = (int)$question['order_by'] + 1;

		$this->db->begin_transaction();

		try {
			$this->db->query("UPDATE question_list SET order_by = order_by + 1 WHERE section_id = $section_id AND order_by >= $position");
			$new_id = $this->qn_copy_question($question, $qid, $section_id, $position, true);
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollback();
			error_log('qn_question_duplicate: '.$e->getMessage());
			return $this->qn_fail('The question could not be duplicated. Please try again.');
		}

		$this->audit('question_added', 'questionnaire', $qid, 'Duplicate of: '.mb_substr($question['question'], 0, 180));

		return $this->qn_ok(array('id' => $new_id));
	}

	/*
	| Saves the order of sections and of the questions inside them, including
	| questions dragged from one section to another. The page sends the whole
	| arrangement: [{"section": id, "questions": [id, id, ...]}, ...]
	*/
	function qn_layout_save(){

		$questionnaire = $this->qn_editable_from_post($error);

		if(!$questionnaire){
			return $this->qn_fail($error);
		}

		$qid = (int)$questionnaire['id'];
		$layout = json_decode((string)($_POST['layout'] ?? ''), true);

		if(!is_array($layout)){
			return $this->qn_fail('The new order could not be read.');
		}

		$sections = array();
		$result = $this->db->query("SELECT id, criteria_id FROM questionnaire_sections WHERE questionnaire_id = $qid");
		while($row = $result->fetch_assoc()){
			$sections[(int)$row['id']] = $row['criteria_id'] !== null ? (int)$row['criteria_id'] : null;
		}

		$questions = array();
		$result = $this->db->query("SELECT id FROM question_list WHERE questionnaire_id = $qid");
		while($row = $result->fetch_assoc()){
			$questions[(int)$row['id']] = true;
		}

		// Everything named must belong to this questionnaire, and appear once
		$seen = array();

		foreach($layout as $group){
			$section_id = (int)($group['section'] ?? 0);
			if(!array_key_exists($section_id, $sections)){
				return $this->qn_fail('The new order mentions a section that is not part of this questionnaire.');
			}
			foreach((array)($group['questions'] ?? array()) as $question_id){
				$question_id = (int)$question_id;
				if(!isset($questions[$question_id]) || isset($seen[$question_id])){
					return $this->qn_fail('The new order mentions a question that is not part of this questionnaire.');
				}
				$seen[$question_id] = true;
			}
		}

		$this->db->begin_transaction();

		try {
			foreach(array_values($layout) as $section_position => $group){

				$section_id = (int)$group['section'];
				$criteria = $sections[$section_id] === null ? 'NULL' : (int)$sections[$section_id];

				$this->db->query("UPDATE questionnaire_sections SET order_by = $section_position WHERE id = $section_id");

				foreach(array_values((array)($group['questions'] ?? array())) as $question_position => $question_id){
					$this->db->query("
						UPDATE question_list
						SET section_id = $section_id, criteria_id = $criteria, order_by = $question_position
						WHERE id = ".(int)$question_id." AND questionnaire_id = $qid
					");
				}
			}
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollback();
			error_log('qn_layout_save: '.$e->getMessage());
			return $this->qn_fail('The new order could not be saved. Please try again.');
		}

		$this->audit('questions_reordered', 'questionnaire', $qid, '');

		return $this->qn_ok();
	}

	/* =====================================================================
	   CRITERIA TEMPLATE QUESTIONS
	   The Likert questions a criterion brings with it when it is added to a
	   questionnaire as a section (qn_section_from_criteria). A questionnaire
	   keeps its own copy, so changing these never changes one.
	===================================================================== */

	private function qn_criterion_from_post(){
		return $this->db->query("SELECT * FROM criteria_list WHERE id = ".(int)($_POST['criteria_id'] ?? 0))->fetch_assoc() ?: null;
	}

	private function qn_renumber_templates($criteria_id){
		$result = $this->db->query("SELECT id FROM criteria_questions WHERE criteria_id = ".(int)$criteria_id." ORDER BY order_by, id");
		$position = 0;
		while($row = $result->fetch_assoc()){
			$this->db->query("UPDATE criteria_questions SET order_by = ".$position++." WHERE id = ".(int)$row['id']);
		}
	}

	function qn_template_save(){

		$criterion = $this->qn_criterion_from_post();

		if(!$criterion){
			return $this->qn_fail('That criterion was not found.');
		}

		$cid = (int)$criterion['id'];
		$text = trim((string)($_POST['question'] ?? ''));

		if($text === ''){
			return $this->qn_fail('Question text is required.');
		}

		$text = mb_substr($text, 0, 2000);
		$id = (int)($_POST['id'] ?? 0);

		if($id){

			if(!$this->db->query("SELECT id FROM criteria_questions WHERE id = $id AND criteria_id = $cid")->num_rows){
				return $this->qn_fail('That question is not part of this criterion.');
			}

			$this->qn_write('criteria_questions', array('question' => $text), $id);
			$this->audit('criteria_question_updated', 'criteria', $cid, mb_substr($text, 0, 200));

		}else{

			$id = $this->qn_write('criteria_questions', array(
				'criteria_id' => $cid,
				'question' => $text,
				'question_type' => 'likert',
				'order_by' => (int)$this->db->query("
					SELECT COALESCE(MAX(order_by), -1) + 1 AS n FROM criteria_questions WHERE criteria_id = $cid
				")->fetch_assoc()['n']
			));

			$this->audit('criteria_question_added', 'criteria', $cid, mb_substr($text, 0, 200));
		}

		return $this->qn_ok(array('id' => $id, 'question' => $text));
	}

	function qn_template_delete(){

		$id = (int)($_POST['id'] ?? 0);
		$template = $this->db->query("SELECT * FROM criteria_questions WHERE id = $id")->fetch_assoc();

		if(!$template){
			return $this->qn_fail('That question was not found.');
		}

		$this->db->query("DELETE FROM criteria_questions WHERE id = $id");
		$this->qn_renumber_templates($template['criteria_id']);
		$this->audit('criteria_question_deleted', 'criteria', (int)$template['criteria_id'], mb_substr($template['question'], 0, 200));

		return $this->qn_ok();
	}

	// The new order of a criterion's questions: every one of them, once
	function qn_template_order(){

		$criterion = $this->qn_criterion_from_post();

		if(!$criterion){
			return $this->qn_fail('That criterion was not found.');
		}

		$cid = (int)$criterion['id'];
		$sent = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : array();

		$have = array();
		$result = $this->db->query("SELECT id FROM criteria_questions WHERE criteria_id = $cid");
		while($row = $result->fetch_assoc()){
			$have[] = (int)$row['id'];
		}

		$check = $sent;
		sort($check);
		sort($have);

		if($check !== $have){
			return $this->qn_fail('The new order could not be read. Please reload the page.');
		}

		foreach($sent as $position => $template_id){
			$this->db->query("UPDATE criteria_questions SET order_by = $position WHERE id = $template_id AND criteria_id = $cid");
		}

		return $this->qn_ok();
	}

	/* =====================================================================
	   STATUS
	===================================================================== */

	function qn_status(){

		$questionnaire = $this->qn_find($_POST['id'] ?? 0);

		if(!$questionnaire){
			return $this->qn_fail('Questionnaire not found.');
		}

		$from = $questionnaire['status'];
		$to = (string)($_POST['to'] ?? '');
		$allowed = $this->qn_transitions()[$from] ?? array();

		if(!in_array($to, $allowed, true)){
			$reasons = array(
				'draft:active' => 'Mark the questionnaire as Ready before publishing it.',
				'active:draft' => 'An active questionnaire cannot go back to being edited. Close it instead.',
				'active:ready' => 'An active questionnaire cannot go back to being edited. Close it instead.',
				'closed:active' => 'A closed evaluation cannot be reopened.',
				'archived:active' => 'This questionnaire is archived and read-only.'
			);
			return $this->qn_fail($reasons["$from:$to"] ?? 'That change of status is not allowed.');
		}

		$id = (int)$questionnaire['id'];

		// Ready and active both need a questionnaire that passes every check
		if(in_array($to, array('ready', 'active'), true)){

			$checks = $this->qn_checks($questionnaire, $this->qn_structure($id));

			if(!$this->qn_passes($checks)){
				return $this->qn_fail(
					$to === 'active'
						? 'Unable to publish questionnaire. Please check the required fields.'
						: 'This questionnaire is not ready yet. Please fix the items marked below.',
					array('checks' => $checks)
				);
			}
		}

		$user = (int)$_SESSION['login_id'];
		$stamp = array(
			'ready' => array('ready_at', 'ready_by'),
			'active' => array('published_at', 'published_by'),
			'closed' => array('closed_at', 'closed_by'),
			'archived' => array('archived_at', 'archived_by')
		);

		$set = "status = '$to'";

		if(isset($stamp[$to])){
			$set .= ", {$stamp[$to][0]} = NOW(), {$stamp[$to][1]} = $user";
		}

		if($to === 'draft'){
			$set .= ", ready_at = NULL, ready_by = NULL";
		}

		$this->db->query("UPDATE questionnaires SET $set WHERE id = $id AND status = '$from'");

		if($this->db->affected_rows !== 1){
			return $this->qn_fail('The questionnaire changed while you were working. Please reload the page.');
		}

		$actions = array(
			'ready' => 'questionnaire_marked_ready',
			'draft' => 'questionnaire_back_to_draft',
			'active' => 'questionnaire_published',
			'closed' => 'questionnaire_closed',
			'archived' => 'questionnaire_archived'
		);

		$this->audit($actions[$to], 'questionnaire', $id, $questionnaire['title']);

		return $this->qn_ok(array('status' => $to));
	}
}
