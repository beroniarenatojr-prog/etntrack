
<?php include 'db_connect.php' ?>

<?php

/* =========================================================
   SUMMARY DATA
========================================================= */

$total_academic = 0;
$total_questions = 0;
$total_answers = 0;

$summary_academic = $conn->query("
    SELECT COUNT(*) AS total
    FROM academic_list
");

if($summary_academic){
    $data = $summary_academic->fetch_assoc();
    $total_academic = $data['total'];
}


$summary_questions = $conn->query("
    SELECT COUNT(*) AS total
    FROM question_list
");

if($summary_questions){
    $data = $summary_questions->fetch_assoc();
    $total_questions = $data['total'];
}


$summary_answers = $conn->query("
    SELECT COUNT(*) AS total
    FROM evaluation_list
");

if($summary_answers){
    $data = $summary_answers->fetch_assoc();
    $total_answers = $data['total'];
}

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
                Create and manage evaluation questions for each academic year.
            </p>

        </div>

    </div>


  

</div>



<!-- =====================================================
     SUMMARY CARDS
===================================================== -->

<div class="row summary-row">


    <!-- ACADEMIC YEARS -->

    <div class="col-lg-4 col-md-4 col-sm-12">

        <div class="summary-card">

            <div class="summary-icon green">

                <i class="fas fa-calendar-alt"></i>

            </div>

            <div class="summary-content">

                <span>
                    Academic Years
                </span>

                <strong>
                    <?php echo number_format($total_academic); ?>
                </strong>

            </div>

        </div>

    </div>



    <!-- QUESTIONS -->

    <div class="col-lg-4 col-md-4 col-sm-12">

        <div class="summary-card">

            <div class="summary-icon blue">

                <i class="fas fa-question-circle"></i>

            </div>

            <div class="summary-content">

                <span>
                    Total Questions
                </span>

                <strong>
                    <?php echo number_format($total_questions); ?>
                </strong>

            </div>

        </div>

    </div>



    <!-- ANSWERS -->

    <div class="col-lg-4 col-md-4 col-sm-12">

        <div class="summary-card">

            <div class="summary-icon purple">

                <i class="fas fa-check-circle"></i>

            </div>

            <div class="summary-content">

                <span>
                    Evaluation Responses
                </span>

                <strong>
                    <?php echo number_format($total_answers); ?>
                </strong>

            </div>

        </div>

    </div>


</div>



<!-- =====================================================
     TABS
     The questionnaire itself, and the criteria its questions
     are grouped under. Both used to be separate menu items.
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


<!-- =====================================================
     QUESTIONNAIRE TABLE
===================================================== -->

<div class="card questionnaire-card">


    <!-- TABLE HEADER -->

    <div class="question-header">

        <div class="header-title">

            <div class="header-icon">

                <i class="fas fa-list-check"></i>

            </div>

            <div>

                <h3>
                    Evaluation Questionnaires
                </h3>

                <p>
                    Manage questions and evaluation responses by academic period.
                </p>

            </div>

        </div>

    </div>



    <!-- TABLE BODY -->

    <div class="card-body">


        <div class="table-responsive">


            <table
                class="table modern-table"
                id="list"
            >


                <thead>

                    <tr>

                        <th class="number-column">
                            #
                        </th>

                        <th>
                            Academic Year
                        </th>

                        <th>
                            Semester
                        </th>

                        <th class="text-center">
                            Questions
                        </th>

                        <th class="text-center">
                            Evaluations
                        </th>

                        <th class="text-center">
                            Status
                        </th>

                        <th class="text-center action-column">
                            Action
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php

                $i = 1;


                $qry = $conn->query("
                    SELECT *
                    FROM academic_list
                    ORDER BY
                    CAST(SUBSTRING_INDEX(year,'-',1) AS UNSIGNED) DESC,
                    semester DESC
                ");


                while($row = $qry->fetch_assoc()):


                    /* QUESTIONS */

                    $questions = 0;

                    $question_query = $conn->query("
                        SELECT COUNT(*) AS total
                        FROM question_list
                        WHERE academic_id = {$row['id']}
                    ");

                    if($question_query){

                        $question_data =
                            $question_query->fetch_assoc();

                        $questions =
                            $question_data['total'];

                    }



                    /* EVALUATIONS */

                    $answers = 0;

                    $answer_query = $conn->query("
                        SELECT COUNT(*) AS total
                        FROM evaluation_list
                        WHERE academic_id = {$row['id']}
                    ");

                    if($answer_query){

                        $answer_data =
                            $answer_query->fetch_assoc();

                        $answers =
                            $answer_data['total'];

                    }



                    /* STATUS */

                    if($row['status'] == 0){

                        $status_text = "Not Yet Started";
                        $status_class = "status-pending";
                        $status_icon = "fa-clock";

                    }elseif($row['status'] == 1){

                        $status_text = "Ongoing";
                        $status_class = "status-ongoing";
                        $status_icon = "fa-play";

                    }else{

                        $status_text = "Closed";
                        $status_class = "status-closed";
                        $status_icon = "fa-lock";

                    }

                ?>


                <tr>


                    <!-- NUMBER -->

                    <td class="number-cell">

                        <span class="row-number">

                            <?php echo $i++; ?>

                        </span>

                    </td>



                    <!-- ACADEMIC YEAR -->

                    <td>

                        <div class="academic-year">

                            <div class="year-icon">

                                <i class="fas fa-calendar"></i>

                            </div>

                            <div>

                                <strong>

                                    <?php echo htmlspecialchars($row['year']); ?>

                                </strong>

                                <?php if($row['is_default'] == 1): ?>

                                    <span class="default-badge">

                                        <i class="fas fa-star"></i>

                                        Default

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </td>



                    <!-- SEMESTER -->

                    <td>

                        <span class="semester-badge">

                            <i class="fas fa-layer-group"></i>

                            <?php

                            if($row['semester'] == 1){

                                echo "1st Semester";

                            }elseif($row['semester'] == 2){

                                echo "2nd Semester";

                            }else{

                                echo $row['semester'];

                            }

                            ?>

                        </span>

                    </td>



                    <!-- QUESTIONS -->

                    <td class="text-center">

                        <div class="metric-box question-count">

                            <div class="metric-icon">

                                <i class="fas fa-question"></i>

                            </div>

                            <div>

                                <strong>

                                    <?php echo number_format($questions); ?>

                                </strong>

                                <small>
                                    Questions
                                </small>

                            </div>

                        </div>

                    </td>



                    <!-- EVALUATIONS -->

                    <td class="text-center">

                        <div class="metric-box answer-count">

                            <div class="metric-icon">

                                <i class="fas fa-check"></i>

                            </div>

                            <div>

                                <strong>

                                    <?php echo number_format($answers); ?>

                                </strong>

                                <small>
                                    Responses
                                </small>

                            </div>

                        </div>

                    </td>



                    <!-- STATUS -->

                    <td class="text-center">

                        <span
                            class="status-badge <?php echo $status_class; ?>"
                        >

                            <i class="fas <?php echo $status_icon; ?>"></i>

                            <?php echo $status_text; ?>

                        </span>

                    </td>



                    <!-- ACTION -->

                    <td class="text-center">

                        <div class="dropdown">

                            <button
                                class="action-menu"
                                type="button"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >

                                <i class="fas fa-ellipsis-v"></i>

                            </button>



                            <div class="dropdown-menu dropdown-menu-right">


                                <a
                                    class="dropdown-item manage_questionnaire"
                                    href="index.php?page=manage_questionnaire&id=<?php echo $row['id']; ?>"
                                >

                                    <span class="dropdown-icon edit-icon">

                                        <i class="fas fa-edit"></i>

                                    </span>

                                    <span>

                                        <strong>
                                            Manage Questionnaire
                                        </strong>

                                        <small>
                                            Add or edit questions
                                        </small>

                                    </span>

                                </a>


                            </div>

                        </div>

                    </td>


                </tr>


                <?php endwhile; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


</div>


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
