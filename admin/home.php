
<?php include('db_connect.php'); ?>

<?php

function ordinal_suffix1($num){

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

$astat = array("Not Yet Started","On-going","Closed");


/*
|--------------------------------------------------------------------------
| REAL EVALUATION ANALYTICS
|--------------------------------------------------------------------------
| All analytics below are based on the actual database structure:
|
| evaluation_answers
| evaluation_list
| question_list
| criteria_list
| activities
| faculty_list
| student_list
| users
|
*/


/* ==========================================================
   OVERALL EVALUATION AVERAGE
   ========================================================== */

$overall_average = 0;

$query = $conn->query("
    SELECT AVG(rate) AS average_rating
    FROM evaluation_answers
    WHERE rate BETWEEN 1 AND 5
");

if($query){

    $row = $query->fetch_assoc();

    if($row['average_rating'] !== null){

        $overall_average = round($row['average_rating'], 2);

    }

}


/* ==========================================================
   TOTAL EVALUATIONS
   ========================================================== */

$total_evaluations = 0;

$query = $conn->query("
    SELECT COUNT(DISTINCT evaluation_id) AS total
    FROM evaluation_answers
");

if($query){

    $row = $query->fetch_assoc();

    $total_evaluations = (int)$row['total'];

}


/* ==========================================================
   TOTAL RATINGS / ANSWERS
   ========================================================== */

$total_ratings = 0;

$query = $conn->query("
    SELECT COUNT(*) AS total
    FROM evaluation_answers
    WHERE rate BETWEEN 1 AND 5
");

if($query){

    $row = $query->fetch_assoc();

    $total_ratings = (int)$row['total'];

}


/* ==========================================================
   EVALUATED ACTIVITIES
   ========================================================== */

$evaluated_activities = 0;

$query = $conn->query("
    SELECT COUNT(DISTINCT activity_id) AS total
    FROM evaluation_answers
    WHERE activity_id > 0
");

if($query){

    $row = $query->fetch_assoc();

    $evaluated_activities = (int)$row['total'];

}


/* ==========================================================
   ACTIVITY ANALYTICS
   ========================================================== */

$activity_labels = array();
$activity_scores = array();
$activity_counts = array();

$query = $conn->query("
    SELECT
        ea.activity_id,
        a.activity_name,
        AVG(ea.rate) AS average_rating,
        COUNT(DISTINCT ea.evaluation_id) AS evaluation_count

    FROM evaluation_answers ea

    LEFT JOIN activities a
        ON a.id = ea.activity_id

    WHERE ea.activity_id > 0
      AND ea.rate BETWEEN 1 AND 5

    GROUP BY
        ea.activity_id,
        a.activity_name

    ORDER BY average_rating DESC
");

if($query){

    while($row = $query->fetch_assoc()){

        $activity_name = trim($row['activity_name']);

        if($activity_name == ''){

            $activity_name =
                'Activity #' . $row['activity_id'];

        }

        $activity_labels[] = $activity_name;

        $activity_scores[] =
            round((float)$row['average_rating'], 2);

        $activity_counts[] =
            (int)$row['evaluation_count'];

    }

}


/* ==========================================================
   RATING DISTRIBUTION
   ========================================================== */

$rating_counts = array(
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0
);

$query = $conn->query("
    SELECT
        rate,
        COUNT(*) AS total

    FROM evaluation_answers

    WHERE rate BETWEEN 1 AND 5

    GROUP BY rate

    ORDER BY rate ASC
");

if($query){

    while($row = $query->fetch_assoc()){

        $rating =
            (int)$row['rate'];

        if(isset($rating_counts[$rating])){

            $rating_counts[$rating] =
                (int)$row['total'];

        }

    }

}


/* ==========================================================
   CRITERIA ANALYTICS
   ========================================================== */

$criteria_labels = array();
$criteria_scores = array();
$criteria_counts = array();

$query = $conn->query("
    SELECT

        c.id AS criteria_id,

        c.criteria,

        AVG(ea.rate) AS average_rating,

        COUNT(ea.id) AS total_answers

    FROM evaluation_answers ea

    INNER JOIN question_list q
        ON q.id = ea.question_id

    INNER JOIN criteria_list c
        ON c.id = q.criteria_id

    WHERE ea.rate BETWEEN 1 AND 5

    GROUP BY
        c.id,
        c.criteria,
        c.order_by

    ORDER BY
        c.order_by ASC,
        c.id ASC
");

if($query){

    while($row = $query->fetch_assoc()){

        $criteria_name =
            trim($row['criteria']);

        if($criteria_name == ''){

            $criteria_name =
                'Criteria #' . $row['criteria_id'];

        }

        $criteria_labels[] =
            $criteria_name;

        $criteria_scores[] =
            round((float)$row['average_rating'], 2);

        $criteria_counts[] =
            (int)$row['total_answers'];

    }

}


/* ==========================================================
   EVALUATION TREND
   ========================================================== */

$trend_labels = array();
$trend_values = array();

$query = $conn->query("
    SELECT

        DATE(submitted_at) AS evaluation_date,

        AVG(rate) AS average_rating

    FROM evaluation_answers

    WHERE rate BETWEEN 1 AND 5
    AND submitted_at IS NOT NULL

    GROUP BY DATE(submitted_at)

    ORDER BY DATE(submitted_at) ASC
");

if($query){

    while($row = $query->fetch_assoc()){

        $trend_labels[] =
            date(
                'M d, Y',
                strtotime($row['evaluation_date'])
            );

        $trend_values[] =
            round((float)$row['average_rating'], 2);

    }

}


/* ==========================================================
   TOP ACTIVITY
   ========================================================== */

$top_activity = 'No data';

$top_activity_score = 0;

if(count($activity_labels) > 0){

    $top_activity =
        $activity_labels[0];

    $top_activity_score =
        $activity_scores[0];

}


/* ==========================================================
   RATING PERCENTAGE
   ========================================================== */

$rating_percentage = 0;

if($total_ratings > 0){

    $rating_percentage =
        round(
            ($overall_average / 5) * 100,
            1
        );

}

?>



<!-- ==========================================================
     WELCOME CARD
========================================================== -->

<div class="welcome-card">

    <div>

        <h2>

            Welcome,
            <?php echo htmlspecialchars($_SESSION['login_name']); ?>

            👋

        </h2>

        <p>

            Evaluation Management Dashboard

        </p>

    </div>


    <i class="fas fa-chart-line welcome-icon"></i>

</div>



<!-- ==========================================================
     STAT CARDS
========================================================== -->

<div class="row dashboard-row">


    <!-- EVALUATORS -->

    <div class="col-md-3 col-sm-6">

        <div class="dashboard-card">

            <div class="card-content">

                <h2>

                    <?php

                    $result =
                        $conn->query("
                            SELECT COUNT(*) AS total
                            FROM faculty_list
                        ");

                    $row =
                        $result->fetch_assoc();

                    echo number_format(
                        $row['total']
                    );

                    ?>

                </h2>

                <p>Extension Coordinators</p>

            </div>


            <div class="card-icon">

                <i class="fas fa-user-tie"></i>

            </div>

        </div>

    </div>



    <!-- EXTENSION COORDINATORS -->

    <div class="col-md-3 col-sm-6">

        <div class="dashboard-card">

            <div class="card-content">

                <h2>

                    <?php

                    $result =
                        $conn->query("
                            SELECT COUNT(DISTINCT evaluation_id) AS total
                            FROM evaluation_answers
                            WHERE activity_id > 0
                        ");

                    $row =
                        $result->fetch_assoc();

                    echo number_format(
                        $row['total']
                    );

                    ?>

                </h2>

                <p>Evaluators</p>

            </div>


            <div class="card-icon">

                <i class="fas fa-users"></i>

            </div>

        </div>

    </div>



    <!-- TOTAL USERS -->

    <div class="col-md-3 col-sm-6">

        <div class="dashboard-card">

            <div class="card-content">

                <h2>

                    <?php

                    $result =
                        $conn->query("
                            SELECT COUNT(*) AS total
                            FROM users
                        ");

                    $row =
                        $result->fetch_assoc();

                    echo number_format(
                        $row['total']
                    );

                    ?>

                </h2>

                <p>Administrators</p>

            </div>


            <div class="card-icon">

                <i class="fas fa-user-cog"></i>

            </div>

        </div>

    </div>



    <!-- EXTENSION ACTIVITIES -->

    <div class="col-md-3 col-sm-6">

        <div class="dashboard-card">

            <div class="card-content">

                <h2>

                    <?php

                    $result =
                        $conn->query("
                            SELECT COUNT(*) AS total
                            FROM activities
                        ");

                    $row =
                        $result->fetch_assoc();

                    echo number_format(
                        $row['total']
                    );

                    ?>

                </h2>

                <p>Extension Activities</p>

            </div>


            <div class="card-icon">

                <i class="fas fa-clipboard-list"></i>

            </div>

        </div>

    </div>


</div>



<!-- ==========================================================
     ANALYTICS HEADER
========================================================== -->

<div class="analytics-header">

    <div>

        <h2>

            <i class="fas fa-chart-pie"></i>

            Evaluation Data Analytics

        </h2>

        <p>

            Real-time analysis of extension activity
            evaluation results

        </p>

    </div>

</div>



<!-- ==========================================================
     ANALYTICS SUMMARY CARDS
========================================================== -->

<div class="row analytics-row">


    <!-- OVERALL AVERAGE -->

    <div class="col-md-3 col-sm-6">

        <div class="analytics-card">

            <div class="analytics-title">

                Overall Evaluation Average

            </div>


            <div class="average-score">

                <span class="big-average">

                    <?php
                    echo number_format(
                        $overall_average,
                        2
                    );
                    ?>

                </span>

                <span class="out-of">

                    / 5.00

                </span>

            </div>


            <div class="score-bar">

                <div
                    class="score-fill"
                    style="
                    width:<?php echo $rating_percentage; ?>%;
                    ">
                </div>

            </div>


            <p class="analytics-description">

                Average rating from all submitted
                evaluation answers.

            </p>

        </div>

    </div>



    <!-- TOTAL EVALUATIONS -->

    <div class="col-md-3 col-sm-6">

        <div class="analytics-card">

            <div class="analytics-title">

                Total Evaluations

            </div>


            <div class="analytics-number">

                <?php
                echo number_format(
                    $total_evaluations
                );
                ?>

            </div>


            <div class="analytics-icon green">

                <i class="fas fa-file-alt"></i>

            </div>


            <p class="analytics-description">

                Unique evaluation submissions.

            </p>

        </div>

    </div>



    <!-- TOTAL RATINGS -->

    <div class="col-md-3 col-sm-6">

        <div class="analytics-card">

            <div class="analytics-title">

                Total Ratings

            </div>


            <div class="analytics-number">

                <?php
                echo number_format(
                    $total_ratings
                );
                ?>

            </div>


            <div class="analytics-icon blue">

                <i class="fas fa-star"></i>

            </div>


            <p class="analytics-description">

                Individual ratings recorded.

            </p>

        </div>

    </div>



    <!-- EVALUATED ACTIVITIES -->

    <div class="col-md-3 col-sm-6">

        <div class="analytics-card">

            <div class="analytics-title">

                Evaluated Activities

            </div>


            <div class="analytics-number">

                <?php
                echo number_format(
                    $evaluated_activities
                );
                ?>

            </div>


            <div class="analytics-icon purple">

                <i class="fas fa-project-diagram"></i>

            </div>


            <p class="analytics-description">

                Activities with submitted evaluations.

            </p>

        </div>

    </div>


</div>



<!-- ==========================================================
     ACTIVITY PERFORMANCE + RATING DISTRIBUTION
========================================================== -->

<div class="row">


    <!-- ACTIVITY PERFORMANCE -->

    <div class="col-md-8">

        <div class="chart-card">

            <div class="chart-header">

                <div>

                    <h3>

                        Evaluation Results per Activity

                    </h3>

                    <p>

                        Average rating of each evaluated
                        extension activity

                    </p>

                </div>


                <i class="fas fa-chart-bar"></i>

            </div>


            <div class="chart-container">

                <?php if(count($activity_labels) > 0){ ?>

                    <canvas id="activityChart"></canvas>

                <?php } else { ?>

                    <div class="no-data">

                        <i class="fas fa-chart-bar"></i>

                        <p>
                            No activity evaluation data available.
                        </p>

                    </div>

                <?php } ?>

            </div>

        </div>

    </div>



    <!-- RATING DISTRIBUTION -->

    <div class="col-md-4">

        <div class="chart-card">

            <div class="chart-header">

                <div>

                    <h3>

                        Rating Distribution

                    </h3>

                    <p>

                        Actual evaluation ratings

                    </p>

                </div>


                <i class="fas fa-star"></i>

            </div>


            <div class="chart-container">

                <?php if($total_ratings > 0){ ?>

                    <canvas id="ratingChart"></canvas>

                <?php } else { ?>

                    <div class="no-data">

                        <i class="fas fa-star"></i>

                        <p>
                            No rating data available.
                        </p>

                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</div>



<!-- ==========================================================
     CRITERIA PERFORMANCE
========================================================== -->

<div class="row">

    <div class="col-md-12">

        <div class="chart-card">

            <div class="chart-header">

                <div>

                    <h3>

                        Evaluation Criteria Performance

                    </h3>

                    <p>

                        Actual average rating for each
                        evaluation criterion

                    </p>

                </div>


                <i class="fas fa-tasks"></i>

            </div>


            <div class="chart-container criteria-chart">

                <?php if(count($criteria_labels) > 0){ ?>

                    <canvas id="criteriaChart"></canvas>

                <?php } else { ?>

                    <div class="no-data">

                        <i class="fas fa-tasks"></i>

                        <p>
                            No criteria evaluation data available.
                        </p>

                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</div>



<!-- ==========================================================
     EVALUATION TREND
========================================================== -->

<div class="row">

    <div class="col-md-8">

        <div class="chart-card">

            <div class="chart-header">

                <div>

                    <h3>

                        Evaluation Trend

                    </h3>

                    <p>

                        Average evaluation rating over time

                    </p>

                </div>


                <i class="fas fa-chart-line"></i>

            </div>


            <div class="chart-container">

                <?php if(count($trend_labels) > 0){ ?>

                    <canvas id="trendChart"></canvas>

                <?php } else { ?>

                    <div class="no-data">

                        <i class="fas fa-chart-line"></i>

                        <p>
                            No trend data available.
                        </p>

                    </div>

                <?php } ?>

            </div>

        </div>

    </div>



    <!-- TOP ACTIVITY -->

    <div class="col-md-4">

        <div class="chart-card top-activity-card">

            <div class="chart-header">

                <div>

                    <h3>

                        Top Performing Activity

                    </h3>

                    <p>

                        Highest average evaluation rating

                    </p>

                </div>


                <i class="fas fa-trophy"></i>

            </div>


            <?php if($top_activity != 'No data'){ ?>

                <div class="top-activity-content">

                    <div class="trophy-icon">

                        <i class="fas fa-trophy"></i>

                    </div>


                    <h4>

                        <?php
                        echo htmlspecialchars(
                            $top_activity
                        );
                        ?>

                    </h4>


                    <div class="top-score">

                        <?php
                        echo number_format(
                            $top_activity_score,
                            2
                        );
                        ?>

                        <span>/ 5.00</span>

                    </div>


                    <div class="top-label">

                        Highest Rated Activity

                    </div>

                </div>

            <?php } else { ?>

                <div class="no-data">

                    <i class="fas fa-trophy"></i>

                    <p>
                        No evaluated activity yet.
                    </p>

                </div>

            <?php } ?>

        </div>

    </div>

</div>



<!-- ==========================================================
     CHART.JS
========================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function(){

        /* =====================================================
           ACTIVITY CHART
        ===================================================== */

        const activityCanvas =
            document.getElementById(
                'activityChart'
            );

        if(activityCanvas){

            new Chart(
                activityCanvas,
                {

                    type: 'bar',

                    data: {

                        labels:

                            <?php
                            echo json_encode(
                                $activity_labels,
                                JSON_UNESCAPED_UNICODE
                            );
                            ?>,

                        datasets: [

                            {

                                label:
                                    'Average Rating',

                                data:

                                    <?php
                                    echo json_encode(
                                        $activity_scores
                                    );
                                    ?>,

                                borderWidth: 1,

                                borderRadius: 8,

                                maxBarThickness: 55

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        scales: {

                            y: {

                                beginAtZero: true,

                                max: 5,

                                ticks: {

                                    stepSize: 1

                                },

                                title: {

                                    display: true,

                                    text:
                                        'Average Rating'

                                }

                            },

                            x: {

                                ticks: {

                                    autoSkip: false,

                                    maxRotation: 45,

                                    minRotation: 0

                                }

                            }

                        },


                        plugins: {

                            legend: {

                                display: false

                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function(context){

                                            return ' Average Rating: '
                                                +
                                                context.raw
                                                +
                                                ' / 5';

                                        }

                                }

                            }

                        }

                    }

                }

            );

        }



        /* =====================================================
           RATING DISTRIBUTION
        ===================================================== */

        const ratingCanvas =
            document.getElementById(
                'ratingChart'
            );

        if(ratingCanvas){

            new Chart(
                ratingCanvas,
                {

                    type: 'doughnut',

                    data: {

                        labels: [

                            '5 - Excellent',

                            '4 - Very Good',

                            '3 - Good',

                            '2 - Fair',

                            '1 - Poor'

                        ],

                        datasets: [

                            {

                                data: [

                                    <?php
                                    echo $rating_counts[5];
                                    ?>,

                                    <?php
                                    echo $rating_counts[4];
                                    ?>,

                                    <?php
                                    echo $rating_counts[3];
                                    ?>,

                                    <?php
                                    echo $rating_counts[2];
                                    ?>,

                                    <?php
                                    echo $rating_counts[1];
                                    ?>

                                ],

                                borderWidth: 2

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        cutout: '62%',


                        plugins: {

                            legend: {

                                position: 'bottom',

                                labels: {

                                    padding: 15,

                                    usePointStyle: true

                                }

                            }

                        }

                    }

                }

            );

        }



        /* =====================================================
           CRITERIA CHART
        ===================================================== */

        const criteriaCanvas =
            document.getElementById(
                'criteriaChart'
            );

        if(criteriaCanvas){

            new Chart(
                criteriaCanvas,
                {

                    type: 'bar',

                    data: {

                        labels:

                            <?php
                            echo json_encode(
                                $criteria_labels,
                                JSON_UNESCAPED_UNICODE
                            );
                            ?>,

                        datasets: [

                            {

                                label:
                                    'Average Rating',

                                data:

                                    <?php
                                    echo json_encode(
                                        $criteria_scores
                                    );
                                    ?>,

                                borderWidth: 1,

                                borderRadius: 8,

                                maxBarThickness: 45

                            }

                        ]

                    },


                    options: {

                        indexAxis: 'y',

                        responsive: true,

                        maintainAspectRatio: false,

                        scales: {

                            x: {

                                beginAtZero: true,

                                max: 5,

                                ticks: {

                                    stepSize: 1

                                },

                                title: {

                                    display: true,

                                    text:
                                        'Average Rating'

                                }

                            }

                        },


                        plugins: {

                            legend: {

                                display: false

                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function(context){

                                            return ' Average Rating: '
                                                +
                                                context.raw
                                                +
                                                ' / 5';

                                        }

                                }

                            }

                        }

                    }

                }

            );

        }



        /* =====================================================
           EVALUATION TREND
        ===================================================== */

        const trendCanvas =
            document.getElementById(
                'trendChart'
            );

        if(trendCanvas){

            new Chart(
                trendCanvas,
                {

                    type: 'line',

                    data: {

                        labels:

                            <?php
                            echo json_encode(
                                $trend_labels,
                                JSON_UNESCAPED_UNICODE
                            );
                            ?>,

                        datasets: [

                            {

                                label:
                                    'Average Rating',

                                data:

                                    <?php
                                    echo json_encode(
                                        $trend_values
                                    );
                                    ?>,

                                borderWidth: 3,

                                tension: 0.35,

                                fill: false,

                                pointRadius: 5,

                                pointHoverRadius: 7

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        scales: {

                            y: {

                                beginAtZero: true,

                                max: 5,

                                ticks: {

                                    stepSize: 1

                                }

                            }

                        },


                        plugins: {

                            legend: {

                                display: false

                            }

                        }

                    }

                }

            );

        }

    }

);

</script>



<style>

/* =========================================================
   WELCOME
========================================================= */

.welcome-card{

    background:
        linear-gradient(
            135deg,
            #10b981,
            #047857
        );

    color:white;

    padding:30px;

    border-radius:20px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:25px;

    box-shadow:
        0 10px 25px
        rgba(16,185,129,.25);

}


.welcome-card h2{

    font-size:26px;

    font-weight:600;

    margin:0 0 5px;

}


.welcome-card p{

    margin:0;

    opacity:.9;

}


.welcome-icon{

    font-size:70px;

    opacity:.25;

}



/* =========================================================
   DASHBOARD CARDS
========================================================= */

.dashboard-card{

    background:white;

    border-radius:18px;

    padding:22px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:25px;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,.08);

    transition:.3s;

    border:1px solid #ecfdf5;

}


.dashboard-card:hover{

    transform:translateY(-5px);

    box-shadow:
        0 15px 30px
        rgba(16,185,129,.18);

}


.card-content h2{

    font-size:35px;

    font-weight:700;

    margin:0;

    color:#047857;

}


.card-content p{

    margin-top:8px;

    font-size:15px;

    color:#6b7280;

    font-weight:600;

}


.card-icon{

    height:60px;

    width:60px;

    border-radius:50%;

    background:#ecfdf5;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:26px;

    color:#10b981;

}


.dashboard-row{

    margin-top:10px;

}



/* =========================================================
   ANALYTICS HEADER
========================================================= */

.analytics-header{

    background:white;

    border-radius:18px;

    padding:22px 25px;

    margin:5px 0 20px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    border-left:5px solid #10b981;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,.06);

}


.analytics-header h2{

    margin:0;

    font-size:22px;

    font-weight:700;

    color:#064e3b;

}


.analytics-header h2 i{

    color:#10b981;

    margin-right:8px;

}


.analytics-header p{

    margin:5px 0 0;

    color:#6b7280;

    font-size:14px;

}



/* =========================================================
   ANALYTICS CARDS
========================================================= */

.analytics-row{

    margin-bottom:5px;

}


.analytics-card{

    background:white;

    border-radius:18px;

    padding:25px;

    margin-bottom:25px;

    min-height:190px;

    position:relative;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,.07);

    border:1px solid #ecfdf5;

    transition:.3s;

}


.analytics-card:hover{

    transform:translateY(-4px);

    box-shadow:
        0 15px 30px
        rgba(16,185,129,.15);

}


.analytics-title{

    color:#6b7280;

    font-size:14px;

    font-weight:600;

    margin-bottom:12px;

}


.average-score{

    display:flex;

    align-items:baseline;

    gap:5px;

}


.big-average{

    font-size:42px;

    font-weight:800;

    color:#047857;

}


.out-of{

    font-size:16px;

    color:#9ca3af;

}


.analytics-number{

    font-size:42px;

    font-weight:800;

    color:#047857;

    margin-top:5px;

}


.analytics-description{

    color:#9ca3af;

    font-size:13px;

    margin-top:15px;

    line-height:1.5;

}



/* =========================================================
   ANALYTICS ICONS
========================================================= */

.analytics-icon{

    position:absolute;

    right:25px;

    top:55px;

    width:55px;

    height:55px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:22px;

}


.analytics-icon.green{

    background:#ecfdf5;

    color:#10b981;

}


.analytics-icon.blue{

    background:#eff6ff;

    color:#3b82f6;

}


.analytics-icon.purple{

    background:#f5f3ff;

    color:#8b5cf6;

}



/* =========================================================
   SCORE BAR
========================================================= */

.score-bar{

    width:100%;

    height:9px;

    background:#ecfdf5;

    border-radius:20px;

    margin-top:10px;

    overflow:hidden;

}


.score-fill{

    height:100%;

    background:#10b981;

    border-radius:20px;

    transition:width .5s ease;

}



/* =========================================================
   CHART CARD
========================================================= */

.chart-card{

    background:white;

    border-radius:18px;

    padding:25px;

    margin-bottom:25px;

    box-shadow:
        0 8px 20px
        rgba(0,0,0,.07);

    border:1px solid #ecfdf5;

}


.chart-header{

    display:flex;

    justify-content:space-between;

    align-items:flex-start;

    margin-bottom:20px;

}


.chart-header h3{

    margin:0;

    font-size:18px;

    font-weight:700;

    color:#064e3b;

}


.chart-header p{

    margin:5px 0 0;

    font-size:13px;

    color:#9ca3af;

}


.chart-header > i{

    font-size:24px;

    color:#10b981;

    background:#ecfdf5;

    padding:12px;

    border-radius:12px;

}



/* =========================================================
   CHART CONTAINER
========================================================= */

.chart-container{

    height:320px;

    position:relative;

}


.criteria-chart{

    height:350px;

}



/* =========================================================
   NO DATA
========================================================= */

.no-data{

    height:100%;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    color:#9ca3af;

    text-align:center;

}


.no-data i{

    font-size:40px;

    margin-bottom:12px;

    color:#d1d5db;

}


.no-data p{

    margin:0;

    font-size:14px;

}



/* =========================================================
   TOP ACTIVITY
========================================================= */

.top-activity-card{

    min-height:395px;

}


.top-activity-content{

    text-align:center;

    padding-top:15px;

}


.trophy-icon{

    width:75px;

    height:75px;

    border-radius:50%;

    background:#ecfdf5;

    color:#10b981;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:32px;

    margin:0 auto 18px;

}


.top-activity-content h4{

    font-size:18px;

    font-weight:700;

    color:#064e3b;

    line-height:1.4;

    margin:10px auto;

}


.top-score{

    font-size:38px;

    font-weight:800;

    color:#047857;

    margin-top:15px;

}


.top-score span{

    font-size:14px;

    color:#9ca3af;

    font-weight:500;

}


.top-label{

    font-size:13px;

    color:#9ca3af;

    margin-top:5px;

}



/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:767px){

    .welcome-card{

        padding:22px;

    }


    .welcome-card h2{

        font-size:21px;

    }


    .welcome-icon{

        font-size:45px;

    }


    .analytics-card{

        min-height:170px;

    }


    .chart-card{

        padding:18px;

    }


    .chart-container{

        height:280px;

    }


    .criteria-chart{

        height:320px;

    }

}

</style>

