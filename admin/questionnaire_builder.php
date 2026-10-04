<?php

/*
|--------------------------------------------------------------------------
| QUESTIONNAIRE BUILDER
|--------------------------------------------------------------------------
| Five steps: Basic Information, Sections & Criteria, Questions, Preview, and
| Review & Publish. A new questionnaire exists once step 1 is saved.
|
| Step 1 is drawn here. Steps 2 to 5 are drawn by
| assets/js/questionnaire-builder.js from ajax.php?action=qn_get, and every
| change goes through the qn_* actions, which check everything again.
|
|   index.php?page=questionnaire_builder              a new questionnaire
|   index.php?page=questionnaire_builder&copy=ID      a new one copied from ID
|   index.php?page=questionnaire_builder&id=ID&step=N an existing one
*/

require_once 'questionnaire_lib.php';

$steps = array(
    1 => array('Basic Information', 'fa-info-circle'),
    2 => array('Sections & Criteria', 'fa-layer-group'),
    3 => array('Questions', 'fa-list-ol'),
    4 => array('Preview', 'fa-desktop'),
    5 => array('Review & Publish', 'fa-paper-plane')
);

$asset_version = function($path){
    return @filemtime($path) ?: 1;
};

?>

<link rel="stylesheet" href="assets/css/questionnaire.css?v=<?php echo $asset_version('assets/css/questionnaire.css'); ?>">

<?php

if(!questionnaires_ready($conn)):
?>

<div class="qb-wrap">
    <div class="qb-banner qb-banner-info">
        <i class="fas fa-database"></i>
        <div>
            <b>One more step is needed.</b>
            Apply the latest database update to start building questionnaires.
            <a href="index.php?page=system_update">Open System Update</a>
        </div>
    </div>
</div>

<?php
    return;
endif;

$id = (int)($_GET['id'] ?? 0);
$questionnaire = null;

if($id > 0){
    $questionnaire = $conn->query("SELECT * FROM questionnaires WHERE id = $id")->fetch_assoc();

    if(!$questionnaire):
?>

<div class="qb-wrap">
    <div class="qb-banner qb-banner-warning">
        <i class="fas fa-search"></i>
        <div>
            <b>Questionnaire not found.</b>
            It may have been deleted.
            <a href="index.php?page=questionnaire">Back to Questionnaires</a>
        </div>
    </div>
</div>

<?php
        return;
    endif;
}

$step = (int)($_GET['step'] ?? 1);
$step = $questionnaire && isset($steps[$step]) ? $step : 1;

$editable = !$questionnaire || in_array($questionnaire['status'], array('draft', 'ready'), true);

// Starting a new questionnaire from a copy of another
$source = $questionnaire;
$copy_from = 0;

if(!$questionnaire && !empty($_GET['copy'])){
    $copy = $conn->query("SELECT * FROM questionnaires WHERE id = ".(int)$_GET['copy'])->fetch_assoc();
    if($copy){
        $source = $copy;
        $copy_from = (int)$copy['id'];
    }
}

$term = questionnaire_current_term();

$values = array(
    'title' => (string)($source['title'] ?? ''),
    'description' => (string)($source['description'] ?? ''),
    'instructions' => $source ? (string)$source['instructions'] : questionnaire_default_instructions(),
    'evaluation_type' => (string)($source['evaluation_type'] ?? 'project'),
    'target_respondent' => (string)($source['target_respondent'] ?? 'community'),
    'project_id' => (int)($source['project_id'] ?? 0),
    'academic_year' => $questionnaire ? (string)$questionnaire['academic_year'] : $term['academic_year'],
    'semester' => $questionnaire ? (string)$questionnaire['semester'] : $term['semester'],
    'start_date' => (string)($questionnaire['start_date'] ?? ''),
    'end_date' => (string)($questionnaire['end_date'] ?? '')
);

$scale = $source ? questionnaire_scale($source) : questionnaire_default_scale();

$years = questionnaire_year_choices($conn);

if($values['academic_year'] !== '' && !in_array($values['academic_year'], $years, true)){
    $years[] = $values['academic_year'];
    rsort($years);
}

$projects = array();
$result = $conn->query("SELECT id, title, academic_year, semester FROM projects ORDER BY created_at DESC, id DESC");
while($row = $result->fetch_assoc()){
    $projects[] = $row;
}

$copies = array();
if(!$questionnaire){
    $result = $conn->query("SELECT id, title, academic_year, semester, status FROM questionnaires ORDER BY id DESC");
    while($row = $result->fetch_assoc()){
        $copies[] = $row;
    }
}

