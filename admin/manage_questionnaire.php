<?php
include 'db_connect.php';

/*
| Questions used to be managed here, per academic year. They are now built
| per questionnaire in the Questionnaire Builder, where a published
| questionnaire's questions are locked. Once the database update has been
| applied, this page sends you to the builder for that year's questionnaire.
*/
require_once 'questionnaire_lib.php';

if(questionnaires_ready($conn)){

    $academic_id = (int)($_GET['id'] ?? 0);

    $linked = $academic_id
        ? $conn->query("SELECT id FROM questionnaires WHERE academic_id = $academic_id ORDER BY id DESC LIMIT 1")->fetch_assoc()
        : null;

    $target = $linked
        ? 'index.php?page=questionnaire_builder&id='.(int)$linked['id'].'&step=3'
        : 'index.php?page=questionnaire';

    echo '<script>location.replace('.json_encode($target).');</script>';
    echo '<p><a href="'.htmlspecialchars($target).'">Questions are now managed in the Questionnaire Builder. Continue there.</a></p>';
    return;
}

if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM academic_list WHERE id = ".(int)$_GET['id'])->fetch_array();

    foreach($qry as $k => $v){
        $$k = $v;
    }
}

function ordinal_suffix($num){
    $num = $num % 100;

    if($num < 11 || $num > 13){

        switch($num % 10){

            case 1: return $num.'st';
            case 2: return $num.'nd';
            case 3: return $num.'rd';

        }

    }

    return $num.'th';
}

$q_arr = array();
?>

<div class="container-fluid questionnaire-manage">


<!-- PAGE INTRO -->
<div class="page-intro">

    <div>
        <div class="breadcrumb-mini">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
            <i class="fas fa-chevron-right"></i>
            <span>Questionnaires</span>
            <i class="fas fa-chevron-right"></i>
            <strong>Manage</strong>
        </div>

        <h2>
            Evaluation Questionnaire
        </h2>

        <p>
            Create and organize evaluation questions for
            <strong>
                <?php echo $year.' '.ordinal_suffix($semester); ?> Semester
            </strong>
        </p>
    </div>

    <div class="academic-pill">

        <div class="academic-pill-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>

        <div>
            <small>Academic Year</small>
            <strong>
                <?php echo $year; ?>
            </strong>
        </div>

    </div>

</div>


