<?php include 'db_connect.php'; ?> 
 
<?php 
 
$total = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports")->fetch_assoc()['total']; 
 
$pending = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE status='pending'")->fetch_assoc()['total']; 
 
$approved = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE status='approved'")->fetch_assoc()['total']; 
 
$rejected = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE status='rejected'")->fetch_assoc()['total']; 
 
?> 
 
 
<style> 
    .report-card{ 
        border:none; 
        border-radius:18px; 
        overflow:hidden; 
        box-shadow:0 6px 18px rgba(0,0,0,.12); 
    } 
 
    .report-header{ 
        background:#28a745; 
        color:#fff; 
        padding:18px 25px; 
    } 
 
    .report-header h4{ 
        margin:0; 
        font-weight:600; 
    } 
 
    .table{ 
        margin-bottom:0; 
    } 
 
    .table thead{ 
        background:#f5fcf8; 
    } 
 
    .table thead th{ 
        color:#374151; 
        font-weight:700; 
        border-bottom:2px solid #d1fae5; 
    } 
 
    .table tbody tr{ 
        transition:.25s; 
    } 
 
    .table tbody tr:hover{ 
        background:#f0fdf4; 
        transform:scale(1.002); 
    } 
 
    .table td, 
    .table th{ 
        vertical-align:middle; 
    } 
 
    .btn-view{ 
        background:#28a745; 
        border-color:#28a745; 
        color:#fff; 
        border-radius:25px; 
        padding:4px 14px; 
    } 
 
    .btn-view:hover{ 
        background:#218838; 
        border-color:#218838; 
        color:#fff; 
    } 
 
    .action-btn{ 
        width:40px; 
        height:40px; 
        border-radius:10px; 
        display:inline-flex; 
        align-items:center; 
        justify-content:center; 
        margin:2px; 
        transition:.25s; 
    } 
 
    .action-btn:hover{ 
        transform:translateY(-2px); 
    } 
 
    /* DELETE BUTTON */
    .delete-report{ 
        color:#dc2626 !important; 
        border-color:#fecaca !important; 
        background:#fff5f5 !important; 
    } 
 
    .delete-report:hover{ 
        color:#fff !important; 
        background:#dc2626 !important; 
        border-color:#dc2626 !important; 
        box-shadow:0 5px 12px rgba(220,38,38,.20); 
    } 
 
    .badge{ 
        padding:8px 18px; 
        border-radius:30px; 
        font-size:13px; 
        font-weight:600; 
    } 
 
    .badge-warning{ 
        background:#FEF3C7; 
        color:#B45309; 
    } 
 
    .badge-success{ 
        background:#DCFCE7; 
        color:#15803D; 
    } 
 
    .badge-danger{ 
        background:#FEE2E2; 
        color:#DC2626; 
    } 
 
    #reportTable_wrapper{ 
        padding:20px; 
    } 
 
    /* Search */ 
 
    .dataTables_filter{ 
        margin-bottom:20px; 
    } 
 
    .dataTables_filter input{ 
        border:1px solid #e5e7eb !important; 
        border-radius:12px !important; 
        padding:10px 16px !important; 
        width:260px !important; 
        height:42px; 
        box-shadow:none !important; 
    } 
 
    .dataTables_filter input:focus{ 
        border-color:#22c55e !important; 
        box-shadow:0 0 0 .15rem rgba(34,197,94,.15) !important; 
    } 
 
    .dataTables_length{ 
        margin-bottom:20px; 
    } 
 
    .dataTables_length select{ 
        border-radius:10px !important; 
        border:1px solid #e5e7eb !important; 
        height:42px; 
    } 
 
    /* ===== Statistics Cards ===== */ 
 
    .stat-card{ 
        background:#fff; 
        border-radius:18px; 
        padding:22px; 
        display:flex; 
        align-items:center; 
        gap:18px; 
        box-shadow:0 10px 25px rgba(0,0,0,.08); 
        transition:.3s; 
        border:1px solid #f2f2f2; 
    } 
 
    .stat-card:hover{ 
        transform:translateY(-5px); 
        box-shadow:0 20px 35px rgba(0,0,0,.12); 
    } 
 
    .stat-icon{ 
        width:70px; 
        height:70px; 
        border-radius:50%; 
        display:flex; 
        align-items:center; 
        justify-content:center; 
        font-size:28px; 
    } 
 
    .stat-content{ 
        flex:1; 
        border-left:1px solid #e5e7eb; 
        padding-left:18px; 
    } 
 
    .stat-content h6{ 
        margin:0; 
        color:#6b7280; 
        font-weight:600; 
    } 
 
    .stat-content h2{ 
        margin:5px 0; 
        font-size:32px; 
        font-weight:700; 
    } 
 
    .stat-content small{ 
        color:#9ca3af; 
    } 
 
    /* Colors */ 
 
    .total{ 
        background:#dcfce7; 
        color:#16a34a; 
    } 
 
    .pending{ 
        background:#fef3c7; 
        color:#f59e0b; 
    } 
 
    .approved{ 
        background:#dcfce7; 
        color:#16a34a; 
    } 
 
    .rejected{ 
        background:#fee2e2; 
        color:#ef4444; 
    } 
 
    .dataTables_paginate{ 
        margin-top:20px; 
    } 
 
    .paginate_button{ 
        border-radius:10px !important; 
    } 
 
    .paginate_button.current{ 
        background:#16a34a !important; 
        color:white !important; 
        border:none !important; 
    } 
 
