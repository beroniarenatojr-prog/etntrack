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

    var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    // Built by hand so the year is always four digits, whatever the browser's locale
    function date_display(value){
        if(!value || value === '0000-00-00') return '';
        var parts = String(value).substring(0, 10).split('-');
        if(parts.length !== 3) return esc(value);
        var month = MONTHS[parseInt(parts[1], 10) - 1];
        if(!month) return esc(value);
        return month + ' ' + parseInt(parts[2], 10) + ', ' + parts[0];
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
                { name: 'title', label: 'Assessment Title', type: 'text', required: true, key: true },
                { name: 'community', label: 'Community / Beneficiary', type: 'text', half: true, key: true },
                { name: 'assessment_date', label: 'Date of Assessment', type: 'date', half: true, key: true },
                { name: 'location', label: 'Location', type: 'text' },
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
                { name: 'title', label: 'MOA Title', type: 'text', required: true, key: true },
                { name: 'partner', label: 'Partner Organisation / Community', type: 'text', half: true, key: true },
                { name: 'moa_date', label: 'MOA Date', type: 'date', half: true, key: true },
                { name: 'description', label: 'Agreement Description', type: 'textarea' },
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
                { name: 'title', label: 'Project Title', type: 'text', required: true, key: true },
                { name: 'date_prepared', label: 'Date Prepared', type: 'date', half: true, key: true },
                { name: 'prepared_by', label: 'Prepared By', type: 'text', half: true, key: true },
                { name: 'rationale', label: 'Rationale', type: 'textarea' },
                { name: 'objectives', label: 'Objectives', type: 'textarea' },
                { name: 'target_beneficiaries', label: 'Target Beneficiaries', type: 'textarea' },
                { name: 'location', label: 'Location', type: 'text', half: true },
                { name: 'duration', label: 'Project Duration', type: 'text', half: true, hint: 'e.g. 6 months' },
                { name: 'major_activities', label: 'Major Activities', type: 'textarea' },
                { name: 'expected_outputs', label: 'Expected Outputs', type: 'textarea' },
                { name: 'expected_outcomes', label: 'Expected Outcomes', type: 'textarea' }
            ]
        },
        proposal: {
            label: 'Proposal',
            icon: 'fa-file-signature',
            blurb: 'The full proposal the Extension Office approves before the project runs.',
            fields: [
                { name: 'title', label: 'Proposal Title', type: 'text', required: true, key: true },
                { name: 'budget', label: 'Proposed Budget (PHP)', type: 'number', half: true, key: true },
                { name: 'funding_source', label: 'Funding Source', type: 'text', half: true, key: true },
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
                { name: 'sustainability_plan', label: 'Sustainability Plan', type: 'textarea' },
                { name: 'prepared_by', label: 'Prepared By', type: 'text' }
            ]
        },
        preparation: {
            label: 'Conduct Preparation',
            icon: 'fa-tasks',
            blurb: 'The final arrangements before the activity is run.',
            fields: [
                { name: 'final_date', label: 'Final Activity Date', type: 'date', half: true, key: true },
                { name: 'venue', label: 'Venue', type: 'text', half: true, key: true },
                { name: 'expected_participants', label: 'Expected Participants', type: 'number', half: true, key: true },
                { name: 'target_beneficiaries', label: 'Target Beneficiaries', type: 'text', half: true, key: true },
                { name: 'start_time', label: 'Start Time', type: 'time', half: true },
                { name: 'end_time', label: 'End Time', type: 'time', half: true },
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
        { name: 'personnel_name', label: 'Name', type: 'text', required: true, key: true },
        { name: 'role', label: 'Role / Designation', type: 'text', required: true, key: true, hint: 'e.g. Project Leader, Trainer, Documentation Officer' },
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

            // The six pre-activity stages can be clicked to open that step
            var jumpable = PRE_STEPS.indexOf(stage.key) !== -1;

            html += '<div class="pj-step ' + stage.state + (jumpable ? ' pj-step-link' : '') + '"' +
                        (jumpable ? ' data-jump="' + stage.key + '" title="Open ' + esc(stage.label) + '"' : '') + '>' +
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

        var attached = doc.file_name
            ? '<span class="pj-clip" title="A file is attached"><i class="fas fa-paperclip"></i></span>'
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
                       '<div class="pj-doc-title">' + esc(doc.title || DOCS[type].label) + ' ' + attached + '</div>' +
                       '<div class="pj-doc-meta">Updated ' + esc((doc.updated_at || '').substring(0, 10)) + '</div>' +
                       remarks +
                   '</div>' +
                   '<span class="pj-status ' + esc(doc.status) + '">' + esc(doc.status_label) + '</span>' +
                   '<div class="pj-doc-actions">' +
                       '<button type="button" class="pj-mini" data-view="' + type + '" data-id="' + doc.id + '">' +
                       '<i class="fas fa-eye"></i> View</button>' +
                       review +
                       '<button type="button" class="pj-mini" data-edit="' + type + '" data-id="' + doc.id + '"><i class="fas fa-pen"></i></button>' +
                       '<button type="button" class="pj-mini danger" data-remove="' + type + '" data-id="' + doc.id + '"><i class="fas fa-trash-alt"></i></button>' +
                   '</div>' +
               '</div>';
    }

    /* =================================================================
       READING A DOCUMENT
       Shows what was filled in, and previews the attached file when the
       browser can display it.
    ================================================================= */

    /* Word documents are turned into readable HTML in the browser by Mammoth,
       which is fetched only when a .docx actually needs showing. */
    var mammothLoading = null;

    function load_mammoth(){

        if(window.mammoth){
            return $.Deferred().resolve().promise();
        }

        if(!mammothLoading){
            mammothLoading = $.ajax({
                url: 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.13.0/mammoth.browser.min.js',
                dataType: 'script',
                cache: true,
                timeout: 20000
            });
        }

        return mammothLoading;
    }

    function show_docx(url, target){

        var box = $(target);

        box.html('<div class="pj-empty"><i class="fas fa-spinner fa-spin"></i>Opening the Word document...</div>');

        load_mammoth().done(function(){

            fetch(url, { credentials: 'same-origin' })
                .then(function(response){
                    if(!response.ok) throw new Error('could not be read');
                    return response.arrayBuffer();
                })
                .then(function(buffer){
                    return window.mammoth.convertToHtml({ arrayBuffer: buffer });
                })
                .then(function(result){

                    var html = $.trim(result.value);

                    if(!html){
                        box.html('<div class="pj-empty"><i class="fas fa-file-word"></i>' +
                                 'This Word document has no text to show. Use Download to open it.</div>');
                        return;
                    }

                    box.html('<div class="pj-docx">' + html + '</div>');
                })
                .catch(function(){
                    box.html('<div class="pj-empty"><i class="fas fa-file-word"></i>' +
                             'This Word document could not be shown here. Use Download to open it.</div>');
                });

        }).fail(function(){
            box.html('<div class="pj-empty"><i class="fas fa-file-word"></i>' +
                     'The Word viewer could not be loaded, which usually means no internet connection. ' +
                     'Use Download to open the file.</div>');
        });
    }

    function file_preview(type, id, fileName){

        if(!fileName){
            return '<div class="pj-empty"><i class="fas fa-paperclip"></i>No file is attached to this document.</div>';
        }

        var url = 'project_file.php?type=' + type + '&id=' + id;
        var extension = String(fileName).split('.').pop().toLowerCase();

        var expand = '<button type="button" class="pj-mini" data-fullscreen="1">' +
                     '<i class="fas fa-expand"></i> Full screen</button>';

        var buttons = '<div class="pj-doc-actions mt-2">' +
                      '<a class="pj-mini" target="_blank" href="' + url + '"><i class="fas fa-external-link-alt"></i> Open in a new tab</a>' +
                      '<a class="pj-mini" href="' + url + '&download=1"><i class="fas fa-download"></i> Download</a>' +
                      '</div>';

        // Wrapped so the whole preview can be blown up to fill the screen
        function wrap(inner){
            return '<div class="pj-preview-wrap">' +
                       '<button type="button" class="pj-expand-btn" data-fullscreen="1" title="Full screen">' +
                       '<i class="fas fa-expand"></i></button>' +
                       inner +
                   '</div>' +
                   '<div class="pj-doc-actions mt-2">' +
                       expand +
                       '<a class="pj-mini" target="_blank" href="' + url + '"><i class="fas fa-external-link-alt"></i> Open in a new tab</a>' +
                       '<a class="pj-mini" href="' + url + '&download=1"><i class="fas fa-download"></i> Download</a>' +
                   '</div>';
        }

        if(extension === 'pdf'){
            return wrap('<iframe class="pj-preview" src="' + url + '#view=FitH" title="Attached document"></iframe>');
        }

        if(['jpg','jpeg','png','webp'].indexOf(extension) !== -1){
            return wrap('<img class="pj-preview-img" src="' + url + '" alt="Attached picture">');
        }

        if(extension === 'docx'){
            // Filled in by show_docx() once the dialog is on screen
            return wrap('<div id="pj-docx-box" data-url="' + url + '"></div>');
        }

        var names = { doc: 'older Word (.doc)', xls: 'Excel', xlsx: 'Excel', ppt: 'PowerPoint', pptx: 'PowerPoint' };

        return '<div class="pj-empty"><i class="fas fa-file-alt"></i>' +
               'This is ' + esc(names[extension] || extension.toUpperCase()) + ', which a browser cannot display. ' +
               'Use Download to open it' +
               (extension === 'doc' ? ', or re-save it as .docx to read it here' : '') + '.' +
               '</div>' + buttons;
    }

    function open_doc_view(type, doc){

        var isPerson = type === 'designation';
        var config = isPerson ? { label: 'Designation', fields: DESIGNATION_FIELDS } : DOCS[type];

        var html = '<div class="pj-view-head">' +
                   '<span class="pj-status ' + esc(doc.status) + '">' +
                   esc(doc.status_label || (doc.status === 'ended' ? 'Ended' : 'Active')) + '</span>' +
                   '<span class="pj-muted">Last updated ' + esc((doc.updated_at || '').substring(0, 16)) + '</span>' +
                   '</div>';

        if(doc.admin_remarks){
            html += '<div class="pj-note"><b>Extension Office:</b> ' + nl2br(doc.admin_remarks) + '</div>';
        }

        // What was filled in. Empty fields are left out so the view stays readable.
        var rows = '';

        $.each(config.fields, function(i, field){

            var value = doc[field.name];

            if(value === null || value === undefined || String(value).trim() === ''){
                return;
            }

            if(field.type === 'check'){
                value = String(value) === '1' ? 'Yes' : 'No';
            }else if(field.type === 'date'){
                value = date_display(value);
            }else if(field.type === 'number' && field.name === 'budget'){
                value = 'PHP ' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2 });
            }

            rows += '<div class="pj-view-row">' +
                        '<dt>' + esc(field.label) + '</dt>' +
                        '<dd>' + nl2br(value) + '</dd>' +
                    '</div>';
        });

        html += rows
            ? '<dl class="pj-view-list">' + rows + '</dl>'
            : '<div class="pj-empty"><i class="fas fa-align-left"></i>Nothing has been filled in yet.</div>';

        html += '<h3 class="pj-section-title mt-4"><i class="fas fa-paperclip"></i> Attached File</h3>' +
                file_preview(type, doc.id, doc.file_name);

        $('#pj-view-modal .modal-title').text(config.label);
        $('#pj-view-body').html(html);

        // The Edit button carries on from here
        $('#pj-view-edit').off('click').on('click', function(){
            $('#pj-view-modal').modal('hide');
            setTimeout(function(){
                if(isPerson){ open_person_form(doc); } else { open_doc_form(type, doc); }
            }, 300);
        });

        $('#pj-view-modal').modal('show');

        // A Word document is rendered once the dialog is up
        var docx = $('#pj-docx-box');

        if(docx.length){
            show_docx(docx.data('url'), docx);
        }
    }

    /* The six pre-activity steps, in the order the Extension Office works
       through them. 'designation' is a list of people rather than a document. */
    var PRE_STEPS = ['assessment', 'moa', 'capsule', 'proposal', 'designation', 'preparation'];

    var currentStep = 'assessment';   // which step the right-hand side is showing

    function step_config(key){

        if(key === 'designation'){
            return {
                label: 'Designation',
                icon: 'fa-user-tag',
                blurb: 'Who is assigned to this project, and as what.',
                addLabel: 'Assign Someone'
            };
        }

        return $.extend({ addLabel: 'Add ' + DOCS[key].label }, DOCS[key]);
    }

    // What the left-hand list shows against each step
    function step_state(key){

        var stage = data.lifecycle.filter(function(s){ return s.key === key; })[0];

        if(!stage){
            return { state: 'waiting', note: '' };
        }

        return { state: stage.state, note: stage.status_label };
    }

    function render_pre(){

        if(PRE_STEPS.indexOf(currentStep) === -1){
            currentStep = 'assessment';
        }

        // ---- the step list ----
        var nav = '';

        $.each(PRE_STEPS, function(i, key){

            var config = step_config(key);
            var state = step_state(key);

            var mark = state.state === 'done' ? '<i class="fas fa-check"></i>'
                     : state.state === 'attention' ? '<i class="fas fa-exclamation"></i>'
                     : (i + 1);

            var count = key === 'designation'
                ? data.designations.length
                : (data.documents[key] || []).length;

            nav += '<button type="button" class="pj-step-item ' + state.state +
                       (key === currentStep ? ' active' : '') + '" data-step="' + key + '">' +
                       '<span class="pj-step-num">' + mark + '</span>' +
                       '<span class="pj-step-text">' +
                           '<span class="pj-step-name">' + esc(config.label) + '</span>' +
                           '<span class="pj-step-note">' + esc(state.note) + '</span>' +
                       '</span>' +
                       (count ? '<span class="pj-step-count">' + count + '</span>' : '') +
                   '</button>';
        });

        // ---- the selected step ----
        var config = step_config(currentStep);
        var isPerson = currentStep === 'designation';
        var list = isPerson ? data.designations : (data.documents[currentStep] || []);

        var body = '<div class="pj-step-head">' +
                       '<h3 class="pj-section-title"><i class="fas ' + config.icon + '"></i> ' + esc(config.label) + '</h3>' +
                       '<p class="pj-section-sub">' + esc(config.blurb) + '</p>' +
                       '<button type="button" class="pj-btn pj-btn-green pj-step-add" ' +
                       (isPerson ? 'data-add-person="1"' : 'data-add="' + currentStep + '"') + '>' +
                       '<i class="fas fa-plus"></i> ' + esc(config.addLabel) + '</button>' +
                   '</div>';

        if(list.length){
            $.each(list, function(i, item){
                body += isPerson ? person_row(item) : doc_row(currentStep, item);
            });
        }else{
            body += '<div class="pj-empty"><i class="fas ' + config.icon + '"></i>' +
                    (isPerson ? 'Nobody is assigned to this project yet.'
                              : 'No ' + config.label.toLowerCase() + ' yet.') +
                    '</div>';
        }

        // ---- previous / next, so the six steps can be walked through ----
        var index = PRE_STEPS.indexOf(currentStep);

        body += '<div class="pj-step-move">' +
                (index > 0
                    ? '<button type="button" class="pj-mini" data-step="' + PRE_STEPS[index - 1] + '">' +
                      '<i class="fas fa-arrow-left"></i> ' + esc(step_config(PRE_STEPS[index - 1]).label) + '</button>'
                    : '<span></span>') +
                (index < PRE_STEPS.length - 1
                    ? '<button type="button" class="pj-mini" data-step="' + PRE_STEPS[index + 1] + '">' +
                      esc(step_config(PRE_STEPS[index + 1]).label) + ' <i class="fas fa-arrow-right"></i></button>'
                    : '<span></span>') +
                '</div>';

        $('#pj-pre').html(
            '<div class="pj-split">' +
                '<nav class="pj-steps" aria-label="Pre-activity steps">' + nav + '</nav>' +
                '<div class="pj-step-body">' + body + '</div>' +
            '</div>'
        );
    }

    function person_row(person){

        var attached = person.file_name
            ? '<span class="pj-clip" title="A file is attached"><i class="fas fa-paperclip"></i></span>'
            : '';

        return '<div class="pj-doc">' +
                   '<div class="pj-doc-main">' +
                       '<div class="pj-doc-title">' + esc(person.personnel_name) + ' ' + attached + '</div>' +
                       '<div class="pj-doc-meta">' + esc(person.role) +
                       (person.responsibility ? ' &middot; ' + esc(person.responsibility) : '') + '</div>' +
                   '</div>' +
                   '<span class="pj-status ' + esc(person.status) + '">' +
                   esc(person.status === 'ended' ? 'Ended' : 'Active') + '</span>' +
                   '<div class="pj-doc-actions">' +
                       '<button type="button" class="pj-mini" data-view-person="' + person.id + '"><i class="fas fa-eye"></i> View</button>' +
                       '<button type="button" class="pj-mini" data-edit-person="' + person.id + '"><i class="fas fa-pen"></i></button>' +
                       '<button type="button" class="pj-mini danger" data-remove-person="' + person.id + '"><i class="fas fa-trash-alt"></i></button>' +
                   '</div>' +
               '</div>';
    }

    // Choosing a step, from the list or the previous / next buttons
    $(document).on('click', '[data-step]', function(){
        currentStep = $(this).data('step');
        render_pre();
        var panel = document.querySelector('.pj-step-body');
        if(panel && window.innerWidth < 992){
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    // Clicking a stage in the timeline jumps to that step
    $(document).on('click', '.pj-step[data-jump]', function(){
        var key = $(this).data('jump');
        if(PRE_STEPS.indexOf(key) === -1) return;
        currentStep = key;
        $('.pj-tab[data-panel="pre"]').click();
        render_pre();
    });

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
                                '<button type="button" class="pj-mini" data-view="' + type + '" data-id="' + doc.id + '"><i class="fas fa-eye"></i> View</button>' +
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
                            '<button type="button" class="pj-mini" data-view-person="' + person.id + '"><i class="fas fa-eye"></i> View</button>' +
                            '<a class="pj-mini" href="project_file.php?type=designation&id=' + person.id + '&download=1"><i class="fas fa-download"></i></a>' +
                        '</div>' +
                    '</div>';
        });

        $('#pj-files').html(html || '<div class="pj-empty"><i class="fas fa-folder-open"></i>No file has been attached to this project yet.</div>');
    }

    /* =================================================================
       THE DOCUMENT FORM
    ================================================================= */

    function build_field(field, values){

        var value = values ? (values[field.name] == null ? '' : values[field.name]) : '';
        var width = field.half ? 'col-md-6' : 'col-12';
        var id = 'pjf-' + field.name;
        var html = '<div class="' + width + ' form-group">';

        if(field.type === 'check'){
            return html +
                   '<div class="custom-control custom-checkbox mt-2">' +
                   '<input type="checkbox" class="custom-control-input" id="' + id + '" name="' + field.name + '" value="1"' +
                   (String(value) === '1' ? ' checked' : '') + '>' +
                   '<label class="custom-control-label" for="' + id + '">' + esc(field.label) + '</label>' +
                   '</div></div>';
        }

        html += '<label for="' + id + '">' + esc(field.label) +
                (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';

        if(field.type === 'textarea'){
            html += '<textarea class="form-control" id="' + id + '" name="' + field.name + '"' +
                    (field.required ? ' required' : '') + '>' + esc(value) + '</textarea>';
        }else{
            html += '<input type="' + field.type + '" class="form-control" id="' + id + '" name="' + field.name + '" value="' + esc(value) + '"' +
                    (field.type === 'number' ? ' step="any" min="0"' : '') +
                    (field.required ? ' required' : '') + '>';
        }

        if(field.hint){
            html += '<small class="pj-muted">' + esc(field.hint) + '</small>';
        }

        return html + '</div>';
    }

    /* The few fields worth filling in are shown; the rest sit behind "More
       details", because the attached file is usually the document itself.
       When a record already uses one of those extra fields, they start open. */
    function build_fields(fields, values){

        var main = '';
        var extra = '';
        var extraUsed = false;

        $.each(fields, function(i, field){

            if(field.key){
                main += build_field(field, values);
                return;
            }

            extra += build_field(field, values);

            var value = values ? values[field.name] : '';

            if(value !== null && value !== undefined && String(value).trim() !== '' && String(value) !== '0'){
                extraUsed = true;
            }
        });

        var html = '<div class="row">' + main + '</div>';

        if(extra){
            html += '<div class="pj-more">' +
                        '<button type="button" class="pj-more-toggle' + (extraUsed ? ' open' : '') + '">' +
                            '<i class="fas fa-chevron-right"></i> More details ' +
                            '<span class="pj-muted">(optional)</span>' +
                        '</button>' +
                        '<div class="pj-more-body"' + (extraUsed ? '' : ' style="display:none"') + '>' +
                            '<div class="row">' + extra + '</div>' +
                        '</div>' +
                    '</div>';
        }

        html += '<div class="form-group mt-3">' +
                '<label for="pjf-document">Attach the document</label>' +
                '<input type="file" class="form-control-file" id="pjf-document" name="document" ' +
                'accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp">' +
                '<small class="pj-muted">PDF, Word, Excel, PowerPoint or a picture. Up to 10 MB.' +
                (values && values.file_name ? ' A file is already attached; choosing a new one replaces it.' : '') +
                '</small></div>';

        return html;
    }

    $(document).on('click', '.pj-more-toggle', function(){
        $(this).toggleClass('open').next('.pj-more-body').slideToggle(160);
    });

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

    $(document).on('click', '[data-view]', function(){
        var type = $(this).data('view');
        var id = $(this).data('id');
        var doc = (data.documents[type] || []).filter(function(d){ return d.id == id; })[0];
        if(doc) open_doc_view(type, doc);
    });

    $(document).on('click', '[data-view-person]', function(){
        var id = $(this).data('view-person');
        var person = data.designations.filter(function(p){ return p.id == id; })[0];
        if(person) open_doc_view('designation', person);
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

    /* ---------------- full screen preview ---------------- */

    // Uses the browser's own full screen when it is available, and falls back
    // to filling the window with CSS when it is not.
    $(document).on('click', '[data-fullscreen]', function(){

        var wrap = $(this).closest('.pj-view-row, .modal-body').find('.pj-preview-wrap').first();

        if(!wrap.length){
            wrap = $('.pj-preview-wrap').first();
        }

        if(!wrap.length) return;

        var element = wrap[0];

        if(document.fullscreenElement || document.webkitFullscreenElement){
            (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            return;
        }

        var request = element.requestFullscreen || element.webkitRequestFullscreen;

        if(request){
            var attempt = request.call(element);
            if(attempt && attempt.catch){
                attempt.catch(function(){ wrap.addClass('pj-expanded'); });
            }
            return;
        }

        wrap.addClass('pj-expanded');
    });

    // Esc leaves full screen. Caught before Bootstrap sees it, so the first Esc
    // shrinks the preview rather than closing the whole dialog.
    document.addEventListener('keydown', function(e){

        if(e.key !== 'Escape') return;

        if(document.querySelector('.pj-preview-wrap.pj-expanded')){
            e.stopPropagation();
            e.preventDefault();
            $('.pj-preview-wrap.pj-expanded').removeClass('pj-expanded');
        }

    }, true);

    // Closing the dialog must never leave the page stuck in full screen
    $(document).on('hide.bs.modal', '#pj-view-modal', function(){
        $('.pj-preview-wrap').removeClass('pj-expanded');
        if(document.fullscreenElement || document.webkitFullscreenElement){
            (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        }
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
