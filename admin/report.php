<?php include 'db_connect.php'; ?> 
 
<?php 
 
$total = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports")->fetch_assoc()['total']; 
 
$pending = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE status='pending'")->fetch_assoc()['total']; 
 
$approved = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE status='approved'")->fetch_assoc()['total']; 
 
$rejected = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE status='rejected'")->fetch_assoc()['total'];

$terminal_total = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE report_type='Terminal Report'")->fetch_assoc()['total'];

$progress_total = $conn->query("SELECT COUNT(*) AS total FROM uploaded_reports WHERE report_type='Progress Report'")->fetch_assoc()['total'];

$coordinators = $conn->query("SELECT id, CONCAT(firstname,' ',lastname) AS name FROM faculty_list ORDER BY firstname, lastname");
 
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

    /* ===== Report Type Tabs ===== */

    .report-tabs .nav-link{
        background:#fff;
        color:#28a745;
        border:1px solid #d1fae5;
        border-radius:12px;
        padding:10px 22px;
        margin-right:10px;
        font-weight:600;
        transition:.25s;
    }

    .report-tabs .nav-link:hover{
        background:#f0fdf4;
    }

    .report-tabs .nav-link.active{
        background:#28a745;
        border-color:#28a745;
        color:#fff;
    }

    .report-tabs .tab-count{
        display:inline-block;
        min-width:24px;
        margin-left:6px;
        padding:2px 8px;
        border-radius:20px;
        background:#dcfce7;
        color:#15803d;
        font-size:12px;
        text-align:center;
    }

    .report-tabs .nav-link.active .tab-count{
        background:rgba(255,255,255,.25);
        color:#fff;
    }

    /* ===== Filters ===== */

    .report-filters{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
    }

    .report-filters .form-control{
        height:42px;
        border:1px solid #e5e7eb;
        border-radius:12px;
        box-shadow:none;
    }

    .report-filters .form-control:focus{
        border-color:#22c55e;
        box-shadow:0 0 0 .15rem rgba(34,197,94,.15);
    }

    .report-filters .filter-search{
        flex:1 1 240px;
        width:auto;
    }

    .report-filters .report-filter{
        flex:0 1 190px;
        width:auto;
    }

    /* ===== Status Tabs ===== */

    .status-tabs{
        border-bottom:2px solid #e5e7eb;
    }

    .status-tabs .nav-link{
        color:#6b7280;
        font-weight:600;
        padding:10px 20px;
        border-bottom:3px solid transparent;
        margin-bottom:-2px;
        transition:.2s;
    }

    .status-tabs .nav-link:hover{
        color:#28a745;
    }

    .status-tabs .nav-link.active{
        color:#28a745;
        border-bottom-color:#28a745;
    }

    .status-count{
        display:inline-block;
        min-width:24px;
        margin-left:4px;
        padding:2px 8px;
        border-radius:20px;
        font-size:12px;
        text-align:center;
    }

    #count-Pending{
        background:#fef3c7;
        color:#b45309;
    }

    #count-Approved{
        background:#dcfce7;
        color:#15803d;
    }

    #count-Rejected{
        background:#fee2e2;
        color:#dc2626;
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
                            <h2 id="stat-total"><?php echo $total ?></h2> 
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
                            <h2 id="stat-pending"><?php echo $pending ?></h2> 
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
                            <h2 id="stat-approved"><?php echo $approved ?></h2> 
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
                            <h2 id="stat-rejected"><?php echo $rejected ?></h2> 
                            <small>Rejected reports</small> 
                        </div> 
 
                    </div> 
 
                </div> 
 
            </div> 
 
        </div> 
 
 
        <div class="card-body">

            <ul class="nav report-tabs mb-4">
                <li class="nav-item">
                    <a href="#terminal" class="nav-link active" data-type="Terminal Report">
                        <i class="fa fa-flag-checkered"></i> Terminal Report
                        <span class="tab-count" id="count-terminal"><?php echo $terminal_total ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#progress" class="nav-link" data-type="Progress Report">
                        <i class="fa fa-chart-line"></i> Progress Report
                        <span class="tab-count" id="count-progress"><?php echo $progress_total ?></span>
                    </a>
                </li>
            </ul>

            <ul class="nav status-tabs mb-4">
                <li class="nav-item">
                    <a href="#" class="nav-link active" data-status="Pending">
                        <i class="fas fa-hourglass-half"></i> For Approval
                        <span class="status-count" id="count-Pending">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-status="Approved">
                        <i class="fas fa-check-circle"></i> Approved
                        <span class="status-count" id="count-Approved">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-status="Rejected">
                        <i class="fas fa-times-circle"></i> Rejected
                        <span class="status-count" id="count-Rejected">0</span>
                    </a>
                </li>
            </ul>

            <div class="report-filters mb-3">

                <input type="text" class="form-control filter-search" id="search-report"
                       placeholder="Search title or file name...">

                <select class="form-control report-filter" id="filter-coordinator">
                    <option value="">All Coordinators</option>
                    <?php while($c = $coordinators->fetch_assoc()): ?>
                    <option value="<?php echo $c['id'] ?>"><?php echo htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <select class="form-control report-filter" id="filter-category">
                    <option value="">All Categories</option>
                    <option>Research</option>
                    <option>Extension</option>
                    <option>Training</option>
                </select>

            </div>

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
 
