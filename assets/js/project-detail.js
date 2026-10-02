/*
 * Project detail page
 * -------------------
 * Draws one project's lifecycle and its pre-activity documents from
 * ajax.php?action=project_detail, and handles adding, editing, reviewing
 * and removing those documents.
 *
 * Every status shown here comes from the server. Nothing is assumed.
 */
(function($){

    var root = $('#pj-detail');
    if(!root.length) return;

    var projectId = root.data('project');
    var isAdmin = root.data('admin') == 1;
    var data = null;

    function esc(text){
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function nl2br(text){
        return esc(text).replace(/\n/g, '<br>');
    }

    function date_display(value){
        if(!value || value === '0000-00-00') return '';
        var d = new Date(value + 'T00:00:00');
        if(isNaN(d)) return esc(value);
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
    }

    /* =================================================================
       THE DOCUMENT TYPES
       Field lists mirror the ones the server accepts (project_actions.php).
    ================================================================= */
    var DOCS = {
        assessment: {
            label: 'Assessment Report',
            icon: 'fa-clipboard-list',
            blurb: 'What the community needs, established before anything is proposed.',
            fields: [
                { name: 'title', label: 'Assessment Title', type: 'text', required: true },
                { name: 'community', label: 'Community / Beneficiary', type: 'text' },
                { name: 'location', label: 'Location', type: 'text' },
                { name: 'assessment_date', label: 'Date of Assessment', type: 'date' },
                { name: 'assessors', label: 'Assessors', type: 'textarea', hint: 'Who carried out the assessment.' },
                { name: 'identified_needs', label: 'Identified Needs / Problems', type: 'textarea' },
                { name: 'findings', label: 'Findings', type: 'textarea' },
                { name: 'recommendations', label: 'Recommendations', type: 'textarea' },
                { name: 'target_beneficiaries', label: 'Target Beneficiaries', type: 'textarea' }
            ]
        },
        moa: {
            label: 'Memorandum of Agreement',
            icon: 'fa-handshake',
            blurb: 'The agreement between the university and the partner community or organisation.',
            fields: [
                { name: 'title', label: 'MOA Title', type: 'text', required: true },
                { name: 'partner', label: 'Partner Organisation / Community', type: 'text' },
                { name: 'description', label: 'Agreement Description', type: 'textarea' },
                { name: 'moa_date', label: 'MOA Date', type: 'date' },
                { name: 'start_date', label: 'Start Date', type: 'date', half: true },
                { name: 'end_date', label: 'End Date', type: 'date', half: true },
                { name: 'school_responsibilities', label: 'Responsibilities of the University', type: 'textarea' },
                { name: 'partner_responsibilities', label: 'Responsibilities of the Partner', type: 'textarea' },
                { name: 'signatories', label: 'Signatories', type: 'textarea' }
            ]
        },
        capsule: {
            label: 'Capsule',
            icon: 'fa-file-alt',
            blurb: 'A short summary of the proposed project.',
            fields: [
                { name: 'title', label: 'Project Title', type: 'text', required: true },
                { name: 'rationale', label: 'Rationale', type: 'textarea' },
                { name: 'objectives', label: 'Objectives', type: 'textarea' },
                { name: 'target_beneficiaries', label: 'Target Beneficiaries', type: 'textarea' },
                { name: 'location', label: 'Location', type: 'text', half: true },
                { name: 'duration', label: 'Project Duration', type: 'text', half: true, hint: 'e.g. 6 months' },
                { name: 'major_activities', label: 'Major Activities', type: 'textarea' },
                { name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea' },
                { name: 'expected_outcomes', label: 'Expected Outcomes', type: 'textarea' },
                { name: 'prepared_by', label: 'Prepared By', type: 'text', half: true },
                { name: 'date_prepared', label: 'Date Prepared', type: 'date', half: true }
            ]
        },
        proposal: {
            label: 'Proposal',
            icon: 'fa-file-signature',
            blurb: 'The full proposal the Extension Office approves before the project runs.',
            fields: [
                { name: 'title', label: 'Proposal Title', type: 'text', required: true },
                { name: 'background', label: 'Background / Rationale', type: 'textarea' },
                { name: 'problem_statement', label: 'Problem Statement', type: 'textarea' },
                { name: 'objectives', label: 'Objectives', type: 'textarea' },
                { name: 'target_beneficiaries', label: 'Target Beneficiaries', type: 'textarea' },
                { name: 'location', label: 'Location', type: 'text' },
                { name: 'methodology', label: 'Methodology', type: 'textarea' },
                { name: 'activities', label: 'Activities', type: 'textarea' },
                { name: 'timeline', label: 'Timeline', type: 'textarea' },
                { name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea' },
                { name: 'expected_outcomes', label: 'Expected Outcomes', type: 'textarea' },
                { name: 'monitoring_plan', label: 'Monitoring and Evaluation Plan', type: 'textarea' },
                { name: 'budget', label: 'Proposed Budget (PHP)', type: 'number', half: true },
                { name: 'funding_source', label: 'Funding Source', type: 'text', half: true },
                { name: 'sustainability_plan', label: 'Sustainability Plan', type: 'textarea' },
                { name: 'prepared_by', label: 'Prepared By', type: 'text' }
            ]
        },
        preparation: {
            label: 'Conduct Preparation',
            icon: 'fa-tasks',
            blurb: 'The final arrangements before the activity is run.',
            fields: [
                { name: 'final_date', label: 'Final Activity Date', type: 'date', half: true },
                { name: 'venue', label: 'Venue', type: 'text', half: true },
                { name: 'start_time', label: 'Start Time', type: 'time', half: true },
                { name: 'end_time', label: 'End Time', type: 'time', half: true },
                { name: 'expected_participants', label: 'Expected Participants', type: 'number', half: true },
                { name: 'target_beneficiaries', label: 'Target Beneficiaries', type: 'text', half: true },
                { name: 'materials', label: 'Required Materials', type: 'textarea' },
                { name: 'equipment', label: 'Equipment', type: 'textarea' },
                { name: 'assigned_personnel', label: 'Assigned Personnel', type: 'textarea' },
                { name: 'logistics', label: 'Logistics', type: 'textarea' },
                { name: 'notes', label: 'Notes', type: 'textarea' },
                { name: 'venue_confirmed', label: 'Venue confirmed', type: 'check' },
                { name: 'personnel_assigned', label: 'Personnel assigned', type: 'check' },
                { name: 'materials_prepared', label: 'Materials prepared', type: 'check' }
            ]
        }
    };

    // Designation is a list of people, so it has its own small form
    var DESIGNATION_FIELDS = [
        { name: 'personnel_name', label: 'Name', type: 'text', required: true },
        { name: 'role', label: 'Role / Designation', type: 'text', required: true, hint: 'e.g. Project Leader, Trainer, Documentation Officer' },
        { name: 'responsibility', label: 'Responsibility', type: 'textarea' },
        { name: 'start_date', label: 'Start Date', type: 'date', half: true },
        { name: 'end_date', label: 'End Date', type: 'date', half: true }
    ];

    /* =================================================================
       LOADING
    ================================================================= */

    function load(){
        $.getJSON('ajax.php?action=project_detail&id=' + projectId)
            .done(function(response){
                if(response.error){
                    $('#pj-loading').hide();
                    $('#pj-error').show().html(
                        '<div class="alert alert-warning">' + esc(response.error) +
                        ' <a href="index.php?page=projects">Back to projects</a>.</div>'
                    );
                    return;
                }
                data = response;
                render();
                $('#pj-loading').hide();
                $('#pj-content').show();
            })
            .fail(function(){
                $('#pj-loading').hide();
                $('#pj-error').show().html(
                    '<div class="alert alert-danger">The project could not be loaded. Please reload the page.</div>'
                );
            });
    }

    /* =================================================================
       DRAWING
    ================================================================= */

    function render(){
        var p = data.project;

        $('#pj-title').text(p.title);
        $('#pj-ref').text(p.ref);
        $('#pj-coordinator').text(p.coordinator);

        render_timeline();
        render_overview();
        render_pre();
        render_activities();
        render_files();
    }

    function render_timeline(){

        var icons = {
            assessment: 'fa-clipboard-list', moa: 'fa-handshake', capsule: 'fa-file-alt',
            proposal: 'fa-file-signature', designation: 'fa-user-tag', preparation: 'fa-tasks',
            conducting: 'fa-calendar-check', evaluation: 'fa-star', documentation: 'fa-images',
            reports: 'fa-file-contract', impact: 'fa-seedling'
        };

        var html = '';

        $.each(data.lifecycle, function(i, stage){
            var mark = stage.state === 'done' ? '<i class="fas fa-check"></i>'
                     : stage.state === 'attention' ? '<i class="fas fa-exclamation"></i>'
                     : '<i class="fas ' + (icons[stage.key] || 'fa-circle') + '"></i>';

            html += '<div class="pj-step ' + stage.state + '">' +
                        '<div class="pj-dot">' + mark + '</div>' +
                        '<div class="pj-step-label">' + esc(stage.label) +
                        '<span class="pj-step-state">' + esc(stage.status_label) + '</span>' +
                        '</div>' +
                    '</div>';
        });

        $('#pj-timeline').html(html);
        $('#pj-progress-text').text(
            data.progress.done + ' of ' + data.progress.total +
            ' pre-activity steps approved (' + data.progress.percent + '%)'
        );
    }

    function render_overview(){

        var p = data.project;

        var facts = [
            ['Stage', '<span class="pj-status ' + esc(p.status) + '">' + esc(p.status_label) + '</span>'],
            ['Category', esc(p.category)],
            ['Lead Coordinator', esc(p.coordinator)],
            ['Location', esc(p.location) || '<span class="pj-muted">Not set</span>'],
            ['Academic Year', esc(p.academic_year) || '<span class="pj-muted">Not set</span>'],
            ['Semester', esc(p.semester) || '<span class="pj-muted">Not set</span>'],
            ['Start Date', date_display(p.start_date) || '<span class="pj-muted">Not set</span>'],
            ['End Date', date_display(p.end_date) || '<span class="pj-muted">Not set</span>'],
            ['Activities', data.activities.length],
            ['Pre-Activity Progress', data.progress.percent + '%']
        ];

        var html = '';

        $.each(facts, function(i, fact){
            html += '<div class="pj-fact"><dt>' + fact[0] + '</dt><dd>' + fact[1] + '</dd></div>';
        });

        $('#pj-facts').html(html);

        var extra = '';

        if(p.description){
            extra += '<h3 class="pj-section-title mt-4"><i class="fas fa-align-left"></i> Description</h3>' +
                     '<p style="color:#344a6e">' + nl2br(p.description) + '</p>';
        }

        if(p.target_beneficiaries){
            extra += '<h3 class="pj-section-title mt-4"><i class="fas fa-users"></i> Target Beneficiaries</h3>' +
                     '<p style="color:#344a6e">' + nl2br(p.target_beneficiaries) + '</p>';
        }

        $('#pj-description').html(extra);
    }

    function doc_row(type, doc){

        var file = doc.file_name
            ? '<a class="pj-mini" target="_blank" href="project_file.php?type=' + type + '&id=' + doc.id + '">' +
              '<i class="fas fa-paperclip"></i> File</a>'
            : '';

        var review = '';

        if(isAdmin && doc.status !== 'approved'){
            review = '<button type="button" class="pj-mini go" data-review="' + type + '" data-id="' + doc.id + '">' +
                     '<i class="fas fa-gavel"></i> Review</button>';
        }

        var remarks = doc.admin_remarks
            ? '<div class="pj-note"><b>Extension Office:</b> ' + nl2br(doc.admin_remarks) + '</div>'
            : '';

        return '<div class="pj-doc">' +
                   '<div class="pj-doc-main">' +
                       '<div class="pj-doc-title">' + esc(doc.title || DOCS[type].label) + '</div>' +
                       '<div class="pj-doc-meta">Updated ' + esc((doc.updated_at || '').substring(0, 10)) + '</div>' +
                       remarks +
                   '</div>' +
                   '<span class="pj-status ' + esc(doc.status) + '">' + esc(doc.status_label) + '</span>' +
                   '<div class="pj-doc-actions">' +
                       file + review +
                       '<button type="button" class="pj-mini" data-edit="' + type + '" data-id="' + doc.id + '"><i class="fas fa-pen"></i></button>' +
                       '<button type="button" class="pj-mini danger" data-remove="' + type + '" data-id="' + doc.id + '"><i class="fas fa-trash-alt"></i></button>' +
                   '</div>' +
               '</div>';
    }

    function render_pre(){

        var html = '';

        $.each(DOCS, function(type, config){

            var list = data.documents[type] || [];

            html += '<div class="mb-4">' +
                        '<h3 class="pj-section-title"><i class="fas ' + config.icon + '"></i> ' + config.label + '</h3>' +
                        '<p class="pj-section-sub">' + config.blurb + '</p>';

            if(list.length){
                $.each(list, function(i, doc){ html += doc_row(type, doc); });
            }else{
                html += '<div class="pj-empty"><i class="fas ' + config.icon + '"></i>' +
                        'No ' + config.label.toLowerCase() + ' yet.</div>';
            }

            html += '<button type="button" class="pj-mini mt-2" data-add="' + type + '">' +
                    '<i class="fas fa-plus"></i> Add ' + config.label + '</button>' +
                    '</div>';

            // Designation sits between Proposal and Conduct Preparation
            if(type === 'proposal'){
                html += render_designations();
            }
        });

        $('#pj-pre').html(html);
    }

    function render_designations(){

        var html = '<div class="mb-4">' +
                   '<h3 class="pj-section-title"><i class="fas fa-user-tag"></i> Designation</h3>' +
                   '<p class="pj-section-sub">Who is assigned to this project, and as what.</p>';

        if(data.designations.length){

            $.each(data.designations, function(i, person){

                var file = person.file_name
                    ? '<a class="pj-mini" target="_blank" href="project_file.php?type=designation&id=' + person.id + '">' +
                      '<i class="fas fa-paperclip"></i> File</a>'
                    : '';

                html += '<div class="pj-doc">' +
                            '<div class="pj-doc-main">' +
                                '<div class="pj-doc-title">' + esc(person.personnel_name) + '</div>' +
                                '<div class="pj-doc-meta">' + esc(person.role) +
                                (person.responsibility ? ' &middot; ' + esc(person.responsibility) : '') + '</div>' +
                            '</div>' +
                            '<span class="pj-status ' + esc(person.status) + '">' +
                            esc(person.status === 'ended' ? 'Ended' : 'Active') + '</span>' +
                            '<div class="pj-doc-actions">' + file +
                                '<button type="button" class="pj-mini" data-edit-person="' + person.id + '"><i class="fas fa-pen"></i></button>' +
                                '<button type="button" class="pj-mini danger" data-remove-person="' + person.id + '"><i class="fas fa-trash-alt"></i></button>' +
                            '</div>' +
                        '</div>';
            });

        }else{
            html += '<div class="pj-empty"><i class="fas fa-user-tag"></i>Nobody is assigned to this project yet.</div>';
        }

        return html + '<button type="button" class="pj-mini mt-2" data-add-person="1">' +
               '<i class="fas fa-plus"></i> Assign Someone</button></div>';
    }

    function render_activities(){

        if(!data.activities.length){
            $('#pj-activities').html(
                '<div class="pj-empty"><i class="fas fa-calendar-plus"></i>' +
                'No activity belongs to this project yet.' +
                '<div class="mt-2"><a href="index.php?page=activities">Open the Activities page</a></div></div>'
            );
            return;
        }

        var html = '';

        $.each(data.activities, function(i, activity){
            html += '<div class="pj-doc">' +
                        '<div class="pj-doc-main">' +
                            '<div class="pj-doc-title">' + esc(activity.activity_name) + '</div>' +
                            '<div class="pj-doc-meta">' + esc(activity.date_display) +
                            (activity.venue ? ' &middot; ' + esc(activity.venue) : '') + '</div>' +
                        '</div>' +
                        '<span class="pj-status ' + esc(activity.status) + '">' +
                        esc(activity.status.charAt(0).toUpperCase() + activity.status.slice(1)) + '</span>' +
                    '</div>';
        });

        $('#pj-activities').html(html);
    }

    function render_files(){

        var html = '';

        $.each(DOCS, function(type, config){
            $.each(data.documents[type] || [], function(i, doc){
                if(!doc.file_name) return;
                html += '<div class="pj-doc">' +
                            '<div class="pj-doc-main">' +
                                '<div class="pj-doc-title">' + esc(doc.title || config.label) + '</div>' +
                                '<div class="pj-doc-meta">' + config.label + '</div>' +
                            '</div>' +
                            '<div class="pj-doc-actions">' +
                                '<a class="pj-mini" target="_blank" href="project_file.php?type=' + type + '&id=' + doc.id + '"><i class="fas fa-eye"></i> View</a>' +
                                '<a class="pj-mini" href="project_file.php?type=' + type + '&id=' + doc.id + '&download=1"><i class="fas fa-download"></i></a>' +
                            '</div>' +
                        '</div>';
            });
        });

        $.each(data.designations, function(i, person){
            if(!person.file_name) return;
            html += '<div class="pj-doc">' +
                        '<div class="pj-doc-main">' +
                            '<div class="pj-doc-title">' + esc(person.personnel_name) + '</div>' +
                            '<div class="pj-doc-meta">Designation</div>' +
                        '</div>' +
                        '<div class="pj-doc-actions">' +
                            '<a class="pj-mini" target="_blank" href="project_file.php?type=designation&id=' + person.id + '"><i class="fas fa-eye"></i> View</a>' +
                            '<a class="pj-mini" href="project_file.php?type=designation&id=' + person.id + '&download=1"><i class="fas fa-download"></i></a>' +
                        '</div>' +
                    '</div>';
        });

        $('#pj-files').html(html || '<div class="pj-empty"><i class="fas fa-folder-open"></i>No file has been attached to this project yet.</div>');
    }

    /* =================================================================
       THE DOCUMENT FORM
    ================================================================= */

    function build_fields(fields, values){

        var html = '<div class="row">';

        $.each(fields, function(i, field){

            var value = values ? (values[field.name] == null ? '' : values[field.name]) : '';
            var width = field.half ? 'col-md-6' : 'col-12';
            var id = 'pjf-' + field.name;

            html += '<div class="' + width + ' form-group">';

            if(field.type === 'check'){
                html += '<div class="custom-control custom-checkbox mt-2">' +
                        '<input type="checkbox" class="custom-control-input" id="' + id + '" name="' + field.name + '" value="1"' +
                        (String(value) === '1' ? ' checked' : '') + '>' +
                        '<label class="custom-control-label" for="' + id + '">' + esc(field.label) + '</label>' +
                        '</div></div>';
                return;
            }

            html += '<label for="' + id + '">' + esc(field.label) +
                    (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';

            if(field.type === 'textarea'){
                html += '<textarea class="form-control" id="' + id + '" name="' + field.name + '"' +
                        (field.required ? ' required' : '') + '>' + esc(value) + '</textarea>';
            }else{
                var type = field.type === 'number' ? 'number' : field.type;
                html += '<input type="' + type + '" class="form-control" id="' + id + '" name="' + field.name + '" value="' + esc(value) + '"' +
                        (field.type === 'number' ? ' step="any" min="0"' : '') +
                        (field.required ? ' required' : '') + '>';
            }

            if(field.hint){
                html += '<small class="pj-muted">' + esc(field.hint) + '</small>';
            }

            html += '</div>';
        });

        html += '</div>' +
                '<div class="form-group">' +
                '<label for="pjf-document">Attach a file</label>' +
                '<input type="file" class="form-control-file" id="pjf-document" name="document" ' +
                'accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp">' +
                '<small class="pj-muted">PDF, Word, Excel, PowerPoint or a picture. Up to 10 MB.' +
                (values && values.file_name ? ' A file is already attached; choosing a new one replaces it.' : '') +
                '</small></div>';

        return html;
    }

    function open_doc_form(type, doc){

        var config = DOCS[type];
        var form = $('#pj-doc-form');

        form[0].reset();
        form.find('[name="doc_type"]').val(type);
        form.find('[name="id"]').val(doc ? doc.id : '');
        form.find('[name="project_id"]').val(projectId);

        $('#pj-doc-modal .modal-title').text((doc ? 'Edit ' : 'New ') + config.label);
        $('#pj-doc-fields').html(build_fields(config.fields, doc));
        $('#pj-doc-modal').modal('show');
    }

    function open_person_form(person){

        var form = $('#pj-doc-form');

        form[0].reset();
        form.find('[name="doc_type"]').val('designation');
        form.find('[name="id"]').val(person ? person.id : '');
        form.find('[name="project_id"]').val(projectId);

        $('#pj-doc-modal .modal-title').text(person ? 'Edit Assignment' : 'Assign Someone');
        $('#pj-doc-fields').html(build_fields(DESIGNATION_FIELDS, person));
        $('#pj-doc-modal').modal('show');
    }

    // Which button was pressed decides whether this is a draft or a submission
    $(document).on('click', '#pj-doc-form button[type="submit"]', function(){
        $('#pj-doc-form [name="submit_for_review"]').val($(this).data('review'));
    });

    $(document).on('submit', '#pj-doc-form', function(e){

        e.preventDefault();

        var form = this;
        var type = $(form).find('[name="doc_type"]').val();
        var isPerson = type === 'designation';
        var buttons = $(form).find('button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: 'ajax.php?action=' + (isPerson ? 'designation_save' : 'doc_save'),
            method: 'POST',
            data: new FormData(form),
            contentType: false,
            processData: false
        }).done(function(resp){

            buttons.prop('disabled', false);
            resp = $.trim(resp);

            if(resp === '1'){
                $('#pj-doc-modal').modal('hide');
                alert_toast('Saved.', 'success');
                load();
                return;
            }

            Swal.fire({ icon: 'warning', title: 'Not saved', text: resp });

        }).fail(function(){
            buttons.prop('disabled', false);
            Swal.fire({ icon: 'error', title: 'Server error', text: 'Please try again.' });
        });

    });

    /* =================================================================
       BUTTONS
    ================================================================= */

    $(document).on('click', '[data-add]', function(){
        open_doc_form($(this).data('add'), null);
    });

    $(document).on('click', '[data-edit]', function(){
        var type = $(this).data('edit');
        var id = $(this).data('id');
        var doc = (data.documents[type] || []).filter(function(d){ return d.id == id; })[0];
        if(doc) open_doc_form(type, doc);
    });

    $(document).on('click', '[data-add-person]', function(){
        open_person_form(null);
    });

    $(document).on('click', '[data-edit-person]', function(){
        var id = $(this).data('edit-person');
        var person = data.designations.filter(function(p){ return p.id == id; })[0];
        if(person) open_person_form(person);
    });

    $(document).on('click', '[data-remove]', function(){

        var type = $(this).data('remove');
        var id = $(this).data('id');

        Swal.fire({
            title: 'Delete this ' + DOCS[type].label.toLowerCase() + '?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then(function(result){
            if(!result.isConfirmed) return;
            $.post('ajax.php?action=doc_delete', { doc_type: type, id: id }).done(function(resp){
                if($.trim(resp) === '1'){ alert_toast('Deleted.', 'success'); load(); }
                else Swal.fire({ icon: 'warning', title: 'Not deleted', text: $.trim(resp) });
            });
        });

    });

    $(document).on('click', '[data-remove-person]', function(){

        var id = $(this).data('remove-person');

        Swal.fire({
            title: 'Remove this assignment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Remove',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then(function(result){
            if(!result.isConfirmed) return;
            $.post('ajax.php?action=designation_delete', { id: id }).done(function(resp){
                if($.trim(resp) === '1'){ alert_toast('Removed.', 'success'); load(); }
                else Swal.fire({ icon: 'warning', title: 'Not removed', text: $.trim(resp) });
            });
        });

    });

    /* ---------------- the admin's review decision ---------------- */

    $(document).on('click', '[data-review]', function(){

        var type = $(this).data('review');
        var id = $(this).data('id');

        Swal.fire({
            title: 'Review ' + DOCS[type].label,
            input: 'select',
            inputOptions: {
                approved: 'Approve',
                revision: 'Send back for revision',
                rejected: 'Reject',
                under_review: 'Mark as under review'
            },
            inputPlaceholder: 'Choose a decision',
            html: '<textarea id="pj-remarks" class="form-control mt-3" placeholder="Remarks for the coordinator (required when sending back)"></textarea>',
            showCancelButton: true,
            confirmButtonText: 'Save decision',
            confirmButtonColor: '#1d5b42',
            cancelButtonColor: '#6c757d',
            preConfirm: function(decision){
                if(!decision){
                    Swal.showValidationMessage('Please choose a decision.');
                    return false;
                }
                var remarks = $('#pj-remarks').val();
                if(decision === 'revision' && !$.trim(remarks)){
                    Swal.showValidationMessage('Please write what needs to be changed.');
                    return false;
                }
                return { decision: decision, remarks: remarks };
            }
        }).then(function(result){

            if(!result.isConfirmed) return;

            $.post('ajax.php?action=doc_review', {
                doc_type: type,
                id: id,
                decision: result.value.decision,
                remarks: result.value.remarks
            }).done(function(resp){
                if($.trim(resp) === '1'){ alert_toast('Decision saved.', 'success'); load(); }
                else Swal.fire({ icon: 'warning', title: 'Not saved', text: $.trim(resp) });
            });

        });

    });

    /* ---------------- tabs ---------------- */

    $(document).on('click', '.pj-tab', function(){
        var panel = $(this).data('panel');
        $('.pj-tab').removeClass('active');
        $(this).addClass('active');
        $('.pj-panel').removeClass('active').filter('[data-panel="' + panel + '"]').addClass('active');
    });

    load();

})(jQuery);