<div class="row">

    <div class="col-12">

        <div class="card modern-card questionnaire-card">


            <!-- HEADER -->

            <div class="questionnaire-header">

                <div class="questionnaire-title">

                    <div class="header-icon light">
                        <i class="fas fa-clipboard-list"></i>
                    </div>

                    <div>

                        <h4>
                            Questionnaire
                        </h4>

                        <p>
                            <?php echo $year.' '.ordinal_suffix($semester); ?> Semester
                        </p>

                    </div>

                </div>


                <div class="header-actions">

                    <button
                        type="button"
                        class="header-btn"
                        id="eval_restrict"
                    >

                        <i class="fas fa-lock"></i>
                        Restrictions

                    </button>


                    <button
                        type="submit"
                        form="order-question"
                        class="header-btn primary"
                    >

                        <i class="fas fa-sort"></i>
                        Save Order

                    </button>


                    <button
                        type="button"
                        class="header-btn primary"
                        id="new_question"
                    >

                        <i class="fas fa-plus"></i>
                        Add Question

                    </button>

                </div>

            </div>


            <!-- SUMMARY -->

            <?php

            $total_questions = $conn->query(
                "SELECT COUNT(*) AS total
                 FROM question_list
                 WHERE academic_id = $id"
            )->fetch_assoc()['total'];

            $total_criteria = $conn->query(
                "SELECT COUNT(DISTINCT criteria_id) AS total
                 FROM question_list
                 WHERE academic_id = $id"
            )->fetch_assoc()['total'];

            ?>

            <div class="question-summary">

                <div class="summary-item">

                    <div class="summary-icon">
                        <i class="fas fa-question"></i>
                    </div>

                    <div>

                        <strong>
                            <?php echo number_format($total_questions); ?>
                        </strong>

                        <span>
                            Total Questions
                        </span>

                    </div>

                </div>


                <div class="summary-divider"></div>


                <div class="summary-item">

                    <div class="summary-icon criteria">
                        <i class="fas fa-layer-group"></i>
                    </div>

                    <div>

                        <strong>
                            <?php echo number_format($total_criteria); ?>
                        </strong>

                        <span>
                            Criteria Used
                        </span>

                    </div>

                </div>


                <div class="summary-note">

                    <i class="fas fa-arrows-alt"></i>

                    Drag questions to change their order.

                </div>

            </div>


            <!-- BODY -->

            <div class="card-body questionnaire-body">


                <!-- RATING LEGEND -->

                <div class="rating-box">

                    <div class="rating-box-title">

                        <i class="fas fa-star"></i>

                        Rating Legend

                    </div>


                    <div class="rating-items">

                        <span class="rating-item excellent">
                            <b>5</b>
                            Strongly Agree
                        </span>

                        <span class="rating-item very-good">
                            <b>4</b>
                            Agree
                        </span>

                        <span class="rating-item good">
                            <b>3</b>
                            Slightly Agree
                        </span>

                        <span class="rating-item fair">
                            <b>2</b>
                            Disagree
                        </span>

                        <span class="rating-item poor">
                            <b>1</b>
                            Strongly Disagree
                        </span>

                    </div>

                </div>


                <form id="order-question">

                <?php

                $criteria = $conn->query(
                    "SELECT * FROM criteria_list
                     ORDER BY abs(order_by) ASC"
                );

                $criteria_number = 1;

                while($crow = $criteria->fetch_assoc()):

                    $questions = $conn->query(
                        "SELECT * FROM question_list
                         WHERE criteria_id = {$crow['id']}
                         AND academic_id = $id
                         ORDER BY abs(order_by) ASC"
                    );

                    $question_count = $questions->num_rows;

                ?>

                <!-- CRITERIA SECTION -->

                <div class="criteria-section">

                    <div class="criteria-heading">

                        <div class="criteria-number">
                            <?php echo $criteria_number++; ?>
                        </div>

                        <div class="criteria-heading-text">

                            <span>CRITERIA</span>

                            <h5>
                                <?php echo htmlspecialchars($crow['criteria']); ?>
                            </h5>

                        </div>

                        <div class="criteria-count">

                            <i class="fas fa-list"></i>

                            <?php echo $question_count; ?>

                            <?php echo ($question_count == 1) ? 'Question' : 'Questions'; ?>

                        </div>

                    </div>


                    <?php if($question_count > 0): ?>


                    <div class="question-list tr-sortable">


                    <?php

                    $question_number = 1;

                    while($row = $questions->fetch_assoc()):

                        $q_arr[$row['id']] = $row;

                    ?>

                    <!-- QUESTION ITEM -->

                    <div
                        class="question-item"
                        data-id="<?php echo $row['id']; ?>"
                    >


                        <div class="question-number">

                            <span>
                                <?php echo $question_number++; ?>
                            </span>

                        </div>


                        <div class="drag-handle">

                            <i class="fas fa-grip-vertical"></i>

                        </div>


                        <div class="question-content">

                            <div class="question-text">

                                <?php echo nl2br(htmlspecialchars($row['question'])); ?>

                            </div>


                            <input
                                type="hidden"
                                name="qid[]"
                                value="<?php echo $row['id']; ?>"
                            >


                            <div class="question-meta">

                                <span>
                                    <i class="fas fa-check-circle"></i>
                                    Required evaluation item
                                </span>

                            </div>

                        </div>


                        <div class="rating-preview">

                            <span>5</span>
                            <span>4</span>
                            <span>3</span>
                            <span>2</span>
                            <span>1</span>

                        </div>


                        <div class="question-actions">

                            <button
                                type="button"
                                class="question-action edit_question"
                                data-id="<?php echo $row['id']; ?>"
                                title="Edit Question"
                            >

                                <i class="fas fa-pen"></i>

                            </button>


                            <button
                                type="button"
                                class="question-action delete_question danger"
                                data-id="<?php echo $row['id']; ?>"
                                title="Delete Question"
                            >

                                <i class="fas fa-trash"></i>

                            </button>

                        </div>


                    </div>

                    <?php endwhile; ?>


                    </div>


                    <?php else: ?>


                    <div class="empty-questions">

                        <div class="empty-icon">
                            <i class="fas fa-clipboard"></i>
                        </div>

                        <strong>
                            No questions yet
                        </strong>

                        <p>
                            Add a question using the Add Question button.
                        </p>

                    </div>


                    <?php endif; ?>

                </div>

                <?php endwhile; ?>

                </form>


            </div>


            <!-- FOOTER -->

            <div class="questionnaire-footer">

                <div>

                    <i class="fas fa-info-circle"></i>

                    Questions are grouped according to their evaluation criteria.

                </div>


                <button
                    type="button"
                    class="btn bottom-save"
                    onclick="$('#order-question').submit();"
                >

                    <i class="fas fa-save"></i>

                    Save Question Order

                </button>

            </div>


        </div>

    </div>

