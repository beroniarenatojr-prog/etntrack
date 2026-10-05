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

<link rel="stylesheet" href="assets/css/project.css?v=<?php echo @filemtime("assets/css/project.css") ?: 1; ?>">

<div id="pj-detail" data-project="<?php echo $project_id; ?>" data-admin="<?php echo $is_admin ? 1 : 0; ?>">

    <div class="pj-card" id="pj-loading">
        <div class="text-center text-muted py-4">
            <i class="fas fa-spinner fa-spin fa-2x mb-3 d-block"></i>
            Loading the project...
        </div>
    </div>

    <div id="pj-content" style="display:none">

        <!-- HEADER: which project, whose, and where it stands -->
        <div class="pj-head">
            <div class="pj-head-icon"><i class="fas fa-stream"></i></div>
            <div class="pj-head-text">
                <div class="pj-head-ref" id="pj-ref"></div>
                <h1 id="pj-title">&nbsp;</h1>
                <p id="pj-head-meta"></p>
                <p class="pj-head-coordinator"><i class="fas fa-user-tie"></i> Coordinator: <span id="pj-coordinator"></span></p>
            </div>
            <div class="pj-head-actions">
                <span class="pj-head-stage" id="pj-head-stage"></span>
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
            <!-- Each part of the project at a glance; a card opens its tab -->
            <div class="pj-summary" id="pj-summary"></div>
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
                <div id="pj-stage" class="mt-4"></div>
                <div id="pj-description" class="mt-3"></div>
            </div>

            <div class="pj-panel" data-panel="pre">
                <div id="pj-pre"></div>
            </div>

            <div class="pj-panel" data-panel="conducting">
                <div class="pj-step-head">
                    <h3 class="pj-section-title"><i class="fas fa-calendar-check"></i> Activities, Evaluation and Documentation</h3>
                    <p class="pj-section-sub">The events run under this project. Each approved activity gets a QR code that participants scan to evaluate it, and its photos are its documentation.</p>
                    <div id="pj-activity-actions" class="pj-step-add"></div>
                </div>
                <div id="pj-activities"></div>
            </div>

            <div class="pj-panel" data-panel="post">
                <div id="pj-post"></div>
            </div>

            <div class="pj-panel" data-panel="documents">
                <h3 class="pj-section-title"><i class="fas fa-folder-open"></i> All Files</h3>
                <p class="pj-section-sub">Every file attached to this project, grouped by stage. Each one opens from its own record.</p>
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


<!-- PROGRESS / TERMINAL REPORT FORM (uploaded right here, in the project) -->

<div class="modal fade pj-modal" id="pj-report-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Upload Report</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="pj-report-form" class="pj-form" novalidate>

                <div class="modal-body">

                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="project_id" value="">

                    <div class="row">

                        <div class="col-md-6 form-group">
                            <label for="pjr-type">Report Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="pjr-type" name="report_type">
                                <option value="Progress Report">Progress Report</option>
                                <option value="Terminal Report">Terminal Report</option>
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="pjr-category">Category</label>
                            <select class="form-control" id="pjr-category" name="category">
                                <option value="">Not set</option>
                                <option>Research</option>
                                <option>Extension</option>
                                <option>Training</option>
                            </select>
                        </div>

                        <div class="col-12 form-group">
                            <label for="pjr-title">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pjr-title" name="title" maxlength="255"
                                   placeholder="e.g. First Quarter Progress Report">
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="pjr-from">Reporting Period: From</label>
                            <input type="date" class="form-control" id="pjr-from" name="period_start">
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="pjr-to">To</label>
                            <input type="date" class="form-control" id="pjr-to" name="period_end">
                        </div>

                        <div class="col-12 form-group">
                            <label for="pjr-description">Description</label>
                            <textarea class="form-control" id="pjr-description" name="description"
                                      placeholder="What this report covers."></textarea>
                        </div>

                        <div class="col-12 form-group mb-0">
                            <label for="pjr-file">Report File <span class="text-danger" id="pjr-file-required">*</span></label>
                            <input type="file" class="form-control-file" id="pjr-file" name="report" accept=".pdf,.doc,.docx">
                            <small class="pj-muted" id="pjr-file-hint">PDF or Word (.doc, .docx), up to 20 MB.</small>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="pj-btn pj-btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="pj-btn pj-btn-green" id="pjr-submit">
                        <i class="fas fa-cloud-upload-alt"></i> <span>Submit Report</span>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="assets/js/project-detail.js?v=<?php echo @filemtime("assets/js/project-detail.js") ?: 1; ?>"></script>
