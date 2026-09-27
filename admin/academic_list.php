
<?php include 'db_connect.php' ?>

<?php

/* =========================================================
   ACADEMIC YEAR COUNTS
========================================================= */

$total_academic = 0;
$active_academic = 0;
$default_academic = 0;
$closed_academic = 0;

$q = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 1) AS active,
        SUM(is_default = 1) AS default_year,
        SUM(status = 2) AS closed
    FROM academic_list
");

if($q){
    $stats = $q->fetch_assoc();

    $total_academic   = (int)$stats['total'];
    $active_academic  = (int)$stats['active'];
    $default_academic = (int)$stats['default_year'];
    $closed_academic  = (int)$stats['closed'];
}


/* =========================================================
   ORDINAL SEMESTER
========================================================= */

function semester_label($semester){

    $semester = (int)$semester;

    if($semester == 1){
        return '1st Semester';
    }

    if($semester == 2){
        return '2nd Semester';
    }

    if($semester == 3){
        return '3rd Semester';
    }

    return $semester . 'th Semester';
}

?>



<div class="academic-wrapper">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-intro">

        <div class="page-intro-left">

            <div class="page-icon">

                <i class="fas fa-calendar-alt"></i>

            </div>

            <div>

                <h2>
                    Academic Year Management
                </h2>

                <p>
                    Manage academic periods and evaluation status
                </p>

            </div>

        </div>


        <button
            class="btn add-academic-btn new_academic">

            <i class="fas fa-plus"></i>

            <span>
                Add New Academic Year
            </span>

        </button>

    </div>



    <!-- =====================================================
         SUMMARY CARDS
    ====================================================== -->

    <div class="academic-stats">


        <!-- TOTAL -->

        <div class="academic-stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Total Academic Years
                </span>

                <strong>
                    <?php echo number_format($total_academic); ?>
                </strong>

            </div>

            <div class="stat-icon green">

                <i class="fas fa-calendar-check"></i>

            </div>

        </div>



        <!-- ACTIVE -->

        <div class="academic-stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Ongoing Evaluations
                </span>

                <strong>
                    <?php echo number_format($active_academic); ?>
                </strong>

            </div>

            <div class="stat-icon emerald">

                <i class="fas fa-play-circle"></i>

            </div>

        </div>



        <!-- DEFAULT -->

        <div class="academic-stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Default Academic Year
                </span>

                <strong>
                    <?php echo number_format($default_academic); ?>
                </strong>

            </div>

            <div class="stat-icon blue">

                <i class="fas fa-star"></i>

            </div>

        </div>



        <!-- CLOSED -->

        <div class="academic-stat-card">

            <div class="stat-info">

                <span class="stat-label">
                    Closed Periods
                </span>

                <strong>
                    <?php echo number_format($closed_academic); ?>
                </strong>

            </div>

            <div class="stat-icon gray">

                <i class="fas fa-lock"></i>

            </div>

        </div>


    </div>



    <!-- =====================================================
         MAIN TABLE CARD
    ====================================================== -->

    <div class="card academic-card">


        <!-- CARD HEADER -->

        <div class="academic-card-header">

            <div>

                <h3>

                    <i class="fas fa-list"></i>

                    Academic Periods

                </h3>

                <p>
                    View and manage all registered academic years
                </p>

            </div>


            <div class="record-count">

                <i class="fas fa-database"></i>

                <?php echo number_format($total_academic); ?>

                Records

            </div>

        </div>



        <!-- CARD BODY -->

        <div class="academic-card-body">


            <div class="table-responsive">


                <table
                    class="table modern-table"
                    id="list">


                    <thead>

                        <tr>

                            <th class="number-column">
                                #
                            </th>

                            <th>
                                Academic Year
                            </th>

                            <th>
                                Semester
                            </th>

                            <th class="center-column">
                                System Default
                            </th>

                            <th class="center-column">
                                Evaluation Status
                            </th>

                            <th class="center-column action-column">
                                Action
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                    <?php

                    $i = 1;

                    $qry = $conn->query("
                        SELECT *
                        FROM academic_list
                        ORDER BY
                            CAST(SUBSTRING_INDEX(year,'-',1) AS UNSIGNED) DESC,
                            semester DESC
                    ");

                    if($qry && $qry->num_rows > 0):

                        while($row = $qry->fetch_assoc()):

                    ?>


                        <tr>


                            <!-- NUMBER -->

                            <td>

                                <span class="row-number">

                                    <?php echo $i++; ?>

                                </span>

                            </td>



                            <!-- YEAR -->

                            <td>

                                <div class="year-cell">

                                    <div class="year-icon">

                                        <i class="fas fa-calendar"></i>

                                    </div>

                                    <div>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $row['year']
                                            );
                                            ?>

                                        </strong>

                                        <?php if($row['is_default'] == 1): ?>

                                            <span class="default-label">

                                                <i class="fas fa-star"></i>

                                                Default

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </td>



                            <!-- SEMESTER -->

                            <td>

                                <span class="semester-badge">

                                    <?php
                                    echo semester_label(
                                        $row['semester']
                                    );
                                    ?>

                                </span>

                            </td>



                            <!-- DEFAULT -->

                            <td class="center-column">

                                <?php if($row['is_default'] == 0): ?>

                                    <button
                                        type="button"
                                        class="status-btn default-no make_default"
                                        data-id="<?php echo $row['id']; ?>"
                                        title="Set as default">

                                        <i class="fas fa-circle"></i>

                                        No

                                    </button>

                                <?php else: ?>

                                    <span class="status-btn default-yes">

                                        <i class="fas fa-check-circle"></i>

                                        Yes

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- STATUS -->

                            <td class="center-column">


                                <?php if($row['status'] == 0): ?>


                                    <span class="status-badge not-started">

                                        <span class="status-dot"></span>

                                        Not Yet Started

                                    </span>


                                <?php elseif($row['status'] == 1): ?>


                                    <span class="status-badge ongoing">

                                        <span class="status-dot"></span>

                                        Ongoing

                                    </span>


                                <?php elseif($row['status'] == 2): ?>


                                    <span class="status-badge closed">

                                        <span class="status-dot"></span>

                                        Closed

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- ACTION -->

                            <td class="center-column">


                                <div class="action-btns">


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="action-btn edit manage_academic"
                                        data-id="<?php echo $row['id']; ?>"
                                        title="Edit Academic Year">

                                        <i class="fas fa-pen"></i>

                                    </button>



                                    <!-- DELETE -->

                                    <button
                                        type="button"
                                        class="action-btn delete delete_academic"
                                        data-id="<?php echo $row['id']; ?>"
                                        title="Delete Academic Year">

                                        <i class="fas fa-trash-alt"></i>

                                    </button>


                                </div>


                            </td>


                        </tr>


                    <?php

                        endwhile;

                    else:

                    ?>


                        <tr>

                            <td
                                colspan="6"
                                class="empty-state">

                                <div class="empty-icon">

                                    <i class="fas fa-calendar-times"></i>

                                </div>

                                <h4>
                                    No Academic Years Found
                                </h4>

                                <p>
                                    There are currently no academic
                                    periods registered in the system.
                                </p>

                                <button
                                    class="btn empty-add-btn new_academic">

                                    <i class="fas fa-plus"></i>

                                    Add Academic Year

                                </button>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </div>