</div>


</div>

<!-- ================================================= -->
<!-- ADD / EDIT QUESTION MODAL -->
<!-- ================================================= -->

<div
    class="modal fade question-modal"
    id="question-modal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="question-modal-title"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">

        <div class="modal-content modern-card form-card">

            <div class="modern-header">

                <div class="header-icon">
                    <i class="fas fa-plus"></i>
                </div>

                <div>

                    <h4 id="question-modal-title">
                        Add Question
                    </h4>

                    <p id="question-modal-subtitle">
                        Create a new evaluation question
                    </p>

                </div>

                <button
                    type="button"
                    class="close question-modal-close"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>


            <div class="modal-body card-body">

                <form action="" id="manage-question">

                    <input
                        type="hidden"
                        name="academic_id"
                        value="<?php echo isset($id) ? $id : ''; ?>"
                    >

                    <input
                        type="hidden"
                        name="id"
                    >


                    <!-- CRITERIA -->

                    <div class="form-section">

                        <label class="modern-label">

                            <span>
                                <i class="fas fa-layer-group"></i>
                                Evaluation Criteria
                            </span>

                            <small>Required</small>

                        </label>


                        <select
                            name="criteria_id"
                            id="criteria_id"
                            class="custom-select select2"
                        >

                            <option value=""></option>

                            <?php

                            $criteria = $conn->query(
                                "SELECT * FROM criteria_list
                                 ORDER BY abs(order_by) ASC"
                            );

                            while($row = $criteria->fetch_assoc()):

                            ?>

                                <option value="<?php echo $row['id']; ?>">

                                    <?php echo htmlspecialchars($row['criteria']); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                        <div class="field-hint">
                            <i class="fas fa-info-circle"></i>
                            Select the category where this question belongs.
                        </div>

                    </div>


                    <!-- QUESTION -->

                    <div class="form-section">

                        <label class="modern-label">

                            <span>
                                <i class="fas fa-question-circle"></i>
                                Question
                            </span>

                            <small>Required</small>

                        </label>


                        <textarea
                            name="question"
                            id="question"
                            class="form-control modern-textarea"
                            rows="6"
                            maxlength="500"
                            placeholder="Enter your evaluation question here..."
                            required><?php echo isset($question) ? htmlspecialchars($question) : ''; ?></textarea>


                        <div class="textarea-footer">

                            <span>
                                <i class="fas fa-pen"></i>
                                Write a clear and measurable question.
                            </span>

                            <span id="questionCounter">
                                0 / 500
                            </span>

                        </div>

                    </div>

                </form>

            </div>


            <!-- FORM FOOTER -->

            <div class="card-footer form-footer">

                <button
                    type="button"
                    class="btn cancel-btn"
                    data-dismiss="modal"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    class="btn save-btn"
                    form="manage-question"
                >

                    <i class="fas fa-save"></i>
                    Save Question

                </button>

            </div>

        </div>

    </div>

</div>


<style>

/* =====================================================
   MAIN
===================================================== */

.questionnaire-manage{
    padding: 10px 5px 40px;
}


/* =====================================================
   PAGE INTRO
===================================================== */

.page-intro{

    display:flex;
    justify-content:space-between;
    align-items:center;

    margin-bottom:22px;

    padding:5px 5px 0;
}


