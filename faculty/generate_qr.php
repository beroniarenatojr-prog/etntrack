<?php
session_start();

if(empty($_SESSION['login_id'])){
    header('Location: ../login.php');
    exit;
}

include '../db_connect.php';
include '../site_url.php';
include 'phpqrcode/qrlib.php';

if(!isset($_GET['id'])){
    die("Invalid Activity.");
}

$id = intval($_GET['id']);

$qry = $conn->query("SELECT * FROM activities WHERE id = $id");

if($qry->num_rows == 0){
    die("Activity not found.");
}

$row = $qry->fetch_assoc();

if($row['status'] != 'approved'){
    die("QR Code can only be generated for approved activities.");
}

$filename = "activity_".$id.".png";
$filepath = "../assets/qrcodes/".$filename;

// Where the QR code sends the participant. Built from the address the site is
// being used on, so a scanned code works on the live site as well as on XAMPP.
$data = site_url('evaluate.php?activity_id='.$id);
// Generate QR only once
// Always regenerate the QR
QRcode::png($data, $filepath, QR_ECLEVEL_H, 8);

?>

<!DOCTYPE html>
<html>
<head>

<title>Activity QR Code</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<style>

body{
    background:#f4fff7;
}

.card{

    max-width:500px;
    margin:50px auto;
    border:none;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.1);

}

.card-header{

    background:#16a34a;
    color:white;
    text-align:center;

}

.qr{

    text-align:center;
    padding:30px;

}

.qr img{

    width:260px;

}

</style>

</head>

<body>

<div class="card">

<div class="card-header">

<h3>Activity QR Code</h3>

</div>

<div class="qr">

<h5><?php echo $row['activity_name']; ?></h5>

<img src="<?php echo $filepath; ?>">

<br><br>

<a href="<?php echo $filepath; ?>"
download
class="btn btn-success">

Download QR

</a>

<button
onclick="window.print()"
class="btn btn-primary">

Print

</button>

</div>

</div>

</body>

</html>