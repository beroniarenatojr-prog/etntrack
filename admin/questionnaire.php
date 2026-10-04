<?php include 'db_connect.php' ?>

<?php

/*
| QUESTIONNAIRES
| Every questionnaire with its academic year, how many questions and
| responses it has, and where it is in its lifecycle. Each row's menu offers
| only what that status allows, and the server checks again on every action.
*/

require_once 'questionnaire_lib.php';

$questionnaires_ready = questionnaires_ready($conn);

$total_years = 0;
$total_questions = 0;
$total_responses = 0;
$total_active = 0;
$questionnaires = array();

if($questionnaires_ready){

    $total_years = (int)$conn->query("
        SELECT COUNT(DISTINCT academic_year) AS c FROM questionnaires
        WHERE academic_year IS NOT NULL AND academic_year <> ''
    ")->fetch_assoc()['c'];

    $total_questions = (int)$conn->query("
        SELECT COUNT(*) AS c FROM question_list WHERE questionnaire_id IS NOT NULL AND is_active = 1
    ")->fetch_assoc()['c'];

    $total_responses = (int)$conn->query("SELECT COUNT(*) AS c FROM evaluation_responses")->fetch_assoc()['c'];

    $total_active = (int)$conn->query("SELECT COUNT(*) AS c FROM questionnaires WHERE status = 'active'")->fetch_assoc()['c'];

    $result = $conn->query("
        SELECT q.*, p.title AS project_title,
            (SELECT COUNT(*) FROM question_list l WHERE l.questionnaire_id = q.id AND l.is_active = 1) AS questions,
            (SELECT COUNT(*) FROM evaluation_responses r WHERE r.questionnaire_id = q.id) AS responses
        FROM questionnaires q
        LEFT JOIN projects p ON p.id = q.project_id
        ORDER BY FIELD(q.status, 'active', 'ready', 'draft', 'closed', 'archived'),
            q.academic_year DESC, q.id DESC
    ");

    while($row = $result->fetch_assoc()){
        $questionnaires[] = $row;
    }
}

$evaluation_types = questionnaire_evaluation_types();

// How each status looks: label, badge class, icon
$status_view = array(
    'draft' => array('Draft', 'status-draft', 'fa-pen'),
    'ready' => array('Ready', 'status-ready', 'fa-check'),
    'active' => array('Active', 'status-ongoing', 'fa-play'),
    'closed' => array('Closed', 'status-closed', 'fa-lock'),
    'archived' => array('Archived', 'status-archived', 'fa-archive')
);

/*
| What each status allows, in menu order. Links open a page; the others
| change the status (or delete) after a confirmation.
*/
$menus = array(
    'draft' => array('edit', 'questions', 'preview', 'ready', 'delete'),
    'ready' => array('edit', 'questions', 'preview', 'publish', 'draft', 'delete'),
    'active' => array('view', 'preview', 'responses', 'results', 'close'),
    'closed' => array('view', 'responses', 'results', 'archive'),
    'archived' => array('view', 'results')
);

$menu_items = array(
    'edit'      => array('fa-edit', 'edit-icon', 'Edit', 'Title, period and scale', 'link', 1),
    'questions' => array('fa-list-ol', 'edit-icon', 'Manage Questions', 'Sections and questions', 'link', 3),
    'view'      => array('fa-eye', 'edit-icon', 'View', 'Read-only', 'link', 1),
    'preview'   => array('fa-desktop', 'edit-icon', 'Preview', 'As respondents see it', 'link', 4),
    'responses' => array('fa-users', 'edit-icon', 'View Responses', 'Who has answered', 'results', null),
    'results'   => array('fa-chart-bar', 'edit-icon', 'View Results', 'Scores and comments', 'results', null),
    'ready'     => array('fa-check-circle', 'edit-icon', 'Mark Ready', 'Checks it can be published', 'status', 'ready'),
    'publish'   => array('fa-paper-plane', 'edit-icon', 'Publish', 'Open it for responses', 'status', 'active'),
    'draft'     => array('fa-undo', 'edit-icon', 'Back to Draft', 'Keep working on it', 'status', 'draft'),
    'close'     => array('fa-lock', 'edit-icon', 'Close Evaluation', 'Stop new responses', 'status', 'closed'),
    'archive'   => array('fa-archive', 'edit-icon', 'Archive', 'Keep it read-only', 'status', 'archived'),
    'delete'    => array('fa-trash-alt', 'delete-icon', 'Delete', 'Remove this draft', 'delete', null)
);

?>



<div class="questionnaire-wrapper">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="question-page-header">

    <div class="page-title-area">

        <div class="page-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>

        <div>

            <h2>
                Questionnaire Management
            </h2>

            <p>
                Build, publish and close the evaluation questionnaires used for extension projects.
            </p>

        </div>

    </div>

    <?php if($questionnaires_ready): ?>
    <a href="index.php?page=questionnaire_builder" class="btn add-question-btn">
        <i class="fas fa-plus"></i> New Questionnaire
    </a>
    <?php endif; ?>

</div>



<!-- =====================================================
     SUMMARY CARDS
===================================================== -->

<div class="row summary-row">

    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="summary-card">
            <div class="summary-icon green"><i class="fas fa-calendar-alt"></i></div>
            <div class="summary-content">
                <span>Academic Years</span>
                <strong><?php echo number_format($total_years); ?></strong>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="summary-card">
            <div class="summary-icon blue"><i class="fas fa-question-circle"></i></div>
            <div class="summary-content">
                <span>Total Questions</span>
                <strong><?php echo number_format($total_questions); ?></strong>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="summary-card">
            <div class="summary-icon purple"><i class="fas fa-check-circle"></i></div>
            <div class="summary-content">
                <span>Evaluation Responses</span>
                <strong><?php echo number_format($total_responses); ?></strong>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="summary-card">
            <div class="summary-icon green"><i class="fas fa-broadcast-tower"></i></div>
            <div class="summary-content">
                <span>Active Questionnaires</span>
                <strong><?php echo number_format($total_active); ?></strong>
            </div>
        </div>
    </div>

</div>



<!-- =====================================================
     TABS
     The questionnaires, and the criteria their sections can be
     built from. Both used to be separate menu items.
===================================================== -->

<div class="qn-tabs" role="tablist">

    <button type="button" class="qn-tab active" data-panel="questionnaires">
        <i class="fas fa-file-alt"></i> Questionnaires
    </button>

    <button type="button" class="qn-tab" data-panel="criteria">
        <i class="fas fa-sliders-h"></i> Evaluation Criteria
    </button>

</div>


<div class="qn-panel active" data-panel="questionnaires">


<div class="card questionnaire-card">

    <div class="question-header">
        <div class="header-title">
            <div class="header-icon"><i class="fas fa-list-check"></i></div>
            <div>
                <h3>Evaluation Questionnaires</h3>
                <p>Draft &rarr; Ready &rarr; Active &rarr; Closed &rarr; Archived</p>
            </div>
        </div>
    </div>

    <div class="card-body">

    <?php if(!$questionnaires_ready): ?>

        <div class="qn-notice">
            <i class="fas fa-database"></i>
            <div>
                <b>One more step is needed.</b>
                Apply the latest database update to start using questionnaires.
                <a href="index.php?page=system_update">Open System Update</a>
            </div>
        </div>

    <?php elseif(!$questionnaires): ?>

        <div class="qn-notice">
            <i class="fas fa-clipboard-list"></i>
            <div>
                <b>No questionnaires yet.</b>
                Click <a href="index.php?page=questionnaire_builder">New Questionnaire</a> to create the first one.
            </div>
        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table modern-table" id="qn-list">

                <thead>
                    <tr>
                        <th class="number-column">#</th>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>Questionnaire</th>
                        <th class="text-center">Questions</th>
                        <th class="text-center">Responses</th>
                        <th class="text-center">Status</th>
                        <th class="text-center action-column">Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($questionnaires as $i => $row):

                    $view = $status_view[$row['status']] ?? array(ucfirst($row['status']), 'status-pending', 'fa-circle');
                    $id = (int)$row['id'];
                ?>

                    <tr data-id="<?php echo $id; ?>" data-title="<?php echo htmlspecialchars($row['title']); ?>">

                        <td class="number-column"><?php echo $i + 1; ?></td>

                        <td>
                            <?php if($row['academic_year']): ?>
                                <strong><?php echo htmlspecialchars($row['academic_year']); ?></strong>
                            <?php else: ?>
                                <span class="qn-muted">Not set</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if($row['semester']): ?>
                                <span class="semester-badge">
                                    <i class="fas fa-calendar-week"></i>
                                    <?php echo htmlspecialchars($row['semester']); ?>
                                </span>
                            <?php else: ?>
                                <span class="qn-muted">Not set</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="qn-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="qn-muted">
                                <?php echo htmlspecialchars($evaluation_types[$row['evaluation_type']] ?? ''); ?>
                                <?php if($row['project_title']): ?>
                                    &middot; <?php echo htmlspecialchars($row['project_title']); ?>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="text-center">
                            <strong><?php echo (int)$row['questions']; ?></strong>
                            <div class="qn-muted"><?php echo (int)$row['questions'] === 1 ? 'question' : 'questions'; ?></div>
                        </td>

                        <td class="text-center">
                            <strong><?php echo (int)$row['responses']; ?></strong>
                            <div class="qn-muted"><?php echo (int)$row['responses'] === 1 ? 'response' : 'responses'; ?></div>
                        </td>

                        <td class="text-center">
                            <span class="status-badge <?php echo $view[1]; ?>">
                                <i class="fas <?php echo $view[2]; ?>"></i>
                                <?php echo $view[0]; ?>
                            </span>
                        </td>

                        <td class="text-center">

                            <div class="dropdown">

                                <button class="action-menu" type="button" data-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false"
                                        aria-label="Actions for <?php echo htmlspecialchars($row['title']); ?>">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-right">

                                <?php foreach($menus[$row['status']] ?? array('view') as $key):

                                    list($icon, $icon_class, $label, $hint, $kind, $arg) = $menu_items[$key];

                                    if($kind === 'link'){
                                        $href = "index.php?page=questionnaire_builder&id=$id&step=$arg";
                                    }elseif($kind === 'results'){
                                        $href = "index.php?page=evaluation_results&questionnaire=$id";
                                    }else{
                                        $href = '#';
                                    }
                                ?>

                                    <a class="dropdown-item"
                                       href="<?php echo $href; ?>"
                                       <?php if($kind === 'status'): ?>data-status-to="<?php echo $arg; ?>"<?php endif; ?>
                                       <?php if($kind === 'delete'): ?>data-delete="1"<?php endif; ?>>

                                        <span class="dropdown-icon <?php echo $icon_class; ?>">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </span>

                                        <span>
                                            <strong><?php echo $label; ?></strong>
                                            <small><?php echo $hint; ?></small>
                                        </span>

                                    </a>

                                <?php endforeach; ?>

                                </div>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

    </div>

</div>


<script src="assets/js/questionnaire-status.js?v=<?php echo @filemtime('assets/js/questionnaire-status.js') ?: 1; ?>"></script>
<script>
$(function(){

    // The menus open over the page, so the table's scroll area does not cut them off
    $('#qn-list .action-menu').dropdown({ popperConfig: { positionFixed: true } });

    // Status changes and deleting ask first (questionnaire-status.js), then reload the list
    function reload(){
        setTimeout(function(){ location.reload(); }, 700);
    }

    $('#qn-list').on('click', '[data-status-to]', function(e){
        e.preventDefault();
        var row = $(this).closest('tr');
        QnStatus.change(row.data('id'), row.data('title'), $(this).data('status-to'), reload);
    });

    $('#qn-list').on('click', '[data-delete]', function(e){
        e.preventDefault();
        var row = $(this).closest('tr');
        QnStatus.remove(row.data('id'), row.data('title'), reload);
    });

});
</script>


</div><!-- /questionnaires panel -->


<div class="qn-panel" data-panel="criteria">

    <?php
    /* The criteria page, shown here instead of as its own menu item.
       It is a self-contained fragment, like every other page. */
    include 'admin/criteria_list.php';
    ?>

</div>



<style>

/* The two tabs on this page */

.qn-tabs {
    display: flex;
    gap: 6px;
    border-bottom: 1px solid #e9eef5;
    margin: 0 0 22px;
    overflow-x: auto;
}

.qn-tab {
    border: none;
    background: none;
    padding: 12px 18px;
    font-size: 14.5px;
    font-weight: 600;
    color: #7b8a9c;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.qn-tab:hover { color: #1d5b42; }

.qn-tab.active {
    color: #1d5b42;
    border-bottom-color: #1d5b42;
}

.qn-panel { display: none; }
.qn-panel.active { display: block; }


/* =========================================================
   MAIN WRAPPER
========================================================= */

.questionnaire-wrapper{

    width:100%;

    padding-bottom:30px;

}



/* =========================================================
   PAGE HEADER
========================================================= */

.question-page-header{

    background:
    linear-gradient(
        135deg,
        #10b981,
        #047857
    );

    border-radius:20px;

    padding:25px 30px;

    margin-bottom:22px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    color:white;

    box-shadow:
    0 10px 25px rgba(16,185,129,.20);

}



.page-title-area{

    display:flex;

    align-items:center;

    gap:18px;

}



.page-icon{

    width:58px;

    height:58px;

    border-radius:16px;

    background:
    rgba(255,255,255,.16);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;

    border:
    1px solid rgba(255,255,255,.20);

}



.question-page-header h2{

    margin:0;

    font-size:25px;

    font-weight:700;

}



.question-page-header p{

    margin:5px 0 0;

    font-size:14px;

    opacity:.85;

}



/* ADD BUTTON */

.add-question-btn{

    background:white!important;

    color:#047857!important;

    border:none!important;

    padding:12px 20px;

    border-radius:12px;

    font-weight:700;

    box-shadow:
    0 5px 15px rgba(0,0,0,.10);

    transition:.25s;

}



.add-question-btn:hover{

    transform:translateY(-2px);

    box-shadow:
    0 8px 20px rgba(0,0,0,.15);

}



.add-question-btn i{

    margin-right:7px;

}



/* =========================================================
   SUMMARY CARDS
========================================================= */

.summary-row{

    margin-bottom:5px;

}



.summary-card{

    background:white;

    border-radius:18px;

    padding:20px;

    margin-bottom:20px;

    display:flex;

    align-items:center;

    gap:16px;

    border:
    1px solid #ecfdf5;

    box-shadow:
    0 7px 20px rgba(0,0,0,.06);

    transition:.25s;

}



.summary-card:hover{

    transform:translateY(-4px);

    box-shadow:
    0 12px 25px rgba(16,185,129,.12);

}



.summary-icon{

    width:55px;

    height:55px;

    border-radius:15px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:22px;

}



.summary-icon.green{

    background:#ecfdf5;

    color:#10b981;

}



.summary-icon.blue{

    background:#eff6ff;

    color:#3b82f6;

}



.summary-icon.purple{

    background:#f5f3ff;

    color:#8b5cf6;

}



.summary-content{

    display:flex;

    flex-direction:column;

}



.summary-content span{

    font-size:13px;

    color:#6b7280;

    font-weight:600;

}



.summary-content strong{

    color:#064e3b;

    font-size:28px;

    line-height:1.2;

}



/* =========================================================
   QUESTIONNAIRE CARD
========================================================= */

.questionnaire-card{

    border:none!important;

    border-radius:20px!important;

    overflow:hidden;

    background:white;

    box-shadow:
    0 10px 25px rgba(0,0,0,.07);

}



/* =========================================================
   TABLE HEADER
========================================================= */

.question-header{

    padding:22px 25px;

    border-bottom:
    1px solid #ecfdf5;

    background:#ffffff;

}



.header-title{

    display:flex;

    align-items:center;

    gap:14px;

}



.header-icon{

    width:48px;

    height:48px;

    border-radius:14px;

    background:#ecfdf5;

    color:#10b981;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:20px;

}



.header-title h3{

    margin:0;

    color:#064e3b;

    font-size:19px;

    font-weight:700;

}



.header-title p{

    margin:4px 0 0;

    color:#9ca3af;

    font-size:13px;

}



/* =========================================================
   TABLE
========================================================= */

.modern-table{

    margin:0!important;

    border-collapse:separate;

    border-spacing:0 7px;

}



.modern-table thead{

    background:#f8fafc;

}



.modern-table thead th{

    border:none!important;

    color:#64748b;

    font-size:12px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:.4px;

    padding:15px 14px;

}



.modern-table tbody tr{

    background:white;

    transition:.2s;

}



.modern-table tbody tr:hover{

    background:#f8fffc;

    box-shadow:
    0 5px 15px rgba(16,185,129,.08);

}



.modern-table tbody td{

    border-top:
    1px solid #f1f5f9;

    border-bottom:
    1px solid #f1f5f9;

    padding:15px 14px;

    vertical-align:middle;

}



.modern-table tbody td:first-child{

    border-left:
    1px solid #f1f5f9;

    border-radius:12px 0 0 12px;

}



.modern-table tbody td:last-child{

    border-right:
    1px solid #f1f5f9;

    border-radius:0 12px 12px 0;

}



.number-column{

    width:60px;

}



.number-cell{

    text-align:center;

}



.row-number{

    width:32px;

    height:32px;

    border-radius:10px;

    background:#ecfdf5;

    color:#047857;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    font-weight:700;

    font-size:13px;

}



/* =========================================================
   ACADEMIC YEAR
========================================================= */

.academic-year{

    display:flex;

    align-items:center;

    gap:11px;

}



.year-icon{

    width:40px;

    height:40px;

    border-radius:11px;

    background:#ecfdf5;

    color:#10b981;

    display:flex;

    align-items:center;

    justify-content:center;

}



.academic-year strong{

    color:#1f2937;

    font-size:15px;

}



.default-badge{

    display:inline-block;

    margin-left:7px;

    background:#fef3c7;

    color:#b45309;

    border-radius:20px;

    padding:3px 8px;

    font-size:10px;

    font-weight:700;

}



.default-badge i{

    margin-right:3px;

}



/* =========================================================
   SEMESTER
========================================================= */

.semester-badge{

    display:inline-flex;

    align-items:center;

    gap:6px;

    background:#eff6ff;

    color:#2563eb;

    padding:7px 12px;

    border-radius:20px;

    font-size:12px;

    font-weight:700;

}



.semester-badge i{

    font-size:11px;

}



/* =========================================================
   METRIC BOX
========================================================= */

.metric-box{

    display:inline-flex;

    align-items:center;

    gap:9px;

    text-align:left;

}



.metric-icon{

    width:34px;

    height:34px;

    border-radius:10px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:13px;

}



.question-count .metric-icon{

    background:#ecfdf5;

    color:#10b981;

}



.answer-count .metric-icon{

    background:#eff6ff;

    color:#3b82f6;

}



.metric-box strong{

    display:block;

    font-size:16px;

    color:#1f2937;

}



.metric-box small{

    display:block;

    color:#9ca3af;

    font-size:10px;

}



/* =========================================================
   STATUS
========================================================= */

.status-badge{

    display:inline-flex;

    align-items:center;

    gap:6px;

    padding:7px 12px;

    border-radius:20px;

    font-size:11px;

    font-weight:700;

}



.status-pending{

    background:#fef3c7;

    color:#b45309;

}



.status-ongoing{

    background:#d1fae5;

    color:#047857;

}



.status-closed{

    background:#e2e8f0;

    color:#475569;

}



.status-draft{

    background:#f1f5f9;

    color:#64748b;

}



.status-ready{

    background:#dbeafe;

    color:#1d4ed8;

}



.status-archived{

    background:#f5f3ff;

    color:#6d28d9;

}



/* =========================================================
   QUESTIONNAIRE LIST
========================================================= */

.qn-title{

    font-weight:700;

    color:#1f2937;

}



.qn-muted{

    color:#9ca3af;

    font-size:12px;

}



.qn-notice{

    display:flex;

    gap:14px;

    align-items:flex-start;

    background:#f0fdf4;

    border:1px dashed #86efac;

    border-radius:14px;

    padding:18px 20px;

    color:#14532d;

}



.qn-notice i{

    font-size:22px;

    color:#10b981;

    margin-top:2px;

}



.qn-notice a{

    font-weight:700;

    color:#047857;

}



.qn-fail-list{

    text-align:left;

    margin:14px 0 0;

    padding-left:20px;

    font-size:14px;

}



.qn-fail-list li{

    margin-bottom:4px;

}



/* =========================================================
   ACTION
========================================================= */

.action-column{

    width:90px;

}



.action-menu{

    width:36px;

    height:36px;

    border:none;

    border-radius:11px;

    background:#ecfdf5;

    color:#047857;

    cursor:pointer;

    transition:.2s;

}



.action-menu:hover{

    background:#10b981;

    color:white;

    transform:rotate(90deg);

}



.dropdown-menu{

    min-width:230px;

    padding:8px;

    border:none!important;

    border-radius:14px!important;

    box-shadow:
    0 12px 30px rgba(0,0,0,.13);

}



.dropdown-item{

    display:flex;

    align-items:center;

    gap:10px;

    padding:10px;

    border-radius:10px;

    color:#374151;

    transition:.2s;

}



.dropdown-item:hover{

    background:#ecfdf5;

    color:#047857;

}



.dropdown-icon{

    width:35px;

    height:35px;

    border-radius:9px;

    display:flex;

    align-items:center;

    justify-content:center;

}



.edit-icon{

    background:#ecfdf5;

    color:#10b981;

}



.delete-icon{

    background:#fef2f2;

    color:#dc2626;

}



.dropdown-item strong{

    display:block;

    font-size:13px;

}



.dropdown-item small{

    display:block;

    color:#9ca3af;

    font-size:10px;

    margin-top:2px;

}



/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:768px){

    .question-page-header{

        flex-direction:column;

        align-items:flex-start;

        gap:18px;

    }


    .add-question-btn{

        width:100%;

        text-align:center;

    }


    .question-page-header h2{

        font-size:21px;

    }


    .modern-table{

        min-width:950px;

    }


    .questionnaire-card{

        overflow:hidden;

    }

}


@media(max-width:576px){

    .question-page-header{

        padding:20px;

    }


    .page-title-area{

        align-items:flex-start;

    }


    .page-icon{

        width:48px;

        height:48px;

        font-size:20px;

    }


    .summary-card{

        padding:17px;

    }

}

</style>



<script>
$(function(){

    /* Remembers which tab you were on, so adding or editing a criteria
       brings you back here rather than to the questionnaire list. */

    function show(panel){

        $('.qn-tab').removeClass('active')
            .filter('[data-panel="' + panel + '"]').addClass('active');

        $('.qn-panel').removeClass('active')
            .filter('[data-panel="' + panel + '"]').addClass('active');

        try {
            sessionStorage.setItem('questionnaire_tab', panel);
        } catch (e) {
            // private browsing: the tab just will not be remembered
        }
    }

    $('.qn-tab').on('click', function(){
        show($(this).data('panel'));
    });

    var opening = 'questionnaires';

    if(location.hash === '#criteria'){
        opening = 'criteria';
    }else{
        try {
            opening = sessionStorage.getItem('questionnaire_tab') || 'questionnaires';
        } catch (e) {}
    }

    show(opening);

});
</script>