.breadcrumb-mini{

    display:flex;
    align-items:center;
    gap:8px;

    font-size:12px;
    color:#9ca3af;

    margin-bottom:8px;
}


.breadcrumb-mini i{
    font-size:9px;
}


.breadcrumb-mini strong{
    color:#047857;
}


.page-intro h2{

    margin:0;

    color:#064e3b;

    font-size:26px;

    font-weight:750;
}


.page-intro p{

    margin:5px 0 0;

    color:#6b7280;

    font-size:14px;
}


.page-intro p strong{
    color:#047857;
}


/* =====================================================
   ACADEMIC PILL
===================================================== */

.academic-pill{

    display:flex;
    align-items:center;
    gap:12px;

    background:white;

    border:1px solid #d1fae5;

    border-radius:14px;

    padding:10px 16px;

    box-shadow:0 5px 15px rgba(0,0,0,.05);
}


.academic-pill-icon{

    width:42px;
    height:42px;

    border-radius:12px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#ecfdf5;

    color:#059669;

    font-size:18px;
}


.academic-pill small{

    display:block;

    color:#9ca3af;

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.5px;
}


.academic-pill strong{

    display:block;

    color:#064e3b;

    font-size:15px;

}


/* =====================================================
   CARDS
===================================================== */

.modern-card{

    border:none!important;

    border-radius:20px!important;

    overflow:hidden;

    background:white;

    box-shadow:
        0 8px 25px rgba(0,0,0,.07);

    margin-bottom:20px;
}


/* =====================================================
   LEFT HEADER
===================================================== */

.modern-header{

    background:
        linear-gradient(
            135deg,
            #10b981,
            #047857
        );

    color:white;

    padding:20px 22px;

    display:flex;

    align-items:center;

    gap:14px;
}


.modern-header h4{

    margin:0;

    font-size:18px;

    font-weight:700;
}


.modern-header p{

    margin:4px 0 0;

    font-size:12px;

    opacity:.85;
}


.header-icon{

    width:44px;
    height:44px;

    border-radius:13px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:rgba(255,255,255,.18);

    font-size:18px;

    flex-shrink:0;
}


.header-icon.light{

    background:rgba(255,255,255,.16);
}


/* =====================================================
   FORM
===================================================== */

.form-card .card-body{
    padding:25px;
}


.form-section{
    margin-bottom:24px;
}


.modern-label{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:9px;

    color:#374151;

    font-size:13px;

    font-weight:700;
}


.modern-label span i{

    color:#10b981;

    margin-right:6px;
}


.modern-label small{

    color:#ef4444;

    font-size:10px;

    text-transform:uppercase;
}


.select2-container--default
.select2-selection--single{

    border:1px solid #d1d5db!important;

    border-radius:11px!important;

    height:45px!important;

    padding:8px 10px!important;
}


.select2-container--default
.select2-selection--single
.select2-selection__rendered{

    line-height:27px!important;

    color:#374151!important;
}


.select2-container--default
.select2-selection--single
.select2-selection__arrow{

    top:9px!important;
}


.modern-textarea{

    resize:none;

    border:1px solid #d1d5db;

    border-radius:12px;

    padding:13px 14px;

    font-size:14px;

    line-height:1.6;

    transition:.2s;

    box-shadow:none!important;
}


.modern-textarea:focus{

    border-color:#10b981;

    box-shadow:
        0 0 0 3px rgba(16,185,129,.10)!important;
}


.modern-textarea::placeholder{
    color:#c0c5cc;
}


.field-hint{

    margin-top:7px;

    font-size:11px;

    color:#9ca3af;
}


.field-hint i{
    color:#10b981;
}


.textarea-footer{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-top:7px;

    font-size:10px;

    color:#9ca3af;
}


.textarea-footer i{
    color:#10b981;
}


/* =====================================================
   FORM FOOTER
===================================================== */

.form-footer{

    background:#fafafa;

    border-top:1px solid #f1f5f9;

    padding:15px 22px;

    display:flex;

    justify-content:flex-end;

    gap:8px;
}


.save-btn{

    background:#059669;

    color:white;

    border:none;

    border-radius:10px;

    padding:10px 18px;

    font-weight:600;

    transition:.2s;
}


