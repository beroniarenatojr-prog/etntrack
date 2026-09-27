<?php include '../db_connect.php' ?>

<?php
if(isset($_GET['id'])){
    $qry = $conn->query("
        SELECT *,
        CONCAT(firstname,' ',lastname) AS name
        FROM faculty_list
        WHERE id = ".$_GET['id']
    )->fetch_array();

    foreach($qry as $k => $v){
        $$k = $v;
    }
}
?>

<div class="container-fluid faculty-view">


<!-- PROFILE CARD -->
<div class="faculty-profile-card">

    <!-- TOP COVER -->
    <div class="faculty-cover">

        <div class="cover-pattern"></div>

        <div class="faculty-cover-content">

            <div class="faculty-title">

                <div class="faculty-icon">
                    <i class="fas fa-user-tie"></i>
                </div>

                <div>
                    <span class="profile-label">
                        Coordinator / Evaluator Profile
                    </span>

                    <h2>
                        <?php echo ucwords($name) ?>
                    </h2>

                    <p>
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($email) ?>
                    </p>
                </div>

            </div>

        </div>

    </div>


    <!-- PROFILE BODY -->
    <div class="faculty-body">

        <!-- AVATAR -->
        <div class="faculty-avatar-wrapper">

            <?php
            if(
                empty($avatar) ||
                (!empty($avatar) &&
                !is_file('../assets/uploads/'.$avatar))
            ):
            ?>

                <div class="faculty-avatar initials-avatar">

                    <?php
                    echo strtoupper(
                        substr($firstname,0,1).
                        substr($lastname,0,1)
                    );
                    ?>

                </div>

            <?php else: ?>

                <img
                    class="faculty-avatar"
                    src="assets/uploads/<?php echo htmlspecialchars($avatar) ?>"
                    alt="Faculty Avatar"
                >

            <?php endif ?>

        </div>


        <!-- NAME -->
        <div class="faculty-name-section">

            <h3>
                <?php echo ucwords($name) ?>
            </h3>

            <span class="faculty-status">
                <span class="status-dot"></span>
                Active Faculty
            </span>

        </div>


        <!-- INFORMATION -->
        <div class="faculty-info-grid">

            <!-- SCHOOL ID -->
            <div class="info-box">

                <div class="info-icon green">
                    <i class="fas fa-id-card"></i>
                </div>

                <div class="info-content">

                    <span>
                        School ID
                    </span>

                    <strong>
                        <?php echo htmlspecialchars($school_id) ?>
                    </strong>

                </div>

            </div>


            <!-- EMAIL -->
            <div class="info-box">

                <div class="info-icon blue">
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="info-content">

                    <span>
                        Email Address
                    </span>

                    <strong>
                        <?php echo htmlspecialchars($email) ?>
                    </strong>

                </div>

            </div>


            <!-- FACULTY TYPE -->
            <div class="info-box">

                <div class="info-icon purple">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>

                <div class="info-content">

                    <span>
                        Account Type
                    </span>

                    <strong>
                        Coordinator / Evaluator
                    </strong>

                </div>

            </div>


            <!-- ACCOUNT DATE -->
            <div class="info-box">

                <div class="info-icon orange">
                    <i class="fas fa-calendar-check"></i>
                </div>

                <div class="info-content">

                    <span>
                        Date Created
                    </span>

                    <strong>

                        <?php
                        if(isset($date_created) && !empty($date_created)){
                            echo date(
                                'F d, Y',
                                strtotime($date_created)
                            );
                        }else{
                            echo 'N/A';
                        }
                        ?>

                    </strong>

                </div>

            </div>

        </div>


        <!-- PROFILE FOOTER -->
        <div class="profile-note">

            <div class="note-icon">
                <i class="fas fa-info-circle"></i>
            </div>

            <div>

                <strong>
                    Coordinator Account
                </strong>

                <p>
                    This account is registered as a Research Extension evaluator
                    in the evaluation management system.
                </p>

            </div>

        </div>

    </div>

</div>

</div>

<!-- MODAL FOOTER -->

<div class="modal-footer faculty-modal-footer">


<button
    type="button"
    class="btn close-btn"
    data-dismiss="modal">

    <i class="fas fa-times"></i>
    Close

</button>


</div>

<style>

/* =========================================================
   FACULTY PROFILE
========================================================= */

.faculty-view{
    padding:0;
}


/* =========================================================
   MAIN CARD
========================================================= */

.faculty-profile-card{

    background:#fff;

    border:none;

    border-radius:20px;

    overflow:hidden;

    box-shadow:
        0 12px 35px rgba(0,0,0,.10);

}


/* =========================================================
   COVER
========================================================= */

.faculty-cover{

    position:relative;

    min-height:180px;

    background:
        linear-gradient(
            135deg,
            #198754,
            #157347,
            #0f5132
        );

    overflow:hidden;

}


.cover-pattern{

    position:absolute;

    width:420px;

    height:420px;

    border-radius:50%;

    border:70px solid rgba(255,255,255,.05);

    right:-180px;

    top:-250px;

}


.cover-pattern:after{

    content:"";

    position:absolute;

    width:280px;

    height:280px;

    border-radius:50%;

    border:50px solid rgba(255,255,255,.04);

    right:100px;

    top:100px;

}


.faculty-cover-content{

    position:relative;

    z-index:2;

    padding:30px;

    color:#fff;

}


.faculty-title{

    display:flex;

    align-items:center;

    gap:18px;

}


.faculty-icon{

    width:58px;

    height:58px;

    border-radius:16px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:rgba(255,255,255,.15);

    backdrop-filter:blur(5px);

    font-size:25px;

}


