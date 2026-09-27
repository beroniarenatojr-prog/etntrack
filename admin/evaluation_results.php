<?php

include 'db_connect.php';


/* =========================================================
   FETCH ALL ACTIVITIES + QUESTIONS + EVALUATION ANSWERS
========================================================= */

$sql = "

    SELECT

        a.id AS activity_id,
        a.activity_name,

        q.id AS question_id,
        q.question,
        q.order_by,
        q.criteria_id,

        ea.evaluation_id,
        ea.Evaluator_name,
        ea.rate

    FROM activities a

    LEFT JOIN evaluation_answers ea
        ON ea.activity_id = a.id

    LEFT JOIN question_list q
        ON q.id = ea.question_id

    ORDER BY
        a.id DESC,
        q.criteria_id ASC,
        q.order_by ASC

";


$result = $conn->query($sql);


if (!$result) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}


/* =========================================================
   ORGANIZE DATA
========================================================= */

$activities = [];


while ($row = $result->fetch_assoc()) {

    $activity_id = intval(
        $row['activity_id']
    );


    /* =====================================================
       CREATE ACTIVITY
    ===================================================== */

    if (!isset($activities[$activity_id])) {

        $activities[$activity_id] = [

            'id' =>
                $activity_id,

            'name' =>
                $row['activity_name'],

            'criteria' =>
                [],

            'evaluation_ids' =>
                []

        ];

    }


    /* =====================================================
       NO EVALUATION YET
    ===================================================== */

    if (
        empty($row['evaluation_id']) ||
        empty($row['question_id'])
    ) {

        continue;

    }


    /* =====================================================
       UNIQUE EVALUATION ID
       One evaluation_id = one completed form
    ===================================================== */

    $evaluation_id =
        intval($row['evaluation_id']);


    $activities[$activity_id]
        ['evaluation_ids']
        [$evaluation_id] = true;


    /* =====================================================
       CRITERIA
    ===================================================== */

    $criteria_id =
        intval($row['criteria_id']);


    if (!isset(
        $activities[$activity_id]
        ['criteria']
        [$criteria_id]
    )) {

        $activities[$activity_id]
            ['criteria']
            [$criteria_id] = [

                'id' =>
                    $criteria_id,

                'questions' =>
                    [],

                'evaluation_ids' =>
                    [],

                'total_score' =>
                    0,

                'total_ratings' =>
                    0

            ];

    }


    /* =====================================================
       UNIQUE EVALUATION FOR THIS CRITERIA
    ===================================================== */

    $activities[$activity_id]
        ['criteria']
        [$criteria_id]
        ['evaluation_ids']
        [$evaluation_id] = true;


    /* =====================================================
       QUESTION
    ===================================================== */

    $question_id =
        intval($row['question_id']);


    if (!isset(
        $activities[$activity_id]
        ['criteria']
        [$criteria_id]
        ['questions']
        [$question_id]
    )) {

        $activities[$activity_id]
            ['criteria']
            [$criteria_id]
            ['questions']
            [$question_id] = [

                'question' =>
                    $row['question'],

                'order_by' =>
                    $row['order_by'],

                'total_score' =>
                    0,

                'total_evaluations' =>
                    0

            ];

    }


    /* =====================================================
       RATE
    ===================================================== */

    $rate = intval(
        $row['rate']
    );


    /* =====================================================
       QUESTION TOTAL
    ===================================================== */

    $activities[$activity_id]
        ['criteria']
        [$criteria_id]
        ['questions']
        [$question_id]
        ['total_score']
        += $rate;


    $activities[$activity_id]
        ['criteria']
        [$criteria_id]
        ['questions']
        [$question_id]
        ['total_evaluations']++;


    /* =====================================================
       CRITERIA TOTAL
    ===================================================== */

    $activities[$activity_id]
        ['criteria']
        [$criteria_id]
        ['total_score']
        += $rate;


    $activities[$activity_id]
        ['criteria']
        [$criteria_id]
        ['total_ratings']++;

}


