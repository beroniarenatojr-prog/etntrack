<?php
include 'db_connect.php';

$id = intval($_GET['id']);

$qry = $conn->query("SELECT * FROM activities WHERE id=$id");

if($qry->num_rows==0){
    die("Activity not found.");
}

$row=$qry->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>

<title>Verify Activity</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<style>

body{

background:#f0fff4;

}

.card{

margin:50px auto;

max-width:700px;

border:none;

border-radius:20px;

box-shadow:0 10px 30px rgba(0,0,0,.1);

}

.card-header{

background:#16a34a;

color:white;

}

</style>

</head>

<body>

<div class="card">

<div class="card-header">

<h3>Verified Activity</h3>

</div>

<div class="card-body">

<h4><?php echo $row['activity_name']; ?></h4>

<hr>

<p><strong>Purpose:</strong> <?php echo $row['purpose']; ?></p>

<p><strong>Date:</strong> <?php echo date("F d, Y",strtotime($row['activity_date'])); ?></p>

<p><strong>Venue:</strong> <?php echo $row['venue']; ?></p>

<p>

<strong>Status:</strong>

<?php

if($row['status']=="approved"){

echo "<span class='badge badge-success'>Approved</span>";

}else{

echo "<span class='badge badge-danger'>Not Approved</span>";

}

?>

</p>

</div>

</div>

</body>

</html>