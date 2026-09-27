<?php
include('db_connect.php');

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

$faculty_id = $_SESSION['login_id'];

// Dashboard Statistics
$total_reports = $conn->query("SELECT COUNT(*) as total FROM uploaded_reports WHERE uploaded_by='$faculty_id'")->fetch_assoc()['total'];

$approved = $conn->query("SELECT COUNT(*) as total FROM uploaded_reports WHERE uploaded_by='$faculty_id' AND status='Approved'")->fetch_assoc()['total'];

$pending = $conn->query("SELECT COUNT(*) as total FROM uploaded_reports WHERE uploaded_by='$faculty_id' AND status='Pending'")->fetch_assoc()['total'];

$revision = $conn->query("SELECT COUNT(*) as total FROM uploaded_reports WHERE uploaded_by='$faculty_id' AND status='Revision'")->fetch_assoc()['total'];
?>

<style>

/* ===========================
   DASHBOARD BACKGROUND
=========================== */

body{
    background:
        radial-gradient(circle at 10% 10%, rgba(25,135,84,.10) 0, transparent 25%),
        radial-gradient(circle at 90% 20%, rgba(46,125,50,.08) 0, transparent 25%),
        radial-gradient(circle at 80% 90%, rgba(13,110,253,.05) 0, transparent 25%),
        linear-gradient(135deg,#f8fbf9 0%,#eef7f1 50%,#f5f7fa 100%);
    min-height:100vh;
}

/* Main dashboard wrapper */

.container-fluid{
    position:relative;
    z-index:1;
}

/* ===========================
   HERO SECTION
=========================== */

.dashboard-card{
    border:none;
    border-radius:22px;
    background:transparent;
}

/* Hero */

.hero-card{
    position:relative;
    overflow:hidden;

    background:
        linear-gradient(135deg,
            rgba(255,255,255,.98),
            rgba(240,250,244,.96)
        );

    border:1px solid rgba(25,135,84,.08);
    border-radius:22px;

    padding:40px;
    margin-bottom:30px;

    box-shadow:
        0 15px 40px rgba(25,135,84,.10);

}

/* Decorative circles */

.hero-card::before{
    content:"";
    position:absolute;

    width:230px;
    height:230px;

    right:-80px;
    top:-100px;

    background:rgba(25,135,84,.10);
    border-radius:50%;
}

.hero-card::after{
    content:"";
    position:absolute;

    width:140px;
    height:140px;

    right:120px;
    bottom:-80px;

    background:rgba(25,135,84,.06);
    border-radius:50%;
}

/* Keep content above decorations */

.hero-card h2,
.hero-card p,
.today-date{
    position:relative;
    z-index:2;
}

.hero-card h2{
    font-size:38px;
    font-weight:700;
    color:#1f2937;
    margin-bottom:5px;
}

.hero-card span{
    color:#198754;
    font-weight:700;
}

.hero-card p{
    color:#6b7280;
    font-size:18px;
    margin-top:8px;
}

/* Date badge */

.today-date{
    display:inline-flex;
    align-items:center;
    gap:8px;

    margin-top:18px;

    background:#e7f6ec;
    color:#198754;

    padding:11px 18px;

    border-radius:12px;

    font-weight:600;

    border:1px solid rgba(25,135,84,.10);
}

/* ===========================
   SUMMARY CARDS
=========================== */

.summary-card{
    position:relative;
    overflow:hidden;

    border:none;
    border-radius:18px;

    color:white;

    padding:23px;

    min-height:145px;

    transition:
        transform .3s ease,
        box-shadow .3s ease;

    box-shadow:
        0 10px 25px rgba(0,0,0,.08);
}

.summary-card:hover{
    transform:translateY(-6px);

    box-shadow:
        0 18px 35px rgba(0,0,0,.13);
}

/* Decorative circle */

.summary-card::after{
    content:"";

    position:absolute;

    width:110px;
    height:110px;

    right:-35px;
    bottom:-45px;

    background:rgba(255,255,255,.10);

    border-radius:50%;
}

/* Second decorative circle */

.summary-card::before{
    content:"";

    position:absolute;

    width:55px;
    height:55px;

    right:35px;
    top:-25px;

    background:rgba(255,255,255,.07);

    border-radius:50%;
}

/* Green */

.bg-green{
    background:
        linear-gradient(135deg,#198754,#157347);
}

/* Blue */

.bg-blue{
    background:
        linear-gradient(135deg,#0d6efd,#3d8bfd);
}

/* Orange */

.bg-orange{
    background:
        linear-gradient(135deg,#fd7e14,#f59f00);
}

/* Red */

.bg-red{
    background:
        linear-gradient(135deg,#dc3545,#bb2d3b);
}

/* Summary text */

.summary-card h2{
    position:relative;
    z-index:2;

    font-size:36px;
    font-weight:700;

    margin-bottom:5px;
}

.summary-card{
    font-size:15px;
    font-weight:600;
}

.summary-card i{
    position:relative;
    z-index:2;

    font-size:42px;

    opacity:.28;

    float:right;
}

/* ===========================
   CONTENT CARDS
=========================== */

.card.dashboard-card{
    background:#ffffff;

    border:1px solid rgba(0,0,0,.04);

    border-radius:18px;

    overflow:hidden;

    box-shadow:
        0 10px 30px rgba(0,0,0,.06);

    transition:.3s ease;
}

.card.dashboard-card:hover{
    box-shadow:
        0 15px 35px rgba(0,0,0,.09);
}

/* Card headers */

.card-header.bg-success{
    background:
        linear-gradient(135deg,#198754,#157347) !important;

    border:none;

    padding:17px 20px;
}

.card-header h5{
    font-weight:600;
}

/* Card body */

.card-body{
    padding:25px;
}

.card-body p{
    color:#6b7280;
    line-height:1.7;
}

/* ===========================
   WHAT YOU CAN DO
=========================== */

.text-success{
    color:#198754 !important;
}

.card-body ul{
    padding-left:20px;
    margin-bottom:0;
}

.card-body li{
    color:#4b5563;
    margin-bottom:10px;
}

.card-body li::marker{
    color:#198754;
}

/* ===========================
   RESPONSIVE
=========================== */

@media(max-width:768px){

    .hero-card{
        padding:28px 22px;
    }

    .hero-card h2{
        font-size:28px;
    }

    .hero-card p{
        font-size:16px;
    }

    .summary-card{
        min-height:130px;
    }

}

</style>

<div class="container-fluid">

<div class="card dashboard-card mb-4">
<div class="hero-card">

    <h2>
        Good Afternoon,
        <span><?php echo $_SESSION['login_name']; ?></span> 👋
    </h2>

    <p>
        Extension Coordinators Dashboard
    </p>

    <div class="today-date">
        <i class="fa fa-calendar"></i>
        Today's Date:
        <b><?php echo date("F d, Y"); ?></b>
    </div>

</div>

<div class="row">

<div class="col-md-3 mb-3">

<div class="summary-card bg-green">

<i class="fa fa-file-pdf"></i>

<h2><?php echo $total_reports; ?></h2>

Submitted Reports

</div>

</div>

<div class="col-md-3 mb-3">

<div class="summary-card bg-blue">

<i class="fa fa-check-circle"></i>

<h2><?php echo $approved; ?></h2>

Approved

</div>

</div>

<div class="col-md-3 mb-3">

<div class="summary-card bg-orange">

<i class="fa fa-clock"></i>

<h2><?php echo $pending; ?></h2>

Pending

</div>

</div>

<div class="col-md-3 mb-3">

<div class="summary-card bg-red">

<i class="fa fa-edit"></i>

<h2><?php echo $revision; ?></h2>

Needs Revision

</div>

</div>

</div>



<div class="row">

<div class="col-md-12">

<div class="card dashboard-card">

<div class="card-header bg-success text-white">

<h5 class="mb-0">

<i class="fa fa-info-circle"></i>

Coordinator Dashboard

</h5>

</div>

<div class="card-body">

<p>

Welcome to <b>ExtenTrack Analytics</b>.

This system enables Extension Coordinators to upload accomplishment reports, monitor submission status, view submission history, and receive feedback from the Extension Office.

</p>

<hr>

<h6 class="text-success">

<i class="fa fa-check-circle"></i>

What you can do

</h6>

<ul>

<li>Upload accomplishment reports.</li>

<li>Track report approval status.</li>

<li>View submission history.</li>

<li>Receive feedback from the Extension Office.</li>

</ul>

</div>

</div>

</div>

</div>

</div>