.save-btn:hover{

    background:#047857;

    color:white;

    transform:translateY(-1px);
}


.cancel-btn{

    background:#f3f4f6;

    color:#6b7280;

    border:none;

    border-radius:10px;

    padding:10px 15px;

    font-weight:600;
}


.cancel-btn:hover{

    background:#e5e7eb;

    color:#374151;
}


/* =====================================================
   QUICK TIP
===================================================== */

.quick-tip{

    display:flex;

    align-items:flex-start;

    gap:12px;

    background:white;

    border-radius:16px;

    padding:16px;

    border:1px solid #d1fae5;

    box-shadow:0 5px 15px rgba(0,0,0,.04);
}


.tip-icon{

    width:35px;
    height:35px;

    border-radius:10px;

    background:#fef3c7;

    color:#d97706;

    display:flex;

    align-items:center;
    justify-content:center;
}


.quick-tip strong{

    color:#374151;

    font-size:12px;
}


.quick-tip p{

    margin:3px 0 0;

    color:#9ca3af;

    font-size:11px;

    line-height:1.5;
}


/* =====================================================
   QUESTIONNAIRE HEADER
===================================================== */

.questionnaire-header{

    background:
        linear-gradient(
            135deg,
            #10b981,
            #047857
        );

    color:white;

    padding:20px 22px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;
}


.questionnaire-title{

    display:flex;

    align-items:center;

    gap:12px;
}


.questionnaire-title h4{

    margin:0;

    font-size:19px;

    font-weight:700;
}


.questionnaire-title p{

    margin:3px 0 0;

    font-size:12px;

    opacity:.8;
}


.header-actions{

    display:flex;

    gap:7px;

    flex-wrap:wrap;

    justify-content:flex-end;
}


.header-btn{

    border:none;

    border-radius:9px;

    padding:9px 13px;

    background:rgba(255,255,255,.15);

    color:white;

    font-size:12px;

    font-weight:600;

    transition:.2s;
}


.header-btn:hover{

    background:rgba(255,255,255,.25);

    color:white;
}


.header-btn.primary{

    background:white;

    color:#047857;
}


.header-btn.primary:hover{

    background:#ecfdf5;

    color:#047857;
}


/* =====================================================
   ADD / EDIT QUESTION MODAL
===================================================== */

.question-modal .modal-content{

    margin-bottom:0;

    box-shadow:
        0 20px 45px rgba(0,0,0,.18);

}


.question-modal-close{

    margin-left:auto;

    align-self:flex-start;

    color:white;

    opacity:.85;

    text-shadow:none;

}


.question-modal-close:hover{

    color:white;

    opacity:1;

}


/* =====================================================
   SUMMARY
===================================================== */

.question-summary{

    display:flex;

    align-items:center;

    gap:20px;

    padding:16px 22px;

    border-bottom:1px solid #f1f5f9;

    background:#fff;
}


.summary-item{

    display:flex;

    align-items:center;

    gap:10px;
}


.summary-icon{

    width:38px;
    height:38px;

    border-radius:10px;

    background:#ecfdf5;

    color:#059669;

    display:flex;

    align-items:center;
    justify-content:center;
}


.summary-icon.criteria{

    background:#eff6ff;

    color:#3b82f6;
}


.summary-item strong{

    display:block;

    color:#064e3b;

    font-size:18px;

    line-height:1;
}


.summary-item span{

    display:block;

    color:#9ca3af;

    font-size:10px;

    margin-top:4px;
}


.summary-divider{

    height:35px;

    width:1px;

    background:#e5e7eb;
}


.summary-note{

    margin-left:auto;

    color:#9ca3af;

    font-size:10px;
}


.summary-note i{

    color:#10b981;

    margin-right:4px;
}


/* =====================================================
   QUESTIONNAIRE BODY
===================================================== */

.questionnaire-body{

    padding:22px;
}


/* =====================================================
   RATING BOX
===================================================== */

.rating-box{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    background:#f8fafc;

    border:1px solid #e5e7eb;

    border-radius:14px;

    padding:13px 15px;

    margin-bottom:22px;
}


.rating-box-title{

    color:#374151;

    font-weight:700;

    font-size:12px;

    white-space:nowrap;
}


