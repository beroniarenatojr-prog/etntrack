<?php
include 'db_connect.php';

// Change this if you already pass academic_id
$activity_id = isset($_GET['activity_id'])
    ? intval($_GET['activity_id'])
    : 0;

if($activity_id == 0){
    die("Invalid QR Code.");
}

// Verify that the activity exists and is approved
$qry = $conn->query("SELECT * FROM activities WHERE id='$activity_id' AND status='approved'");

if($qry->num_rows == 0){
    die("Invalid Activity.");
}

$activity = $qry->fetch_assoc();

$qry = $conn->query("
SELECT
    c.id AS criteria_id,
    c.criteria,
    q.id AS question_id,
    q.question,
    q.order_by
FROM criteria_list c
INNER JOIN question_list q
    ON c.id = q.criteria_id
ORDER BY c.order_by ASC, q.order_by ASC
");

if(!$qry){
    die("SQL Error: ".$conn->error);
}

?>
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Research Extension Evaluation Form</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#eef2f7;
    font-family:'Segoe UI',sans-serif;
    padding:40px;
}

.container{
    width:100%;
    max-width:1100px;
    margin:auto;
}

.paper{

    background:#fff;
    border-radius:15px;
    overflow:hidden;
    box-shadow:0 8px 25px rgba(0,0,0,.12);

}

.header{

    background:#0F6D3D;
    color:#fff;
    text-align:center;
    padding:30px;

}

.header h2{

    font-size:30px;
    margin-bottom:8px;

}

.header h3{

    font-size:20px;
    font-weight:500;

}

.header p{

    margin-top:10px;
    opacity:.9;

}

.content{

    padding:35px;

}

.info-table{

    width:100%;
    margin-bottom:25px;
    border-collapse:collapse;

}

.info-table td{

    padding:12px;

}

.info-table input{

    width:100%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:8px;
    font-size:15px;

}

.instructions{

    border-left:6px solid #0F6D3D;
    background:#F0FFF5;
    padding:20px;
    margin-bottom:30px;

}

.instructions h4{

    color:#0F6D3D;
    margin-bottom:10px;

}

.rating-guide{

    width:100%;
    border-collapse:collapse;
    margin-top:15px;

}

.rating-guide th{

    background:#0F6D3D;
    color:#fff;
    padding:10px;

}

.rating-guide td{

    border:1px solid #ddd;
    text-align:center;
    padding:10px;

}

.section-title{

    margin-top:35px;
    margin-bottom:15px;
    background:#E8F8ED;
    color:#0F6D3D;
    padding:12px 18px;
    font-size:20px;
    font-weight:bold;
    border-left:7px solid #0F6D3D;

}

.evaluation-table{

    width:100%;
    border-collapse:collapse;
    margin-bottom:35px;

}

.evaluation-table th{

    background:#0F6D3D;
    color:#fff;
    padding:12px;
    border:1px solid #ddd;

}

.evaluation-table td{

    border:1px solid #ddd;
    padding:12px;

}

.evaluation-table tr:nth-child(even){

    background:#fafafa;

}

.center{

    text-align:center;

}

input[type=radio]{

    transform:scale(1.2);
    cursor:pointer;

}

textarea{

    width:100%;
    height:120px;
    border:1px solid #ccc;
    border-radius:8px;
    padding:15px;
    resize:none;
    font-size:15px;

}

.submit-btn{

    background:#0F6D3D;
    color:white;
    border:none;
    padding:15px 45px;
    font-size:18px;
    border-radius:8px;
    cursor:pointer;
    margin-top:25px;

}

.submit-btn:hover{

    background:#0b552f;

}

</style>

</head>

<body>

<div class="container">

<div class="paper">

<div class="header">

<h2>ISABELA STATE UNIVERSITY</h2>

<h3>Research Extension Evaluation Form</h3>

<p>
Extension Office
</p>

</div>

<div class="content">

<form action="save_evaluation.php" method="POST">

<input type="hidden"
name="activity_id"
value="<?php echo $activity_id; ?>">

<table class="info-table">

<tr>

<td width="220">

<strong>Evaluator Name</strong><br>
<small>(Optional)</small>

</td>

<td>

<input
type="text"
name="evaluator_name"
placeholder="Leave blank if you prefer to remain anonymous">

</td>

<td width="180">

<strong>Date</strong>

</td>

<td>

<input
type="text"
value="<?php echo date('F d, Y'); ?>"
readonly>

</td>

</tr>

</table>

<div class="instructions">

<h4>Instructions</h4>

<p>

Please evaluate the Research Extension member by selecting the rating that best
reflects your assessment for each statement.

</p>

<table class="rating-guide">

<tr>

<th>Rating</th>
<th>Description</th>

</tr>

<tr>

<td>5</td>
<td>Outstanding</td>

</tr>

<tr>

<td>4</td>
<td>Very Satisfactory</td>

</tr>

<tr>

<td>3</td>
<td>Satisfactory</td>

</tr>

<tr>

<td>2</td>
<td>Fair</td>

</tr>

<tr>

<td>1</td>
<td>Poor</td>

</tr>

</table>

</div>

<?php
$current_criteria = "";

while($row = $qry->fetch_assoc()){

    if($current_criteria != $row['criteria']){

        if($current_criteria != ""){
            echo "</tbody></table>";
        }

        $current_criteria = $row['criteria'];

        echo "<div class='section-title'>{$current_criteria}</div>";

        echo "
        <table class='evaluation-table'>

        <thead>

        <tr>

        <th width='55%'>Statement</th>
        <th class='center'>5</th>
        <th class='center'>4</th>
        <th class='center'>3</th>
        <th class='center'>2</th>
        <th class='center'>1</th>

        </tr>

        </thead>

        <tbody>
        ";
    }

    echo "<tr>";

    echo "<td>{$row['question']}</td>";

    for($i=5;$i>=1;$i--){

        echo "
        <td class='center'>
            <input
            type='radio'
            name='rate[{$row['question_id']}]'
            value='{$i}'
            required>
        </td>
        ";

    }

    echo "</tr>";

}

if($qry->num_rows > 0){
    echo "</tbody></table>";
}
?>

<h3 style="margin-bottom:10px;">
Comments and Suggestions
<span style="font-weight:normal;">(Optional)</span>
</h3>

<textarea
name="comments"
placeholder="Write your comments or suggestions here..."></textarea>

<br><br>

<div style="text-align:center;">

<button
type="submit"
class="submit-btn">

<i class="fa fa-paper-plane"></i>

Submit Evaluation

</button>

</div>

</form>

</div>

</div>

</div>

</body>

</html>