/* =========================================================
   RATING LABEL
========================================================= */

function getRatingLabel($rating)
{

    if ($rating >= 4.50) {
        return 'Excellent';
    }

    if ($rating >= 3.50) {
        return 'Very Good';
    }

    if ($rating >= 2.50) {
        return 'Good';
    }

    if ($rating >= 1.50) {
        return 'Fair';
    }

    return 'Poor';

}


/* =========================================================
   CALCULATE AVERAGES
========================================================= */

foreach ($activities as &$activity) {

    foreach (
        $activity['criteria']
        as &$criteria
    ) {


        /* ================================================
           CRITERIA AVERAGE
        ================================================= */

        if (
            $criteria['total_ratings'] > 0
        ) {

            $criteria['average'] =
                round(
                    $criteria['total_score']
                    /
                    $criteria['total_ratings'],
                    2
                );

        } else {

            $criteria['average'] = 0;

        }


        /* ================================================
           TOTAL UNIQUE EVALUATIONS FOR CRITERIA
        ================================================= */

        $criteria['total_evaluations'] =
            count(
                $criteria['evaluation_ids']
            );


        /* ================================================
           QUESTION AVERAGES
        ================================================= */

        foreach (
            $criteria['questions']
            as &$question
        ) {

            if (
                $question['total_evaluations']
                > 0
            ) {

                $question['average'] =
                    round(
                        $question['total_score']
                        /
                        $question['total_evaluations'],
                        2
                    );

            } else {

                $question['average'] = 0;

            }

        }

        unset($question);

    }

    unset($criteria);

}

unset($activity);

?>

<!DOCTYPE html>

<html lang="en">

<head>


<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Evaluation Results
</title>


<style>

    * {
        box-sizing: border-box;
    }


    body {

        margin: 0;

        padding: 30px;

        font-family:
            Arial,
            sans-serif;

        background: #f4f8f6;

        color: #333;

    }


    .container {

        max-width: 1200px;

        margin: auto;

    }


    .page-header {

        background: #fff;

        padding: 25px;

        border-radius: 12px;

        margin-bottom: 25px;

        box-shadow:
            0 3px 12px
            rgba(0,0,0,.08);

    }


    .page-header h1 {

        margin: 0 0 8px;

        color: #198754;

    }


    .page-header p {

        margin: 0;

        color: #777;

    }


    .activity-card {

        background: #fff;

        border-radius: 12px;

        margin-bottom: 30px;

        overflow: hidden;

        box-shadow:
            0 3px 12px
            rgba(0,0,0,.08);

    }


    .activity-header {

        background: #198754;

        color: #fff;

        padding: 20px;

    }


    .activity-header h2 {

        margin: 0;

        font-size: 21px;

    }


    .activity-header small {

        display: block;

        margin-top: 7px;

        opacity: .9;

    }


    .criteria {

        padding: 20px;

        border-bottom:
            1px solid #eee;

    }


    .criteria:last-child {

        border-bottom: none;

    }


    .criteria-title {

        font-size: 17px;

        font-weight: bold;

        color: #198754;

        margin-bottom: 15px;

    }


    table {

        width: 100%;

        border-collapse: collapse;

    }


    th {

        padding: 13px;

        background: #f1f5f3;

        text-align: left;

        font-size: 13px;

        border-bottom:
            1px solid #ddd;

    }


    td {

        padding: 13px;

        border-bottom:
            1px solid #eee;

        font-size: 14px;

    }


    tr:last-child td {

        border-bottom: none;

    }


    .question {

        max-width: 600px;

    }


    .center {

        text-align: center;

    }


    .average {

        font-size: 17px;

        font-weight: bold;

        color: #198754;

    }


    .result {

        display: inline-block;

        padding: 6px 12px;

        border-radius: 20px;

        background: #e8f5ee;

        color: #198754;

        font-size: 12px;

        font-weight: bold;

    }


    .summary {

        margin-top: 18px;

        padding: 16px;

        background: #f8faf9;

        border-radius: 8px;

        display: flex;

        flex-wrap: wrap;

        gap: 25px;

    }


    .summary-item {

        font-size: 14px;

    }


    .summary-item strong {

        color: #198754;

    }


    /* =================================================
       INDIVIDUAL EVALUATIONS
    ================================================= */

    .individual-section {

        padding: 20px;

        background: #fbfdfc;

        border-top:
            1px solid #eee;

    }


    .individual-title {

        margin: 0 0 15px;

        color: #198754;

        font-size: 17px;

        font-weight: bold;

    }


    .evaluator-table {

        width: 100%;

        border-collapse: collapse;

    }


    .evaluator-table th {

        background: #e8f5ee;

        color: #333;

    }


    .view-btn {

        display: inline-block;

        padding: 7px 13px;

        background: #198754;

        color: white;

        text-decoration: none;

        border-radius: 6px;

        font-size: 13px;

        font-weight: bold;

    }


    .view-btn:hover {

        background: #146c43;

    }


    .not-evaluated {

        padding: 35px;

        text-align: center;

        color: #777;

    }


    .not-evaluated strong {

        display: block;

        color: #555;

        margin-bottom: 5px;

    }


    .empty {

        background: #fff;

        padding: 50px;

        text-align: center;

        border-radius: 12px;

        color: #777;

    }


    @media (max-width: 700px) {

        body {

            padding: 15px;

        }


        .criteria {

            padding: 12px;

            overflow-x: auto;

        }


        th,
        td {

            padding: 9px;

        }


        .evaluator-table {

            min-width: 600px;

        }

    }

