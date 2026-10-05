<?php include 'db_connect.php'; ?>

<?php

// For the Coordinator filter
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

    .report-subtitle{
        margin:4px 0 0;
        font-size:14px;
        opacity:.9;
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
    }

    .table td,
    .table th{
        vertical-align:middle;
    }

    .action-btn{
        width:36px;
        height:36px;
        border-radius:10px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        margin:2px;
        transition:.25s;
        border:1px solid #e5e7eb;
        background:#fff;
    }

    .action-btn:hover{
        transform:translateY(-2px);
    }

    .rp-view{ color:#15803d; border-color:#bbf7d0; background:#f0fdf4; }
    .rp-view:hover{ color:#fff; background:#16a34a; border-color:#16a34a; }

    .rp-approve{ color:#15803d; border-color:#bbf7d0; }
    .rp-approve:hover{ color:#fff; background:#16a34a; border-color:#16a34a; }

    .rp-revise,
    .rp-move{ color:#1d4ed8; border-color:#bfdbfe; }
    .rp-revise:hover,
    .rp-move:hover{ color:#fff; background:#2563eb; border-color:#2563eb; }

    .rp-reject{ color:#b45309; border-color:#fde68a; }
    .rp-reject:hover{ color:#fff; background:#d97706; border-color:#d97706; }

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

    .badge-revision{
        background:#DBEAFE;
        color:#1D4ED8;
    }

    /* ===== Statistics Cards ===== */

    .stat-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
        gap:16px;
    }

    .stat-card{
        background:#fff;
        border-radius:18px;
        padding:20px;
        display:flex;
        align-items:center;
        gap:16px;
        box-shadow:0 10px 25px rgba(0,0,0,.08);
        transition:.3s;
        border:1px solid #f2f2f2;
    }

    .stat-card:hover{
        transform:translateY(-5px);
        box-shadow:0 20px 35px rgba(0,0,0,.12);
    }

    .stat-icon{
        width:58px;
        height:58px;
        flex:0 0 58px;
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:24px;
    }

    .stat-content{
        flex:1;
        border-left:1px solid #e5e7eb;
        padding-left:16px;
    }

    .stat-content h6{
        margin:0;
        color:#6b7280;
        font-weight:600;
    }

    .stat-content h2{
        margin:5px 0;
        font-size:30px;
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

    .revision{
        background:#dbeafe;
        color:#2563eb;
    }

    .rejected{
        background:#fee2e2;
        color:#ef4444;
    }

    /* ===== Report Type Tabs ===== */

    .report-tabs{
        row-gap:10px;
    }

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
        flex:0 1 200px;
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
        background:#f3f4f6;
        color:#4b5563;
    }

    #count-Pending{
        background:#fef3c7;
        color:#b45309;
    }

    #count-Revision{
        background:#dbeafe;
        color:#1d4ed8;
    }

    #count-Approved{
        background:#dcfce7;
        color:#15803d;
    }

    #count-Rejected{
        background:#fee2e2;
        color:#dc2626;
    }

    /* ===== Rows ===== */

    .rp-title{
        font-weight:600;
        color:#1f2937;
    }

    .rp-meta{
        font-size:12px;
        color:#6b7280;
        margin-top:2px;
    }

    .rp-desc{
        font-size:12.5px;
        color:#4b5563;
        margin-top:4px;
        max-width:420px;
    }

    .rp-remarks{
        font-size:12px;
        color:#374151;
        background:#f9fafb;
        border-left:3px solid #93c5fd;
        border-radius:6px;
        padding:5px 8px;
        margin-top:6px;
        max-width:260px;
    }

    .rp-project{
        font-weight:600;
        color:#15803d;
    }

    .rp-project:hover{
        color:#166534;
        text-decoration:underline;
    }

    .rp-ref{
        display:block;
        font-size:11px;
        font-weight:700;
        color:#6b7280;
        letter-spacing:.3px;
    }

    .rp-unassigned{
        display:inline-block;
        font-size:12.5px;
        color:#b45309;
        background:#fffbeb;
        border:1px dashed #fcd34d;
        border-radius:8px;
        padding:3px 8px;
    }

    .rp-link-btn{
        border:1px solid #86efac;
        background:#f0fdf4;
        color:#15803d;
        border-radius:8px;
        font-size:12px;
        font-weight:600;
        padding:3px 10px;
        margin-left:6px;
    }

    .rp-link-btn:hover{
        background:#16a34a;
        border-color:#16a34a;
        color:#fff;
    }

    .rp-actions{
        display:flex;
        flex-wrap:wrap;
        gap:2px;
    }

    .rp-open{
        border:1px solid #bbf7d0;
        background:#f0fdf4;
        color:#15803d;
        border-radius:10px;
        font-weight:600;
    }

    .rp-open:hover{
        background:#16a34a;
        color:#fff;
    }

    .rp-empty{
        text-align:center;
        color:#9ca3af;
        padding:28px 10px;
    }

    .rp-empty i{
        margin-right:6px;
    }

    .rp-note{
        background:#fffbeb;
        border:1px solid #fde68a;
        color:#92400e;
        border-radius:12px;
        padding:10px 14px;
        font-size:14px;
    }

    .rp-note.info{
        background:#f0fdf4;
        border-color:#bbf7d0;
        color:#166534;
    }

    .rp-swal-sub{
        font-weight:600;
        color:#374151;
        margin-bottom:6px;
    }

    #report-table-wrap{
        overflow-x:auto;
    }
</style>



<div class="col-lg-12">
    <div class="card report-card">

        <div class="card-header report-header">
            <h4>
                <i class="fa fa-file-alt"></i>
                Uploaded Reports
            </h4>
            <p class="report-subtitle">Manage and review reports across all extension projects.</p>
        </div>

        <div class="px-4 pt-4">

            <div class="stat-grid">

                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h6>Total Reports</h6>
                        <h2 id="stat-total">0</h2>
                        <small>All submitted reports</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h6>Pending Review</h6>
                        <h2 id="stat-pending">0</h2>
                        <small>Waiting for approval</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-content">
                        <h6>Approved</h6>
                        <h2 id="stat-approved">0</h2>
                        <small>Successfully approved</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon revision">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="stat-content">
                        <h6>Needs Revision</h6>
                        <h2 id="stat-revision">0</h2>
                        <small>Sent back to the coordinator</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon rejected">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stat-content">
                        <h6>Rejected</h6>
                        <h2 id="stat-rejected">0</h2>
                        <small>Rejected reports</small>
                    </div>
                </div>

            </div>

        </div>


        <div class="card-body">

            <ul class="nav report-tabs mb-4">
                <li class="nav-item">
                    <a href="#terminal" class="nav-link active">
                        <i class="fa fa-flag-checkered"></i> Terminal Report
                        <span class="tab-count" id="count-terminal">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#progress" class="nav-link">
                        <i class="fa fa-chart-line"></i> Progress Report
                        <span class="tab-count" id="count-progress">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#impact" class="nav-link">
                        <i class="fa fa-seedling"></i> Impact Assessment
                        <span class="tab-count" id="count-impact">0</span>
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
                    <a href="#" class="nav-link" data-status="Revision">
                        <i class="fas fa-undo"></i> Needs Revision
                        <span class="status-count" id="count-Revision">0</span>
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
                <li class="nav-item">
                    <a href="#" class="nav-link" data-status="All">
                        <i class="fas fa-list"></i> All
                        <span class="status-count" id="count-All">0</span>
                    </a>
                </li>
            </ul>

            <div class="report-filters mb-3">

                <input type="text" class="form-control filter-search" id="search-report"
                       placeholder="Search title, project or coordinator...">

                <select class="form-control report-filter" id="filter-project" aria-label="Project">
                    <option value="">All Projects</option>
                    <option value="none">Unassigned project</option>
                </select>

                <select class="form-control report-filter" id="filter-coordinator" aria-label="Coordinator">
                    <option value="">All Coordinators</option>
                    <?php while($c = $coordinators->fetch_assoc()): ?>
                    <option value="<?php echo (int)$c['id'] ?>"><?php echo htmlspecialchars($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <select class="form-control report-filter" id="filter-category" aria-label="Category">
                    <option value="">All Categories</option>
                    <option>Research</option>
                    <option>Extension</option>
                    <option>Training</option>
                </select>

            </div>

            <div class="rp-note mb-3" id="unassigned-note" style="display:none"></div>

            <div class="rp-note info mb-3" id="impact-note" style="display:none">
                <i class="fas fa-info-circle"></i>
                Impact assessments are written and reviewed inside each project (Post-Activity tab).
                Open one to review it there.
            </div>

            <div id="report-table-wrap">
                <table class="table table-bordered table-hover table-striped" id="reportTable">
                    <thead class="thead-dark" id="report-head"></thead>
                    <tbody id="report-list">
                        <tr><td><div class="rp-empty"><i class="fas fa-spinner fa-spin"></i> Loading reports...</div></td></tr>
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>


<script src="assets/js/reports.js?v=<?php echo @filemtime('assets/js/reports.js') ?: 1; ?>"></script>
<script>

$(function(){

    ReportsPage({
        admin: true,
        // The cards count every report the filters show, of both types
        stats: function(reports){
            var count = function(status){
                return reports.filter(function(r){ return r.status === status; }).length;
            };
            $("#stat-total").text(reports.length);
            $("#stat-pending").text(count("Pending"));
            $("#stat-approved").text(count("Approved"));
            $("#stat-revision").text(count("Revision"));
            $("#stat-rejected").text(count("Rejected"));
        }
    });

});

</script>
