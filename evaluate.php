<?php
include 'db_connect.php';

/* =========================================================
   GET ACTIVITY ID
========================================================= */

$activity_id = isset($_GET['activity_id'])
    ? intval($_GET['activity_id'])
    : 0;

if ($activity_id <= 0) {
    die("Invalid QR Code.");
}


/* =========================================================
   VERIFY ACTIVITY
========================================================= */

$qry = $conn->query("
    SELECT *
    FROM activities
    WHERE id = '$activity_id'
    AND status = 'approved'
    LIMIT 1
");

if (!$qry || $qry->num_rows == 0) {
    die("Invalid Activity.");
}

$activity = $qry->fetch_assoc();


/* =========================================================
   GET ACTIVITY INFORMATION
   Uses whichever column exists in your activities table
========================================================= */

function getActivityValue($activity, $possible_names, $default = '')
{
    foreach ($possible_names as $name) {
        if (isset($activity[$name]) && trim($activity[$name]) !== '') {
            return trim($activity[$name]);
        }
    }

    return $default;
}


$activity_title = getActivityValue(
    $activity,
    [
        'activity_name',
        'title',
        'activity_title',
        'name',
        'training_name',
        'training_title'
    ],
    'Training / Seminar / Workshop / Extension Activity'
);


$activity_date = getActivityValue(
    $activity,
    [
        'date',
        'activity_date',
        'event_date',
        'training_date',
        'date_from'
    ],
    ''
);


$activity_venue = getActivityValue(
    $activity,
    [
        'venue',
        'location',
        'activity_venue',
        'event_venue'
    ],
    ''
);


/* =========================================================
   FORMAT DATE
========================================================= */

if ($activity_date !== '') {

    $timestamp = strtotime($activity_date);

    if ($timestamp !== false) {
        $activity_date = date('F d, Y', $timestamp);
    }
}


/* =========================================================
   GET CRITERIA AND QUESTIONS
========================================================= */

$qry = $conn->query("
    SELECT
        c.id AS criteria_id,
        c.criteria,
        c.order_by AS criteria_order,

        q.id AS question_id,
        q.question,
        q.order_by AS question_order

    FROM criteria_list c

    INNER JOIN question_list q
        ON c.id = q.criteria_id

    ORDER BY
        c.order_by ASC,
        q.order_by ASC
");

if (!$qry) {
    die("SQL Error: " . $conn->error);
}


/* =========================================================
   LOGO
========================================================= */

/*
   Change this if your logo is located somewhere else.

   Example:
   assets/images/song-song-logo.png
*/

$logo_path = 'assets/images/isabela-state-university-logo.png';

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    ISU Extension & Training Services Evaluation Form
</title>


<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
}

body {

    background: #252525;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 10px;

    color: #111;

    padding: 25px 0;
}


/* =========================================================
   A4 PAPER
========================================================= */

.paper {

    width: 210mm;

    min-height: 297mm;

    margin: 0 auto;

    background: #fff;

    padding:
        12mm
        10mm
        12mm
        10mm;

    box-shadow:
        0 0 12px rgba(0,0,0,.45);

}


/* =========================================================
   HEADER
========================================================= */

.header {

    text-align: center;

    line-height: 1.15;

    margin-bottom: 8px;

}

.logo {

    width: 45px;

    height: 45px;

    object-fit: contain;

    margin-bottom: 2px;

}

.header .school {

    font-size: 11px;

    font-weight: bold;

}

.header .campus {

    font-size: 10px;

    font-weight: bold;

}

.header .office {

    font-size: 10px;

    font-weight: bold;

    margin-top: 2px;

}

.header .form-title {

    font-size: 11px;

    font-weight: bold;

    margin-top: 2px;

}


/* =========================================================
   ACTIVITY INFORMATION
========================================================= */

.activity-info {

    width: 100%;

    border-collapse: collapse;

    margin-top: 8px;

    margin-bottom: 4px;

}

.activity-info td {

    padding: 2px 3px;

    vertical-align: top;

}

.label {

    font-weight: bold;

    white-space: nowrap;

}

.activity-value {

    border-bottom: 1px solid #555;

    font-weight: normal;

}


/* =========================================================
   INSTRUCTIONS
========================================================= */

.instructions {

    margin-top: 3px;

    margin-bottom: 4px;

    font-size: 8px;

    line-height: 1.25;

}

.instructions strong {

    font-weight: bold;

}


/* =========================================================
   RATING LEGEND
========================================================= */

.rating-legend {

    width: 100%;

    border-collapse: collapse;

    margin-bottom: 5px;

}

.rating-legend td {

    padding: 1px 3px;

}

.rating-legend .legend-title {

    font-weight: bold;

}


/* =========================================================
   EVALUATION TABLE
========================================================= */

.evaluation-table {

    width: 100%;

    border-collapse: collapse;

    table-layout: fixed;

    margin-bottom: 4px;

    page-break-inside: avoid;

}


/* Main question column */

.evaluation-table th:first-child,
.evaluation-table td:first-child {

    width: 78%;

}


/* Rating columns */

.evaluation-table th:not(:first-child),
.evaluation-table td:not(:first-child) {

    width: 4.4%;

    text-align: center;

}


/* Header */

.evaluation-table thead th {

    border: 1px solid #555;

    background: #e8e8e8;

    font-size: 8px;

    font-weight: bold;

    padding: 2px;

    text-align: center;

}


/* Section heading */

.criteria-heading {

    background: #333;

    color: #fff;

    border: 1px solid #333;

    font-size: 8.5px;

    font-weight: bold;

    text-align: left !important;

    padding: 2px 4px !important;

}


/* Question rows */

.evaluation-table tbody td {

    border: 1px solid #777;

    padding: 2px 4px;

    font-size: 8px;

    line-height: 1.15;

    vertical-align: middle;

}


/* Question number */

.question-number {

    display: inline-block;

    min-width: 16px;

}


/* Radio buttons */

.rating-radio {

    width: 10px;

    height: 10px;

    margin: 0;

    padding: 0;

    vertical-align: middle;

    cursor: pointer;

}


/* =========================================================
   COMMENTS
========================================================= */

.comments-title {

    font-size: 8.5px;

    font-weight: bold;

    margin-top: 10px;

    margin-bottom: 3px;

}

.comments-box {

    width: 100%;

    height: 55px;

    border-bottom: 1px solid #777;

    position: relative;

    background-image:
        repeating-linear-gradient(
            to bottom,
            transparent 0,
            transparent 15px,
            #aaa 15px,
            #aaa 16px
        );

}


/* =========================================================
   SUBMIT
========================================================= */

.submit-area {

    text-align: center;

    margin-top: 15px;

}

.submit-btn {

    background: #0F6D3D;

    color: #fff;

    border: none;

    border-radius: 5px;

    padding: 10px 30px;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

}

.submit-btn:hover {

    background: #0a512d;

}


/* =========================================================
   PRINT BUTTON
========================================================= */

.print-area {

    text-align: center;

    margin-top: 8px;

}

.print-btn {

    background: #555;

    color: #fff;

    border: none;

    border-radius: 5px;

    padding: 7px 18px;

    cursor: pointer;

}


/* =========================================================
   MOBILE
========================================================= */

@media screen and (max-width: 850px) {

    body {

        padding: 0;

        background: #fff;

    }

    .paper {

        width: 100%;

        min-height: auto;

        padding: 20px;

        box-shadow: none;

    }

    .evaluation-table {

        min-width: 650px;

    }

    .table-wrapper {

        overflow-x: auto;

    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    @page {

        size: A4 portrait;

        margin: 0;

    }

    body {

        background: #fff;

        padding: 0;

        margin: 0;

    }

    .paper {

        width: 210mm;

        min-height: 297mm;

        margin: 0;

        padding:
            10mm
            10mm
            10mm
            10mm;

        box-shadow: none;

    }

    .submit-area,
    .print-area {

        display: none !important;

    }

    .evaluation-table {

        page-break-inside: avoid;

    }

    .criteria-heading {

        background: #333 !important;

        color: #fff !important;

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    .evaluation-table thead th {

        background: #e8e8e8 !important;

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

}


/* =========================================================
   CHECKBOX/RADIO PRINT
========================================================= */

@media print {

    input[type="radio"] {

        appearance: none;

        -webkit-appearance: none;

        width: 9px;

        height: 9px;

        border: 1px solid #333;

        border-radius: 50%;

        display: inline-block;

        vertical-align: middle;

    }

}

</style>

</head>


<body>


<div class="paper">


<!-- =====================================================
     HEADER
====================================================== -->

<div class="header">


<?php
$logo_path = 'uploads/OIP.jpg';
?>

<img 
    src="<?php echo $logo_path; ?>" 
    class="logo" 
    alt="School Logo"
>




    <div class="school">
        ISABELA STATE UNIVERSITY
    </div>

    <div class="campus">
        Ilagan Campus
    </div>

    <div class="office">
        EXTENSION &amp; TRAINING SERVICES
    </div>

    <div class="form-title">
        EVALUATION FORM
    </div>

</div>



<!-- =====================================================
     ACTIVITY INFORMATION
====================================================== -->

<table class="activity-info">

<tr>

    <td class="label" style="width: 31%;">
        Name of Training/Seminar/Workshop/Extension Activity:
    </td>

    <td class="activity-value">

        <?php
        echo htmlspecialchars($activity_title);
        ?>

    </td>

</tr>


<tr>

    <td class="label">
        Date:
    </td>

    <td class="activity-value">

        <?php
        echo htmlspecialchars($activity_date);
        ?>

    </td>

</tr>


<tr>

    <td class="label">
        Venue:
    </td>

    <td class="activity-value">

        <?php
        echo htmlspecialchars($activity_venue);
        ?>

    </td>

</tr>

</table>



<!-- =====================================================
     EVALUATOR
====================================================== -->

<table class="activity-info">

<tr>

    <td class="label" style="width: 31%;">
        Name of Evaluator:
    </td>

    <td>

        <input
            type="text"
            name="evaluator_name"
            form="evaluationForm"
            style="
                width:100%;
                border:none;
                border-bottom:1px solid #555;
                outline:none;
                font-size:9px;
            "
            placeholder="Optional">

    </td>

</tr>

</table>



<!-- =====================================================
     INSTRUCTIONS
====================================================== -->

<div class="instructions">

    <strong>Instruction:</strong>

    Kindly evaluate this form by checking the appropriate rating.

</div>



<!-- =====================================================
     RATING GUIDE
====================================================== -->

<table class="rating-legend">

<tr>

    <td class="legend-title" style="width:31%;">
        Particular
    </td>

    <td style="text-align:center;">
        <strong>1</strong> - Poor
    </td>

    <td style="text-align:center;">
        <strong>2</strong> - Fair
    </td>

    <td style="text-align:center;">
        <strong>3</strong> - Good
    </td>

    <td style="text-align:center;">
        <strong>4</strong> - Better
    </td>

    <td style="text-align:center;">
        <strong>5</strong> - Best
    </td>

</tr>

</table>



<!-- =====================================================
     FORM
====================================================== -->

<form
    id="evaluationForm"
    action="save_evaluation.php"
    method="POST">


<input
    type="hidden"
    name="activity_id"
    value="<?php echo $activity_id; ?>">



<!-- =====================================================
     EVALUATION QUESTIONS
====================================================== -->

<div class="table-wrapper">


<?php

$current_criteria = '';

$question_counter = 0;


/*
   Since the query is already ordered by criteria,
   we can create one table per criteria.
*/

while ($row = $qry->fetch_assoc()) {


    /* =====================================================
       NEW CRITERIA
    ====================================================== */

    if ($current_criteria !== $row['criteria']) {


        /*
           Close previous table
        */

        if ($current_criteria !== '') {

            echo '
                </tbody>
                </table>
            ';

        }


        $current_criteria = $row['criteria'];

        $question_counter = 0;


        /*
           Criteria heading
        */

        echo '

        <table class="evaluation-table">

            <thead>

                <tr>

                    <th
                        class="criteria-heading"
                        colspan="6">

                        ' .
                        htmlspecialchars(
                            $current_criteria
                        )
                        . '

                    </th>

                </tr>

                <tr>

                    <th>
                        Particular
                    </th>

                    <th>1</th>

                    <th>2</th>

                    <th>3</th>

                    <th>4</th>

                    <th>5</th>

                </tr>

            </thead>

            <tbody>

        ';

    }


    /* =====================================================
       QUESTION NUMBER
    ====================================================== */

    $question_counter++;


    /* =====================================================
       QUESTION
    ====================================================== */

    echo '<tr>';


    echo '<td>';

    echo '<span class="question-number">'
        . $question_counter
        . '.
        </span>';


    echo htmlspecialchars(
        $row['question']
    );


    echo '</td>';


    /* =====================================================
       RATINGS 1 - 5
    ====================================================== */

    for ($i = 1; $i <= 5; $i++) {

        echo '

        <td>

            <input
                type="radio"
                class="rating-radio"
                name="rate[' .
                    intval($row['question_id'])
                . ']"
                value="' . $i . '"
                required>

        </td>

        ';

    }


    echo '</tr>';

}


/* =========================================================
   CLOSE LAST TABLE
========================================================= */

if ($current_criteria !== '') {

    echo '

        </tbody>

        </table>

    ';

}

?>

</div>



<!-- =====================================================
     COMMENTS
====================================================== -->

<div class="comments-title">

    Other comments/suggestions:

</div>


<textarea
    name="comments"
    style="
        position:absolute;
        left:-9999px;
    "
></textarea>


<div
    class="comments-box"
    onclick="
        document.querySelector(
            'textarea[name=comments]'
        ).focus();
    "
></div>



<!-- =====================================================
     SUBMIT
====================================================== -->

<div class="submit-area">

    <button
        type="submit"
        class="submit-btn">

        Submit Evaluation

    </button>

</div>


</form>



<!-- =====================================================
     PRINT
====================================================== -->

<div class="print-area">

    <button
        type="button"
        class="print-btn"
        onclick="window.print()">

        Print Form

    </button>

</div>


</div>


</body>

</html>