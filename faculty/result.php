<?php
$faculty_id = $_SESSION['login_id'];
?>

<style>
.selected-file-card{

    border:2px solid #d7f1df;

    background:#f8fffa;

    border-radius:12px;

    padding:15px;

    margin-top:18px;

}

.selected-pdf-icon{

    width:50px;

    height:50px;

    border-radius:12px;

    background:#ffe9e9;

    color:#dc3545;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;

}

    .report-icon{
    width:45px;
    height:45px;
    border-radius:12px;
    background:#ffeaea;
    color:#dc3545;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

.badge-status{
    padding:7px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.badge-uploaded{
    background:#d1f7dd;
    color:#198754;
}

.badge-category{
    border-radius:20px;
    padding:6px 12px;
    font-size:12px;
}

.action-btn{
    width:35px;
    height:35px;
    border-radius:8px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-right:5px;
}


.stat-card{

background:white;

border-radius:18px;

padding:25px;

display:flex;

justify-content:space-between;

align-items:center;

box-shadow:0 10px 25px rgba(0,0,0,.08);

transition:.3s;

margin-bottom:20px;

}

.stat-card:hover{

transform:translateY(-5px);

box-shadow:0 18px 35px rgba(0,0,0,.12);

}

.stat-card h6{

color:#888;

margin-bottom:8px;

}

.stat-card h2{

font-weight:bold;

margin:0;

}

.stat-card i{

font-size:45px;

opacity:.85;

}

.green{

border-left:6px solid #198754;

}

.blue{

border-left:6px solid #0d6efd;

}

.orange{

border-left:6px solid #ffc107;

}

.purple{

border-left:6px solid #6f42c1;

}

.table-card{

border-radius:20px;

overflow:hidden;

}

.table tbody tr{

transition:.2s;

}

.table tbody tr:hover{

background:#f4fff7;

transform:scale(1.01);

}

.btn-view{

border-radius:30px;

padding:6px 18px;

}

.upload-card{

transition:.3s;

}

.upload-card:hover{

transform:translateY(-4px);

}

.list-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:12px;
}

.btn-open-upload{
    background:#fff;
    color:#198754;
    border:none;
    border-radius:10px;
    padding:9px 18px;
    font-weight:600;
    transition:.2s;
}

.btn-open-upload:hover{
    background:#e9f8ef;
    color:#146c43;
}

/* Upload form modal */

.upload-modal .modal-content{
    margin-bottom:0;
}

.upload-modal .upload-card:hover{
    transform:none;
}

.upload-modal .upload-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.upload-modal .close{
    color:#fff;
    opacity:.85;
    text-shadow:none;
}

.upload-modal .close:hover{
    color:#fff;
    opacity:1;
}

.upload-modal .modal-footer{
    background:#fafafa;
    border-top:1px solid #f1f5f9;
}

.btn-cancel-upload{
    border-radius:10px;
    height:48px;
    padding:0 20px;
}

body{
    background:#f4f8f6;
}

.upload-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    margin-bottom:25px;
}

.upload-header{
    background:linear-gradient(135deg,#198754,#157347);
    color:#fff;
    padding:18px 25px;
}

.upload-area{

border:3px dashed #198754;

border-radius:15px;

padding:35px;

text-align:center;

cursor:pointer;

transition:.3s;

background:#f8fff9;

}

.upload-area:hover{

background:#e9f8ef;

border-color:#157347;

}

.upload-area.dragover{

background:#d1f2dc;

border-color:#146c43;

}

.upload-big-icon{

font-size:55px;

color:#198754;

margin-bottom:15px;

}

#browse-btn{

border-radius:25px;

padding:10px 25px;

font-weight:600;

}

#file-name{

font-weight:bold;

font-size:15px;

} 

.upload-header h4{
    margin:0;
    font-weight:600;
}

.upload-body{
    padding:30px;
}

.form-control{
    border-radius:10px;
    height:48px;
}

.form-control:focus{
    border-color:#198754;
    box-shadow:0 0 0 .15rem rgba(25,135,84,.2);
}

.btn-upload{
    background:#198754;
    color:#fff;
    border:none;
    border-radius:10px;
    height:48px;
    font-weight:bold;
    transition:.3s;
}