.profile-label{

    display:block;

    font-size:12px;

    text-transform:uppercase;

    letter-spacing:1px;

    opacity:.75;

    margin-bottom:5px;

}


.faculty-title h2{

    margin:0;

    font-size:27px;

    font-weight:700;

}


.faculty-title p{

    margin:6px 0 0;

    font-size:14px;

    opacity:.9;

}


.faculty-title p i{

    margin-right:6px;

}


/* =========================================================
   BODY
========================================================= */

.faculty-body{

    padding:30px;

    position:relative;

}


/* =========================================================
   AVATAR
========================================================= */

.faculty-avatar-wrapper{

    position:absolute;

    left:30px;

    top:-65px;

}


.faculty-avatar{

    width:125px;

    height:125px;

    border-radius:50%;

    object-fit:cover;

    border:6px solid #fff;

    box-shadow:
        0 8px 25px rgba(0,0,0,.18);

}


.initials-avatar{

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        linear-gradient(
            135deg,
            #20c997,
            #198754
        );

    color:white;

    font-size:36px;

    font-weight:700;

}


/* =========================================================
   NAME
========================================================= */

.faculty-name-section{

    padding-left:155px;

    min-height:75px;

    display:flex;

    flex-direction:column;

    justify-content:center;

}


.faculty-name-section h3{

    margin:0;

    font-size:24px;

    font-weight:700;

    color:#1f2937;

}


.faculty-status{

    display:inline-flex;

    align-items:center;

    width:max-content;

    margin-top:6px;

    padding:5px 11px;

    border-radius:20px;

    background:#e8f7ef;

    color:#198754;

    font-size:12px;

    font-weight:600;

}


.status-dot{

    width:7px;

    height:7px;

    border-radius:50%;

    background:#198754;

    margin-right:7px;

}


/* =========================================================
   INFORMATION GRID
========================================================= */

.faculty-info-grid{

    margin-top:28px;

    display:grid;

    grid-template-columns:repeat(2,1fr);

    gap:15px;

}


.info-box{

    display:flex;

    align-items:center;

    gap:14px;

    padding:17px;

    border:1px solid #edf0f2;

    border-radius:14px;

    background:#fff;

    transition:.25s;

}


.info-box:hover{

    transform:translateY(-3px);

    box-shadow:
        0 8px 20px rgba(0,0,0,.07);

    border-color:#d9f3e5;

}


.info-icon{

    width:48px;

    height:48px;

    min-width:48px;

    border-radius:13px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:19px;

}


.info-icon.green{

    background:#e8f7ef;

    color:#198754;

}


.info-icon.blue{

    background:#eaf2ff;

    color:#3b73d1;

}


.info-icon.purple{

    background:#f1eafe;

    color:#7952b3;

}


.info-icon.orange{

    background:#fff3df;

    color:#e58b20;

}


.info-content{

    min-width:0;

}


.info-content span{

    display:block;

    color:#8a929a;

    font-size:12px;

    font-weight:600;

    margin-bottom:4px;

}


.info-content strong{

    display:block;

    color:#343a40;

    font-size:14px;

    word-break:break-word;

}


/* =========================================================
   NOTE
========================================================= */

.profile-note{

    display:flex;

    align-items:flex-start;

    gap:12px;

    margin-top:22px;

    padding:15px 17px;

    border-radius:13px;

    background:#f0faf5;

    border:1px solid #d9f2e3;

}


.note-icon{

    color:#198754;

    font-size:19px;

    margin-top:2px;

}


.profile-note strong{

    display:block;

    color:#146c43;

    font-size:13px;

}


.profile-note p{

    margin:3px 0 0;

    color:#6c757d;

    font-size:12px;

    line-height:1.5;

}


/* =========================================================
   MODAL FOOTER
========================================================= */

.faculty-modal-footer{

    display:flex!important;

    justify-content:flex-end;

    padding:15px 20px!important;

    border-top:1px solid #edf0f2!important;

    background:#fafafa;

}


.close-btn{

    background:#198754;

    color:#fff!important;

    border:none;

    border-radius:10px;

    padding:9px 20px;

    font-weight:600;

    transition:.2s;

}


.close-btn:hover{

    background:#157347;

    transform:translateY(-1px);

}


.close-btn i{

    margin-right:6px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:767px){

    .faculty-cover{

        min-height:165px;

    }


    .faculty-cover-content{

        padding:22px;

    }


    .faculty-title{

        gap:12px;

    }


    .faculty-icon{

        width:48px;

        height:48px;

        font-size:20px;

    }


    .faculty-title h2{

        font-size:21px;

    }


    .faculty-title p{

        font-size:12px;

    }


    .faculty-body{

        padding:25px 20px;

    }


    .faculty-avatar-wrapper{

        left:20px;

    }


    .faculty-avatar{

        width:105px;

        height:105px;

    }


    .faculty-name-section{

        padding-left:125px;

        min-height:65px;

    }


    .faculty-name-section h3{

        font-size:19px;

    }


    .faculty-info-grid{

        grid-template-columns:1fr;

        margin-top:25px;

    }

}


@media(max-width:480px){

    .faculty-avatar-wrapper{

        position:relative;

        left:auto;

        top:auto;

        display:flex;

        justify-content:center;

        margin-top:-75px;

        margin-bottom:12px;

    }


    .faculty-avatar{

        width:110px;

        height:110px;

    }


    .faculty-name-section{

        padding-left:0;

        text-align:center;

        align-items:center;

    }


    .faculty-title{

        align-items:flex-start;

    }


    .faculty-title h2{

        font-size:18px;

    }


    .faculty-title p{

        max-width:190px;

        overflow:hidden;

        text-overflow:ellipsis;

        white-space:nowrap;

    }

}

</style>