.rating-box-title i{

    color:#f59e0b;

    margin-right:5px;
}


.rating-items{

    display:flex;

    flex-wrap:wrap;

    justify-content:flex-end;

    gap:6px;
}


.rating-item{

    padding:5px 9px;

    border-radius:7px;

    font-size:9px;

    background:white;

    border:1px solid #e5e7eb;

    color:#6b7280;
}


.rating-item b{

    margin-right:3px;

    color:#047857;
}


/* =====================================================
   CRITERIA
===================================================== */

.criteria-section{

    margin-bottom:24px;

    border:1px solid #e5e7eb;

    border-radius:15px;

    overflow:hidden;

    background:white;
}


.criteria-heading{

    display:flex;

    align-items:center;

    gap:11px;

    padding:13px 15px;

    background:
        linear-gradient(
            135deg,
            #ecfdf5,
            #f0fdf4
        );

    border-bottom:1px solid #d1fae5;
}


.criteria-number{

    width:34px;
    height:34px;

    border-radius:10px;

    background:#059669;

    color:white;

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:13px;

    font-weight:700;
}


.criteria-heading-text{

    flex:1;
}


.criteria-heading-text span{

    display:block;

    color:#10b981;

    font-size:8px;

    font-weight:700;

    letter-spacing:1px;
}


.criteria-heading-text h5{

    margin:2px 0 0;

    color:#064e3b;

    font-size:14px;

    font-weight:700;
}


.criteria-count{

    color:#047857;

    background:white;

    border:1px solid #d1fae5;

    border-radius:20px;

    padding:5px 10px;

    font-size:9px;

    font-weight:600;
}


.criteria-count i{
    margin-right:3px;
}


/* =====================================================
   QUESTION LIST
===================================================== */

.question-list{

    padding:8px;
}


.question-item{

    display:flex;

    align-items:center;

    gap:10px;

    padding:13px;

    border-radius:11px;

    border:1px solid transparent;

    transition:.2s;

    margin-bottom:5px;

    background:white;
}


.question-item:hover{

    border-color:#d1fae5;

    background:#fafffc;

    box-shadow:
        0 4px 12px rgba(16,185,129,.07);
}


.question-item:last-child{
    margin-bottom:0;
}


.question-number{

    width:28px;

    flex-shrink:0;

    display:flex;

    justify-content:center;
}


.question-number span{

    width:25px;
    height:25px;

    border-radius:7px;

    display:flex;

    align-items:center;
    justify-content:center;

    background:#f3f4f6;

    color:#6b7280;

    font-size:10px;

    font-weight:700;
}


.drag-handle{

    color:#cbd5e1;

    cursor:grab;

    font-size:14px;

    padding:5px;
}


.drag-handle:active{
    cursor:grabbing;
}


.question-content{

    flex:1;

    min-width:0;
}


.question-text{

    color:#374151;

    font-size:13px;

    line-height:1.55;

    font-weight:500;

    word-break:break-word;
}


.question-meta{

    margin-top:5px;

    font-size:9px;

    color:#9ca3af;
}


.question-meta i{

    color:#10b981;

    margin-right:3px;
}


/* =====================================================
   RATING PREVIEW
===================================================== */

.rating-preview{

    display:flex;

    gap:4px;

    flex-shrink:0;
}


.rating-preview span{

    width:25px;
    height:25px;

    border-radius:7px;

    display:flex;

    align-items:center;
    justify-content:center;

    background:#f8fafc;

    border:1px solid #e5e7eb;

    color:#9ca3af;

    font-size:9px;

    font-weight:700;
}


.rating-preview span:first-child{

    background:#ecfdf5;

    color:#059669;

    border-color:#bbf7d0;
}


/* =====================================================
   QUESTION ACTIONS
===================================================== */

.question-actions{

    display:flex;

    gap:5px;

    flex-shrink:0;
}


.question-action{

    width:31px;
    height:31px;

    border:none;

    border-radius:8px;

    background:#eff6ff;

    color:#3b82f6;

    display:flex;

    align-items:center;
    justify-content:center;

    cursor:pointer;

    transition:.2s;
}


.question-action:hover{

    background:#dbeafe;

    transform:translateY(-1px);
}


