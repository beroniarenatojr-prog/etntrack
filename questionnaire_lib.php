<?php

/*
|--------------------------------------------------------------------------
| QUESTIONNAIRES: SHARED HELPERS
|--------------------------------------------------------------------------
| Used by the public evaluation form (evaluate.php, save_evaluation.php) and
| by the admin side, so both agree on which questionnaire applies.
*/

/*
| Whether the questionnaire tables exist yet. New code reaches the server as
| soon as it is pushed, but the database update is applied by hand afterwards
| (System Update). Until then the public form keeps working the old way
| instead of failing in front of people at an event.
*/
function questionnaires_ready($conn){

	static $ready = null;

	if($ready === null){
		$ready = $conn->query("
			SELECT 1 FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = 'question_list'
			AND COLUMN_NAME = 'questionnaire_id'
		")->num_rows > 0
		&& $conn->query("
			SELECT 1 FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluation_responses'
		")->num_rows > 0;
	}

	return $ready;
}

/* ---------------------------------------------------------------
   The names used for questionnaires everywhere in the system
--------------------------------------------------------------- */

function questionnaire_types(){
	return array(
		'likert' => 'Likert Scale',
		'single_choice' => 'Single Choice',
		'multiple_choice' => 'Multiple Choice',
		'yes_no' => 'Yes / No',
		'rating' => 'Rating',
		'short_text' => 'Short Answer',
		'long_text' => 'Long Answer'
	);
}

function questionnaire_evaluation_types(){
	return array(
		'project' => 'Project Evaluation',
		'coordinator' => 'Coordinator Evaluation',
		'beneficiary' => 'Beneficiary Evaluation',
		'general' => 'General Extension Evaluation'
	);
}

function questionnaire_respondents(){
	return array(
		'community' => 'Community Beneficiaries',
		'students' => 'Students',
		'faculty' => 'Faculty',
		'staff' => 'Staff',
		'coordinators' => 'Extension Coordinators',
		'other' => 'Other'
	);
}

function questionnaire_semesters(){
	return array('1st Semester', '2nd Semester', 'Midyear');
}

function questionnaire_status_labels(){
	return array(
		'draft' => 'Draft',
		'ready' => 'Ready',
		'active' => 'Active',
		'closed' => 'Closed',
		'archived' => 'Archived'
	);
}

// The answer scale printed on the ISU Extension evaluation form.
// New questionnaires start with it; each questionnaire can change its own.
function questionnaire_default_scale(){
	return array(
		array('value' => 5, 'label' => 'Lubos na Sumasang-ayon (Strongly Agree)'),
		array('value' => 4, 'label' => 'Sumasang-ayon (Agree)'),
		array('value' => 3, 'label' => 'Bahagyang Sumasang-ayon (Slightly Agree)'),
		array('value' => 2, 'label' => 'Hindi Sumasang-ayon (Disagree)'),
		array('value' => 1, 'label' => 'Lubos na Hindi Sumasang-ayon (Strongly Disagree)')
	);
}

// The instruction printed on the ISU Extension evaluation form
function questionnaire_default_instructions(){
	return "Sagutin kung gaano kayo sumasang-ayon o hindi sumasang-ayon sa mga sumusunod na pahayag. "
		."(Indicate the extent to which you agree or disagree with the following statements.)";
}

/*
| Today's academic year and semester: 1st Semester is August to December,
| 2nd Semester January to May, Midyear June to July (as for activities).
*/
function questionnaire_current_term($today = null){

	$time = $today ? strtotime($today) : time();
	$year = (int)date('Y', $time);
	$month = (int)date('n', $time);

	if($month >= 8){
		return array('academic_year' => $year.'-'.($year + 1), 'semester' => '1st Semester');
	}

	return array(
		'academic_year' => ($year - 1).'-'.$year,
		'semester' => $month <= 5 ? '2nd Semester' : 'Midyear'
	);
}

// Academic years to choose from: next year back to five years ago, plus any already in use
function questionnaire_year_choices($conn){

	$current = (int)substr(questionnaire_current_term()['academic_year'], 0, 4);
	$years = array();

	for($start = $current + 1; $start >= $current - 5; $start--){
		$years[] = $start.'-'.($start + 1);
	}

	// Two queries rather than a UNION, which fails when the columns' collations differ
	foreach(array(
		"SELECT DISTINCT academic_year FROM questionnaires WHERE academic_year IS NOT NULL",
		"SELECT DISTINCT year FROM academic_list"
	) as $sql){
		$result = $conn->query($sql);
		while($result && $row = $result->fetch_row()){
			if(preg_match('/^\d{4}-\d{4}$/', (string)$row[0]) && !in_array($row[0], $years, true)){
				$years[] = $row[0];
			}
		}
	}

	rsort($years);

	return $years;
}

/*
| SQL that keeps only answers given on the agree-disagree (Likert) scale, for
| averages. Ratings (1 to 10), choices and typed answers are other kinds of
| answer and would distort an average. $alias is the evaluation_answers alias
| ('' for none). Before the database update every answer was a 1-5 rating.
*/
function questionnaire_likert_filter($conn, $alias = 'ea'){

	$prefix = $alias !== '' ? $alias.'.' : '';

	if(!questionnaires_ready($conn)){
		return "{$prefix}rate BETWEEN 1 AND 5";
	}

	return "{$prefix}rate IS NOT NULL AND {$prefix}question_id IN (SELECT id FROM question_list WHERE question_type = 'likert')";
}

/*
| Words for an average on the scale, the same ones the results have always
| used (Excellent 4.50 and up ... Poor). An average on a longer or shorter
| scale is compared as if it were out of 5.
*/
function questionnaire_rating_label($average, $scale_max = 5){

	if($average === null || $scale_max <= 0){
		return '';
	}

	$out_of_five = $average / $scale_max * 5;

	if($out_of_five >= 4.50) return 'Excellent';
	if($out_of_five >= 3.50) return 'Very Good';
	if($out_of_five >= 2.50) return 'Good';
	if($out_of_five >= 1.50) return 'Fair';

	return 'Poor';
}

// A questionnaire's own scale, or the default one when it has none
function questionnaire_scale($questionnaire){

	$scale = json_decode((string)($questionnaire['likert_scale'] ?? ''), true);

	if(!is_array($scale) || !$scale){
		return questionnaire_default_scale();
	}

	return $scale;
}

// What respondents call themselves on the form
function questionnaire_respondent_types(){
	return array(
		'community' => 'Community member',
		'student' => 'Student',
		'faculty' => 'Faculty',
		'staff' => 'Staff',
		'other' => 'Other'
	);
}

/*
| A questionnaire's sections in order, each with its questions in order, each
| question with its answer options. Questions not in any section are listed
| separately so the builder can point them out.
|
| With $visible_only, only what respondents see: active sections and their
| active questions (the public form and the preview).
*/
function questionnaire_structure($conn, $questionnaire_id, $visible_only = false){

	$qid = (int)$questionnaire_id;
	$sections = array();

	$result = $conn->query("
		SELECT id, criteria_id, title, description, order_by, is_active
		FROM questionnaire_sections
		WHERE questionnaire_id = $qid".($visible_only ? " AND is_active = 1" : "")."
		ORDER BY order_by, id
	");

	while($row = $result->fetch_assoc()){
		$sections[(int)$row['id']] = array(
			'id' => (int)$row['id'],
			'criteria_id' => $row['criteria_id'] !== null ? (int)$row['criteria_id'] : null,
			'title' => $row['title'],
			'description' => (string)$row['description'],
			'is_active' => (int)$row['is_active'],
			'questions' => array()
		);
	}

	$options = array();

	$result = $conn->query("
		SELECT o.id, o.question_id, o.label, o.value
		FROM question_options o
		INNER JOIN question_list q ON q.id = o.question_id
		WHERE q.questionnaire_id = $qid
		ORDER BY o.order_by, o.id
	");

	while($row = $result->fetch_assoc()){
		$options[(int)$row['question_id']][] = array(
			'id' => (int)$row['id'],
			'label' => $row['label'],
			'value' => $row['value'] !== null ? (float)$row['value'] : null
		);
	}

	$unsectioned = array();

	$result = $conn->query("
		SELECT id, section_id, question, question_type, is_required, is_active, settings
		FROM question_list
		WHERE questionnaire_id = $qid".($visible_only ? " AND is_active = 1" : "")."
		ORDER BY order_by, id
	");

	while($row = $result->fetch_assoc()){

		$settings = json_decode((string)$row['settings'], true);

		$question = array(
			'id' => (int)$row['id'],
			'section_id' => $row['section_id'] !== null ? (int)$row['section_id'] : null,
			'question' => $row['question'],
			'question_type' => $row['question_type'],
			'is_required' => (int)$row['is_required'],
			'is_active' => (int)$row['is_active'],
			'settings' => is_array($settings) ? $settings : new stdClass(),
			'options' => $options[(int)$row['id']] ?? array()
		);

		if($question['section_id'] !== null && isset($sections[$question['section_id']])){
			$sections[$question['section_id']]['questions'][] = $question;
		}elseif(!$visible_only){
			$unsectioned[] = $question;
		}
	}

	if($visible_only){
		// A section with nothing to answer is not shown
		$sections = array_filter($sections, function($section){ return count($section['questions']) > 0; });
	}

	return array('sections' => array_values($sections), 'unsectioned' => $unsectioned);
}

/*
| The questionnaire an activity's QR evaluation uses. Only an ACTIVE
| questionnaire ever applies. In order of preference:
|   1. one made for the activity's own project
|   2. a general one for the activity's academic year and semester
|   3. the most recently published general one
| Returns the questionnaire row, or null when none is open.
*/
function questionnaire_for_activity($conn, $activity){

	$project_id = (int)($activity['project_id'] ?? 0);

	if($project_id > 0){
		$row = $conn->query("
			SELECT * FROM questionnaires
			WHERE status = 'active' AND project_id = $project_id
			ORDER BY published_at DESC, id DESC
			LIMIT 1
		")->fetch_assoc();

		if($row){
			return $row;
		}
	}

	$year = $conn->real_escape_string((string)($activity['academic_year'] ?? ''));
	$semester = $conn->real_escape_string((string)($activity['semester'] ?? ''));

	if($year !== '' && $semester !== ''){
		$row = $conn->query("
			SELECT * FROM questionnaires
			WHERE status = 'active' AND project_id IS NULL
			AND academic_year = '$year' AND semester = '$semester'
			ORDER BY published_at DESC, id DESC
			LIMIT 1
		")->fetch_assoc();

		if($row){
			return $row;
		}
	}

	$row = $conn->query("
		SELECT * FROM questionnaires
		WHERE status = 'active' AND project_id IS NULL
		ORDER BY published_at DESC, id DESC
		LIMIT 1
	")->fetch_assoc();

	return $row ?: null;
}