</div>



<style>

/* =========================================================
   MAIN WRAPPER
========================================================= */

.academic-wrapper{

    padding:5px 0 30px;

}



/* =========================================================
   PAGE INTRO
========================================================= */

.page-intro{

    background:
        linear-gradient(
            135deg,
            #047857,
            #10b981
        );

    border-radius:20px;

    padding:24px 28px;

    margin-bottom:22px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    box-shadow:
        0 10px 25px
        rgba(16,185,129,.20);

    color:white;

}


.page-intro-left{

    display:flex;

    align-items:center;

    gap:16px;

}


.page-icon{

    width:58px;

    height:58px;

    border-radius:16px;

    background:
        rgba(255,255,255,.16);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;

    border:
        1px solid
        rgba(255,255,255,.20);

}


.page-intro h2{

    margin:0;

    font-size:24px;

    font-weight:700;

    letter-spacing:-.3px;

}


.page-intro p{

    margin:5px 0 0;

    color:
        rgba(255,255,255,.82);

    font-size:14px;

}



/* =========================================================
   ADD BUTTON
========================================================= */

.add-academic-btn{

    border:none;

    background:white;

    color:#047857!important;

    padding:12px 19px;

    border-radius:11px;

    font-size:14px;

    font-weight:700;

    display:flex;

    align-items:center;

    gap:8px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.10);

    transition:.25s;

}