$status_labels = questionnaire_status_labels();
$status = $questionnaire ? $questionnaire['status'] : 'new';

$selected = function($a, $b){
    return (string)$a === (string)$b ? ' selected' : '';
};

$disabled = $editable ? '' : ' disabled';

$link = function($to_step) use ($id){
    return "index.php?page=questionnaire_builder&id=$id&step=$to_step";
};

?>

<div class="qb-wrap" id="qb"
     data-id="<?php echo $id; ?>"
     data-step="<?php echo $step; ?>"
     data-status="<?php echo htmlspecialchars($status); ?>"
     data-editable="<?php echo $editable ? '1' : '0'; ?>">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="qb-head">

    <div class="qb-head-icon">
        <i class="fas fa-clipboard-list"></i>
    </div>

    <div class="qb-head-text">
        <a href="index.php?page=questionnaire" class="qb-back">
            <i class="fas fa-arrow-left"></i> Questionnaires
        </a>
        <h1>
            <?php echo $questionnaire ? htmlspecialchars($questionnaire['title']) : 'New Questionnaire'; ?>
        </h1>
        <p>
            <?php if($questionnaire): ?>
                <?php echo htmlspecialchars(trim($questionnaire['academic_year'].' '.$questionnaire['semester'])) ?: 'Academic year not set'; ?>
            <?php else: ?>
                Fill in the basic information to create it. You can add sections and questions next.
            <?php endif; ?>
        </p>
    </div>

    <?php if($questionnaire): ?>
    <span class="qb-status qb-status-<?php echo htmlspecialchars($status); ?>">
        <?php echo htmlspecialchars($status_labels[$status] ?? ucfirst($status)); ?>
    </span>
    <?php endif; ?>

</div>


<!-- =====================================================
     STEPS
===================================================== -->

<nav class="qb-steps" aria-label="Questionnaire steps">

    <?php foreach($steps as $number => $info): ?>

        <?php if($questionnaire): ?>
            <a href="<?php echo $link($number); ?>"
               class="qb-step<?php echo $number === $step ? ' current' : ''; ?>"
               data-step="<?php echo $number; ?>"
               <?php echo $number === $step ? 'aria-current="step"' : ''; ?>>
        <?php else: ?>
            <span class="qb-step<?php echo $number === $step ? ' current' : ' unavailable'; ?>" data-step="<?php echo $number; ?>">
        <?php endif; ?>

                <span class="qb-step-number">
                    <span class="qb-step-digit"><?php echo $number; ?></span>
                    <i class="fas fa-check qb-step-check"></i>
                </span>
                <span class="qb-step-label"><?php echo $info[0]; ?></span>

        <?php echo $questionnaire ? '</a>' : '</span>'; ?>

    <?php endforeach; ?>

</nav>

<!-- On a phone the steps are just numbers, so say where you are -->
<p class="qb-step-caption">
    Step <?php echo $step; ?> of <?php echo count($steps); ?>: <b><?php echo $steps[$step][0]; ?></b>
</p>


<?php if(!$editable): ?>

<div class="qb-banner qb-banner-locked">
    <i class="fas fa-lock"></i>
    <div>
        <b>
            <?php
            switch($status){
                case 'active': echo 'This questionnaire is already active and cannot be edited.'; break;
                case 'closed': echo 'This questionnaire is closed. Its questions can no longer be changed.'; break;
                default: echo 'This questionnaire is archived and read-only.';
            }
            ?>
        </b>
        Every response must answer the same questions, so they stay as they were published.
        To use different questions, make a copy and publish that instead.
    </div>
    <a href="index.php?page=questionnaire_builder&copy=<?php echo $id; ?>" class="qb-btn qb-btn-light">
        <i class="fas fa-copy"></i> Make a Copy
    </a>
</div>

<?php endif; ?>


<?php if($step === 1): ?>

<!-- =====================================================
     STEP 1: BASIC INFORMATION
===================================================== -->

