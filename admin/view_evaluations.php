<?php

include 'db_connect.php';


/* =========================================================
   GET EVALUATION ID
========================================================= */

$evaluation_id = isset($_GET['evaluation_id'])
    ? intval($_GET['evaluation_id'])
    : 0;


if ($evaluation_id <= 0) {

    die("Invalid Evaluation ID.");

}


/* =========================================================
   GET EVALUATION INFORMATION
========================================================= */

$info_sql = "

    SELECT

        ea.evaluation_id,

        ea.activity_id,

        ea.Evaluator_name,

        a.activity_name

    FROM evaluation_answers ea

    LEFT JOIN activities a
        ON a.id = ea.activity_id

    WHERE ea.evaluation_id = ?

    LIMIT 1

";


$stmt = $conn->prepare($info_sql);


if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    "i",
    $evaluation_id
);


$stmt->execute();


$info_result =
    $stmt->get_result();


$evaluation_info =
    $info_result->fetch_assoc();


$stmt->close();


if (!$evaluation_info) {

    die("Evaluation not found.");

}


/* =========================================================
   GET ALL ANSWERS
========================================================= */

$answer_sql = "

    SELECT

        ea.evaluation_id,

        ea.question_id,

        ea.rate,

        ea.Evaluator_name,

        q.question,

        q.order_by,

        q.criteria_id

    FROM evaluation_answers ea

    LEFT JOIN question_list q
        ON q.id = ea.question_id

    WHERE ea.evaluation_id = ?

    ORDER BY

        q.criteria_id ASC,
        q.order_by ASC

";


$stmt = $conn->prepare($answer_sql);


