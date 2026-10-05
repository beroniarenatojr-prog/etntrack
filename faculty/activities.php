<?php
include_once 'db_connect.php';

$faculty_id = $_SESSION['login_id'];

// Largest form PHP accepts (post_max_size), minus 1 MB for the other fields: the picture picker stays under it
$post_limit = trim(ini_get('post_max_size'));
$units = array('G' => 1073741824, 'M' => 1048576, 'K' => 1024);
$unit = strtoupper(substr($post_limit, -1));
$max_send_bytes = (int)((float)$post_limit * (isset($units[$unit]) ? $units[$unit] : 1)) - 1048576;

// Activities belong to a project once the database update that links them has run
require_once 'questionnaire_lib.php';
$links_ready = $conn->query("
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activities' AND COLUMN_NAME = 'start_time'
")->num_rows > 0;

// For the Coordinator filter on the All Activities tab
$coordinators = $conn->query("SELECT id, CONCAT(firstname,' ',lastname) AS name FROM faculty_list ORDER BY firstname, lastname");
?>

<style>



    .upload-box.dragging{

    background:#e8fff0;

    border-color:#198754;

    transform:scale(1.02);

}

body{
    background:#f4f8f6;
}

.activity-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.card-header-green{
    background:#198754;
    color:#fff;
    padding:20px 25px;
    border-bottom:none;
}

.card-header-green h4{
    font-weight:600;
    margin:0;
    font-size:20px;
}

.card-header-green p{
    margin:4px 0 0;
    opacity:.85;
    font-size:14px;
}

.card-body{
    padding:25px;
}

.form-control{
    border-radius:10px;
}

.btn-save{
    background:#198754;
    color:#fff;
    font-weight:bold;
    border:none;
    border-radius:10px;
}

.btn-save:hover{
    background:#146c43;
    color:white;
}

.table thead{
    background:#198754;
    color:white;
}

.stats-card{
    background:#fff;
    border-radius:18px;
    padding:20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    box-shadow:0 8px 20px rgba(0,0,0,.06);
    transition:.3s;
    height:110px;
}

.stats-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 30px rgba(0,0,0,.12);
}