</style> 
 
 
 
<div class="col-lg-12"> 
    <div class="card report-card"> 
 
        <div class="card-header report-header d-flex justify-content-between align-items-center"> 
            <h4> 
                <i class="fa fa-file-alt"></i> 
                Uploaded Reports 
            </h4> 
        </div> 
 
        <div class="px-4 pt-4"> 
 
            <div class="row"> 
 
                <div class="col-lg-3 col-md-6 mb-3"> 
 
                    <div class="stat-card"> 
 
                        <div class="stat-icon total"> 
                            <i class="fas fa-file-alt"></i> 
                        </div> 
 
                        <div class="stat-content"> 
                            <h6>Total Reports</h6> 
                            <h2><?php echo $total ?></h2> 
                            <small>All submitted reports</small> 
                        </div> 
 
                    </div> 
 
                </div> 
 
 
                <div class="col-lg-3 col-md-6 mb-3"> 
 
                    <div class="stat-card"> 
 
                        <div class="stat-icon pending"> 
                            <i class="fas fa-clock"></i> 
                        </div> 
 
                        <div class="stat-content"> 
                            <h6>Pending</h6> 
                            <h2><?php echo $pending ?></h2> 
                            <small>Waiting for approval</small> 
                        </div> 
 
                    </div> 
 
                </div> 
 
 
                <div class="col-lg-3 col-md-6 mb-3"> 
 
                    <div class="stat-card"> 
 
                        <div class="stat-icon approved"> 
                            <i class="fas fa-check"></i> 
                        </div> 
 
                        <div class="stat-content"> 
                            <h6>Approved</h6> 
                            <h2><?php echo $approved ?></h2> 
                            <small>Successfully approved</small> 
                        </div> 
 
                    </div> 
 
                </div> 
 
 
                <div class="col-lg-3 col-md-6 mb-3"> 
 
                    <div class="stat-card"> 
 
                        <div class="stat-icon rejected"> 
                            <i class="fas fa-times"></i> 
                        </div> 
 
                        <div class="stat-content"> 
                            <h6>Rejected</h6> 
                            <h2><?php echo $rejected ?></h2> 
                            <small>Rejected reports</small> 
                        </div> 
 
                    </div> 
 
                </div> 
 
            </div> 
 
        </div> 
 
 
        <div class="card-body"> 
 
            <table class="table table-bordered table-hover table-striped" id="reportTable"> 
 
                <thead class="thead-dark"> 
 
                    <tr> 
                        <th width="5%">#</th> 
                        <th>Report Title</th> 
                        <th>File Name</th> 
                        <th>Uploaded By</th> 
                        <th> Uploaded at </th> 
                        <th>Status</th> 
                        <th width="15%">Action</th> 
                    </tr> 
 
                </thead> 
 
                <tbody id="report-list"> 
                </tbody> 
 
            </table> 
 
        </div> 
 
    </div> 
</div> 
 
 
<script> 
 
$(document).ready(function(){ 
    load_reports(); 
}); 
 
 
function load_reports(){ 
 
    $.ajax({ 
        url: "ajax.php?action=list_reportsadmin", 
        method: "POST", 
 
        success: function(resp){ 
            $("#report-list").html(resp); 
        }, 
 
        error: function(xhr, status, error){ 
            console.log(xhr.responseText); 
            alert("Failed to load reports."); 
        } 
    }); 
 
} 
 
 
/* ================================
   APPROVE REPORT
================================ */ 
 
$(document).on("click", ".approve-report", function(){ 
 
    var id = $(this).data("id"); 
 
    $.ajax({ 
        url: "ajax.php?action=approve_reportadmin", 
        type: "POST", 
        data: {id:id}, 
 
        success: function(resp){ 
 
            if(resp == 1){ 
                load_reports(); 
            }else{ 
                alert("Failed to approve report."); 
            } 
 
        } 
    }); 
 
}); 
 
 
/* ================================
   REJECT REPORT
================================ */ 
 
$(document).on("click", ".reject-report", function(){ 
 
    var id = $(this).data("id"); 
 
    $.ajax({ 
        url: "ajax.php?action=reject_reportadmin", 
        type: "POST", 
        data: {id:id}, 
 
        success: function(resp){ 
 
            if(resp == 1){ 
                load_reports(); 
            }else{ 
                alert("Failed to reject report."); 
            } 
 
        } 
    }); 
 
}); 
 
 
/* ================================
   DELETE REPORT
================================ */ 
 
$(document).on("click", ".delete-report", function(){ 
 
    var id = $(this).data("id"); 
 
    if(!id){ 
        alert("Invalid report ID."); 
        return; 
    } 
 
    if(confirm("Are you sure you want to delete this report?\n\nThis action cannot be undone.")){ 
 
        $.ajax({ 
            url: "ajax.php?action=delete_report", 
            type: "POST", 
            data: {id:id}, 
 
            success: function(resp){ 
 
                if(resp == 1){ 
 
                    alert("Report successfully deleted."); 
 
                    // Reload page so statistics update too
                    location.reload(); 
 
                }else{ 
 
                    alert("Failed to delete report."); 
                    console.log(resp); 
 
                } 
 
            }, 
 
            error: function(xhr, status, error){ 
 
                console.log(xhr.responseText); 
                alert("An error occurred while deleting the report."); 
 
            } 
 
        }); 
 
    } 
 
}); 
 
</script>