var currentReportType = "Terminal Report";
var currentStatus = "Pending";

$(document).ready(function(){

    // Keep the selected tab after a page refresh
    if(location.hash == "#progress"){
        set_report_tab($('.report-tabs .nav-link[href="#progress"]'));
    }

    load_reports();

    $(".report-tabs .nav-link").click(function(e){
        e.preventDefault();
        set_report_tab($(this));
        history.replaceState(null, "", $(this).attr("href"));
        load_reports();
    });

    $(".status-tabs .nav-link").click(function(e){
        e.preventDefault();
        $(".status-tabs .nav-link").removeClass("active");
        $(this).addClass("active");
        currentStatus = $(this).data("status");
        load_reports();
    });

    var searchTimer;

    $("#search-report").on("input", function(){
        clearTimeout(searchTimer);
        searchTimer = setTimeout(load_reports, 300);
    });

    $(".report-filter").change(load_reports);

});


function set_report_tab(tab){
    $(".report-tabs .nav-link").removeClass("active");
    tab.addClass("active");
    currentReportType = tab.data("type");
}


function load_reports(){

    $.ajax({
        url: "ajax.php?action=list_reportsadmin",
        method: "POST",
        data: {
            report_type: currentReportType,
            search: $("#search-report").val(),
            coordinator: $("#filter-coordinator").val(),
            status: currentStatus,
            category: $("#filter-category").val()
        },

        success: function(resp){
            $("#report-list").html(resp);
        },

        error: function(xhr, status, error){
            console.log(xhr.responseText);
            alert("Failed to load reports.");
        }
    });

    load_counts();

}


function load_counts(){

    $.ajax({
        url: "ajax.php?action=report_counts",
        method: "POST",
        dataType: "json",
        data: {
            search: $("#search-report").val(),
            coordinator: $("#filter-coordinator").val(),
            category: $("#filter-category").val()
        },

        success: function(resp){

            var counts = resp.types[currentReportType];

            $.each(["Pending", "Approved", "Rejected"], function(i, status){
                $("#count-" + status).text(counts[status]);
            });

            $("#count-terminal").text(resp.types["Terminal Report"].Total);
            $("#count-progress").text(resp.types["Progress Report"].Total);

            $("#stat-total").text(resp.overall.Total);
            $("#stat-pending").text(resp.overall.Pending);
            $("#stat-approved").text(resp.overall.Approved);
            $("#stat-rejected").text(resp.overall.Rejected);

        },

        error: function(xhr){
            console.log(xhr.responseText);
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
 
                    // Also refreshes the statistics and tab counts
                    load_reports();
 
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