<?php

/*
|--------------------------------------------------------------------------
| THE EVALUATION FORM
|--------------------------------------------------------------------------
| Draws a questionnaire as the ISU Extension evaluation form, checks a
| submitted form, and saves it. The QR form (evaluate.php) and the admin
| preview (questionnaire_preview.php) both draw it here, so the preview is
| exactly what respondents see.
*/

require_once __DIR__.'/questionnaire_lib.php';

function qf_e($text){
	return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

/* =====================================================================
   THE PAGE AROUND THE FORM
===================================================================== */

function questionnaire_page_start($title){
	$css = 'assets/css/evaluation-form.css';
	$version = @filemtime(__DIR__.'/'.$css) ?: 1;
	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo qf_e($title); ?></title>
<link rel="stylesheet" href="<?php echo $css; ?>?v=<?php echo $version; ?>">
</head>
<body>
<?php
}

function questionnaire_page_end(){
	echo "\n</body>\n</html>\n";
}

function questionnaire_form_header(){
	?>
<div class="header">
    <img src="assets/uploads/logo.webp" class="logo" alt="Isabela State University">
    <div class="school">ISABELA STATE UNIVERSITY</div>
    <div class="campus">Ilagan Campus</div>
    <div class="office">EXTENSION &amp; TRAINING SERVICES</div>
    <div class="form-title">EVALUATION FORM</div>
</div>
	<?php
}

/*
| A short page instead of the form: not open yet, closed, already answered,
| thank you. $kind is info, success or warning.
*/
function questionnaire_message_page($title, $message, $kind = 'info', $detail = ''){

	$icons = array('info' => '&#9432;', 'success' => '&#10003;', 'warning' => '!');

	questionnaire_page_start($title);
	?>
<div class="paper paper-message">
    <?php questionnaire_form_header(); ?>
    <div class="ef-message ef-message-<?php echo qf_e($kind); ?>" role="status">
        <div class="ef-message-icon" aria-hidden="true"><?php echo $icons[$kind] ?? $icons['info']; ?></div>
        <h1><?php echo qf_e($title); ?></h1>
        <p><?php echo qf_e($message); ?></p>
        <?php if($detail !== ''): ?>
            <p class="ef-message-detail"><?php echo qf_e($detail); ?></p>
        <?php endif; ?>
    </div>
</div>
	<?php
	questionnaire_page_end();
}

/* =====================================================================
   ONE RESPONSE PER DEVICE
===================================================================== */

/*
| A random number kept in a cookie on the respondent's device. It says
| nothing about the person; it only lets the form recognise that this
| device already answered an activity's evaluation.
*/
function questionnaire_device_token(){

	static $token = null;

	if($token !== null){
		return $token;
	}

	$cookie = $_COOKIE['etn_device'] ?? '';
	$token = preg_match('/^[a-f0-9]{64}$/', $cookie) ? $cookie : bin2hex(random_bytes(32));

	if(!headers_sent()){
		setcookie('etn_device', $token, array(
			'expires' => time() + 400 * 86400,
			'path' => '/',
			'httponly' => true,
			'samesite' => 'Lax',
			'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
		));
	}

	return $token;
}

function questionnaire_already_answered($conn, $questionnaire_id, $activity_id, $token){
	$stmt = $conn->prepare("
		SELECT id FROM evaluation_responses
		WHERE questionnaire_id = ? AND activity_id = ? AND device_token = ?
		LIMIT 1
	");
	$stmt->bind_param('iis', $questionnaire_id, $activity_id, $token);
	$stmt->execute();
	return (bool)$stmt->get_result()->fetch_row();
}

/*
| Whether the questionnaire's evaluation period has started and not ended.
| Returns null when it is open, or [title, message] when it is not.
*/
function questionnaire_period_problem($questionnaire, $today = null){

	$today = $today ?: date('Y-m-d');
	$start = (string)$questionnaire['start_date'];
	$end = (string)$questionnaire['end_date'];

	if($start !== '' && $today < $start){
		return array('Evaluation not open yet',
			'This evaluation opens on '.date('F j, Y', strtotime($start)).'. Please come back then.');
	}

	if($end !== '' && $today > $end){
		return array('Evaluation closed',
			'This evaluation ended on '.date('F j, Y', strtotime($end)).' and no longer accepts responses.');
	}

	return null;
}

/* =====================================================================
   CHECKING A SUBMITTED FORM
===================================================================== */

function questionnaire_find_option($question, $value){
	if(!is_scalar($value) || !preg_match('/^\d+$/', (string)$value)){
		return null;
	}
	foreach($question['options'] as $option){
		if($option['id'] === (int)$value){
			return $option;
		}
	}
	return null;
}

/*
| Reads the answers in $post (q[question id]) against the questionnaire.
| Returns:
|   answers   rows to save: question_id, rate, option_id, answer_text
|   errors    question id => what is wrong
|   answered  how many questions have an answer
*/
function questionnaire_collect_answers($questionnaire, $structure, $post){

	$given = isset($post['q']) && is_array($post['q']) ? $post['q'] : array();

	// A form opened before this update sends its ratings as rate[question id]
	$legacy = isset($post['rate']) && is_array($post['rate']) ? $post['rate'] : array();

	$scale = array_map(function($step){ return (int)$step['value']; }, questionnaire_scale($questionnaire));

	$answers = array();
	$errors = array();
	$answered = 0;

	foreach($structure['sections'] as $section){
		foreach($section['questions'] as $question){

			$id = $question['id'];
			$type = $question['question_type'];
			$value = $given[$id] ?? ($type === 'likert' ? ($legacy[$id] ?? null) : null);
			$rows = array();
			$invalid = false;

			switch($type){

				case 'likert':
					if($value !== null && $value !== ''){
						if(is_scalar($value) && preg_match('/^\d+$/', (string)$value) && in_array((int)$value, $scale, true)){
							$rows[] = array('rate' => (int)$value);
						}else{
							$invalid = true;
						}
					}
					break;

				case 'rating':
					$settings = (array)$question['settings'];
					$min = isset($settings['min']) ? (int)$settings['min'] : 1;
					$max = isset($settings['max']) ? (int)$settings['max'] : 5;
					if($value !== null && $value !== ''){
						if(is_scalar($value) && preg_match('/^\d+$/', (string)$value) && (int)$value >= $min && (int)$value <= $max){
							$rows[] = array('rate' => (int)$value);
						}else{
							$invalid = true;
						}
					}
					break;

				case 'single_choice':
				case 'yes_no':
					if($value !== null && $value !== ''){
						$option = questionnaire_find_option($question, $value);
						if($option){
							$rows[] = array('option_id' => $option['id']);
						}else{
							$invalid = true;
						}
					}
					break;

				case 'multiple_choice':
					$values = is_array($value) ? $value : (($value !== null && $value !== '') ? array($value) : array());
					$seen = array();
					foreach($values as $one){
						$option = questionnaire_find_option($question, $one);
						if(!$option){
							$invalid = true;
							break;
						}
						if(!isset($seen[$option['id']])){
							$seen[$option['id']] = true;
							$rows[] = array('option_id' => $option['id']);
						}
					}
					break;

				case 'short_text':
				case 'long_text':
					$text = is_scalar($value) ? trim((string)$value) : '';
					if($text !== ''){
						$rows[] = array('answer_text' => mb_substr($text, 0, $type === 'short_text' ? 255 : 5000));
					}
					break;

				default:
					// A type this form does not know is never asked
					continue 2;
			}

			if($invalid){
				$errors[$id] = 'Please choose one of the answers given.';
				continue;
			}

			if(!$rows){
				if($question['is_required'] === 1){
					$errors[$id] = 'This question is required.';
				}
				continue;
			}

			$answered++;

			foreach($rows as $row){
				$answers[] = array_merge(
					array('question_id' => $id, 'rate' => null, 'option_id' => null, 'answer_text' => null),
					$row
				);
			}
		}
	}

	return array('answers' => $answers, 'errors' => $errors, 'answered' => $answered);
}

/* =====================================================================
   SAVING
===================================================================== */

function questionnaire_has_column($conn, $table, $column){
	static $known = array();
	$key = "$table.$column";
	if(!isset($known[$key])){
		$known[$key] = $conn->query("
			SELECT 1 FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = '".$conn->real_escape_string($table)."'
			AND COLUMN_NAME = '".$conn->real_escape_string($column)."'
		")->num_rows > 0;
	}
	return $known[$key];
}

/*
| Saves one response and its answers, all or nothing.
| Returns the response number, 'duplicate' when this device already
| answered (a double tap on Submit), or false when it could not be saved.
*/
function questionnaire_save_response($conn, $questionnaire, $activity, $respondent, $answers){

	$questionnaire_id = (int)$questionnaire['id'];
	$activity_id = (int)$activity['id'];
	$project_id = !empty($activity['project_id']) ? (int)$activity['project_id'] : null;

	$name = $respondent['name'] !== '' ? $respondent['name'] : 'Anonymous';
	$type = $respondent['type'] !== '' ? $respondent['type'] : null;
	$barangay = $respondent['barangay'] !== '' ? $respondent['barangay'] : null;
	$comments = $respondent['comments'] !== '' ? $respondent['comments'] : null;
	$device = $respondent['device'];
	$short_name = mb_substr($name, 0, 100);

	// Comments are kept once the database update that adds them has run
	$with_comments = questionnaire_has_column($conn, 'evaluation_responses', 'comments');

	$conn->begin_transaction();

	try {

		$stmt = $conn->prepare("
			INSERT INTO evaluation_responses
			(questionnaire_id, activity_id, project_id, respondent_name, respondent_type, barangay, device_token"
			.($with_comments ? ", comments" : "").", submitted_at)
			VALUES (?, ?, ?, ?, ?, ?, ?".($with_comments ? ", ?" : "").", NOW())
		");

		if($with_comments){
			$stmt->bind_param('iiisssss', $questionnaire_id, $activity_id, $project_id, $name, $type, $barangay, $device, $comments);
		}else{
			$stmt->bind_param('iiissss', $questionnaire_id, $activity_id, $project_id, $name, $type, $barangay, $device);
		}

		if(!$stmt->execute()){
			throw new mysqli_sql_exception($stmt->error, $stmt->errno);
		}

		$response_id = (int)$conn->insert_id;

		$answer = $conn->prepare("
			INSERT INTO evaluation_answers
			(evaluation_id, questionnaire_id, question_id, rate, option_id, answer_text, activity_id, Evaluator_name, submitted_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
		");

		foreach($answers as $row){
			$question_id = (int)$row['question_id'];
			$rate = $row['rate'];
			$option_id = $row['option_id'];
			$text = $row['answer_text'];
			$answer->bind_param('iiiiisis', $response_id, $questionnaire_id, $question_id, $rate, $option_id, $text, $activity_id, $short_name);
			if(!$answer->execute()){
				throw new mysqli_sql_exception($answer->error, $answer->errno);
			}
		}

		$conn->commit();

		return $response_id;

	} catch (mysqli_sql_exception $e) {

		$conn->rollback();

		if((int)$e->getCode() === 1062){
			return 'duplicate';
		}

		error_log('questionnaire_save_response: '.$e->getMessage());
		return false;
	}
}

/* =====================================================================
   DRAWING THE FORM
===================================================================== */

/*
| $context:
|   mode       'public' (the QR form) or 'preview' (nothing is sent)
|   action     where the form is sent
|   hidden     hidden fields, name => value
|   activity   title, date and venue shown at the top
|   old        what was sent before, to fill the form in again
|   errors     question id => message
|   notice     a message above the questions
*/
function questionnaire_render_form($questionnaire, $structure, $context){

	$mode = $context['mode'] ?? 'public';
	$preview = $mode === 'preview';
	$old = $context['old'] ?? array();
	$errors = $context['errors'] ?? array();
	$activity = $context['activity'] ?? array();
	$given = isset($old['q']) && is_array($old['q']) ? $old['q'] : array();

	$scale = questionnaire_scale($questionnaire);
	$ascending = $scale;
	usort($ascending, function($a, $b){ return $a['value'] <=> $b['value']; });

	$uses_likert = false;
	foreach($structure['sections'] as $section){
		foreach($section['questions'] as $question){
			if($question['question_type'] === 'likert'){
				$uses_likert = true;
			}
		}
	}

	$types = questionnaire_respondent_types();
	$map = array('community' => 'community', 'students' => 'student', 'faculty' => 'faculty', 'staff' => 'staff');
	$default_type = $map[$questionnaire['target_respondent']] ?? '';
	$respondent_type = isset($old['respondent_type']) ? (string)$old['respondent_type'] : $default_type;

	$checked = function($question_id, $value) use ($given){
		$sent = $given[$question_id] ?? null;
		if(is_array($sent)){
			return in_array((string)$value, array_map('strval', $sent), true) ? ' checked' : '';
		}
		return $sent !== null && (string)$sent === (string)$value ? ' checked' : '';
	};

	$text_value = function($question_id) use ($given){
		$sent = $given[$question_id] ?? '';
		return is_scalar($sent) ? qf_e($sent) : '';
	};

	?>
<div class="paper<?php echo $preview ? ' paper-preview' : ''; ?>">

<?php if($preview): ?>
    <div class="ef-preview-banner" role="note">
        <b>Preview</b> &middot; This is how respondents see the form. Nothing you enter here is saved.
    </div>
<?php endif; ?>

<?php questionnaire_form_header(); ?>

<table class="activity-info">
    <tr>
        <td class="label">Name of Training/Seminar/Workshop/Extension Activity:</td>
        <td class="activity-value"><?php echo qf_e($activity['title'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="label">Date:</td>
        <td class="activity-value"><?php echo qf_e($activity['date'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="label">Venue:</td>
        <td class="activity-value"><?php echo qf_e($activity['venue'] ?? ''); ?></td>
    </tr>
</table>

<form id="evaluationForm" class="ef-form" method="post"
      action="<?php echo qf_e($preview ? '#' : ($context['action'] ?? '')); ?>"
      <?php echo $preview ? 'data-preview="1"' : ''; ?>>

<?php foreach(($context['hidden'] ?? array()) as $name => $value): ?>
    <input type="hidden" name="<?php echo qf_e($name); ?>" value="<?php echo qf_e($value); ?>">
<?php endforeach; ?>

    <!-- Left empty by people; filled in by spam robots -->
    <div class="ef-hp" aria-hidden="true">
        <label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <table class="activity-info respondent-info">
        <tr>
            <td class="label"><label for="ef-name">Name of Evaluator:</label></td>
            <td><input type="text" id="ef-name" name="evaluator_name" maxlength="100" placeholder="Optional"
                       value="<?php echo qf_e($old['evaluator_name'] ?? ''); ?>"></td>
        </tr>
        <tr>
            <td class="label"><label for="ef-barangay">Barangay:</label></td>
            <td><input type="text" id="ef-barangay" name="barangay" maxlength="100" placeholder="Optional"
                       value="<?php echo qf_e($old['barangay'] ?? ''); ?>"></td>
        </tr>
        <tr>
            <td class="label"><label for="ef-type">I am a:</label></td>
            <td>
                <select id="ef-type" name="respondent_type">
                    <option value="">Prefer not to say</option>
                    <?php foreach($types as $key => $label): ?>
                        <option value="<?php echo $key; ?>"<?php echo $respondent_type === $key ? ' selected' : ''; ?>><?php echo qf_e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>

<?php if(trim((string)$questionnaire['instructions']) !== ''): ?>
    <div class="instructions">
        <strong>Panuto (Instruction):</strong>
        <?php echo nl2br(qf_e($questionnaire['instructions'])); ?>
    </div>
<?php endif; ?>

<?php if($uses_likert): ?>
    <table class="rating-legend">
        <tr>
            <td class="legend-title">Particular</td>
            <?php foreach($ascending as $step): ?>
                <td><strong><?php echo (int)$step['value']; ?></strong> - <?php echo qf_e($step['label']); ?></td>
            <?php endforeach; ?>
        </tr>
    </table>
<?php endif; ?>

<?php if($errors): ?>
    <div class="ef-error-summary" role="alert">
        <?php echo count($errors) === 1
            ? 'One question still needs an answer. It is marked in red below.'
            : count($errors).' questions still need an answer. They are marked in red below.'; ?>
    </div>
<?php elseif(!empty($context['notice'])): ?>
    <div class="ef-error-summary" role="alert"><?php echo qf_e($context['notice']); ?></div>
<?php endif; ?>

<p class="ef-required-note"><span class="ef-req">*</span> Required</p>

<?php foreach($structure['sections'] as $section):

    $number = 0;
    $groups = array();

    // Questions in order; Likert questions next to each other share one table
    foreach($section['questions'] as $question){
        $last = count($groups) - 1;
        if($question['question_type'] === 'likert' && $last >= 0 && $groups[$last]['likert']){
            $groups[$last]['questions'][] = $question;
        }else{
            $groups[] = array('likert' => $question['question_type'] === 'likert', 'questions' => array($question));
        }
    }
?>

<section class="ef-section" aria-label="<?php echo qf_e($section['title']); ?>">

    <div class="criteria-heading ef-heading"><?php echo qf_e($section['title']); ?></div>

    <?php if($section['description'] !== ''): ?>
        <p class="ef-section-desc"><?php echo qf_e($section['description']); ?></p>
    <?php endif; ?>

    <?php foreach($groups as $group): ?>

        <?php if($group['likert']): ?>

        <div class="table-wrapper">
        <table class="evaluation-table">
            <thead>
                <tr>
                    <th>Particular</th>
                    <?php foreach($ascending as $step): ?>
                        <th scope="col" title="<?php echo qf_e($step['label']); ?>"><?php echo (int)$step['value']; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($group['questions'] as $question):
                $number++;
                $id = $question['id'];
                $required = $question['is_required'] === 1;
            ?>
                <tr class="ef-q<?php echo isset($errors[$id]) ? ' ef-missing' : ''; ?>" id="q-<?php echo $id; ?>" role="radiogroup"
                    aria-labelledby="q-<?php echo $id; ?>-text"<?php echo $required ? ' aria-required="true"' : ''; ?>>
                    <td class="ef-q-text" id="q-<?php echo $id; ?>-text">
                        <span class="question-number"><?php echo $number; ?>.</span>
                        <?php echo qf_e($question['question']); ?>
                        <?php if($required): ?><span class="ef-req" aria-hidden="true">*</span><?php endif; ?>
                        <?php if(isset($errors[$id])): ?><span class="ef-error"><?php echo qf_e($errors[$id]); ?></span><?php endif; ?>
                    </td>
                    <?php foreach($ascending as $step): ?>
                        <td class="ef-scale-cell">
                            <label class="ef-scale">
                                <input type="radio" class="rating-radio" name="q[<?php echo $id; ?>]"
                                       value="<?php echo (int)$step['value']; ?>"<?php echo $checked($id, $step['value']); ?><?php echo $required ? ' required' : ''; ?>>
                                <span class="ef-scale-label"><b><?php echo (int)$step['value']; ?></b> <?php echo qf_e($step['label']); ?></span>
                            </label>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php else:

            $question = $group['questions'][0];
            $number++;
            $id = $question['id'];
            $type = $question['question_type'];
            $required = $question['is_required'] === 1;
            $req = $required ? ' required' : '';
            $settings = (array)$question['settings'];
        ?>

        <div class="ef-block ef-q<?php echo isset($errors[$id]) ? ' ef-missing' : ''; ?>" id="q-<?php echo $id; ?>"
             <?php echo $type === 'multiple_choice' && $required ? 'data-required-group="1"' : ''; ?>>

            <div class="ef-block-text" id="q-<?php echo $id; ?>-text">
                <span class="question-number"><?php echo $number; ?>.</span>
                <?php echo qf_e($question['question']); ?>
                <?php if($required): ?><span class="ef-req" aria-hidden="true">*</span><?php endif; ?>
                <?php if($type === 'multiple_choice'): ?><span class="ef-hint">Tick all that apply.</span><?php endif; ?>
            </div>

            <?php if(isset($errors[$id])): ?>
                <div class="ef-error"><?php echo qf_e($errors[$id]); ?></div>
            <?php endif; ?>

            <div class="ef-block-answer">

            <?php if($type === 'single_choice' || $type === 'yes_no'): ?>

                <div class="ef-choices<?php echo $type === 'yes_no' ? ' ef-choices-inline' : ''; ?>" role="radiogroup" aria-labelledby="q-<?php echo $id; ?>-text">
                    <?php foreach($question['options'] as $option): ?>
                        <label class="ef-choice">
                            <input type="radio" name="q[<?php echo $id; ?>]" value="<?php echo $option['id']; ?>"<?php echo $checked($id, $option['id']).$req; ?>>
                            <span><?php echo qf_e($option['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

            <?php elseif($type === 'multiple_choice'): ?>

                <div class="ef-choices" role="group" aria-labelledby="q-<?php echo $id; ?>-text">
                    <?php foreach($question['options'] as $option): ?>
                        <label class="ef-choice">
                            <input type="checkbox" name="q[<?php echo $id; ?>][]" value="<?php echo $option['id']; ?>"<?php echo $checked($id, $option['id']); ?>>
                            <span><?php echo qf_e($option['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

            <?php elseif($type === 'rating'):
                $min = isset($settings['min']) ? (int)$settings['min'] : 1;
                $max = isset($settings['max']) ? (int)$settings['max'] : 5;
            ?>

                <div class="ef-rating-caption"><?php echo $min; ?> = lowest, <?php echo $max; ?> = highest</div>
                <div class="ef-rating" role="radiogroup" aria-labelledby="q-<?php echo $id; ?>-text">
                    <span class="ef-rating-end">Lowest</span>
                    <?php for($n = $min; $n <= $max; $n++): ?>
                        <label class="ef-rating-option">
                            <input type="radio" name="q[<?php echo $id; ?>]" value="<?php echo $n; ?>"<?php echo $checked($id, $n).$req; ?>>
                            <span><?php echo $n; ?></span>
                        </label>
                    <?php endfor; ?>
                    <span class="ef-rating-end">Highest</span>
                </div>

            <?php elseif($type === 'short_text'): ?>

                <input type="text" class="ef-text" name="q[<?php echo $id; ?>]" maxlength="255"
                       aria-labelledby="q-<?php echo $id; ?>-text"
                       value="<?php echo $text_value($id); ?>"<?php echo $req; ?>>

            <?php else: ?>

                <textarea class="ef-text ef-textarea" name="q[<?php echo $id; ?>]" rows="3" maxlength="5000"
                          aria-labelledby="q-<?php echo $id; ?>-text"<?php echo $req; ?>><?php echo $text_value($id); ?></textarea>

            <?php endif; ?>

            </div>
        </div>

        <?php endif; ?>

    <?php endforeach; ?>

</section>

<?php endforeach; ?>

    <label class="comments-title" for="ef-comments">Other comments/suggestions:</label>
    <textarea id="ef-comments" name="comments" class="comments-box" rows="4" maxlength="5000"><?php echo qf_e($old['comments'] ?? ''); ?></textarea>

    <div class="submit-area">
    <?php if($preview): ?>
        <button type="button" class="submit-btn" id="ef-try">Check My Answers</button>
        <p class="ef-preview-note" id="ef-try-result">In the preview, Submit only checks the answers. Nothing is saved.</p>
    <?php else: ?>
        <button type="submit" class="submit-btn">Submit Evaluation</button>
    <?php endif; ?>
    </div>

</form>

<div class="print-area">
    <button type="button" class="print-btn" onclick="window.print()">Print Form</button>
</div>

</div>

<script>
/* Small helpers for the form: works without them, but they make it kinder */
(function () {

    var form = document.getElementById('evaluationForm');

    if (!form) {
        return;
    }

    // A required "tick all that apply" question needs at least one tick
    function checkGroups() {
        var groups = form.querySelectorAll('[data-required-group]');
        Array.prototype.forEach.call(groups, function (group) {
            var boxes = group.querySelectorAll('input[type=checkbox]');
            var ticked = Array.prototype.some.call(boxes, function (box) { return box.checked; });
            if (boxes.length) {
                boxes[0].setCustomValidity(ticked ? '' : 'Please tick at least one answer.');
            }
        });
    }

    // Unanswered questions turn red when the form is sent, and clear when answered
    form.addEventListener('invalid', function (e) {
        var question = e.target.closest('.ef-q');
        if (question) {
            question.classList.add('ef-missing');
        }
    }, true);

    form.addEventListener('change', function (e) {
        checkGroups();
        var question = e.target.closest('.ef-q');
        if (question && e.target.checkValidity()) {
            question.classList.remove('ef-missing');
        }
    });

    form.addEventListener('submit', function (e) {
        checkGroups();
        if (form.getAttribute('data-preview')) {
            e.preventDefault();
            return;
        }
        var button = form.querySelector('.submit-btn');
        if (button) {
            // One submission only, even with a double tap
            setTimeout(function () { button.disabled = true; button.textContent = 'Submitting…'; }, 0);
        }
    });

    // The preview checks the answers instead of sending them
    var tryButton = document.getElementById('ef-try');
    if (tryButton) {
        tryButton.addEventListener('click', function () {
            checkGroups();
            var result = document.getElementById('ef-try-result');
            if (form.reportValidity()) {
                result.textContent = 'Every required question is answered. A real respondent could submit now.';
                result.className = 'ef-preview-note ef-preview-ok';
            } else {
                result.textContent = 'Some required questions are not answered yet. They are marked in red.';
                result.className = 'ef-preview-note ef-preview-missing';
            }
        });
    }

    checkGroups();
})();
</script>
	<?php
}