.btn-upload:hover{
    background:#157347;
}

.table-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.table thead{
    background:#198754;
    color:white;
}

.table td,
.table th{
    vertical-align:middle;
}

.btn-view{
    background:#198754;
    color:white;
    border-radius:8px;
}

.btn-view:hover{
    color:white;
    background:#146c43;
}

.upload-icon{
    font-size:40px;
    color:#198754;
    margin-bottom:10px;
}

.report-tabs .nav-link{
    background:#fff;
    color:#198754;
    border-radius:12px;
    padding:12px 24px;
    margin-right:10px;
    font-weight:600;
    box-shadow:0 5px 15px rgba(0,0,0,.06);
    transition:.3s;
}

.report-tabs .nav-link:hover{
    background:#e9f8ef;
}

.report-tabs .nav-link.active{
    background:linear-gradient(135deg,#198754,#157347);
    color:#fff;
}

.report-filters{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:10px;
}

.report-filters .filter-search{
    flex:1 1 220px;
    width:auto;
}

.report-filters .report-filter{
    flex:0 1 160px;
    width:auto;
}

.status-tabs{
    border-bottom:2px solid #eef2ef;
}

.status-tabs .nav-link{
    color:#6c757d;
    font-weight:600;
    padding:10px 18px;
    border-bottom:3px solid transparent;
    margin-bottom:-2px;
    transition:.2s;
}

.status-tabs .nav-link:hover{
    color:#198754;
}

.status-tabs .nav-link.active{
    color:#198754;
    border-bottom-color:#198754;
}

.status-count{
    display:inline-block;
    min-width:22px;
    margin-left:4px;
    padding:1px 7px;
    border-radius:20px;
    font-size:12px;
    text-align:center;
}

#count-Pending{
    background:#fff3cd;
    color:#b45309;
}

#count-Approved{
    background:#d1f7dd;
    color:#198754;
}

#count-Rejected{
    background:#fde2e2;
    color:#dc3545;
}

@media(max-width:768px){

.upload-body{
padding:20px;
}

.upload-header h4{
font-size:18px;
}

.table{
font-size:13px;
}

}

/* Text areas grow with their rows */
textarea.form-control{
    height:auto;
}

.report-purpose{
    color:#6b7c93;
    font-size:14px;
    margin:0 0 14px;
}

.report-purpose i{
    color:#198754;
    margin-right:4px;
}

.report-tabs{
    row-gap:10px;
}

.report-tabs .tab-count{
    display:inline-block;
    min-width:22px;
    margin-left:6px;
    padding:1px 7px;
    border-radius:20px;
    background:#d1f7dd;
    color:#198754;
    font-size:12px;
    text-align:center;
}

.report-tabs .nav-link.active .tab-count{
    background:rgba(255,255,255,.25);
    color:#fff;
}

.status-count{
    background:#eef2ef;
    color:#4b5563;
}

#count-Revision{
    background:#dbeafe;
    color:#1d4ed8;
}

