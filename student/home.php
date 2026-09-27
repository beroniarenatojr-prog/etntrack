<?php 
include('C:\xampp\htdocs\eval\db_connect.php');

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

$astat = array("Not Yet Started","Started","Closed");
?>

<style>

.dashboard-welcome {
    background: linear-gradient(135deg,#667eea,#764ba2);
    border-radius:20px;
    padding:30px;
    color:white;
    box-shadow:0 10px 25px rgba(0,0,0,.15);
}

.welcome-title {
    font-size:28px;
    font-weight:700;
}

.welcome-sub {
    opacity:.9;
    margin-top:5px;
}


.info-card {
    margin-top:25px;
    border-radius:18px;
    background:white;
    padding:25px;
    box-shadow:0 5px 18px rgba(0,0,0,.08);
    border-left:6px solid #667eea;
}


.info-item {
    display:flex;
    align-items:center;
    gap:15px;
    margin-bottom:15px;
}


.icon-box {
    width:45px;
    height:45px;
    border-radius:12px;
    background:#eef1ff;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#667eea;
    font-size:20px;
}


.status {
    padding:6px 15px;
    border-radius:20px;
    background:#667eea;
    color:white;
    font-size:13px;
}


</style>


<div class="col-12">

    <div class="dashboard-welcome">

        <div class="welcome-title">
             Welcome, <?php echo $_SESSION['login_name']; ?>!
        </div>

        <div class="welcome-sub">
            Evaluation System Dashboard
        </div>


        <div class="info-card">

            <div class="info-item">

                <div class="icon-box">
                    <i class="fas fa-calendar-alt"></i>
                </div>

                <div>
                    <small class="text-muted">
                        Academic Year
                    </small>

                    <h5 class="mb-0">
                        <b>
                        <?php 
                        echo $_SESSION['academic']['year'].' '
                        .ordinal_suffix1($_SESSION['academic']['semester'])
                        .' Semester';
                        ?>
                        </b>
                    </h5>
                </div>

            </div>



            <div class="info-item">

                <div class="icon-box">
                    <i class="fas fa-clipboard-check"></i>
                </div>


                <div>

                    <small class="text-muted">
                        Evaluation Status
                    </small>

                    <h5 class="mb-0">

                        <span class="status">

                        <?php 
                        echo $astat[$_SESSION['academic']['status']];
                        ?>

                        </span>

                    </h5>

                </div>

            </div>


        </div>


    </div>

</div>