</style>


</head>

<body>

<div class="container">


<!-- =====================================================
     PAGE HEADER
====================================================== -->

<div class="page-header">

    <h1>
        Evaluation Results
    </h1>

    <p>
        Results grouped by activity and evaluation criteria
    </p>

</div>


<?php if (!empty($activities)): ?>


    <?php foreach (
        $activities
        as $activity
    ): ?>


        <div class="activity-card">


            <!-- =================================================
                 ACTIVITY HEADER
            ================================================== -->

            <div class="activity-header">

                <h2>

                    <?= htmlspecialchars(
                        $activity['name']
                    ) ?>

                </h2>


                <small>

                    Activity ID:
                    <?= htmlspecialchars(
                        $activity['id']
                    ) ?>

                    &nbsp; | &nbsp;

                    Total Evaluations:

                    <?= count(
                        $activity[
                            'evaluation_ids'
                        ]
                    ) ?>

                </small>

            </div>


            <!-- =================================================
                 CRITERIA
            ================================================== -->

            <?php if (
                !empty(
                    $activity['criteria']
                )
            ): ?>


                <?php foreach (
                    $activity['criteria']
                    as $criteria
                ): ?>


                    <div class="criteria">


                        <div class="criteria-title">

                            Criteria
                            #<?= htmlspecialchars(
                                $criteria['id']
                            ) ?>

                        </div>


                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Question
                                    </th>

                                    <th class="center">
                                        Evaluations
                                    </th>

                                    <th class="center">
                                        Total Score
                                    </th>

                                    <th class="center">
                                        Average
                                    </th>

                                    <th class="center">
                                        Result
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $criteria['questions']
                                as $question
                            ): ?>


                                <tr>


                                    <td class="question">

                                        <?= htmlspecialchars(
                                            $question[
                                                'question'
                                            ]
                                        ) ?>

                                    </td>


                                    <td class="center">

                                        <?= htmlspecialchars(
                                            $question[
                                                'total_evaluations'
                                            ]
                                        ) ?>

                                    </td>


                                    <td class="center">

                                        <?= htmlspecialchars(
                                            $question[
                                                'total_score'
                                            ]
                                        ) ?>

                                    </td>


                                    <td class="center">

                                        <span
                                            class="average"
                                        >

                                            <?= htmlspecialchars(
                                                $question[
                                                    'average'
                                                ]
                                            ) ?>

                                        </span>

                                        / 5

                                    </td>


                                    <td class="center">

                                        <span
                                            class="result"
                                        >

                                            <?= htmlspecialchars(
                                                getRatingLabel(
                                                    $question[
                                                        'average'
                                                    ]
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>

                        </table>


                        <!-- =====================================
                             CRITERIA SUMMARY
                        ====================================== -->

                        <div class="summary">


                            <div class="summary-item">

                                Total Evaluations:

                                <strong>

                                    <?= htmlspecialchars(
                                        $criteria[
                                            'total_evaluations'
                                        ]
                                    ) ?>

                                </strong>

                            </div>


                            <div class="summary-item">

                                Total Ratings:

                                <strong>

                                    <?= htmlspecialchars(
                                        $criteria[
                                            'total_ratings'
                                        ]
                                    ) ?>

                                </strong>

                            </div>


                            <div class="summary-item">

                                Total Score:

                                <strong>

                                    <?= htmlspecialchars(
                                        $criteria[
                                            'total_score'
                                        ]
                                    ) ?>

                                </strong>

                            </div>


                            <div class="summary-item">

                                Criteria Average:

                                <strong>

                                    <?= htmlspecialchars(
                                        $criteria[
                                            'average'
                                        ]
                                    ) ?>

                                    / 5

                                </strong>

                            </div>


                            <div class="summary-item">

                                Result:

                                <strong>

                                    <?= htmlspecialchars(
                                        getRatingLabel(
                                            $criteria[
                                                'average'
                                            ]
                                        )
                                    ) ?>

                                </strong>

                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="not-evaluated">

                    <strong>
                        Not Yet Evaluated
                    </strong>

                    No evaluation results
                    have been submitted
                    for this activity.

                </div>


            <?php endif; ?>


            <!-- =================================================
                 INDIVIDUAL EVALUATIONS
            ================================================== -->

            <?php

            $evaluators = [];


            $evaluator_sql = "

                SELECT

                    evaluation_id,

                    MAX(Evaluator_name)
                        AS Evaluator_name

                FROM evaluation_answers

                WHERE activity_id = ?

                GROUP BY evaluation_id

                ORDER BY evaluation_id DESC

            ";


            $stmt =
                $conn->prepare(
                    $evaluator_sql
                );


            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $activity['id']
                );

                $stmt->execute();


                $evaluator_result =
                    $stmt->get_result();


                while (
                    $evaluator =
                        $evaluator_result
                        ->fetch_assoc()
                ) {

                    $evaluators[] =
                        $evaluator;

                }


                $stmt->close();

            }

            ?>


            <?php if (
                !empty($evaluators)
            ): ?>


                <div class="individual-section">


                    <div class="individual-title">

                        Individual Evaluations

                    </div>


                    <table class="evaluator-table">


                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Evaluator
                                </th>

                                <th>
                                    Evaluation ID
                                </th>

                                <th class="center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php

                        $counter = 1;

                        foreach (
                            $evaluators
                            as $evaluator
                        ):

                        ?>


                            <tr>


                                <td>

                                    <?= $counter++ ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $evaluator[
                                                'Evaluator_name'
                                            ]
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $evaluator[
                                            'evaluation_id'
                                        ]
                                    ) ?>

                                </td>


                                <td class="center">

                               <a
href="admin/view_evaluations.php?evaluation_id=<?= urlencode($evaluator['evaluation_id']) ?>"
class="view-btn"

>


👁 View Evaluation


</a>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>


    <?php endforeach; ?>


<?php else: ?>


    <div class="empty">

        <h3>
            No Activities Found
        </h3>

        <p>
            There are no activities available.
        </p>

    </div>


<?php endif; ?>

</div>

</body>

</html>