/* Status badges */
.badge{
    padding:7px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.badge-warning{ background:#fff3cd; color:#b45309; }
.badge-success{ background:#d1f7dd; color:#198754; }
.badge-danger{ background:#fde2e2; color:#dc3545; }
.badge-revision{ background:#dbeafe; color:#1d4ed8; }

/* Rows */
.rp-title{ font-weight:600; color:#1f2937; }
.rp-meta{ font-size:12px; color:#6c757d; margin-top:2px; }
.rp-desc{ font-size:12.5px; color:#4b5563; margin-top:4px; max-width:380px; }

.rp-remarks{
    font-size:12px;
    color:#374151;
    background:#f8fafc;
    border-left:3px solid #93c5fd;
    border-radius:6px;
    padding:5px 8px;
    margin-top:6px;
    max-width:260px;
}

.rp-project{ font-weight:600; color:#198754; }
.rp-project:hover{ color:#146c43; text-decoration:underline; }

.rp-ref{
    display:block;
    font-size:11px;
    font-weight:700;
    color:#6c757d;
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
    color:#198754;
    border-radius:8px;
    font-size:12px;
    font-weight:600;
    padding:3px 10px;
    margin-left:6px;
}

.rp-link-btn:hover{ background:#198754; border-color:#198754; color:#fff; }

.rp-actions{ display:flex; flex-wrap:wrap; gap:4px; }

.action-btn{ border:1px solid #e5e7eb; background:#fff; margin-right:0; }
.rp-view{ color:#198754; border-color:#bbf7d0; background:#f0fdf4; }
.rp-view:hover{ color:#fff; background:#198754; }
.rp-edit,
.rp-move{ color:#1d4ed8; border-color:#bfdbfe; }
.rp-edit:hover,
.rp-move:hover{ color:#fff; background:#2563eb; }
.delete-report{ color:#dc3545; border-color:#fecaca; background:#fff5f5; }
.delete-report:hover{ color:#fff; background:#dc3545; }

.rp-open{
    border:1px solid #bbf7d0;
    background:#f0fdf4;
    color:#198754;
    border-radius:8px;
    font-weight:600;
}

.rp-open:hover{ background:#198754; color:#fff; }

.rp-empty{ text-align:center; color:#9ca3af; padding:28px 10px; }
.rp-empty i{ margin-right:6px; }

.rp-note{
    background:#fffbeb;
    border:1px solid #fde68a;
    color:#92400e;
    border-radius:12px;
    padding:10px 14px;
    font-size:14px;
}

.rp-note.info{ background:#f0fdf4; border-color:#bbf7d0; color:#166534; }

.rp-swal-sub{ font-weight:600; color:#374151; margin-bottom:6px; }

.upload-modal label{ margin-bottom:6px; }
.req{ color:#dc3545; }

#latest_upload{ display:block; max-width:170px; }
</style>


<div class="container-fluid">

<p class="report-purpose">
    <i class="fas fa-info-circle"></i>
    Manage and review reports across all extension projects. Every report belongs to a project and
    also appears in that project's Post-Activity tab.
</p>

<div class="row mb-4">

    <div class="col-md-3">
        <div class="stat-card green">
            <div>
                <h6>Total Reports</h6>
                <h2 id="total_reports">0</h2>
            </div>
            <i class="fa fa-file-alt"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card blue">
            <div>
                <h6>Uploaded Today</h6>
                <h2 id="today_reports">0</h2>
            </div>
            <i class="fa fa-cloud-upload-alt"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card orange">
            <div>
                <h6>Storage Used</h6>
                <h2 id="storage_used">0 MB</h2>
            </div>
            <i class="fa fa-database"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card purple">
            <div>
                <h6>Latest Upload</h6>
                <small id="latest_upload" class="text-truncate">No upload</small>
            </div>
            <i class="fa fa-clock"></i>
        </div>
    </div>

</div>

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

<div class="row">

<div class="col-12">

<div class="card table-card">

<div class="upload-header list-header">

<h4>
<i class="fa fa-folder-open"></i>
My <span class="report-type-label">Terminal Report</span>s
</h4>

<button type="button" class="btn btn-open-upload" id="open-upload">
    <i class="fa fa-cloud-upload-alt"></i>
    Upload Report
</button>

</div>

<div class="card-body">

<ul class="nav status-tabs mb-3">
    <li class="nav-item">
        <a href="#" class="nav-link active" data-status="Pending">
            <i class="fa fa-clock"></i> Pending
            <span class="status-count" id="count-Pending">0</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link" data-status="Revision">
            <i class="fa fa-undo"></i> Needs Revision
            <span class="status-count" id="count-Revision">0</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link" data-status="Approved">
            <i class="fa fa-check-circle"></i> Approved
            <span class="status-count" id="count-Approved">0</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link" data-status="Rejected">
            <i class="fa fa-times-circle"></i> Rejected
            <span class="status-count" id="count-Rejected">0</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link" data-status="All">
            <i class="fa fa-list"></i> All
            <span class="status-count" id="count-All">0</span>
        </a>
    </li>
</ul>

<div class="report-filters mb-3">

    <div class="input-group filter-search">
        <div class="input-group-prepend">
            <span class="input-group-text bg-white">
                <i class="fa fa-search text-success"></i>
            </span>
        </div>
        <input type="text" class="form-control" id="search-report"
               placeholder="Search reports or projects...">
    </div>

    <select class="form-control report-filter" id="filter-project" aria-label="Project">
        <option value="">All Projects</option>
        <option value="none">Unassigned project</option>
    </select>

    <select class="form-control report-filter" id="filter-category" aria-label="Category">
        <option value="">All Categories</option>
        <option>Research</option>
        <option>Extension</option>
        <option>Training</option>
    </select>

    <select class="form-control report-filter" id="sort-report" aria-label="Sort">
        <option value="newest">Sort by : Newest</option>
        <option value="oldest">Oldest</option>
        <option value="title">Title</option>
    </select>

</div>

<div class="rp-note mb-3" id="unassigned-note" style="display:none"></div>

<div class="rp-note info mb-3" id="impact-note" style="display:none">
    <i class="fas fa-info-circle"></i>
    The Impact Assessment is written inside its project, in the Post-Activity tab, once the project is completed.
    Open a project to start or continue one.
</div>

<div class="table-responsive">
<table class="table table-hover align-middle">
    <thead class="bg-light" id="report-head"></thead>
    <tbody id="report-list">
        <tr><td><div class="rp-empty"><i class="fas fa-spinner fa-spin"></i> Loading reports...</div></td></tr>
    </tbody>
</table>
</div>

</div>

</div>

</div>

</div>

</div>


<!-- UPLOAD / EDIT REPORT MODAL -->
<div class="modal fade upload-modal" id="upload-modal" tabindex="-1" role="dialog"
     aria-labelledby="upload-modal-title" aria-hidden="true">

<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">

<div class="modal-content upload-card">

<div class="upload-header">

<h4 id="upload-modal-title">
<i class="fa fa-cloud-upload-alt"></i>
<span id="upload-modal-label">Upload Report</span>
</h4>

<button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
</button>

</div>

<div class="modal-body upload-body">

<form id="upload-report" enctype="multipart/form-data" novalidate>

    <input type="hidden" name="id" id="report_id" value="">

    <div class="form-group">
        <label for="report_project"><b>Project</b> <span class="req">*</span></label>
        <select name="project_id" id="report_project" class="form-control">
            <option value="">Choose the project this report is for</option>
        </select>
        <!-- Shown when you have no project yet -->
        <div id="no-project" class="alert alert-warning small mt-2 mb-0" style="display:none">
            You have no project yet. Reports belong to a project, so
            <a href="index.php?page=projects">create the project first</a>.
        </div>
    </div>

    <div class="form-group mt-3">
        <label for="report_type"><b>Report Type</b> <span class="req">*</span></label>
        <select name="report_type" id="report_type" class="form-control">
            <option value="Terminal Report">Terminal Report</option>
            <option value="Progress Report">Progress Report</option>
        </select>
    </div>

    <div class="form-group mt-3">
        <label for="report_title"><b>Report Title</b> <span class="req">*</span></label>
        <input type="text" name="title" id="report_title" class="form-control" placeholder="Enter report title" maxlength="255">
    </div>

    <div class="form-row mt-3">
        <div class="form-group col-6">
            <label for="period_start"><b>Reporting Period</b> <small class="text-muted">(from)</small></label>
            <input type="date" name="period_start" id="period_start" class="form-control">
        </div>
        <div class="form-group col-6">
            <label for="period_end"><b>&nbsp;</b><small class="text-muted">(to)</small></label>
            <input type="date" name="period_end" id="period_end" class="form-control">
        </div>
    </div>

    <div class="form-group">
        <label for="report_category"><b>Category</b> <small class="text-muted">(optional)</small></label>
        <select class="form-control" name="category" id="report_category">
            <option value="">Select category</option>
            <option>Research</option>
            <option>Extension</option>
            <option>Training</option>
        </select>
    </div>

    <div class="form-group mt-3">
        <label for="report_description"><b>Description</b> <small class="text-muted">(optional)</small></label>
        <textarea
            name="description"
            id="report_description"
            class="form-control"
            rows="3"
            placeholder="What this report covers"></textarea>
    </div>

    <div class="form-group mt-3 mb-0">

        <label><b>Report File</b> <span class="req" id="file-required">*</span></label>

        <div class="upload-area" id="upload-area">

            <input
                type="file"
                id="report"
                name="report"
                accept=".pdf,.doc,.docx"
                hidden>

            <i class="fa fa-cloud-upload upload-big-icon"></i>

            <h5 class="mt-2">Drag &amp; Drop your file here</h5>

            <small class="text-muted d-block mb-3">
                or
            </small>

            <button type="button" class="btn btn-success px-4" id="browse-btn">
                <i class="fa fa-folder-open"></i>
                Browse Files
            </button>

            <div class="upload-info mt-3">
                PDF or Word (.doc, .docx) • Maximum size: 20 MB
            </div>

        </div>

        <small class="text-muted d-block mt-2" id="keep-file-note" style="display:none !important">
            Leave it empty to keep the current file.
        </small>

    </div>

    <!-- FILE PREVIEW -->
    <div id="selected-file" style="display:none">

        <div class="selected-file-card">

            <div class="d-flex align-items-center">

                <div class="selected-pdf-icon">
                    <i class="fa fa-file-alt"></i>
                </div>

                <div class="ml-3 flex-grow-1 text-truncate">

                    <div id="file-name" class="text-truncate"></div>

                    <small id="file-size" class="text-muted"></small>

                </div>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger ml-2"
                    id="remove-file"
                    aria-label="Remove file">

                    <i class="fa fa-times"></i>

                </button>

            </div>

        </div>

    </div>

</form>

</div>

<div class="modal-footer">

    <button type="button" class="btn btn-light btn-cancel-upload" data-dismiss="modal">
        Cancel
    </button>

    <button type="submit" form="upload-report" class="btn btn-upload px-4">
        <i class="fa fa-upload"></i>
        <span class="btn-upload-label">Submit Report</span>
    </button>

</div>

</div>

</div>

</div>


<script src="assets/js/reports.js?v=<?php echo @filemtime('assets/js/reports.js') ?: 1; ?>"></script>
<script>
$(function(){

    var MAX_BYTES = 20 * 1024 * 1024;
    var ALLOWED = /\.(pdf|docx?)$/i;

    var fileInput = document.getElementById("report");

    function today(){
        var d = new Date();
        return d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
    }

    var page = ReportsPage({
        admin: false,
        // The cards count the reports the filters show, of both types
        stats: function(reports){

            var bytes = 0, todays = 0, latest = null;

            $.each(reports, function(i, r){
                bytes += r.bytes || 0;
                if(r.uploaded_date === today()) todays++;
                if(!latest || r.uploaded_at > latest.uploaded_at) latest = r;
            });

            $("#total_reports").text(reports.length);
            $("#today_reports").text(todays);
            $("#storage_used").text((bytes / 1048576).toFixed(2) + " MB");
            $("#latest_upload").text(latest ? latest.title + " · " + latest.uploaded_display : "No upload")
                               .attr("title", latest ? latest.title : "");
        },
        edit: open_form
    });

    // The projects a report can be for (your own)
    page.projects.done(function(projects){
        var select = $("#report_project");
        $.each(projects, function(i, p){
            select.append($("<option>").val(p.id).text(p.ref + " · " + p.title + (p.term ? " (" + p.term + ")" : "")));
        });
        $("#no-project").toggle(projects.length === 0);
    });

    // Move the modal to <body> so the page's cards can't stack it under the backdrop
    $("#upload-modal").appendTo("body");

    /* ---------------- the form ---------------- */

    function show_file(file){
        $("#file-name").text(file.name);
        $("#file-size").text((file.size / 1024 / 1024).toFixed(2) + " MB");
        $("#selected-file").show();
    }

    function clear_file(){
        fileInput.value = "";
        $("#selected-file").hide();
        $("#file-name, #file-size").text("");
    }

    function usable(file){
        if(!ALLOWED.test(file.name)){
            Swal.fire({ icon: "error", title: "Invalid File", text: "Please choose a PDF or Word document." });
            return false;
        }
        if(file.size > MAX_BYTES){
            Swal.fire({ icon: "error", title: "File Too Large", text: "The file is larger than 20 MB." });
            return false;
        }
        return true;
    }

    // A new report (optionally for a project and type), or one of yours to change and resubmit
    function open_form(report, project_id, type){

        var editing = !!(report && report.id);

        $("#upload-report")[0].reset();
        clear_file();

        $("#report_id").val(editing ? report.id : "");
        $("#upload-modal-label").text(editing ? "Edit Report" : "Upload Report");
        $(".btn-upload-label").text(editing ? "Save and Resubmit" : "Submit Report");
        $("#file-required").toggle(!editing);
        $("#keep-file-note").attr("style", editing ? "" : "display:none !important");

        page.projects.done(function(){
            if(editing){
                $("#report_project").val(report.project_id ? String(report.project_id) : "");
                $("#report_type").val(report.type);
                $("#report_title").val(report.title);
                $("#period_start").val(report.period_start || "");
                $("#period_end").val(report.period_end || "");
                $("#report_category").val(report.category || "");
                $("#report_description").val(report.description || "");
            }else{
                $("#report_project").val(project_id ? String(project_id) : "");
                $("#report_type").val(type || page.type() || "Terminal Report");
                if(!$("#report_type").val()) $("#report_type").val("Terminal Report");
            }
            $("#upload-modal").modal("show");
        });
    }

    $("#open-upload").click(function(){
        open_form(null);
    });

    $("#upload-modal").on("shown.bs.modal", function(){
        $($("#report_project").val() ? "#report_title" : "#report_project").trigger("focus");
    });

    $("#browse-btn").click(function(){
        fileInput.click();
    });

    $(fileInput).change(function(){
        if(this.files.length){
            if(usable(this.files[0])){
                show_file(this.files[0]);
            }else{
                clear_file();
            }
        }
    });

    $("#remove-file").click(clear_file);

    $("#upload-area").on("dragover", function(e){
        e.preventDefault();
        $(this).addClass("dragover");
    }).on("dragleave", function(){
        $(this).removeClass("dragover");
    }).on("drop", function(e){
        e.preventDefault();
        $(this).removeClass("dragover");
        var files = e.originalEvent.dataTransfer.files;
        if(files.length && usable(files[0])){
            fileInput.files = files;
            show_file(files[0]);
        }
    });

    $("#upload-report").submit(function(e){

        e.preventDefault();

        var editing = !!$("#report_id").val();
        var problem =
            !$("#report_project").val() ? "Choose the project this report is for." :
            !$.trim($("#report_title").val()) ? "Please enter the report title." :
            (!editing && !fileInput.files.length) ? "Please attach the report file." :
            ($("#period_start").val() && $("#period_end").val() && $("#period_start").val() > $("#period_end").val())
                ? "The reporting period ends before it starts." : "";

        if(problem){
            Swal.fire({ icon: "warning", title: "Almost there", text: problem });
            return;
        }

        var type = $("#report_type").val();
        var button = $(".btn-upload");

        $.ajax({
            url: "ajax.php?action=report_save",
            type: "POST",
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function(){
                button.prop("disabled", true).find("i").attr("class", "fa fa-spinner fa-spin");
            }
        }).done(function(resp){

            if(!resp.ok){
                Swal.fire({ icon: "error", title: "Not submitted", text: resp.error });
                return;
            }

            Swal.fire({
                icon: "success",
                title: editing ? "Resubmitted!" : "Submitted!",
                text: "The report is waiting for the Extension Office's review. It also appears in the project's Post-Activity tab.",
                timer: 2200,
                showConfirmButton: false
            });

            $("#upload-modal").modal("hide");

            // New and changed reports wait for review
            page.show(type, "Pending");

        }).fail(function(xhr){
            var resp = xhr.responseJSON;
            Swal.fire({ icon: "error", title: "Not submitted", text: (resp && resp.error) || "Please try again." });
        }).always(function(){
            button.prop("disabled", false).find("i").attr("class", "fa fa-upload");
        });
    });

    // Opened from a project's "Upload a Report" button (?new=1&project=ID)
    var params = new URLSearchParams(location.search);
    if(params.get("new") === "1"){
        open_form(null, params.get("project"), params.get("type"));
    }

});
</script>