.question-action.danger{

    background:#fef2f2;

    color:#ef4444;
}


.question-action.danger:hover{

    background:#fee2e2;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-questions{

    text-align:center;

    padding:28px 15px;

    color:#9ca3af;
}


.empty-icon{

    width:50px;
    height:50px;

    margin:0 auto 10px;

    border-radius:14px;

    background:#f0fdf4;

    color:#10b981;

    display:flex;

    align-items:center;
    justify-content:center;

    font-size:20px;
}


.empty-questions strong{

    display:block;

    color:#6b7280;

    font-size:13px;
}


.empty-questions p{

    margin:4px 0 0;

    font-size:11px;
}


/* =====================================================
   FOOTER
===================================================== */

.questionnaire-footer{

    border-top:1px solid #f1f5f9;

    background:#fafafa;

    padding:13px 20px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:10px;

    color:#9ca3af;

    font-size:10px;
}


.questionnaire-footer i{

    color:#10b981;

    margin-right:4px;
}


.bottom-save{

    background:#059669;

    color:white;

    border:none;

    border-radius:9px;

    padding:8px 13px;

    font-size:11px;

    font-weight:600;
}


.bottom-save:hover{

    background:#047857;

    color:white;
}


/* =====================================================
   SORTABLE
===================================================== */

.ui-sortable-helper{

    box-shadow:
        0 10px 25px rgba(0,0,0,.12)!important;

    background:white!important;

    border-color:#10b981!important;
}


.ui-sortable-placeholder{

    visibility:visible!important;

    background:#ecfdf5!important;

    border:2px dashed #86efac!important;

    border-radius:11px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:991px){

    .page-intro{

        align-items:flex-start;

        gap:15px;

        flex-direction:column;
    }


    .academic-pill{
        width:100%;
    }


    .questionnaire-header{

        align-items:flex-start;

        flex-direction:column;
    }


    .header-actions{

        width:100%;

        justify-content:flex-start;
    }

}


@media(max-width:767px){

    .question-summary{

        flex-wrap:wrap;
    }


    .summary-note{

        width:100%;

        margin-left:0;
    }


    .rating-box{

        align-items:flex-start;

        flex-direction:column;
    }


    .rating-items{

        justify-content:flex-start;
    }


    .rating-preview{

        display:none;
    }


    .criteria-count{

        display:none;
    }


    .questionnaire-footer{

        align-items:flex-start;

        flex-direction:column;
    }

}


@media(max-width:500px){

    .page-intro h2{
        font-size:21px;
    }


    .questionnaire-body{
        padding:12px;
    }


    .question-item{
        padding:10px;
    }


    .drag-handle{
        display:none;
    }


    .question-actions{
        flex-direction:column;
    }

}

</style>

<script>