.add-academic-btn:hover{

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(0,0,0,.15);

    background:#f0fdf4;

}



/* =========================================================
   STATISTICS
========================================================= */

.academic-stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:18px;

    margin-bottom:22px;

}


.academic-stat-card{

    background:white;

    border:
        1px solid #ecfdf5;

    border-radius:17px;

    padding:20px;

    min-height:112px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.055);

    transition:.25s;

}


.academic-stat-card:hover{

    transform:
        translateY(-3px);

    box-shadow:
        0 12px 25px
        rgba(16,185,129,.12);

}


.stat-info{

    display:flex;

    flex-direction:column;

    gap:7px;

}


.stat-label{

    color:#6b7280;

    font-size:12px;

    font-weight:600;

}


.stat-info strong{

    color:#064e3b;

    font-size:27px;

    font-weight:800;

}


.stat-icon{

    width:50px;

    height:50px;

    border-radius:14px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:20px;

}


.stat-icon.green{

    background:#ecfdf5;

    color:#10b981;

}


.stat-icon.emerald{

    background:#d1fae5;

    color:#047857;

}


.stat-icon.blue{

    background:#eff6ff;

    color:#3b82f6;

}


.stat-icon.gray{

    background:#f3f4f6;

    color:#6b7280;

}



/* =========================================================
   MAIN CARD
========================================================= */

.academic-card{

    border:none!important;

    border-radius:20px!important;

    overflow:hidden;

    background:white;

    box-shadow:
        0 10px 28px
        rgba(0,0,0,.07);

}



/* =========================================================
   CARD HEADER
========================================================= */

.academic-card-header{

    padding:22px 25px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    border-bottom:
        1px solid #f1f5f9;

}


.academic-card-header h3{

    margin:0;

    color:#064e3b;

    font-size:18px;

    font-weight:700;

}


.academic-card-header h3 i{

    color:#10b981;

    margin-right:8px;

}


.academic-card-header p{

    margin:5px 0 0;

    color:#9ca3af;

    font-size:13px;

}


.record-count{

    background:#ecfdf5;

    color:#047857;

    padding:8px 13px;

    border-radius:9px;

    font-size:12px;

    font-weight:700;

}


.record-count i{

    margin-right:5px;

}



/* =========================================================
   CARD BODY
========================================================= */

.academic-card-body{

    padding:0;

}



/* =========================================================
   TABLE
========================================================= */

.modern-table{

    margin:0!important;

    border-collapse:separate;

    border-spacing:0;

}


.modern-table thead{

    background:#f8fafc;

}


.modern-table thead th{

    border:none!important;

    padding:15px 20px;

    color:#64748b;

    font-size:11px;

    font-weight:800;

    text-transform:uppercase;

    letter-spacing:.6px;

    white-space:nowrap;

}


.modern-table tbody td{

    padding:17px 20px;

    border-top:
        1px solid #f1f5f9!important;

    color:#475569;

    vertical-align:middle;

    font-size:14px;

}


.modern-table tbody tr{

    transition:
        background .2s,
        transform .2s;

}


.modern-table tbody tr:hover{

    background:#fafffd;

}


.number-column{

    width:60px;

}


.center-column{

    text-align:center;

}


.action-column{

    width:130px;

}



/* =========================================================
   ROW NUMBER
========================================================= */

.row-number{

    width:30px;

    height:30px;

    border-radius:9px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    background:#f0fdf4;

    color:#047857;

    font-size:12px;

    font-weight:700;

}



/* =========================================================
   YEAR CELL
========================================================= */

.year-cell{

    display:flex;

    align-items:center;

    gap:12px;

}


.year-icon{

    width:40px;

    height:40px;

    border-radius:11px;

    background:#ecfdf5;

    color:#10b981;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:16px;

}


.year-cell strong{

    color:#1f2937;

    font-size:15px;

    display:block;

}


.default-label{

    display:inline-flex;

    align-items:center;

    gap:4px;

    margin-top:4px;

    color:#059669;

    font-size:10px;

    font-weight:700;

}


.default-label i{

    font-size:9px;

}



