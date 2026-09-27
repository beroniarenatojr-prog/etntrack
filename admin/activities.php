
<?php include 'db_connect.php'; ?>

<div class="container-fluid activities-wrapper">

    <!-- ==========================================
         MAIN CARD
    =========================================== -->
    <div class="card activity-card">

        <!-- ======================================
             HEADER
        ======================================= -->
        <div class="activity-header">

            <div class="header-content">

                <div class="header-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>

                <div>
                    <h3>Extension Coordinators Activities</h3>

                    <p>
                        Review, approve, or reject submitted extension activities.
                    </p>
                </div>

            </div>

            <?php
            $pending_count = $conn->query("
                SELECT COUNT(*) AS total
                FROM activities
                WHERE status = 'pending'
            ")->fetch_assoc()['total'];

            $approved_count = $conn->query("
                SELECT COUNT(*) AS total
                FROM activities
                WHERE status = 'approved'
            ")->fetch_assoc()['total'];

            $rejected_count = $conn->query("
                SELECT COUNT(*) AS total
                FROM activities
                WHERE status = 'rejected'
            ")->fetch_assoc()['total'];
            ?>

            <div class="header-stat">

                <span class="stat-label">
                    Pending Review
                </span>

                <strong>
                    <?php echo number_format($pending_count); ?>
                </strong>

            </div>

        </div>


        <!-- ======================================
             STATUS SUMMARY
        ======================================= -->
        <div class="status-summary">

            <div class="summary-item">

                <div class="summary-icon total-icon">
                    <i class="fas fa-layer-group"></i>
                </div>

                <div>
                    <small>Total Activities</small>

                    <strong>
                        <?php
                        $total = $conn->query("
                            SELECT COUNT(*) AS total
                            FROM activities
                        ")->fetch_assoc()['total'];

                        echo number_format($total);
                        ?>
                    </strong>
                </div>

            </div>


            <div class="summary-item">

                <div class="summary-icon pending-icon">
                    <i class="fas fa-clock"></i>
                </div>

                <div>
                    <small>Pending</small>

                    <strong>
                        <?php echo number_format($pending_count); ?>
                    </strong>
                </div>

            </div>


            <div class="summary-item">

                <div class="summary-icon approved-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <div>
                    <small>Approved</small>

                    <strong>
                        <?php echo number_format($approved_count); ?>
                    </strong>
                </div>

            </div>


            <div class="summary-item">

                <div class="summary-icon rejected-icon">
                    <i class="fas fa-times-circle"></i>
                </div>

                <div>
                    <small>Rejected</small>

                    <strong>
                        <?php echo number_format($rejected_count); ?>
                    </strong>
                </div>

            </div>

        </div>


        <!-- ======================================
             TABLE
        ======================================= -->
        <div class="card-body activity-body">

            <div class="table-responsive">

                <table
                    class="table modern-table"
                    id="list"
                    width="100%"
                >

                    <thead>

                        <tr>

                            <th width="45">#</th>

                            <th>Faculty</th>

                            <th>Activity</th>

                            <th>Purpose</th>

                            <th>Date</th>

                            <th>Venue</th>

                            <th class="text-center">
                                Image
                            </th>

                            <th class="text-center">
                                Status
                            </th>

                            <th class="text-center action-column">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $i = 1;

                    $qry = $conn->query("
                        SELECT
                            a.*,
                            CONCAT(
                                f.firstname,
                                ' ',
                                f.lastname
                            ) AS faculty_name
                        FROM activities a
                        LEFT JOIN faculty_list f
                            ON a.faculty_id = f.id
                        ORDER BY a.created_at DESC
                    ");

                    if($qry && $qry->num_rows > 0):

                        while($row = $qry->fetch_assoc()):

                            $faculty_name = !empty($row['faculty_name'])
                                ? $row['faculty_name']
                                : "Faculty #".$row['faculty_id'];

                            $activity_name = htmlspecialchars(
                                $row['activity_name'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            $purpose = htmlspecialchars(
                                $row['purpose'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            $venue = htmlspecialchars(
                                $row['venue'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );

                    ?>

                    <tr>

                        <!-- NUMBER -->
                        <td>

                            <span class="row-number">
                                <?php echo $i++; ?>
                            </span>

                        </td>


                        <!-- FACULTY -->
                        <td>

                            <div class="faculty-info">

                                <div class="faculty-avatar">

                                    <?php
                                    echo strtoupper(
                                        substr(
                                            trim($faculty_name),
                                            0,
                                            1
                                        )
                                    );
                                    ?>

                                </div>

                                <div>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $faculty_name,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </strong>

                                    <small>
                                        Extension Coordinator
                                    </small>

                                </div>

                            </div>

                        </td>


                        <!-- ACTIVITY -->
                        <td>

                            <div class="activity-name">

                                <i class="fas fa-bookmark"></i>

                                <strong>
                                    <?php echo $activity_name; ?>
                                </strong>

                            </div>

                        </td>


                        <!-- PURPOSE -->
                        <td>

                            <div class="purpose-text">

                                <?php echo $purpose; ?>

                            </div>

                        </td>


                        <!-- DATE -->
                        <td>

                            <div class="date-box">

                                <i class="far fa-calendar-alt"></i>

                                <span>

                                <?php

                                if(
                                    !empty($row['activity_date']) &&
                                    strtotime($row['activity_date'])
                                ){

                                    echo date(
                                        "M d, Y",
                                        strtotime(
                                            $row['activity_date']
                                        )
                                    );

                                }else{

                                    echo "-";

                                }

                                ?>

                                </span>

                            </div>

                        </td>


                        <!-- VENUE -->
                        <td>

                            <?php if(!empty($venue)): ?>

                                <div class="venue-box">

                                    <i class="fas fa-map-marker-alt"></i>

                                    <span>
                                        <?php echo $venue; ?>
                                    </span>

                                </div>

                            <?php else: ?>

                                <span class="empty-value">
                                    No venue
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- IMAGE -->
                        <td class="text-center">

                            <?php if(!empty($row['image'])): ?>

                                <a
                                    href="assets/uploads/<?php
                                        echo htmlspecialchars(
                                            $row['image'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                    ?>"
                                    target="_blank"
                                    class="image-preview"
                                    title="View Activity Image"
                                >

                                    <img
                                        src="uploads/activities/<?php
                                            echo htmlspecialchars(
                                                $row['image'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                        ?>"
                                        class="activity-img"
                                        alt="Activity Image"
                                    >

                                    <span class="image-overlay">
                                        <i class="fas fa-search-plus"></i>
                                    </span>

                                </a>

                            <?php else: ?>

                                <div class="no-image">

                                    <i class="fas fa-image"></i>

                                    <span>
                                        No Image
                                    </span>

                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- STATUS -->
                        <td class="text-center">

                            <?php if($row['status'] == 'pending'): ?>

                                <span class="status-badge pending">

                                    <i class="fas fa-clock"></i>

                                    Pending

                                </span>

                            <?php elseif($row['status'] == 'approved'): ?>

                                <span class="status-badge approved">

                                    <i class="fas fa-check-circle"></i>

                                    Approved

                                </span>

                            <?php elseif($row['status'] == 'rejected'): ?>

                                <span class="status-badge rejected">

                                    <i class="fas fa-times-circle"></i>

                                    Rejected

                                </span>

                            <?php else: ?>

                                <span class="status-badge unknown">
                                    Unknown
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- ACTION -->
                        <td class="text-center">

                            <div class="action-buttons">


                            <?php if($row['status'] == 'pending'): ?>

                                <!-- APPROVE -->

                                <button
                                    type="button"
                                    class="activity-action approve_activity"
                                    data-id="<?php echo $row['id']; ?>"
                                    title="Approve Activity"
                                >

                                    <i class="fas fa-check"></i>

                                    <span>
                                        Approve
                                    </span>

                                </button>


                                <!-- REJECT -->

                                <button
                                    type="button"
                                    class="activity-action reject reject_activity"
                                    data-id="<?php echo $row['id']; ?>"
                                    title="Reject Activity"
                                >

                                    <i class="fas fa-times"></i>

                                    <span>
                                        Reject
                                    </span>

                                </button>


                            <?php elseif($row['status'] == 'approved'): ?>

                                <!-- GENERATE QR -->

                                <a
                                    href="faculty/generate_qr.php?id=<?php
                                        echo $row['id'];
                                    ?>"
                                    target="_blank"
                                    class="activity-action qr-button"
                                    title="Generate QR Code"
                                >

                                    <i class="fas fa-qrcode"></i>

                                    <span>
                                        Generate QR
                                    </span>

                                </a>


                            <?php elseif($row['status'] == 'rejected'): ?>

                                <span class="no-action">

                                    <i class="fas fa-ban"></i>

                                    No Action

                                </span>


                            <?php endif; ?>


                                <!-- ======================================
                                     DELETE
                                ======================================= -->

                                <button
                                    type="button"
                                    class="activity-action delete_activity"
                                    data-id="<?php echo $row['id']; ?>"
                                    title="Delete Activity"
                                >

                                    <i class="fas fa-trash-alt"></i>

                                    <span>
                                        Delete
                                    </span>

                                </button>


                            </div>

                        </td>

                    </tr>

                    <?php

                        endwhile;

                    endif;

                    ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- ==========================================
     CSS
=========================================== -->

<style>


/* ==========================================
   VARIABLES
========================================== */

:root{

    --green:#16a34a;
    --green-dark:#15803d;
    --green-deep:#166534;
    --green-light:#ecfdf5;

    --border:#e5e7eb;

    --text:#1f2937;
    --muted:#6b7280;

}


/* ==========================================
   PAGE
========================================== */

.activities-wrapper{

    padding:10px 5px 30px;

}


/* ==========================================
   MAIN CARD
========================================== */

.activity-card{

    border:none;

    border-radius:22px;

    overflow:hidden;

    background:#fff;

    box-shadow:
        0 12px 35px rgba(16,185,129,.10);

}


/* ==========================================
   HEADER
========================================== */

.activity-header{

    background:
        linear-gradient(
            135deg,
            #22c55e 0%,
            #16a34a 50%,
            #15803d 100%
        );

    color:white;

    padding:28px 32px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

}


.header-content{

    display:flex;

    align-items:center;

    gap:18px;

}


.header-icon{

    width:58px;

    height:58px;

    border-radius:16px;

    background:rgba(255,255,255,.16);

    border:1px solid rgba(255,255,255,.20);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;

}


.activity-header h3{

    margin:0;

    font-size:23px;

    font-weight:700;

    letter-spacing:.2px;

}


.activity-header p{

    margin:6px 0 0;

    font-size:14px;

    opacity:.9;

}


.header-stat{

    min-width:145px;

    padding:12px 18px;

    border-radius:14px;

    background:rgba(255,255,255,.14);

    border:1px solid rgba(255,255,255,.20);

    text-align:center;

}


.header-stat .stat-label{

    display:block;

    font-size:11px;

    text-transform:uppercase;

    letter-spacing:.8px;

    opacity:.85;

}


.header-stat strong{

    display:block;

    font-size:25px;

    margin-top:2px;

}


/* ==========================================
   SUMMARY
========================================== */

.status-summary{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:15px;

    padding:20px 25px;

    background:#fff;

    border-bottom:1px solid #edf0f2;

}


.summary-item{

    display:flex;

    align-items:center;

    gap:12px;

    padding:15px;

    border:1px solid #edf2ef;

    border-radius:15px;

    background:#fbfffc;

    transition:.25s;

}


.summary-item:hover{

    transform:translateY(-2px);

    box-shadow:
        0 7px 18px rgba(16,185,129,.08);

}


.summary-icon{

    width:44px;

    height:44px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:18px;

}


.summary-item small{

    display:block;

    color:var(--muted);

    font-size:12px;

    margin-bottom:2px;

}


.summary-item strong{

    display:block;

    color:var(--text);

    font-size:21px;

}


.total-icon{

    background:#eff6ff;

    color:#2563eb;

}


.pending-icon{

    background:#fffbeb;

    color:#d97706;

}


.approved-icon{

    background:#ecfdf5;

    color:#16a34a;

}


.rejected-icon{

    background:#fef2f2;

    color:#dc2626;

}


/* ==========================================
   BODY
========================================== */

.activity-body{

    padding:20px 25px 25px;

}


/* ==========================================
   TABLE
========================================== */

.modern-table{

    margin:0!important;

    border-collapse:separate;

    border-spacing:0 7px;

}


.modern-table thead th{

    background:#f0fdf4;

    color:#166534;

    border:none!important;

    padding:14px 13px;

    font-size:12px;

    text-transform:uppercase;

    letter-spacing:.35px;

    white-space:nowrap;

}


.modern-table thead th:first-child{

    border-radius:10px 0 0 10px;

}


.modern-table thead th:last-child{

    border-radius:0 10px 10px 0;

}


.modern-table tbody tr{

    background:#fff;

    transition:.2s;

}


.modern-table tbody tr:hover{

    background:#f8fffa;

    box-shadow:
        0 5px 18px rgba(0,0,0,.045);

}


.modern-table tbody td{

    border-top:1px solid #f0f2f3;

    border-bottom:1px solid #f0f2f3;

    padding:13px;

    vertical-align:middle;

    color:#374151;

    font-size:13px;

}


.modern-table tbody td:first-child{

    border-left:1px solid #f0f2f3;

    border-radius:12px 0 0 12px;

}


.modern-table tbody td:last-child{

    border-right:1px solid #f0f2f3;

    border-radius:0 12px 12px 0;

}


/* ==========================================
   ROW NUMBER
========================================== */

.row-number{

    width:30px;

    height:30px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    background:#ecfdf5;

    color:#15803d;

    border-radius:9px;

    font-weight:700;

    font-size:12px;

}


/* ==========================================
   FACULTY
========================================== */

.faculty-info{

    display:flex;

    align-items:center;

    gap:10px;

    min-width:170px;

}


.faculty-avatar{

    width:39px;

    height:39px;

    flex:none;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        linear-gradient(
            135deg,
            #dcfce7,
            #bbf7d0
        );

    color:#15803d;

    font-weight:800;

}


.faculty-info strong{

    display:block;

    color:#1f2937;

    font-size:13px;

}


.faculty-info small{

    display:block;

    color:#9ca3af;

    font-size:10px;

    margin-top:2px;

}


/* ==========================================
   ACTIVITY
========================================== */

.activity-name{

    display:flex;

    align-items:flex-start;

    gap:8px;

    min-width:150px;

}


.activity-name i{

    color:#16a34a;

    margin-top:2px;

}


.activity-name strong{

    color:#166534;

    line-height:1.4;

}


/* ==========================================
   PURPOSE
========================================== */

.purpose-text{

    max-width:220px;

    line-height:1.5;

    color:#6b7280;

}


/* ==========================================
   DATE
========================================== */

.date-box{

    display:flex;

    align-items:center;

    gap:7px;

    white-space:nowrap;

}


.date-box i{

    color:#16a34a;

}


/* ==========================================
   VENUE
========================================== */

.venue-box{

    display:flex;

    align-items:flex-start;

    gap:7px;

    max-width:150px;

}


.venue-box i{

    color:#ef4444;

    margin-top:3px;

}


.venue-box span{

    line-height:1.4;

}


.empty-value{

    color:#a1a1aa;

    font-style:italic;

}


/* ==========================================
   IMAGE
========================================== */

.image-preview{

    position:relative;

    width:62px;

    height:62px;

    display:inline-block;

    overflow:hidden;

    border-radius:13px;

    border:3px solid #dcfce7;

}


.activity-img{

    width:100%;

    height:100%;

    object-fit:cover;

    display:block;

    transition:.3s;

}


.image-overlay{

    position:absolute;

    inset:0;

    background:rgba(22,163,74,.72);

    display:flex;

    align-items:center;

    justify-content:center;

    color:#fff;

    font-size:16px;

    opacity:0;

    transition:.25s;

}


.image-preview:hover .image-overlay{

    opacity:1;

}


.image-preview:hover .activity-img{

    transform:scale(1.08);

}


.no-image{

    width:62px;

    height:62px;

    margin:auto;

    border-radius:13px;

    background:#f5f7f8;

    color:#9ca3af;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    font-size:17px;

}


.no-image span{

    font-size:9px;

    margin-top:3px;

}


/* ==========================================
   STATUS
========================================== */

.status-badge{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:6px;

    padding:7px 12px;

    border-radius:30px;

    font-size:11px;

    font-weight:700;

    white-space:nowrap;

}


.status-badge.pending{

    background:#fffbeb;

    color:#a16207;

    border:1px solid #fde68a;

}


.status-badge.approved{

    background:#ecfdf5;

    color:#15803d;

    border:1px solid #bbf7d0;

}


.status-badge.rejected{

    background:#fef2f2;

    color:#b91c1c;

    border:1px solid #fecaca;

}


.status-badge.unknown{

    background:#f3f4f6;

    color:#6b7280;

}


/* ==========================================
   ACTION BUTTONS
========================================== */

.action-buttons{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:5px;

    min-width:170px;

}


.activity-action{

    border:none;

    border-radius:9px;

    padding:8px 10px;

    font-size:11px;

    font-weight:700;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:5px;

    cursor:pointer;

    text-decoration:none!important;

    transition:.2s;

}


.activity-action:hover{

    transform:translateY(-2px);

}


.approve_activity{

    background:#16a34a;

    color:#fff;

}


.approve_activity:hover{

    background:#15803d;

    color:#fff;

    box-shadow:
        0 5px 12px rgba(22,163,74,.22);

}


.activity-action.reject{

    background:#fee2e2;

    color:#dc2626;

}


.activity-action.reject:hover{

    background:#fecaca;

    color:#b91c1c;

}


.qr-button{

    background:#2563eb;

    color:#fff;

}


.qr-button:hover{

    background:#1d4ed8;

    color:#fff;

    box-shadow:
        0 5px 12px rgba(37,99,235,.20);

}


/* ==========================================
   DELETE BUTTON
========================================== */

.activity-action.delete_activity{

    background:#fff1f2;

    color:#e11d48;

    border:1px solid #fecdd3;

}


.activity-action.delete_activity:hover{

    background:#e11d48;

    color:#fff;

    border-color:#e11d48;

    box-shadow:
        0 5px 12px rgba(225,29,72,.20);

}


.no-action{

    color:#9ca3af;

    font-size:11px;

    font-style:italic;

}


/* ==========================================
   DATATABLE
========================================== */

.dataTables_wrapper{

    padding-top:5px;

}


.dataTables_wrapper .dataTables_filter{

    margin-bottom:12px;

}


.dataTables_wrapper .dataTables_filter label{

    color:#6b7280;

    font-size:13px;

    font-weight:600;

}


.dataTables_wrapper .dataTables_filter input{

    border:1px solid #dfe7e2!important;

    border-radius:10px!important;

    padding:8px 12px!important;

    outline:none!important;

    margin-left:7px!important;

}


.dataTables_wrapper .dataTables_filter input:focus{

    border-color:#22c55e!important;

    box-shadow:
        0 0 0 3px rgba(34,197,94,.10);

}


.dataTables_wrapper .dataTables_length{

    color:#6b7280;

    font-size:13px;

}


.dataTables_wrapper .dataTables_length select{

    border:1px solid #dfe7e2;

    border-radius:8px;

    padding:5px 8px;

}


.dataTables_wrapper .dataTables_info{

    color:#9ca3af;

    font-size:12px;

}


.dataTables_wrapper .dataTables_paginate{

    margin-top:10px;

}


.dataTables_wrapper .dataTables_paginate .paginate_button{

    border:none!important;

    border-radius:8px!important;

    margin:2px!important;

    color:#166534!important;

}


.dataTables_wrapper .dataTables_paginate .paginate_button.current{

    background:#16a34a!important;

    color:#fff!important;

    border:none!important;

}


.dataTables_wrapper .dataTables_paginate .paginate_button:hover{

    background:#dcfce7!important;

    color:#166534!important;

}


/* ==========================================
   RESPONSIVE
========================================== */

@media(max-width:992px){

    .status-summary{

        grid-template-columns:
            repeat(2,1fr);

    }

    .activity-header{

        align-items:flex-start;

        flex-direction:column;

    }

    .header-stat{

        width:100%;

    }

}


@media(max-width:768px){

    .activities-wrapper{

        padding:5px 0 20px;

    }

    .activity-header{

        padding:22px 18px;

    }

    .header-content{

        align-items:flex-start;

    }

    .activity-header h3{

        font-size:19px;

    }

    .activity-header p{

        font-size:12px;

    }

    .status-summary{

        grid-template-columns:
            repeat(2,1fr);

        padding:15px;

    }

    .summary-item{

        padding:12px;

    }

    .activity-body{

        padding:15px;

    }

}


@media(max-width:500px){

    .status-summary{

        grid-template-columns:1fr;

    }

    .header-icon{

        width:48px;

        height:48px;

        font-size:20px;

    }

    .activity-header h3{

        font-size:17px;

    }

    .action-buttons{

        flex-direction:column;

    }

    .activity-action{

        width:100%;

    }

}


/* ==========================================
   DELETE BUTTON MOBILE
========================================== */

@media(max-width:500px){

    .activity-action.delete_activity{

        width:100%;

    }

}

</style>


<!-- ==========================================
     JAVASCRIPT
========================================== -->

<script>

$(document).ready(function(){

    /* ======================================
       DATATABLE
    ======================================= */

    $('#list').DataTable({

        responsive: true,

        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, -1],
            [10, 25, 50, "All"]
        ],

        order: [],

        columnDefs: [

            {
                orderable:false,
                targets:[
                    6,
                    8
                ]
            }

        ],

        language: {

            search: "",

            searchPlaceholder:
                "Search activities...",

            lengthMenu:
                "Show _MENU_ activities",

            emptyTable:
                "No activities submitted yet.",

            zeroRecords:
                "No matching activities found."

        }

    });


    /* ======================================
       APPROVE
    ======================================= */

    $(document).on(
        'click',
        '.approve_activity',
        function(){

            var id = $(this).data('id');

            if(confirm(
                "Are you sure you want to approve this activity?"
            )){

                start_load();

                $.ajax({

                    url:
                        'ajax.php?action=update_activity_status',

                    method:'POST',

                    data:{

                        id:id,

                        status:'approved'

                    },

                    success:function(resp){

                        if(resp == 1){

                            alert_toast(
                                "Activity successfully approved.",
                                "success"
                            );

                            setTimeout(
                                function(){

                                    location.reload();

                                },
                                1000
                            );

                        }else{

                            alert_toast(
                                "Unable to update activity status.",
                                "error"
                            );

                            end_load();

                        }

                    },

                    error:function(){

                        alert_toast(
                            "An error occurred while processing the request.",
                            "error"
                        );

                        end_load();

                    }

                });

            }

        }
    );


    /* ======================================
       REJECT
    ======================================= */

    $(document).on(
        'click',
        '.reject_activity',
        function(){

            var id = $(this).data('id');

            if(confirm(
                "Are you sure you want to reject this activity?"
            )){

                start_load();

                $.ajax({

                    url:
                        'ajax.php?action=update_activity_status',

                    method:'POST',

                    data:{

                        id:id,

                        status:'rejected'

                    },

                    success:function(resp){

                        if(resp == 1){

                            alert_toast(
                                "Activity successfully rejected.",
                                "success"
                            );

                            setTimeout(
                                function(){

                                    location.reload();

                                },
                                1000
                            );

                        }else{

                            alert_toast(
                                "Unable to update activity status.",
                                "error"
                            );

                            end_load();

                        }

                    },

                    error:function(){

                        alert_toast(
                            "An error occurred while processing the request.",
                            "error"
                        );

                        end_load();

                    }

                });

            }

        }
    );


    /* ======================================
       DELETE ACTIVITY
    ======================================= */

    $(document).on(
        'click',
        '.delete_activity',
        function(){

            var id = $(this).data('id');

            if(!id){

                alert_toast(
                    "Invalid activity ID.",
                    "error"
                );

                return;

            }


            if(confirm(
                "Are you sure you want to delete this activity?\n\n" +
                "This action cannot be undone."
            )){

                start_load();

                $.ajax({

                    url:
                        'ajax.php?action=delete_activity_admin',

                    method:'POST',

                    data:{

                        id:id

                    },

                    success:function(resp){

                        if(resp == 1){

                            alert_toast(
                                "Activity successfully deleted.",
                                "success"
                            );

                            setTimeout(
                                function(){

                                    location.reload();

                                },
                                1000
                            );

                        }else{

                            alert_toast(
                                "Unable to delete activity.",
                                "error"
                            );

                            end_load();

                        }

                    },

                    error:function(){

                        alert_toast(
                            "An error occurred while deleting the activity.",
                            "error"
                        );

                        end_load();

                    }

                });

            }

        }
    );


});


/* ==========================================
   ACTIVITY LIST AJAX
========================================== */

function load_activityadmin(){

    $.ajax({

        url:
            "ajax.php?action=list_activityadmin",

        success:function(resp){

            $("#activity-listadmin")
                .html(resp);

        },

        error:function(){

            console.log(
                "Unable to load activities."
            );

        }

    });

}

</script>