<form id="qb-basic" class="qb-card" novalidate autocomplete="off">

    <input type="hidden" name="id" value="<?php echo $id ?: ''; ?>">
    <?php if($copy_from): ?>
        <input type="hidden" name="copy_from" value="<?php echo $copy_from; ?>">
    <?php endif; ?>

    <div class="qb-card-head">
        <div>
            <h3>Basic Information</h3>
            <p>Who the questionnaire is for, and when it is used. Fields marked <span class="qb-req-mark">*</span> are needed before publishing.</p>
        </div>
    </div>

    <?php if($copy_from): ?>
    <div class="qb-banner qb-banner-info qb-banner-inline">
        <i class="fas fa-copy"></i>
        <div>
            Copying <b><?php echo htmlspecialchars($source['title']); ?></b>.
            Its sections and questions are copied when you save. The original is not changed.
        </div>
    </div>
    <?php endif; ?>

    <div class="qb-alert" role="alert" hidden></div>

    <div class="form-row">

        <div class="form-group col-md-6">
            <label for="qb-year">Academic Year <span class="qb-req-mark">*</span></label>
            <select id="qb-year" name="academic_year" class="form-control" required<?php echo $disabled; ?>>
                <option value="">Choose the academic year</option>
                <?php foreach($years as $year): ?>
                    <option value="<?php echo htmlspecialchars($year); ?>"<?php echo $selected($year, $values['academic_year']); ?>>
                        <?php echo htmlspecialchars($year); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group col-md-6">
            <label for="qb-semester">Semester <span class="qb-req-mark">*</span></label>
            <select id="qb-semester" name="semester" class="form-control" required<?php echo $disabled; ?>>
                <option value="">Choose the semester</option>
                <?php foreach(questionnaire_semesters() as $semester): ?>
                    <option value="<?php echo $semester; ?>"<?php echo $selected($semester, $values['semester']); ?>><?php echo $semester; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>

    <div class="form-group">
        <label for="qb-title">Questionnaire Title <span class="qb-req-mark">*</span></label>
        <input type="text" id="qb-title" name="title" class="form-control" maxlength="255" required
               placeholder="e.g. Extension Project Evaluation"
               value="<?php echo htmlspecialchars($values['title']); ?>"<?php echo $disabled; ?>>
    </div>

    <div class="form-group">
        <label for="qb-description">Description <span class="qb-req-mark">*</span></label>
        <textarea id="qb-description" name="description" class="form-control" rows="3"
                  placeholder="What this questionnaire evaluates, e.g. the planning, delivery and impact of extension projects."<?php echo $disabled; ?>><?php echo htmlspecialchars($values['description']); ?></textarea>
    </div>

    <div class="form-row">

        <div class="form-group col-md-6">
            <label for="qb-type">Evaluation Type</label>
            <select id="qb-type" name="evaluation_type" class="form-control"<?php echo $disabled; ?>>
                <?php foreach(questionnaire_evaluation_types() as $key => $label): ?>
                    <option value="<?php echo $key; ?>"<?php echo $selected($key, $values['evaluation_type']); ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group col-md-6">
            <label for="qb-respondent">Target Respondents</label>
            <select id="qb-respondent" name="target_respondent" class="form-control"<?php echo $disabled; ?>>
                <?php foreach(questionnaire_respondents() as $key => $label): ?>
                    <option value="<?php echo $key; ?>"<?php echo $selected($key, $values['target_respondent']); ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>

    <div class="form-group">
        <label for="qb-project">Used For</label>
        <select id="qb-project" name="project_id" class="form-control"<?php echo $disabled; ?>>
            <option value="0">All extension activities in the chosen academic year and semester</option>
            <?php foreach($projects as $project): ?>
                <option value="<?php echo (int)$project['id']; ?>"<?php echo $selected($project['id'], $values['project_id']); ?>>
                    Only: <?php echo htmlspecialchars($project['title']); ?>
                    <?php if($project['academic_year']): ?>
                        (<?php echo htmlspecialchars(trim($project['academic_year'].' '.$project['semester'])); ?>)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">
            A questionnaire for one project is used for that project's activities instead of the general one.
        </small>
    </div>

    <div class="form-row">

        <div class="form-group col-md-6">
            <label for="qb-start">Evaluation Starts</label>
            <input type="date" id="qb-start" name="start_date" class="form-control"
                   value="<?php echo htmlspecialchars($values['start_date']); ?>"<?php echo $disabled; ?>>
        </div>

        <div class="form-group col-md-6">
            <label for="qb-end">Evaluation Ends</label>
            <input type="date" id="qb-end" name="end_date" class="form-control"
                   value="<?php echo htmlspecialchars($values['end_date']); ?>"<?php echo $disabled; ?>>
        </div>

    </div>
    <small class="form-text text-muted qb-under-row">
        Optional. Outside these dates the form tells respondents the evaluation is not open.
    </small>

    <div class="form-group">
        <label for="qb-instructions">Instructions for Respondents</label>
        <textarea id="qb-instructions" name="instructions" class="form-control" rows="3"<?php echo $disabled; ?>><?php echo htmlspecialchars($values['instructions']); ?></textarea>
    </div>

    <div class="form-group qb-scale-group">

        <label>Likert Scale</label>
        <p class="qb-hint">
            The answers for Likert questions, highest first. Values count down to 1.
        </p>

        <ol class="qb-scale" id="qb-scale">
            <?php foreach($scale as $position => $scale_step): ?>
                <li class="qb-scale-step">
                    <span class="qb-scale-value"><?php echo count($scale) - $position; ?></span>
                    <input type="text" name="scale[]" class="form-control" maxlength="120"
                           aria-label="Label for scale value <?php echo count($scale) - $position; ?>"
                           value="<?php echo htmlspecialchars($scale_step['label']); ?>"<?php echo $disabled; ?>>
                    <?php if($editable): ?>
                    <button type="button" class="qb-icon-btn qb-scale-remove" title="Remove this step" aria-label="Remove this step">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>

        <?php if($editable): ?>
        <div class="qb-scale-tools">
            <button type="button" class="qb-link-btn" id="qb-scale-add">
                <i class="fas fa-plus"></i> Add a step
            </button>
            <button type="button" class="qb-link-btn" id="qb-scale-reset">
                <i class="fas fa-undo"></i> Use the official ISU scale
            </button>
        </div>
        <?php endif; ?>

    </div>

    <?php if(!$questionnaire && !$copy_from && $copies): ?>
    <div class="form-group">
        <label for="qb-copy">Start From</label>
        <select id="qb-copy" name="copy_from" class="form-control">
            <option value="0">An empty questionnaire</option>
            <?php foreach($copies as $copy): ?>
                <option value="<?php echo (int)$copy['id']; ?>">
                    A copy of: <?php echo htmlspecialchars($copy['title']); ?>
                    (<?php echo htmlspecialchars(trim($copy['academic_year'].' '.$copy['semester']) ?: 'no term'); ?>,
                    <?php echo htmlspecialchars($status_labels[$copy['status']] ?? $copy['status']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">
            A copy brings every section and question with it, ready to change.
        </small>
    </div>
    <?php endif; ?>

    <div class="qb-actions">

        <a href="index.php?page=questionnaire" class="qb-btn qb-btn-light">Cancel</a>

        <?php if($editable): ?>
            <button type="submit" class="qb-btn qb-btn-light" data-then="stay">
                <i class="fas fa-save"></i> Save Draft
            </button>
            <button type="submit" class="qb-btn qb-btn-primary" data-then="next">
                Save and Continue <i class="fas fa-arrow-right"></i>
            </button>
        <?php else: ?>
            <a href="<?php echo $link(2); ?>" class="qb-btn qb-btn-primary">
                Next <i class="fas fa-arrow-right"></i>
            </a>
        <?php endif; ?>

    </div>

</form>

<?php else: ?>

<!-- =====================================================
     STEPS 2 TO 5: drawn by questionnaire-builder.js
===================================================== -->

<div id="qb-step-body" class="qb-step-body" aria-live="polite">
    <div class="qb-loading">
        <i class="fas fa-circle-notch fa-spin"></i> Loading the questionnaire&hellip;
    </div>
</div>

<div class="qb-actions qb-actions-nav">

    <a href="<?php echo $link($step - 1); ?>" class="qb-btn qb-btn-light">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <?php if($step < 5): ?>
        <a href="<?php echo $link($step + 1); ?>" class="qb-btn qb-btn-primary">
            Next: <?php echo $steps[$step + 1][0]; ?> <i class="fas fa-arrow-right"></i>
        </a>
    <?php endif; ?>

</div>

<?php endif; ?>

</div>


<!-- =====================================================
     SECTION DIALOG
===================================================== -->

<div class="modal fade qb-modal" id="qb-section-modal" tabindex="-1" role="dialog" aria-labelledby="qb-section-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" id="qb-section-form" novalidate>

            <div class="modal-header">
                <h5 class="modal-title" id="qb-section-modal-title">Add Section</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="qb-alert" role="alert" hidden></div>

                <input type="hidden" name="id">

                <div class="form-group">
                    <label for="qb-section-title">Section Title <span class="qb-req-mark">*</span></label>
                    <input type="text" id="qb-section-title" name="title" class="form-control" maxlength="255"
                           placeholder="e.g. Project Planning">
                </div>

                <div class="form-group">
                    <label for="qb-section-description">Description</label>
                    <textarea id="qb-section-description" name="description" class="form-control" rows="3"
                              placeholder="Optional. Shown under the section title."></textarea>
                </div>

                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="qb-section-active" name="is_active" checked>
                    <label class="custom-control-label" for="qb-section-active">Show this section to respondents</label>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="qb-btn qb-btn-light" data-dismiss="modal">Cancel</button>
                <button type="submit" class="qb-btn qb-btn-primary">Save Section</button>
            </div>

        </form>
    </div>
</div>


<!-- =====================================================
     QUESTION DIALOG
===================================================== -->

<div class="modal fade qb-modal" id="qb-question-modal" tabindex="-1" role="dialog" aria-labelledby="qb-question-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <form class="modal-content" id="qb-question-form" novalidate>

            <div class="modal-header">
                <h5 class="modal-title" id="qb-question-modal-title">Add Question</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="qb-alert" role="alert" hidden></div>

                <input type="hidden" name="id">

                <div class="form-row">

                    <div class="form-group col-md-7">
                        <label for="qb-question-section">Section</label>
                        <select id="qb-question-section" name="section_id" class="form-control"></select>
                    </div>

                    <div class="form-group col-md-5">
                        <label for="qb-question-type">Question Type</label>
                        <select id="qb-question-type" name="question_type" class="form-control">
                            <?php foreach(questionnaire_types() as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="form-group">
                    <label for="qb-question-text">Question <span class="qb-req-mark">*</span></label>
                    <textarea id="qb-question-text" name="question" class="form-control" rows="3"
                              placeholder="e.g. The objectives of the project were clearly defined."></textarea>
                </div>

                <p class="qb-type-help" id="qb-type-help"></p>

                <!-- What respondents answer with, for each type -->

                <div class="qb-config" data-types="likert">
                    <div class="qb-config-title">Answers (this questionnaire's Likert scale)</div>
                    <ol class="qb-config-scale" id="qb-config-scale"></ol>
                    <small class="text-muted">The scale is set in Basic Information and is the same for every Likert question.</small>
                </div>

                <div class="qb-config" data-types="single_choice multiple_choice">
                    <div class="qb-config-title">Options</div>
                    <ol class="qb-options" id="qb-options"></ol>
                    <button type="button" class="qb-link-btn" id="qb-option-add">
                        <i class="fas fa-plus"></i> Add an option
                    </button>
                </div>

                <div class="qb-config" data-types="yes_no">
                    <div class="qb-config-title">Answers</div>
                    <p class="mb-2">Yes &middot; No</p>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="qb-allow-na" name="allow_na">
                        <label class="custom-control-label" for="qb-allow-na">Also offer &ldquo;Not Applicable&rdquo;</label>
                    </div>
                </div>

                <div class="qb-config" data-types="rating">
                    <div class="qb-config-title">Rating range</div>
                    <div class="qb-range">
                        <label for="qb-rating-min">From</label>
                        <select id="qb-rating-min" name="rating_min" class="form-control">
                            <option value="0">0</option>
                            <option value="1" selected>1</option>
                        </select>
                        <label for="qb-rating-max">to</label>
                        <select id="qb-rating-max" name="rating_max" class="form-control">
                            <?php for($n = 2; $n <= 10; $n++): ?>
                                <option value="<?php echo $n; ?>"<?php echo $n === 5 ? ' selected' : ''; ?>><?php echo $n; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="qb-config" data-types="short_text long_text">
                    <div class="qb-config-title">Answers</div>
                    <p class="mb-0 text-muted">Respondents type their own answer.</p>
                </div>

                <hr>

                <div class="qb-switches">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="qb-question-required" name="is_required" checked>
                        <label class="custom-control-label" for="qb-question-required">Required</label>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="qb-question-active" name="is_active" checked>
                        <label class="custom-control-label" for="qb-question-active">Show to respondents</label>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="qb-btn qb-btn-light" data-dismiss="modal">Cancel</button>
                <button type="submit" class="qb-btn qb-btn-primary">Save Question</button>
            </div>

        </form>
    </div>
</div>


<script>
    // Names the builder shows, from questionnaire_lib.php
    window.QB_VOCAB = <?php echo json_encode(array(
        'evaluation_types' => questionnaire_evaluation_types(),
        'respondents' => questionnaire_respondents(),
        'statuses' => $status_labels,
        'official_scale' => questionnaire_default_scale()
    ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>
<script src="assets/js/questionnaire-status.js?v=<?php echo $asset_version('assets/js/questionnaire-status.js'); ?>"></script>
<script src="assets/js/questionnaire-builder.js?v=<?php echo $asset_version('assets/js/questionnaire-builder.js'); ?>"></script>