/* =========================================================
   SEMESTER
========================================================= */

.semester-badge{

    display:inline-flex;

    align-items:center;

    padding:7px 12px;

    border-radius:9px;

    background:#f0fdf4;

    color:#047857;

    font-size:12px;

    font-weight:700;

}



/* =========================================================
   DEFAULT STATUS
========================================================= */

.status-btn{

    border:none;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:6px;

    border-radius:20px;

    padding:7px 13px;

    font-size:12px;

    font-weight:700;

    transition:.2s;

}


.default-no{

    background:#f1f5f9;

    color:#64748b;

}


.default-no i{

    font-size:7px;

    color:#94a3b8;

}


.default-no:hover{

    background:#ecfdf5;

    color:#047857;

}


.default-yes{

    background:#ecfdf5;

    color:#047857;

}


.default-yes i{

    color:#10b981;

}



/* =========================================================
   STATUS BADGES
========================================================= */

.status-badge{

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:7px 13px;

    border-radius:20px;

    font-size:11px;

    font-weight:700;

    white-space:nowrap;

}


.status-dot{

    width:7px;

    height:7px;

    border-radius:50%;

    display:block;

}


.not-started{

    background:#f1f5f9;

    color:#64748b;

}


.not-started .status-dot{

    background:#94a3b8;

}


.ongoing{

    background:#ecfdf5;

    color:#047857;

}


.ongoing .status-dot{

    background:#10b981;

}


.closed{

    background:#eff6ff;

    color:#2563eb;

}


.closed .status-dot{

    background:#3b82f6;

}



/* =========================================================
   ACTION BUTTONS
========================================================= */

.action-btns{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:8px;

}


.action-btn{

    width:36px;

    height:36px;

    border:none;

    border-radius:10px;

    display:flex;

    align-items:center;

    justify-content:center;

    color:white!important;

    cursor:pointer;

    transition:.22s;

    box-shadow:
        0 3px 8px
        rgba(0,0,0,.08);

}


.action-btn.edit{

    background:#10b981;

}


.action-btn.edit:hover{

    background:#059669;

    transform:
        translateY(-2px);

    box-shadow:
        0 6px 13px
        rgba(16,185,129,.25);

}


.action-btn.delete{

    background:#ef4444;

}


.action-btn.delete:hover{

    background:#dc2626;

    transform:
        translateY(-2px);

    box-shadow:
        0 6px 13px
        rgba(239,68,68,.22);

}


.action-btn i{

    font-size:13px;

}



/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state{

    padding:70px 20px!important;

    text-align:center;

}


.empty-icon{

    width:70px;

    height:70px;

    margin:0 auto 15px;

    border-radius:18px;

    background:#f0fdf4;

    color:#10b981;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:28px;

}


.empty-state h4{

    margin:0;

    color:#334155;

    font-size:17px;

    font-weight:700;

}


.empty-state p{

    margin:7px auto 18px;

    color:#94a3b8;

    font-size:13px;

    max-width:400px;

}


.empty-add-btn{

    background:#10b981;

    color:white!important;

    border:none;

    border-radius:9px;

    padding:9px 16px;

    font-size:13px;

    font-weight:700;

}


.empty-add-btn:hover{

    background:#059669;

}



/* =========================================================
   DATATABLE SEARCH
========================================================= */

.dataTables_wrapper{

    padding:18px 20px 0;

}


.dataTables_wrapper .dataTables_filter{

    margin-bottom:15px;

}


.dataTables_wrapper
.dataTables_filter input{

    border:
        1px solid #e2e8f0!important;

    border-radius:10px!important;

    padding:8px 12px!important;

    margin-left:8px!important;

    outline:none;

}


.dataTables_wrapper
.dataTables_filter input:focus{

    border-color:#10b981!important;

    box-shadow:
        0 0 0 3px
        rgba(16,185,129,.10);

}


.dataTables_wrapper
.dataTables_length select{

    border:
        1px solid #e2e8f0;

    border-radius:8px;

    padding:5px 8px;

}



