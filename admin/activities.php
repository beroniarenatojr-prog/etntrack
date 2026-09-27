
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

                <strong id="stat-header-pending">
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

                    <strong id="stat-total">
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

                    <strong id="stat-pending">
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

                    <strong id="stat-approved">
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

                    <strong id="stat-rejected">
                        <?php echo number_format($rejected_count); ?>
                    </strong>
                </div>

            </div>

        </div>


        <!-- ======================================
             TABLE (filled by assets/js/activity-table.js)
        ======================================= -->
        <div class="card-body activity-body">

            <div class="admin-activity-toolbar">

                <div class="admin-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="admin-search" placeholder="Search by activity or implementer...">
                </div>

                <select id="admin-status" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="revision">Needs Revision</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>

            </div>

            <!-- Shown when rows are ticked -->
            <div class="at-bulk" id="admin-bulk">
                <span class="at-bulk-count"></span>
                <button type="button" class="btn btn-sm btn-success" data-bulk="approve">
                    <i class="fas fa-check mr-1"></i> Approve Selected
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bulk="reject">
                    <i class="fas fa-times mr-1"></i> Reject Selected
                </button>
                <button type="button" class="btn btn-sm btn-danger" data-bulk="delete">
                    <i class="fas fa-trash-alt mr-1"></i> Delete Selected
                </button>
            </div>

            <table id="admin-activity-table" class="table at-table"></table>

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
   TABLE TOOLBAR (the table itself: assets/css/activity-table.css)
========================================== */

.admin-activity-toolbar{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:15px;
}

.admin-search{
    flex:1 1 260px;
    display:flex;
    align-items:center;
    gap:10px;
    height:44px;
    padding:0 14px;
    border:1px solid #e5e7eb;
    border-radius:12px;
    background:#fff;
}

.admin-search i{
    color:#9ca3af;
}

.admin-search input{
    flex:1;
    border:none;
    outline:none;
    background:transparent;
}

#admin-status{
    flex:0 0 190px;
    height:44px;
    border-radius:12px;
    border-color:#e5e7eb;
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

}


</style>


<!-- ==========================================
     JAVASCRIPT
=========================================== -->
<link rel="stylesheet" href="assets/css/activity-table.css">

<script src="assets/js/activity-table.js"></script>

<script>

var adminTable;   // ActivityTable instance (assets/js/activity-table.js)

$(document).ready(function(){

    // Every coordinator's activities, with bulk Approve / Reject / Delete
    adminTable = ActivityTable.create({
        table: "#admin-activity-table",
        selectable: true,
        search: "#admin-search",
        bulkBar: "#admin-bulk",
        filters: function(){
            return { status: $("#admin-status").val() };
        },
        onChange: refresh_summary,
        emptyText: "No activities submitted yet."
    });

    $("#admin-status").change(function(){
        adminTable.reload();
    });

});

// Keeps the numbers at the top in step with the table after every change
function refresh_summary(){

    $.ajax({

        url: "ajax.php?action=activity_counts",
        dataType: "json",

        success: function(resp){

            var all = resp.all;

            $("#stat-total").text(all.total);
            $("#stat-pending, #stat-header-pending").text(all.pending);
            $("#stat-approved").text(all.approved);
            $("#stat-rejected").text(all.rejected);

        }

    });

}

</script>
