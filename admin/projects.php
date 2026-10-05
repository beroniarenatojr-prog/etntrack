<?php
/*
| EXTENSION PROJECTS
| The list of projects. The admin sees every project; a coordinator sees only
| their own. Shared by both roles (faculty/projects.php includes this file).
*/

include 'db_connect.php';

$is_admin = ($_SESSION['login_type'] ?? 0) == 1;

// The impact assessment period can be set once the database update that adds it has run
$impact_ready = $conn->query("
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'impact_years'
")->num_rows > 0;

// Values for the filter dropdowns, taken from the projects that exist
$years = array();
$qry = $conn->query("SELECT DISTINCT academic_year FROM projects WHERE academic_year <> '' ORDER BY academic_year DESC");
while($row = $qry->fetch_assoc()){
    $years[] = $row['academic_year'];
}

$coordinators = array();
if($is_admin){
    $qry = $conn->query("SELECT id, CONCAT(firstname,' ',lastname) AS name FROM faculty_list ORDER BY firstname");
    while($row = $qry->fetch_assoc()){
        $coordinators[] = $row;
    }
}

$stages = array(
    'draft' => 'Draft',
    'pre_activity' => 'Pre-Activity',
    'for_approval' => 'For Approval',
    'ready_for_conduct' => 'Ready for Conduct',
    'ongoing' => 'Ongoing',
    'conducted' => 'Conducted',
    'post_activity' => 'Post-Activity',
    'completed' => 'Completed',
    'impact_monitoring' => 'Impact Monitoring',
    'closed' => 'Closed'
);
?>

<link rel="stylesheet" href="assets/css/project.css">

<div class="pj-head">

    <div class="pj-head-icon"><i class="fas fa-project-diagram"></i></div>

    <div>
        <h1>Extension Projects</h1>
        <p>Manage complete extension projects from planning to impact assessment.</p>
    </div>

    <div class="pj-head-actions">
        <button type="button" class="pj-btn" id="pj-new">
            <i class="fas fa-plus"></i> New Project
        </button>
    </div>

</div>


<div class="pj-card">

    <div class="pj-filters">

        <div>
            <label for="pj-search">Search</label>
            <input type="text" id="pj-search" class="form-control" placeholder="Project, venue<?php echo $is_admin ? ' or coordinator' : ''; ?>...">
        </div>

        <div>
            <label for="pj-year">Academic Year</label>
            <select id="pj-year" class="form-control">
                <option value="">All years</option>
                <?php foreach($years as $year): ?>
                    <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="pj-semester">Semester</label>
            <select id="pj-semester" class="form-control">
                <option value="">All semesters</option>
                <option value="1st Semester">1st Semester</option>
                <option value="2nd Semester">2nd Semester</option>
                <option value="Midyear">Midyear</option>
            </select>
        </div>

        <div>
            <label for="pj-category">Category</label>
            <select id="pj-category" class="form-control">
                <option value="">All categories</option>
                <option value="Extension">Extension</option>
                <option value="Research">Research</option>
                <option value="Training">Training</option>
            </select>
        </div>

        <div>
            <label for="pj-stage">Stage</label>
            <select id="pj-stage" class="form-control">
                <option value="">All stages</option>
                <?php foreach($stages as $key => $label): ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if($is_admin): ?>
        <div>
            <label for="pj-coordinator">Coordinator</label>
            <select id="pj-coordinator" class="form-control">
                <option value="">All coordinators</option>
                <?php foreach($coordinators as $coordinator): ?>
                    <option value="<?php echo (int)$coordinator['id']; ?>"><?php echo htmlspecialchars($coordinator['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

    </div>

</div>


<div class="pj-card">
    <table id="pj-table" class="table pj-table" style="width:100%"></table>
</div>


<!-- NEW / EDIT PROJECT -->

<div class="modal fade pj-modal" id="pj-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">New Project</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="pj-form" class="pj-form">

                <div class="modal-body">

                    <input type="hidden" name="id" value="">

                    <div class="form-group">
                        <label for="pj-f-title">Project Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="pj-f-title" class="form-control" required
                               placeholder="e.g. Community Digital Literacy Training">
                    </div>

                    <div class="form-group">
                        <label for="pj-f-description">Description</label>
                        <textarea name="description" id="pj-f-description" class="form-control"
                                  placeholder="What this project is for, in a few sentences."></textarea>
                    </div>

                    <div class="row">

                        <div class="col-md-6 form-group">
                            <label for="pj-f-category">Category</label>
                            <select name="category" id="pj-f-category" class="form-control">
                                <option value="Extension">Extension</option>
                                <option value="Research">Research</option>
                                <option value="Training">Training</option>
                            </select>
                        </div>

                        <?php if($is_admin): ?>
                        <div class="col-md-6 form-group">
                            <label for="pj-f-faculty">Lead Coordinator</label>
                            <select name="faculty_id" id="pj-f-faculty" class="form-control">
                                <option value="">Not assigned</option>
                                <?php foreach($coordinators as $coordinator): ?>
                                    <option value="<?php echo (int)$coordinator['id']; ?>"><?php echo htmlspecialchars($coordinator['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-6 form-group">
                            <label for="pj-f-location">Location</label>
                            <input type="text" name="location" id="pj-f-location" class="form-control"
                                   placeholder="e.g. Barangay Calamagui, Ilagan">
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="pj-f-year">Academic Year</label>
                            <input type="text" name="academic_year" id="pj-f-year" class="form-control"
                                   placeholder="e.g. 2026-2027">
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="pj-f-semester">Semester</label>
                            <select name="semester" id="pj-f-semester" class="form-control">
                                <option value="">Not set</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Midyear">Midyear</option>
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="pj-f-start">Start Date</label>
                            <input type="date" name="start_date" id="pj-f-start" class="form-control">
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="pj-f-end">End Date</label>
                            <input type="date" name="end_date" id="pj-f-end" class="form-control">
                        </div>

                        <?php if($impact_ready): ?>
                        <div class="col-md-6 form-group">
                            <label for="pj-f-impact">Impact Assessment Due</label>
                            <select name="impact_years" id="pj-f-impact" class="form-control">
                                <?php for($y = 1; $y <= 10; $y++): ?>
                                <option value="<?php echo $y; ?>"<?php echo $y === 3 ? ' selected' : ''; ?>>
                                    <?php echo $y.' year'.($y > 1 ? 's' : ''); ?> after the project is completed
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                    </div>

                    <div class="form-group">
                        <label for="pj-f-beneficiaries">Target Beneficiaries</label>
                        <textarea name="target_beneficiaries" id="pj-f-beneficiaries" class="form-control"
                                  placeholder="Who the project is for, and roughly how many."></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="pj-btn pj-btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="pj-btn pj-btn-green">
                        <i class="fas fa-save"></i> Save Project
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<script>
$(function(){

    var isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;

    function esc(text){
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    /* ---------------- the table ---------------- */

    var columns = [
        { data: 'ref', title: 'ID', className: 'pj-ref' },
        {
            data: 'title',
            title: 'Project',
            render: function(value, type, row){
                if(type !== 'display') return value;
                return '<div class="pj-title">' + esc(value) + '</div>' +
                       '<div class="pj-muted">' + esc(row.category) +
                       (row.location ? ' &middot; ' + esc(row.location) : '') + '</div>';
            }
        }
    ];

    if(isAdmin){
        columns.push({
            data: 'coordinator',
            title: 'Coordinator',
            render: function(value){ return esc(value); }
        });
    }

    columns.push(
        {
            data: 'academic_year',
            title: 'Year',
            render: function(value, type, row){
                if(type !== 'display') return value;
                if(!value) return '<span class="pj-muted">Not set</span>';
                return esc(value) + '<div class="pj-muted">' + esc(row.semester) + '</div>';
            }
        },
        {
            data: 'progress',
            title: 'Pre-Activity',
            render: function(value, type, row){
                if(type !== 'display') return value;
                return '<div class="pj-progress">' +
                       '<div class="pj-bar"><span style="width:' + value + '%"></span></div>' +
                       '<div class="pj-muted" style="margin-top:5px">' + esc(row.progress_text) + '</div>' +
                       '</div>';
            }
        },
        {
            data: 'status_label',
            title: 'Stage',
            render: function(value, type, row){
                if(type !== 'display') return value;
                return '<span class="pj-status ' + esc(row.status) + '">' + esc(value) + '</span>';
            }
        },
        {
            data: null,
            title: '',
            orderable: false,
            searchable: false,
            className: 'text-right',
            render: function(data, type, row){
                return '<div class="pj-doc-actions justify-content-end">' +
                       '<a class="pj-mini go" href="index.php?page=project_detail&id=' + row.id + '">' +
                       '<i class="fas fa-stream"></i> Open</a>' +
                       '<button type="button" class="pj-mini" data-edit="' + row.id + '"><i class="fas fa-pen"></i></button>' +
                       '<button type="button" class="pj-mini danger" data-remove="' + row.id + '"><i class="fas fa-trash-alt"></i></button>' +
                       '</div>';
            }
        }
    );

    var table = $('#pj-table').DataTable({
        ajax: {
            url: 'ajax.php?action=project_list',
            data: function(){
                return {
                    search: $('#pj-search').val(),
                    academic_year: $('#pj-year').val(),
                    semester: $('#pj-semester').val(),
                    category: $('#pj-category').val(),
                    lifecycle_status: $('#pj-stage').val(),
                    coordinator: isAdmin ? $('#pj-coordinator').val() : ''
                };
            },
            dataSrc: 'data'
        },
        columns: columns,
        order: [],
        pageLength: 10,
        lengthMenu: [10, 25, 50],
        autoWidth: false,
        dom: "rt<'at-footer'lip>",
        language: {
            emptyTable: 'No projects yet. Click "New Project" to add the first one.',
            zeroRecords: 'No project matches these filters.',
            info: 'Showing _START_ to _END_ of _TOTAL_ projects',
            infoEmpty: 'No projects',
            lengthMenu: 'Show _MENU_',
            loadingRecords: 'Loading projects...',
            paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
        }
    });

    var searchTimer = null;

    $('#pj-search').on('input', function(){
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function(){ table.ajax.reload(); }, 300);
    });

    $('#pj-year, #pj-semester, #pj-category, #pj-stage, #pj-coordinator').on('change', function(){
        table.ajax.reload();
    });


    /* ---------------- new / edit ---------------- */

    function open_form(row){

        var form = $('#pj-form')[0];
        form.reset();

        $('#pj-modal .modal-title').text(row ? 'Edit Project' : 'New Project');
        $('#pj-form [name="id"]').val(row ? row.id : '');

        if(row){
            // The list has only part of the project, so the rest is fetched
            $.getJSON('ajax.php?action=project_detail&id=' + row.id, function(data){
                if(data.error){
                    Swal.fire({ icon: 'error', title: 'Not available', text: data.error });
                    return;
                }
                var p = data.project;
                $('#pj-f-title').val(p.title);
                $('#pj-f-description').val(p.description);
                $('#pj-f-category').val(p.category);
                $('#pj-f-location').val(p.location);
                $('#pj-f-year').val(p.academic_year);
                $('#pj-f-semester').val(p.semester);
                $('#pj-f-start').val(p.start_date);
                $('#pj-f-end').val(p.end_date);
                $('#pj-f-impact').val(String(p.impact_years || 3));
                $('#pj-f-beneficiaries').val(p.target_beneficiaries);
                if(isAdmin){ $('#pj-f-faculty').val(p.faculty_id || ''); }
                $('#pj-modal').modal('show');
            });
            return;
        }

        $('#pj-modal').modal('show');
    }

    $('#pj-new').on('click', function(){ open_form(null); });

    $('#pj-table').on('click', '[data-edit]', function(){
        open_form(table.row($(this).closest('tr')).data());
    });

    $('#pj-form').on('submit', function(e){

        e.preventDefault();

        var button = $(this).find('button[type="submit"]');
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: 'ajax.php?action=project_save',
            method: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false
        }).done(function(resp){

            resp = $.trim(resp);
            button.prop('disabled', false).html('<i class="fas fa-save"></i> Save Project');

            // A new project answers with its id, an edit answers with 1
            if(/^\d+$/.test(resp)){
                $('#pj-modal').modal('hide');
                table.ajax.reload();
                alert_toast('Project saved.', 'success');
                if(resp !== '1'){
                    setTimeout(function(){
                        location.href = 'index.php?page=project_detail&id=' + resp;
                    }, 700);
                }
                return;
            }

            Swal.fire({ icon: 'warning', title: 'Not saved', text: resp });

        }).fail(function(){
            button.prop('disabled', false).html('<i class="fas fa-save"></i> Save Project');
            Swal.fire({ icon: 'error', title: 'Server error', text: 'Please try again.' });
        });

    });


    /* ---------------- delete ---------------- */

    $('#pj-table').on('click', '[data-remove]', function(){

        var row = table.row($(this).closest('tr')).data();

        Swal.fire({
            titleText: 'Delete "' + row.title + '"?',
            text: 'Its documents are deleted too. Activities are kept, but they stop belonging to a project. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then(function(result){

            if(!result.isConfirmed) return;

            $.post('ajax.php?action=project_delete', { id: row.id }).done(function(resp){
                if($.trim(resp) === '1'){
                    table.ajax.reload();
                    alert_toast('Project deleted.', 'success');
                }else{
                    Swal.fire({ icon: 'warning', title: 'Not deleted', text: $.trim(resp) });
                }
            });

        });

    });

});
</script>