if (!$stmt) {

    die(
        "Database Error: " .
        htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    "i",
    $evaluation_id
);


$stmt->execute();


$result =
    $stmt->get_result();


/* =========================================================
   ORGANIZE BY CRITERIA
========================================================= */

$criteria = [];

$total_score = 0;
$total_answers = 0;


while ($row = $result->fetch_assoc()) {

    $criteria_id =
        intval($row['criteria_id']);


    if (!isset($criteria[$criteria_id])) {

        $criteria[$criteria_id] = [

            'id' =>
                $criteria_id,

            'questions' =>
                [],

            'total_score' =>
                0,

            'total_answers' =>
                0

        ];

    }


    $rate =
        intval($row['rate']);


    $criteria[$criteria_id]
        ['questions'][]
        = [

            'question' =>
                $row['question'],

            'rate' =>
                $rate,

            'order_by' =>
                $row['order_by']

        ];


    $criteria[$criteria_id]
        ['total_score']
        += $rate;


    $criteria[$criteria_id]
        ['total_answers']++;


    $total_score += $rate;

    $total_answers++;

}


$stmt->close();


/* =========================================================
   OVERALL AVERAGE
========================================================= */

$overall_average = 0;


if ($total_answers > 0) {

    $overall_average =
        round(
            $total_score /
            $total_answers,
            2
        );

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
    View Evaluation
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

        max-width: 1000px;

        margin: auto;

    }


    .top-card {

        background: #fff;

        padding: 25px;

        border-radius: 12px;

        margin-bottom: 20px;

        box-shadow:
            0 3px 12px
            rgba(0,0,0,.08);

    }


    .top-card h1 {

        margin: 0 0 20px;

        color: #198754;

    }


    .info-grid {

        display: grid;

        grid-template-columns:
            repeat(2, 1fr);

        gap: 15px;

    }


    .info-box {

        background: #f8faf9;

        padding: 15px;

        border-radius: 8px;

    }


    .info-label {

        display: block;

        font-size: 12px;

        color: #777;

        margin-bottom: 5px;

    }


    .info-value {

        font-size: 15px;

        font-weight: bold;

        color: #333;

    }


    .criteria-card {

        background: #fff;

        margin-bottom: 20px;

        border-radius: 12px;

        overflow: hidden;

        box-shadow:
            0 3px 12px
            rgba(0,0,0,.08);

    }


    .criteria-header {

        background: #198754;

        color: #fff;

        padding: 16px 20px;

        font-size: 17px;

        font-weight: bold;

    }


    table {

        width: 100%;

        border-collapse: collapse;

    }


    th {

        background: #f1f5f3;

        padding: 13px;

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

        width: 75%;

    }


    .rating {

        width: 25%;

        text-align: center;

        font-size: 18px;

        font-weight: bold;

        color: #198754;

    }


    .criteria-summary {

        padding: 15px 20px;

        background: #f8faf9;

        display: flex;

        gap: 25px;

        flex-wrap: wrap;

    }


    .summary-item {

        font-size: 14px;

    }


    .summary-item strong {

        color: #198754;

    }


    .overall {

        background: #fff;

        padding: 25px;

        border-radius: 12px;

        box-shadow:
            0 3px 12px
            rgba(0,0,0,.08);

        text-align: center;

        margin-top: 20px;

    }


    .overall h2 {

        margin-top: 0;

        color: #198754;

    }


    .overall-score {

        font-size: 36px;

        font-weight: bold;

        color: #198754;

    }


    .overall-label {

        display: inline-block;

        margin-top: 10px;

        padding: 7px 16px;

        background: #e8f5ee;

        color: #198754;

        border-radius: 20px;

        font-weight: bold;

    }


    .back-btn {

        display: inline-block;

        margin-bottom: 20px;

        padding: 9px 15px;

        background: #198754;

        color: #fff;

        text-decoration: none;

        border-radius: 7px;

        font-size: 14px;

    }


    .back-btn:hover {

        background: #146c43;

    }


    .no-answer {

        padding: 30px;

        text-align: center;

        color: #777;

    }


    @media (max-width: 700px) {

        body {

            padding: 15px;

        }


        .info-grid {

            grid-template-columns: 1fr;

        }


        .question {

            width: 65%;

        }


        .rating {

            width: 35%;

        }

    }

</style>


</head>

<body>

<div class="container">


<!-- =====================================================
     BACK
====================================================== -->

<a
href="/eval/index.php?page=evaluation_results"
class="back-btn"

>


← Back to Evaluation Results


</a>


<!-- =====================================================
     EVALUATION INFORMATION
====================================================== -->

<div class="top-card">

    <h1>
        Evaluation Details
    </h1>


    <div class="info-grid">


        <div class="info-box">

            <span class="info-label">
                Activity
            </span>

            <span class="info-value">

                <?= htmlspecialchars(
                    $evaluation_info[
                        'activity_name'
                    ]
                ) ?>

            </span>

        </div>


        <div class="info-box">

            <span class="info-label">
                Evaluator
            </span>

            <span class="info-value">

                <?= htmlspecialchars(
                    $evaluation_info[
                        'Evaluator_name'
                    ]
                ) ?>

            </span>

        </div>


        <div class="info-box">

            <span class="info-label">
                Evaluation ID
            </span>

            <span class="info-value">

                <?= htmlspecialchars(
                    $evaluation_info[
                        'evaluation_id'
                    ]
                ) ?>

            </span>

        </div>


        <div class="info-box">

            <span class="info-label">
                Activity ID
            </span>

            <span class="info-value">

                <?= htmlspecialchars(
                    $evaluation_info[
                        'activity_id'
                    ]
                ) ?>

            </span>

        </div>


    </div>

</div>


<!-- =====================================================
     CRITERIA
====================================================== -->

<?php if (!empty($criteria)): ?>


    <?php foreach (
        $criteria
        as $criterion
    ): ?>


        <div class="criteria-card">


            <div class="criteria-header">

                Criteria
                #<?= htmlspecialchars(
                    $criterion['id']
                ) ?>

            </div>


            <table>


                <thead>

                    <tr>

                        <th class="question">
                            Question
                        </th>

                        <th class="rating">
                            Rating
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach (
                    $criterion['questions']
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


                        <td class="rating">

                            <?= htmlspecialchars(
                                $question[
                                    'rate'
                                ]
                            ) ?>

                            / 5

                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>


            </table>


            <!-- =========================================
                 CRITERIA SUMMARY
            ========================================== -->

            <?php

            $criteria_average = 0;

            if (
                $criterion[
                    'total_answers'
                ] > 0
            ) {

                $criteria_average =
                    round(
                        $criterion[
                            'total_score'
                        ]
                        /
                        $criterion[
                            'total_answers'
                        ],
                        2
                    );

            }

            ?>


            <div class="criteria-summary">


                <div class="summary-item">

                    Total Questions:

                    <strong>

                        <?= htmlspecialchars(
                            $criterion[
                                'total_answers'
                            ]
                        ) ?>

                    </strong>

                </div>


                <div class="summary-item">

                    Total Score:

                    <strong>

                        <?= htmlspecialchars(
                            $criterion[
                                'total_score'
                            ]
                        ) ?>

                    </strong>

                </div>


                <div class="summary-item">

                    Average:

                    <strong>

                        <?= htmlspecialchars(
                            $criteria_average
                        ) ?>

                        / 5

                    </strong>

                </div>


                <div class="summary-item">

                    Result:

                    <strong>

                        <?= htmlspecialchars(
                            getRatingLabel(
                                $criteria_average
                            )
                        ) ?>

                    </strong>

                </div>


            </div>


        </div>


    <?php endforeach; ?>


<?php else: ?>


    <div class="criteria-card">

        <div class="no-answer">

            No answers found for this evaluation.

        </div>

    </div>


<?php endif; ?>


<!-- =====================================================
     OVERALL RESULT
====================================================== -->

<div class="overall">

    <h2>
        Overall Evaluation Result
    </h2>


    <div class="overall-score">

        <?= htmlspecialchars(
            $overall_average
        ) ?>

        / 5

    </div>


    <div class="overall-label">

        <?= htmlspecialchars(
            getRatingLabel(
                $overall_average
            )
        ) ?>

    </div>


    <p>

        Total Score:

        <strong>
            <?= htmlspecialchars(
                $total_score
            ) ?>
        </strong>

        &nbsp; | &nbsp;

        Total Questions:

        <strong>
            <?= htmlspecialchars(
                $total_answers
            ) ?>
        </strong>

    </p>

</div>


</div>

</body>

</html>