$(document).ready(function(){

    /* ==========================================
       SELECT2
    ========================================== */

    // Move the modal to <body> so the page's cards can't stack it under the backdrop

    $('#question-modal').appendTo('body');


    $('.select2').select2({

        placeholder:"Please select here",

        width:"100%",

        // Inside the modal, or its search box can't take focus

        dropdownParent:$('#question-modal')

    });


    /* ==========================================
       QUESTION COUNTER
    ========================================== */

    function updateCounter(){

        var length = $('#question').val().length;

        $('#questionCounter').text(
            length + ' / 500'
        );

    }


    $('#question').on('input',function(){

        updateCounter();

    });


    updateCounter();


    /* ==========================================
       ADD / EDIT QUESTION MODAL
    ========================================== */

    function open_question_modal(data){

        var form = $('#manage-question');


        form[0].reset();


        form
            .find("[name='id']")
            .val(data ? data.id : '');


        form
            .find("[name='question']")
            .val(data ? data.question : '');


        $('#criteria_id')
            .val(data ? data.criteria_id : '')
            .trigger('change');


        updateCounter();


        $('#question-modal .header-icon').html(
            data
                ? '<i class="fas fa-edit"></i>'
                : '<i class="fas fa-plus"></i>'
        );


        $('#question-modal-title').text(
            data ? 'Edit Question' : 'Add Question'
        );


        $('#question-modal-subtitle').text(
            data
                ? 'Update this evaluation question'
                : 'Create a new evaluation question'
        );


        $('#question-modal .save-btn').html(
            '<i class="fas fa-save"></i> ' +
            (data ? 'Update Question' : 'Save Question')
        );


        $('#question-modal').modal('show');

    }


    $('#question-modal').on('shown.bs.modal',function(){

        $('#question').trigger('focus');

    });


    $('#new_question').click(function(){

        open_question_modal(null);

    });


    /* ==========================================
       EDIT QUESTION
    ========================================== */

    $('.edit_question').click(function(){

        var id = $(this).attr('data-id');

        var question = <?php echo json_encode($q_arr); ?>;


        if(question[id]){

            open_question_modal(question[id]);

        }

    });


    /* ==========================================
       SAVE QUESTION
    ========================================== */

    $('#manage-question').submit(function(e){

        e.preventDefault();

        start_load();


        if($('#criteria_id').val() == ''){

            alert_toast(
                "Please select an evaluation criteria first",
                'error'
            );

            end_load();

            return false;
        }


        if($.trim($('#question').val()) == ''){

            alert_toast(
                "Please enter the question first",
                'error'
            );

            end_load();

            return false;
        }


        $.ajax({

            url:'ajax.php?action=save_question',

            data:new FormData($(this)[0]),

            cache:false,

            contentType:false,

            processData:false,

            method:'POST',

            type:'POST',

            success:function(resp){

                if(resp == 1){

                    $('#question-modal').modal('hide');

                    alert_toast(
                        'Question successfully saved',
                        'success'
                    );


                    setTimeout(function(){

                        location.reload();

                    },1000);

                }else{

                    alert_toast(
                        'Unable to save question',
                        'error'
                    );

                    end_load();

                }

            },

            error:function(){

                alert_toast(
                    'An error occurred while saving',
                    'error'
                );

                end_load();

            }

        });

    });


    /* ==========================================
       SAVE ORDER
    ========================================== */

    $('#order-question').submit(function(e){

        e.preventDefault();

        start_load();


        $.ajax({

            url:'ajax.php?action=save_question_order',

            data:new FormData($(this)[0]),

            cache:false,

            contentType:false,

            processData:false,

            method:'POST',

            type:'POST',

            success:function(resp){

                if(resp == 1){

                    alert_toast(
                        'Question order successfully saved',
                        'success'
                    );

                    end_load();

                }else{

                    alert_toast(
                        'Unable to save question order',
                        'error'
                    );

                    end_load();

                }

            },

            error:function(){

                alert_toast(
                    'An error occurred while saving order',
                    'error'
                );

                end_load();

            }

        });

    });


    /* ==========================================
       DELETE QUESTION
    ========================================== */

    $('.delete_question').click(function(){

        _conf(
            "Are you sure you want to delete this question?",
            "delete_question",
            [$(this).attr('data-id')]
        );

    });


    /* ==========================================
       DELETE FUNCTION
    ========================================== */

    window.delete_question = function($id){

        start_load();

        $.ajax({

            url:'ajax.php?action=delete_question',

            method:'POST',

            data:{
                id:$id
            },

            success:function(resp){

                if(resp == 1){

                    alert_toast(
                        "Question successfully deleted",
                        'success'
                    );


                    setTimeout(function(){

                        location.reload();

                    },1000);

                }else{

                    alert_toast(
                        "Unable to delete question",
                        'error'
                    );

                    end_load();

                }

            },

            error:function(){

                alert_toast(
                    "An error occurred while deleting",
                    'error'
                );

                end_load();

            }

        });

    };


    /* ==========================================
       RESTRICTIONS
    ========================================== */

    $('#eval_restrict').click(function(){

        uni_modal(
            "Manage Evaluation Restrictions",
            "<?php echo $_SESSION['login_view_folder']; ?>manage_restriction.php?id=<?php echo $id; ?>",
            "mid-large"
        );

    });


    /* ==========================================
       DRAG AND DROP
    ========================================== */

    $('.tr-sortable').sortable({

        handle:'.drag-handle',

        placeholder:'ui-sortable-placeholder',

        tolerance:'pointer',

        cursor:'grabbing',

        opacity:.85

    });


});

</script>
