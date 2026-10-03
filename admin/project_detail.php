<?php
/*
| PROJECT DETAIL
| One project's whole lifecycle: where it stands, and every document that
| belongs to it. Shared by both roles (faculty/project_detail.php includes it).
|
| Everything on this page comes from the database through
| ajax.php?action=project_detail. No stage is ever shown as done unless the
| record behind it says so.
*/

$project_id = (int)($_GET['id'] ?? 0);
$is_admin = ($_SESSION['login_type'] ?? 0) == 1;

if($project_id <= 0){
    echo '<div class="alert alert-warning m-4">No project was chosen. '
       . '<a href="index.php?page=projects">Back to projects</a>.</div>';
    return;
}
?>

<link rel="stylesheet" href="assets/css/project.css">

<div id="pj-detail" data-project="<?php echo $project_id; ?>" data-admin="<?php echo $is_admin ? 1 : 0; ?>">

    <div class="pj-card" id="pj-loading">
        <div class="text-center text-muted py-4">
            <i class="fas fa-spinner fa-spin fa-2x mb-3 d-block"></i>
            Loading the project...
        </div>
    </div>

    <div id="pj-content" style="display:none">

        <!-- HEADER -->
        <div class="pj-head">
            <div class="pj-head-icon"><i class="fas fa-stream"></i></div>
            <div>
                <h1 id="pj-title">&nbsp;</h1>
                <p><span id="pj-ref"></span> &middot; <span id="pj-coordinator"></span></p>
            </div>
            <div class="pj-head-actions">
                <a class="pj-btn" href="index.php?page=projects">
                    <i class="fas fa-arrow-left"></i> All Projects
                </a>
            </div>
        </div>


        <!-- LIFECYCLE -->
        <div class="pj-card">
            <h2 class="pj-section-title"><i class="fas fa-route"></i> Project Lifecycle</h2>
            <p class="pj-section-sub" id="pj-progress-text"></p>
            <div class="pj-timeline" id="pj-timeline"></div>
        </div>


        <!-- TABS -->
        <div class="pj-card">

            <div class="pj-tabs" role="tablist">
                <button type="button" class="pj-tab active" data-panel="overview">Overview</button>
                <button type="button" class="pj-tab" data-panel="pre">Pre-Activity</button>
                <button type="button" class="pj-tab" data-panel="conducting">Conducting</button>
                <button type="button" class="pj-tab" data-panel="post">Post-Activity</button>
                <button type="button" class="pj-tab" data-panel="documents">Documents</button>
            </div>

            <div class="pj-panel active" data-panel="overview">
                <dl class="pj-overview" id="pj-facts"></dl>
                <div id="pj-description" class="mt-3"></div>
            </div>

            <div class="pj-panel" data-panel="pre">
                <div id="pj-pre"></div>
            </div>

            <div class="pj-panel" data-panel="conducting">
                <h3 class="pj-section-title"><i class="fas fa-calendar-check"></i> Activities</h3>
                <p class="pj-section-sub">The events run under this project. Each approved activity gets a QR code that participants scan to evaluate it.</p>
                <div id="pj-activities"></div>
            </div>

            <div class="pj-panel" data-panel="post">
                <h3 class="pj-section-title"><i class="fas fa-flag-checkered"></i> Post-Activity</h3>
                <p class="pj-section-sub">Terminal Report, Progress Reports and the 3-year Impact Assessment.</p>
                <div class="pj-empty">
                    <i class="fas fa-hourglass-half"></i>
                    Terminal and Progress Reports are not linked to projects yet, and Impact Assessment is not built yet.
                    Both arrive in the next update.
                    <div class="mt-2"><a href="index.php?page=report">Open the Reports page</a></div>
                </div>
            </div>

            <div class="pj-panel" data-panel="documents">
                <h3 class="pj-section-title"><i class="fas fa-folder-open"></i> All Files</h3>
                <p class="pj-section-sub">Every file attached to this project.</p>
                <div id="pj-files"></div>
            </div>

        </div>

    </div>

    <div id="pj-error" style="display:none"></div>

</div>


<!-- READING A DOCUMENT -->

<div class="modal fade pj-modal" id="pj-view-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Document</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" id="pj-view-body"></div>

            <div class="modal-footer">
                <button type="button" class="pj-btn pj-btn-light" data-dismiss="modal">Close</button>
                <button type="button" class="pj-btn pj-btn-green" id="pj-view-edit">
                    <i class="fas fa-pen"></i> Edit
                </button>
            </div>

        </div>
    </div>
</div>


<!-- DOCUMENT FORM -->

<div class="modal fade pj-modal" id="pj-doc-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Document</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="pj-doc-form" class="pj-form">

                <div class="modal-body">
                    <input type="hidden" name="doc_type" value="">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="project_id" value="">
                    <input type="hidden" name="submit_for_review" value="0">
                    <div id="pj-doc-fields"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="pj-btn pj-btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="pj-btn pj-btn-light" data-review="0">
                        <i class="fas fa-save"></i> Save Draft
                    </button>
                    <button type="submit" class="pj-btn pj-btn-green" data-review="1">
                        <i class="fas fa-paper-plane"></i> Submit for Review
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="assets/js/project-detail.js"></script>