.stats-icon{
    width:58px;
    height:58px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

.stats-icon.green{
    background:#e8f8ef;
    color:#1a9c5b;
}

.stats-icon.success{
    background:#e8f8ef;
    color:#28a745;
}

.stats-icon.warning{
    background:#fff5de;
    color:#f0ad4e;
}

.stats-icon.danger{
    background:#ffeaea;
    color:#dc3545;
}

.stats-content{
    flex:1;
    margin-left:15px;
}

.stats-content small{
    color:#999;
    display:block;
    font-weight:600;
}

.stats-content h3{
    margin:4px 0;
    font-size:30px;
    font-weight:700;
    color:#222;
}

.stats-content span{
    font-size:12px;
    color:#999;
}

.stats-chart{
    font-size:28px;
    opacity:.7;
}

/* ==========================
   CREATE ACTIVITY CARD
========================== */

.activity-card{
    border:none;
    border-radius:24px;
    overflow:hidden;
    background:#fff;
    box-shadow:0 10px 30px rgba(0,0,0,.06);
}

.card-header-green{
    background:#fff;
    padding:28px;
    border-bottom:1px solid #edf2f7;
}

.card-header-green h4{
    color:#1b1b1b;
    font-size:24px;
    font-weight:700;
    margin-bottom:5px;
}

.card-header-green p{
    color:#8d98a5;
    margin:0;
    font-size:14px;
}

.card-header-green i{
    width:50px;
    height:50px;
    border-radius:15px;
    background:#e9f8ef;
    color:#19a75d;
    display:inline-flex;
    justify-content:center;
    align-items:center;
    margin-right:12px;
    font-size:22px;
}

.card-body{
    padding:30px;
}

/* ==========================
LABEL
========================== */

.form-group label{
    font-size:13px;
    font-weight:700;
    color:#374151;
    margin-bottom:8px;
}

/* ==========================
INPUTS
========================== */

.form-control{
    height:48px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    box-shadow:none;
    transition:.3s;
}

textarea.form-control{
    height:auto;
    resize:none;
}

.form-control:focus{
    border-color:#1ea65b;
    box-shadow:0 0 0 4px rgba(30,166,91,.12);
}

/* ==========================
DATE + VENUE
========================== */

.row-gap{
    margin-top:15px;
}

/* ==========================
UPLOAD BOX
========================== */

.upload-box{

height:150px;

border:2px dashed #1ea65b;

border-radius:18px;

display:flex;

flex-direction:column;

justify-content:center;

align-items:center;

cursor:pointer;

transition:.3s;

background:#fcfffd;

}

.upload-box:hover{

background:#f2fff6;

}

.upload-box i{

font-size:48px;

color:#1ea65b;

margin-bottom:12px;

}

.upload-box input{

display:none;

}

/* Chosen pictures (thumbnails with × under the drop zone) */

.image-grid{
display:grid;
grid-template-columns:repeat(auto-fill, minmax(84px, 1fr));
gap:10px;
margin-top:12px;
}

.image-grid:empty{
display:none;
}

.image-thumb{
position:relative;
aspect-ratio:1 / 1;
border-radius:12px;
overflow:hidden;
border:1px solid #e3e9e5;
background:#f4f8f6;
}

.image-thumb img{
display:block;
width:100%;
height:100%;
object-fit:cover;
}

.image-thumb .remove-thumb{
position:absolute;
top:5px;
right:5px;
width:24px;
height:24px;
padding:0;
border:none;
border-radius:50%;
background:rgba(220,53,69,.92);
color:#fff;
font-size:16px;
line-height:24px;
cursor:pointer;
}

.image-thumb .remove-thumb:hover{
background:#b02a37;
}

/* ==========================
BUTTON
========================== */

.btn-save{

    height:52px;

    border:none;

    border-radius:12px;

    background:linear-gradient(90deg,#1fa15d,#14894d);

    color:white;

    font-size:16px;

    font-weight:600;

    transition:.3s;

}

.btn-save:hover{

    transform:translateY(-2px);

    box-shadow:0 12px 20px rgba(25,135,84,.25);

}


/* =======================
   TABLE CARD
=======================*/

.activity-card{

    border:none;

    border-radius:22px;

    background:white;

    box-shadow:0 10px 30px rgba(0,0,0,.05);

}

/* Header */

.modern-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:25px;

    border-bottom:1px solid #edf1f7;

}

.header-left{

    display:flex;

    align-items:center;

}

.header-icon{

    width:50px;

    height:50px;

    border-radius:15px;

    background:#eaf8ef;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#19a85d;

    font-size:22px;

    margin-right:15px;

}

.header-left h4{

    margin:0;

    font-size:24px;

    font-weight:700;

}

.header-left p{

    margin:0;

    color:#9aa4af;

    font-size:13px;

}

/* Search */

.header-right{

    display:flex;

    align-items:center;

    gap:15px;

}

.search-box{

    display:flex;

    align-items:center;

    background:#fff;

    border:1px solid #e5e7eb;

    border-radius:12px;

    padding:0 15px;

    height:45px;

}

.search-box i{

    color:#999;

}

.search-box input{

    border:none;

    outline:none;

    padding-left:10px;

    width:230px;

}

/* Status filter */

.status-filter{

    height:45px;

    padding:0 15px;

    border-radius:12px;

    background:white;

    border:1px solid #e5e7eb;

    font-weight:600;

    color:#495057;

    cursor:pointer;

}

.status-filter:focus{

    outline:none;

    border-color:#198754;

}

/* Create Activity button */

.btn-create-activity{

    height:45px;

    padding:0 18px;

    border:none;

    border-radius:12px;

    background:linear-gradient(90deg,#1fa15d,#14894d);

    color:white;

    font-weight:600;

    white-space:nowrap;

    transition:.3s;

}

.btn-create-activity:hover{

    color:white;

    transform:translateY(-2px);

    box-shadow:0 12px 20px rgba(25,135,84,.25);

}

/* Create / Edit Activity modal */

.activity-modal .modal-content{

    border:none;

}

.activity-modal .card-header-green{

    display:flex;

    justify-content:space-between;

    align-items:flex-start;

    gap:12px;

}

.activity-modal .modal-body{

    padding:25px 28px;

}

.activity-modal .modal-footer{

    padding:15px 28px;

    background:#fafafa;

    border-top:1px solid #edf2f7;

}

.btn-cancel-activity{

    height:52px;

    padding:0 22px;

    border-radius:12px;

}

/* Date range / coordinator filter row */

.activity-filters{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:10px 15px;
    padding:15px 25px;
    border-bottom:1px solid #edf1f7;
    background:#fafcfb;
}

.activity-filters .filter-field{
    display:flex;
    align-items:center;
    gap:8px;
}

.activity-filters label{
    margin:0;
    font-size:13px;
    font-weight:600;
    color:#6c757d;
}

.activity-filters .filter-input{
    height:40px;
    padding:0 12px;
    border:1px solid #e5e7eb;
    border-radius:10px;
    background:#fff;
    color:#495057;
}

.activity-filters .filter-input:focus{
    outline:none;
    border-color:#198754;
}

/* Table */

.table{

    margin-bottom:0;

}

.table thead th{

    background:#179653;

    color:white;

    border:none;

    font-weight:600;

    padding:15px;

}

.table thead th:first-child{

    border-top-left-radius:12px;

}

.table thead th:last-child{

    border-top-right-radius:12px;

}

.table td{

    vertical-align:middle;

    padding:18px 15px;

    border-top:1px solid #f3f3f3;

}

/* ===========================
   MY / ALL ACTIVITIES TABS
=========================== */

.activity-tabs .nav-link{
    background:#fff;
    color:#198754;
    border-radius:12px;
    padding:12px 24px;
    margin-right:10px;
    font-weight:600;
    box-shadow:0 5px 15px rgba(0,0,0,.06);
    transition:.3s;
}

.activity-tabs .nav-link:hover{
    background:#e9f8ef;
}

.activity-tabs .nav-link.active{
    background:linear-gradient(135deg,#198754,#157347);
    color:#fff;
}

/* ===========================
   RESPONSIVE
=========================== */

@media(max-width:992px){

    .modern-header{
        align-items:flex-start;
        flex-direction:column;
    }

    .header-right{
        width:100%;
    }

    .search-box{
        flex:1;
    }

    .search-box input{
        width:100%;
    }

}

@media(max-width:480px){

    .activity-tabs .nav-link{
        padding:10px 16px;
    }

    .header-right{
        flex-direction:column;
        align-items:stretch;
    }

    .status-filter{
        width:100%;
    }

}



</style>

<div class="container-fluid">

<p class="at-page-purpose">
    <i class="fas fa-info-circle"></i>
    Manage and monitor activities across all extension projects. Every activity belongs to a project;
    open the project to follow it from planning to its reports.
</p>

<!-- My / All Activities -->
<ul class="nav activity-tabs mb-4">
    <li class="nav-item">
        <a href="#mine" class="nav-link active" data-scope="mine">
            <i class="fa fa-user"></i> My Activities
        </a>
    </li>
    <li class="nav-item">
        <a href="#all" class="nav-link" data-scope="all">
            <i class="fa fa-users"></i> All Activities
        </a>
    </li>
</ul>

<!-- Dashboard Summary (follows the selected tab) -->
<div class="row mb-4">

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon green">
                <i class="fa fa-calendar"></i>
            </div>

            <div class="stats-content">
                <small>Total Activities</small>
                <h3 id="total_activity">0</h3>
                <span id="total_activity_note">Your submitted activities</span>
            </div>

            <div class="stats-chart text-success">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon success">
                <i class="fa fa-check-circle"></i>
            </div>

            <div class="stats-content">
                <small>Approved</small>
                <h3 id="approved_activity">0</h3>
                <span id="approved_activity_note">0% of total</span>
            </div>

            <div class="stats-chart text-success">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon warning">
                <i class="fa fa-clock-o"></i>
            </div>

            <div class="stats-content">
                <small>Pending</small>
                <h3 id="pending_activity">0</h3>
                <span id="pending_activity_note">0% of total</span>
            </div>

            <div class="stats-chart text-warning">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon danger">
                <i class="fa fa-times-circle"></i>
            </div>

            <div class="stats-content">
                <small>Rejected</small>
                <h3 id="rejected_activity">0</h3>
                <span id="rejected_activity_note">0% of total</span>
            </div>

            <div class="stats-chart text-danger">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

</div>






<!-- MY ACTIVITIES TAB: own activities (the create form opens in #activity-modal) -->
<div id="tab-mine">

<div class="row">

<div class="col-12">

<div class="card activity-card">

<div class="modern-header">

    <div class="header-left">

        <div class="header-icon">
            <i class="fa fa-list"></i>
        </div>

        <div>

            <h4>My Activities</h4>

            <p>Monitor the status of your submitted activities.</p>

        </div>

    </div>

    <div class="header-right">

        <div class="search-box">

            <i class="fa fa-search"></i>

            <input type="text" id="my-search" placeholder="Search activities...">

        </div>

        <select class="status-filter" id="my-status">
            <option value="">All Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="revision">Needs Revision</option>
            <option value="rejected">Rejected</option>
        </select>

        <button type="button" class="btn btn-create-activity" id="open-create">
            <i class="fa fa-calendar-plus mr-1"></i>
            Create Activity
        </button>

    </div>

</div>

<div class="activity-filters">

    <div class="filter-field">
        <label for="my-from">From</label>
        <input type="date" class="filter-input" id="my-from">
    </div>

    <div class="filter-field">
        <label for="my-to">To</label>
        <input type="date" class="filter-input" id="my-to">
    </div>

</div>

<div class="card-body">

<!-- Shown when rows are ticked -->
<div class="at-bulk" id="my-bulk">
    <span class="at-bulk-count"></span>
    <button type="button" class="btn btn-sm btn-danger" data-bulk="delete">
        <i class="fas fa-trash-alt mr-1"></i> Delete Selected
    </button>
</div>

<!-- Filled by ActivityTable (assets/js/activity-table.js) -->
<table id="my-activity-table" class="table at-table"></table>

</div>

</div>

</div>

</div>

</div>
<!-- /MY ACTIVITIES TAB -->

<!-- ALL ACTIVITIES TAB: every coordinator's activities, view only -->
<div id="tab-all" style="display:none">

<div class="card activity-card">

<div class="modern-header">

    <div class="header-left">

        <div class="header-icon">
            <i class="fa fa-globe"></i>
        </div>

        <div>

            <h4>All Activities</h4>

            <p>View the extension activities of all coordinators.</p>

        </div>

    </div>

    <div class="header-right">

        <div class="search-box">

            <i class="fa fa-search"></i>

            <input type="text" id="all-search" placeholder="Search activities or coordinators...">

        </div>

        <select class="status-filter" id="all-status">
            <option value="">All Status</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="revision">Needs Revision</option>
            <option value="rejected">Rejected</option>
        </select>

    </div>

</div>

<div class="activity-filters">

    <div class="filter-field">
        <label for="all-coordinator">Coordinator</label>
        <select class="filter-input" id="all-coordinator">
            <option value="">All Coordinators</option>
            <?php while($c = $coordinators->fetch_assoc()): ?>
            <option value="<?php echo $c['id'] ?>"><?php echo htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="all-from">From</label>
        <input type="date" class="filter-input" id="all-from">
    </div>

    <div class="filter-field">
        <label for="all-to">To</label>
        <input type="date" class="filter-input" id="all-to">
    </div>

</div>

<div class="card-body">

<!-- Filled by ActivityTable (assets/js/activity-table.js); view only -->
<table id="all-activity-table" class="table at-table"></table>

</div>

</div>

</div>
<!-- /ALL ACTIVITIES TAB -->

</div>

<!-- CREATE / EDIT ACTIVITY MODAL -->
<div class="modal fade activity-modal" id="activity-modal" tabindex="-1" role="dialog"
     aria-labelledby="form-title" aria-hidden="true">

<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">

<div class="modal-content activity-card">

<div class="card-header-green">

<div>

<!-- Text changes to "Edit Activity" while editing (see start_edit) -->
<h4 id="form-title">
<i class="fa fa-calendar-plus mr-2"></i>
Create Activity
</h4>

<p id="form-subtitle">Submit extension activities for approval.</p>

</div>

<button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
</button>

</div>

<div class="modal-body">

<form id="activity-form" enctype="multipart/form-data">

<!-- Set while editing an existing activity -->
<input type="hidden" name="id" id="activity_id" value="">

<!-- The admin's revision note, shown while editing -->
<div id="edit-note" class="alert alert-warning" style="display:none"></div>

<div class="form-group">

<label>Pictures <small class="text-muted" id="image-hint">(Optional · up to 10 · JPG, PNG, GIF or WEBP · 5 MB each)</small></label>

<!-- One drop zone for several pictures; the chosen pictures show below it -->
<label class="upload-box" id="drop-area">
    <i class="fa fa-cloud-upload"></i>
    <h6>Drag &amp; drop pictures here</h6>
    <small>or click to choose (you can pick several)</small>
    <input
        type="file"
        id="activity_images"
        accept="image/jpeg,image/png,image/gif,image/webp"
        multiple>
</label>

<div class="image-grid" id="image-grid"></div>

<small class="text-muted d-block mt-2" id="image-count">0 / 10 pictures</small>

</div>


<?php if($links_ready): ?>
<div class="form-group">
<label for="activity_project">Project <span class="text-danger">*</span></label>
<select name="project_id" id="activity_project" class="form-control" required>
    <option value="">Choose the project this activity is part of</option>
</select>
<!-- Shown when you have no project yet -->
<div id="no-project" class="alert alert-warning small mt-2 mb-0" style="display:none">
    You have no project yet. Activities belong to a project, so
    <a href="index.php?page=projects">create the project first</a>.
</div>
</div>
<?php endif; ?>

<div class="form-group">

<label>Activity Name</label>

<textarea
name="activity_name"
class="form-control"
rows="3"
required></textarea>

</div>

<div class="form-group">

<label>Purpose</label>

<textarea
name="purpose"
class="form-control"
rows="3"
required></textarea>

</div>

<div class="form-group">

<label>Description</label>

<textarea
name="description"
class="form-control"
rows="4"></textarea>

</div>

<div class="form-group">

<label>Activity Date</label>

<input type="date"
name="activity_date"
id="activity_date"
class="form-control"
required>

</div>

<?php if($links_ready): ?>
<div class="form-row">
<div class="form-group col-6">
<label for="activity_start">Start Time</label>
<input type="time" name="start_time" id="activity_start" class="form-control">
</div>
<div class="form-group col-6">
<label for="activity_end">End Time</label>
<input type="time" name="end_time" id="activity_end" class="form-control">
</div>
</div>
<?php endif; ?>

<div class="form-group">

<label>Venue</label>

<input
type="text"
name="venue"
id="venue"
class="form-control"
placeholder="e.g. ISU Gym"
required>

<!-- Shown when the venue is already booked on the chosen date -->
<div id="venue-conflict" class="text-danger small mt-2" style="display:none"></div>

</div>

</form>

</div>

<div class="modal-footer">

<button type="button" class="btn btn-light btn-cancel-activity" data-dismiss="modal">
    Cancel
</button>

<button type="submit" form="activity-form" class="btn btn-save px-4">

<i class="fa fa-paper-plane mr-2"></i>

<span id="submit-text">Submit Activity</span>

</button>

</div>

</div>

</div>

</div>

<link rel="stylesheet" href="assets/css/activity-table.css?v=<?php echo @filemtime('assets/css/activity-table.css') ?: 1; ?>">

<script src="assets/js/activity-table.js?v=<?php echo @filemtime('assets/js/activity-table.js') ?: 1; ?>"></script>

<script>

var currentScope = "mine";   // "mine" or "all"
var activityCounts = null;   // stat card numbers from activity_counts
var myTable, allTable;       // ActivityTable instances (assets/js/activity-table.js)

$(document).ready(function(){

// My Activities: your own activities, with checkboxes, Delete Selected and Edit
myTable = ActivityTable.create({
    table: "#my-activity-table",
    scope: "mine",
    selectable: true,
    showImplementer: false,
    search: "#my-search",
    bulkBar: "#my-bulk",
    filters: function(){
        return {
            status: $("#my-status").val(),
            date_from: $("#my-from").val(),
            date_to: $("#my-to").val()
        };
    },
    onEdit: start_edit,
    onChange: load_activity_counts,
    emptyText: "You haven't submitted any activities yet."
});

// All Activities: every coordinator's activities, view only
allTable = ActivityTable.create({
    table: "#all-activity-table",
    scope: "all",
    selectable: false,
    search: "#all-search",
    filters: function(){
        return {
            status: $("#all-status").val(),
            coordinator: $("#all-coordinator").val(),
            date_from: $("#all-from").val(),
            date_to: $("#all-to").val()
        };
    },
    onChange: load_activity_counts,
    emptyText: "There are no submitted activities yet."
});

// Keep the selected tab after a page refresh
if(location.hash == "#all"){
    set_scope("all");
}

$(".activity-tabs .nav-link").click(function(e){
    e.preventDefault();
    set_scope($(this).data("scope"));
    history.replaceState(null, "", $(this).attr("href"));
});

$("#my-status, #my-from, #my-to").change(function(){ myTable.reload(); });
$("#all-status, #all-coordinator, #all-from, #all-to").change(function(){ allTable.reload(); });

// Keep each date range valid: "To" can't be before "From"
$("#my-from, #all-from").change(function(){
    $(this).closest(".activity-filters").find('[id$="-to"]').attr("min", this.value);
});

$("#my-to, #all-to").change(function(){
    $(this).closest(".activity-filters").find('[id$="-from"]').attr("max", this.value);
});

// Check the booking as soon as the date or venue changes
var conflictTimer;

$("#activity_date, #venue").on("input change", function(){
    clearTimeout(conflictTimer);
    conflictTimer = setTimeout(check_conflict, 400);
});

// The projects an activity can belong to (your own). Opened from a project's
// "Request an Activity" button (?new=1&project=ID), the form starts with it chosen.
var projectsLoaded = $.Deferred();

if($("#activity_project").length){
    $.getJSON("ajax.php?action=project_options").done(function(projects){
        var select = $("#activity_project");
        $.each(projects, function(i, p){
            select.append($("<option>").val(p.id).text(p.ref + " · " + p.title + (p.term ? " (" + p.term + ")" : "")));
        });
        $("#no-project").toggle(projects.length === 0);
        projectsLoaded.resolve(projects);
    });
}else{
    projectsLoaded.resolve([]);
}

(function(){
    var params = new URLSearchParams(location.search);
    if(params.get("new") === "1"){
        projectsLoaded.done(function(){
            if(params.get("project")){
                $("#activity_project").val(params.get("project"));
            }
            $("#activity-modal").modal("show");
        });
    }
})();

// Move the modal to <body> so the page's cards can't stack it under the backdrop
$("#activity-modal").appendTo("body");

$("#open-create").click(function(){
    $("#activity-modal").modal("show");
});

$("#activity-modal").on("shown.bs.modal", function(){
    $("#activity-form [name=activity_name]").trigger("focus");
});

// Closing while editing drops the edit; an unfinished new activity is kept for next time
$("#activity-modal").on("hidden.bs.modal", function(){
    if($("#activity_id").val() !== ""){
        stop_edit();
    }
});

$("#activity-form").submit(function(e){

    e.preventDefault();

    var editing = $("#activity_id").val() !== "";
    var formData = new FormData(this);

    // The chosen pictures, and saved ones to delete (see PICTURES below)
    $.each(newImages, function(i, img){ formData.append("images[]", img.file); });
    $.each(removedImageIds, function(i, id){ formData.append("remove_images[]", id); });

    $.ajax({

        url: editing ? "ajax.php?action=update_activity" : "ajax.php?action=save_activity",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,

        success: function(resp){

            if($.trim(resp) == "1"){

                Swal.fire({
                    icon: "success",
                    title: "Success!",
                    text: editing ? "Activity updated and sent back for approval." : "Activity submitted successfully.",
                    timer: 1800,
                    showConfirmButton: false
                });

                $("#activity-modal").modal("hide");
                stop_edit();
                myTable.reload();

            }else{

                // Someone may have booked the venue since the live check ran
                if(resp.indexOf("already booked") > -1){
                    show_conflict($.trim(resp));
                }

                Swal.fire({
                    icon: "error",
                    title: "Could not submit",
                    // the server lists one problem per line
                    html: ActivityTable.escape($.trim(resp)).replace(/\n/g, "<br>")
                });

            }

        }

    });

});

});

/* =========================
   CREATE / EDIT FORM
========================= */

// Loads one of your activities into the form so you can change and resubmit it
function start_edit(row){

    set_scope("mine");
    stop_edit();

    $("#activity_id").val(row.id);
    $("#activity-form [name=activity_name]").val(row.title);
    $("#activity-form [name=purpose]").val(row.purpose);
    $("#activity-form [name=description]").val(row.description);
    $("#activity_date").val(row.date);
    $("#venue").val(row.venue);
    $("#activity_project").val(row.project_id ? String(row.project_id) : "");
    $("#activity_start").val(row.start_time || "");
    $("#activity_end").val(row.end_time || "");

    $("#form-title").html('<i class="fa fa-pen mr-2"></i> Edit Activity');
    $("#form-subtitle").text("Saving sends it back to the admin as Pending.");
    $("#image-hint").text("(Up to 10 · × removes a picture when you save)");

    savedImages = (row.images || []).slice();
    render_images();
    $("#submit-text").text("Save Changes");

    if(row.revision_note){
        $("#edit-note").text("Admin note: " + row.revision_note).show();
    }

    check_conflict();

    $("#activity-modal").modal("show");

}

// Back to an empty "Create Activity" form
function stop_edit(){

    $("#activity-form")[0].reset();
    $("#activity_id").val("");

    clear_images();
    show_conflict("");

    $("#edit-note").hide().text("");
    $("#form-title").html('<i class="fa fa-calendar-plus mr-2"></i> Create Activity');
    $("#form-subtitle").text("Submit extension activities for approval.");
    $("#image-hint").text("(Optional · up to 10 · JPG, PNG, GIF or WEBP · 5 MB each)");
    $("#submit-text").text("Submit Activity");

}

/* =========================
   PICTURES (up to 10 per activity)
   The file input can't be edited, so the chosen pictures are kept here and
   added to the form data on submit (images[] and remove_images[]).
========================= */

var MAX_IMAGES = 10;
var MAX_IMAGE_BYTES = 5 * 1024 * 1024;
var MAX_SEND_BYTES = <?php echo $max_send_bytes; ?>;   // PHP's post_max_size, minus room for the form fields
var IMAGE_TYPES = ["image/jpeg", "image/png", "image/gif", "image/webp"];

var savedImages = [];       // pictures already saved for the activity being edited: {id, url}
var removedImageIds = [];   // saved pictures to delete when saving
var newImages = [];         // pictures chosen now: {file, url}

function image_total(){
    return savedImages.length + newImages.length;
}

// Adds chosen/dropped pictures, skipping (and listing) any that break the rules
function add_images(fileList){

    var problems = [];

    $.each(fileList, function(i, file){

        var sending = newImages.reduce(function(sum, img){ return sum + img.file.size; }, 0);

        if(IMAGE_TYPES.indexOf(file.type) === -1){
            problems.push(file.name + " is not a JPG, PNG, GIF or WEBP picture.");
        }else if(file.size > MAX_IMAGE_BYTES){
            problems.push(file.name + " is larger than 5 MB.");
        }else if(image_total() >= MAX_IMAGES){
            problems.push(file.name + " was not added: an activity can have up to " + MAX_IMAGES + " pictures.");
        }else if(sending + file.size > MAX_SEND_BYTES){
            problems.push(file.name + " was not added: one save can send up to " + Math.floor(MAX_SEND_BYTES / 1048576) + " MB of pictures. Add it afterwards with Edit.");
        }else{
            newImages.push({ file: file, url: URL.createObjectURL(file) });
        }

    });

    render_images();

    if(problems.length){
        Swal.fire({
            icon: "warning",
            title: "Some pictures were not added",
            html: problems.map(ActivityTable.escape).join("<br>")
        });
    }

}

function render_images(){

    var $grid = $("#image-grid").empty();

    $.each(savedImages, function(i, img){ $grid.append(image_thumb(img.url, "saved", i)); });
    $.each(newImages, function(i, img){ $grid.append(image_thumb(img.url, "new", i)); });

    $("#image-count").text(image_total() + " / " + MAX_IMAGES + " pictures");

}

function image_thumb(url, kind, index){

    return $('<div class="image-thumb"></div>')
        .append($('<img alt="">').attr("src", url))
        .append(
            $('<button type="button" class="remove-thumb" title="Remove this picture" aria-label="Remove this picture">&times;</button>')
                .attr({ "data-kind": kind, "data-index": index })
        );

}

// × on a thumbnail: new pictures are dropped now, saved ones are deleted when saving
$("#image-grid").on("click", ".remove-thumb", function(){

    var index = Number($(this).attr("data-index"));

    if($(this).attr("data-kind") === "saved"){
        removedImageIds.push(savedImages[index].id);
        savedImages.splice(index, 1);
    }else{
        URL.revokeObjectURL(newImages[index].url);
        newImages.splice(index, 1);
    }

    render_images();

});

function clear_images(){

    $.each(newImages, function(i, img){ URL.revokeObjectURL(img.url); });

    savedImages = [];
    removedImageIds = [];
    newImages = [];

    $("#activity_images").val("");
    render_images();

}

// choose with the file picker (several at once)
$("#activity_images").on("change", function(){
    add_images(this.files);
    this.value = "";   // so the same picture can be picked again after removing it
});

// drag and drop
const dropArea = document.getElementById("drop-area");

["dragenter", "dragover"].forEach(eventName => {
    dropArea.addEventListener(eventName, function(e){
        e.preventDefault();
        dropArea.classList.add("dragging");
    });
});

["dragleave", "drop"].forEach(eventName => {
    dropArea.addEventListener(eventName, function(e){
        e.preventDefault();
        dropArea.classList.remove("dragging");
    });
});

dropArea.addEventListener("drop", function(e){
    add_images(e.dataTransfer.files);
});

render_images();

// Asks the server whether the chosen venue is already booked on that date
function check_conflict(){

    var date = $("#activity_date").val();
    var venue = $.trim($("#venue").val());

    if(!date || !venue){
        show_conflict("");
        return;
    }

    $.ajax({

        url: "ajax.php?action=check_activity_conflict",
        dataType: "json",
        // While editing, the activity's own booking doesn't count as a conflict
        data: { activity_date: date, venue: venue, exclude_id: $("#activity_id").val() },

        success: function(resp){

            // Ignore answers for a date/venue the user has already changed
            if(date != $("#activity_date").val() || venue != $.trim($("#venue").val())) return;

            show_conflict(resp.conflict ? resp.message : "");

        }

    });

}

// Shows the conflict under Venue and blocks Submit until it's resolved
function show_conflict(message){

    $("#venue-conflict").text(message).toggle(message != "");
    $("#venue").toggleClass("is-invalid", message != "");
    $("#activity-modal .btn-save").prop("disabled", message != "");

}

/* =========================
   TABS + STAT CARDS
========================= */

function set_scope(scope){

    currentScope = scope;

    $(".activity-tabs .nav-link").removeClass("active");
    $('.activity-tabs .nav-link[data-scope="' + scope + '"]').addClass("active");

    $("#tab-mine").toggle(scope == "mine");
    $("#tab-all").toggle(scope == "all");

    // Fresh rows each time the tab opens; widths are measured once it's visible
    if(scope == "all" && allTable){
        allTable.reload();
        allTable.dt.columns.adjust();
    }

    render_counts();

}

function load_activity_counts(){

    $.ajax({

        url: "ajax.php?action=activity_counts",
        dataType: "json",

        success: function(resp){
            activityCounts = resp;
            render_counts();
        },

        error: function(xhr){
            console.log(xhr.responseText);
        }

    });

}

// Fill the stat cards with the numbers for the selected tab
function render_counts(){

    if(!activityCounts) return;

    var counts = activityCounts[currentScope];

    $("#total_activity").text(counts.total);

    $("#total_activity_note").text(
        currentScope == "mine" ? "Your submitted activities" : "All coordinators' activities"
    );

    $.each(["approved", "pending", "rejected"], function(i, status){

        var percent = counts.total ? Math.round(counts[status] / counts.total * 1000) / 10 : 0;

        $("#" + status + "_activity").text(counts[status]);
        $("#" + status + "_activity_note").text(percent + "% of total");

    });

}

</script>