/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px){

    .academic-stats{

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:767px){

    .page-intro{

        flex-direction:column;

        align-items:flex-start;

        gap:18px;

        padding:20px;

    }


    .add-academic-btn{

        width:100%;

        justify-content:center;

    }


    .academic-stats{

        grid-template-columns:1fr;

    }


    .academic-card-header{

        flex-direction:column;

        align-items:flex-start;

        gap:12px;

    }


    .record-count{

        align-self:flex-start;

    }


    .modern-table{

        min-width:850px;

    }


    .academic-card-body{

        overflow-x:auto;

    }

}

</style>
<script>
$(document).ready(function(){

    /* =========================================================
       DATATABLE
    ========================================================= */

    if($('#list').length){

        $('#list').DataTable({
            pageLength: 10,
            ordering: false,
            responsive: false
        });

    }


    /* =========================================================
       ADD NEW ACADEMIC YEAR
    ========================================================= */

    $(document).on('click', '.new_academic', function(e){

        e.preventDefault();

        uni_modal(
            "Add New Academic Year",
            "manage_academic.php"
        );

    });


    /* =========================================================
       EDIT ACADEMIC YEAR
    ========================================================= */

    $(document).on('click', '.manage_academic', function(e){

        e.preventDefault();

        var id = $(this).attr('data-id');

        if(!id){

            alert_toast(
                "Invalid academic year ID.",
                "danger"
            );

            return;

        }

        uni_modal(
            "Edit Academic Year",
            "manage_academic.php?id=" + id
        );

    });


    /* =========================================================
       DELETE ACADEMIC YEAR
    ========================================================= */

    $(document).on('click', '.delete_academic', function(e){

        e.preventDefault();

        var id = $(this).attr('data-id');

        if(!id){

            alert_toast(
                "Invalid academic year ID.",
                "danger"
            );

            return;

        }

        _conf(
            "Are you sure you want to delete this academic year?",
            "delete_academic",
            [id]
        );

    });


    /* =========================================================
       SET DEFAULT ACADEMIC YEAR
    ========================================================= */

    $(document).on('click', '.make_default', function(e){

        e.preventDefault();

        var id = $(this).attr('data-id');

        if(!id){

            alert_toast(
                "Invalid academic year ID.",
                "danger"
            );

            return;

        }

        _conf(
            "Are you sure you want to set this academic year as the default?",
            "make_default",
            [id]
        );

    });

});


/* =========================================================
   DELETE ACADEMIC YEAR
========================================================= */

function delete_academic(id){

    if(!id){

        alert_toast(
            "Invalid academic year ID.",
            "danger"
        );

        return;

    }

    start_load();

    $.ajax({

        url: 'ajax.php?action=delete_academic',

        method: 'POST',

        data: {
            id: id
        },

        success: function(resp){

            resp = $.trim(resp);

            console.log(
                "Delete Academic Response:",
                resp
            );

            if(resp == '1'){

                alert_toast(
                    "Academic year successfully deleted.",
                    "success"
                );

                setTimeout(function(){

                    location.reload();

                }, 1200);

            }else{

                end_load();

                alert_toast(
                    "Unable to delete academic year.",
                    "danger"
                );

                console.log(
                    "Delete failed response:",
                    resp
                );

            }

        },

        error: function(xhr){

            end_load();

            console.log(
                "Delete AJAX Error:",
                xhr.responseText
            );

            alert_toast(
                "An error occurred while deleting the academic year.",
                "danger"
            );

        }

    });

}


/* =========================================================
   MAKE DEFAULT
========================================================= */

function make_default(id){

    if(!id){

        alert_toast(
            "Invalid academic year ID.",
            "danger"
        );

        return;

    }

    start_load();

    $.ajax({

        url: 'ajax.php?action=make_default',

        method: 'POST',

        data: {
            id: id
        },

        success: function(resp){

            resp = $.trim(resp);

            console.log(
                "Make Default Response:",
                resp
            );

            if(resp == '1'){

                alert_toast(
                    "Academic year successfully set as default.",
                    "success"
                );

                setTimeout(function(){

                    location.reload();

                }, 1200);

            }else{

                end_load();

                alert_toast(
                    "Unable to set academic year as default.",
                    "danger"
                );

                console.log(
                    "Make default failed response:",
                    resp
                );

            }

        },

        error: function(xhr){

            end_load();

            console.log(
                "Make Default AJAX Error:",
                xhr.responseText
            );

            alert_toast(
                "An error occurred while updating the default academic year.",
                "danger"
            );

        }

    });

}

</script>
