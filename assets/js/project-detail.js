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
        },
        // The last stage, written years after the project and reviewed like the others
        impact: {
            label: 'Impact Assessment',
            icon: 'fa-seedling',
            post: true,
            blurb: 'How the project changed things for its beneficiaries, some years after it ended.',
            fields: [
                { name: 'title', label: 'Assessment Title', type: 'text', required: true, key: true },
                { name: 'period_start', label: 'Period Assessed: From', type: 'date', half: true, key: true },
                { name: 'period_end', label: 'Period Assessed: To', type: 'date', half: true, key: true },
                { name: 'assessment_date', label: 'Date of Assessment', type: 'date', half: true },
                { name: 'findings', label: 'Findings', type: 'textarea' },
                { name: 'outcomes', label: 'Outcomes', type: 'textarea', hint: 'What the project achieved in the long run.' },
                { name: 'beneficiary_impact', label: 'Impact on the Beneficiaries', type: 'textarea' },
                { name: 'sustainability', label: 'Sustainability', type: 'textarea', hint: 'Whether the benefits are still going, and why.' },
                { name: 'recommendations', label: 'Recommendations', type: 'textarea' }
            ]
        }
    };

    // The project stages, in order (the same names as on the Projects page)
    var STAGES = [
        ['draft', 'Draft'], ['pre_activity', 'Pre-Activity'], ['for_approval', 'For Approval'],
        ['ready_for_conduct', 'Ready for Conduct'], ['ongoing', 'Ongoing'], ['conducted', 'Conducted'],
        ['post_activity', 'Post-Activity'], ['completed', 'Completed'], ['impact_monitoring', 'Impact Monitoring'],
        ['closed', 'Closed']
    ];

    // Report statuses as the page's status colours know them
    var REPORT_CLASS = { Pending: 'pending', Approved: 'approved', Revision: 'revision', Rejected: 'rejected' };

    function plural(count, word, words){
        return count + ' ' + (count === 1 ? word : (words || word + 's'));
    }

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
        $('#pj-ref').text('Project ID: ' + p.ref);
        $('#pj-coordinator').text(p.coordinator);
        $('#pj-head-meta').text([p.category, p.academic_year, p.semester].filter(Boolean).join(' • '));
        $('#pj-head-stage').attr('class', 'pj-head-stage pj-status ' + p.status).text(p.status_label);

        render_timeline();
        render_summary();
        render_overview();
        render_stage();
        render_pre();
        render_activities();
        render_post();
        render_files();
    }

    /* Each part of the project at a glance. A card opens the tab it is about. */
    function render_summary(){

        var s = data.summary;
        var due = data.impact_due;
        var later = 'After the database update';

        var cards = [
            { tab: 'pre', icon: 'fa-clipboard-check', label: 'Pre-Activity', value: s.pre.done + ' / ' + s.pre.total, note: 'steps approved' },
            { tab: 'conducting', icon: 'fa-calendar-check', label: 'Activities', value: s.activities.total, note: s.activities.approved + ' approved' },
            { tab: 'conducting', icon: 'fa-star', label: 'Evaluation', value: s.evaluations, note: s.evaluations === 1 ? 'response' : 'responses' },
            { tab: 'conducting', icon: 'fa-images', label: 'Documentation', value: s.photos, note: s.photos === 1 ? 'photo' : 'photos' },
            data.links_ready
                ? { tab: 'post', icon: 'fa-file-contract', label: 'Reports', value: s.reports.approved + ' / ' + s.reports.total, note: 'approved' }
                : { tab: 'post', icon: 'fa-file-contract', label: 'Reports', value: '–', note: later },
            data.links_ready
                ? { tab: 'post', icon: 'fa-seedling', label: 'Impact Assessment', value: s.impact, small: true,
                    note: due && due.date ? 'Due ' + due.display : (due ? due.label : '') }
                : { tab: 'post', icon: 'fa-seedling', label: 'Impact Assessment', value: '–', note: later }
        ];

        $('#pj-summary').html($.map(cards, function(card){
            return '<button type="button" class="pj-sum" data-open-tab="' + card.tab + '">' +
                       '<span class="pj-sum-icon"><i class="fas ' + card.icon + '"></i></span>' +
                       '<span class="pj-sum-text">' +
                           '<span class="pj-sum-label">' + esc(card.label) + '</span>' +
                           '<span class="pj-sum-value' + (card.small ? ' small' : '') + '">' + esc(card.value) + '</span>' +
                           '<span class="pj-sum-note">' + esc(card.note) + '</span>' +
                       '</span>' +
                   '</button>';
        }).join(''));
    }

    $(document).on('click', '[data-open-tab]', function(){
        $('.pj-tab[data-panel="' + $(this).data('open-tab') + '"]').click();
        var tabs = document.querySelector('.pj-tabs');
        if(tabs) tabs.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    /* Where the project is among all its stages, and (for the Extension
       Office) a way to move it, with a suggestion when the records say so. */
    function render_stage(){

        var current = data.project.status;
        var index = -1;

        $.each(STAGES, function(i, stage){
            if(stage[0] === current) index = i;
        });

        var path = $.map(STAGES, function(stage, i){
            return '<li class="pj-path-step' + (i < index ? ' past' : (i === index ? ' now' : '')) + '">' +
                       '<span class="pj-path-dot">' + (i < index ? '<i class="fas fa-check"></i>' : (i + 1)) + '</span>' +
                       '<span class="pj-path-label">' + esc(stage[1]) + '</span>' +
                   '</li>';
        }).join('');

        var html = '<h3 class="pj-section-title"><i class="fas fa-flag"></i> Project Stage</h3>' +
                   '<p class="pj-section-sub">The stage moves forward by itself as documents, activities and reports come in, up to Post-Activity. ' +
                   'The Extension Office marks a project Completed, Impact Monitoring and Closed.</p>' +
                   '<ol class="pj-path">' + path + '</ol>';

        if(isAdmin){

            if(data.suggestion){
                html += '<div class="pj-suggest"><i class="fas fa-lightbulb"></i>' +
                        '<div>Suggested next stage: <b>' + esc(data.suggestion.label) + '</b>. ' + esc(data.suggestion.reason) + '</div>' +
                        '<button type="button" class="pj-mini go" data-set-stage="' + esc(data.suggestion.status) + '">' +
                        'Mark as ' + esc(data.suggestion.label) + '</button></div>';
            }

            html += '<div class="pj-stage-set">' +
                        '<label for="pj-stage-select">Change the stage by hand</label>' +
                        '<select id="pj-stage-select" class="form-control">' +
                            $.map(STAGES, function(stage){
                                return '<option value="' + stage[0] + '"' + (stage[0] === current ? ' selected' : '') + '>' + esc(stage[1]) + '</option>';
                            }).join('') +
                        '</select>' +
                        '<button type="button" class="pj-mini go" id="pj-stage-save">Save Stage</button>' +
                    '</div>';
        }

        $('#pj-stage').html(html);
    }

    function set_stage(status){

        var label = STAGES.filter(function(stage){ return stage[0] === status; })[0];

        Swal.fire({
            title: 'Change the stage to ' + (label ? label[1] : status) + '?',
            text: status === 'completed'
                ? 'The completion date is recorded today, and the Impact Assessment becomes due ' +
                  plural(data.project.impact_years, 'year') + ' from it.'
                : '',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Change Stage',
            confirmButtonColor: '#1d5b42',
            cancelButtonColor: '#6c757d'
        }).then(function(result){
            if(!result.isConfirmed) return;
            $.post('ajax.php?action=project_set_status', { id: projectId, status: status }).done(function(resp){
                if($.trim(resp) === '1'){ alert_toast('Stage changed.', 'success'); load(); }
                else Swal.fire({ icon: 'warning', title: 'Not changed', text: $.trim(resp) });
            });
        });
    }

    $(document).on('click', '[data-set-stage]', function(){
        set_stage($(this).data('set-stage'));
    });

    $(document).on('click', '#pj-stage-save', function(){
        var status = $('#pj-stage-select').val();
        if(status === data.project.status) return;
        set_stage(status);
    });

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

        if(data.links_ready){
            facts.push(['Impact Assessment', plural(p.impact_years, 'year') + ' after completion']);
            if(p.completed_at){
                facts.push(['Completed', date_display(p.completed_at)]);
            }
        }

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

    /* CONDUCTING: the project's activities, each with its evaluations and
       photos. Coordinators request activities from here; the Activities page
       opens with this project already chosen. */
    function render_activities(){

        $('#pj-activity-actions').html(
            !isAdmin && data.links_ready
                ? '<a class="pj-btn pj-btn-green" href="index.php?page=activities&new=1&project=' + projectId + '">' +
                  '<i class="fas fa-calendar-plus"></i> Request an Activity</a>'
                : ''
        );

        if(!data.activities.length){
            $('#pj-activities').html(
                '<div class="pj-empty"><i class="fas fa-calendar-plus"></i>' +
                'No activity belongs to this project yet.' +
                (isAdmin ? ' The project\'s coordinator requests activities from the Activities page.' : '') +
                '<div class="mt-2"><a href="index.php?page=activities">Open the Activities page</a></div></div>'
            );
            return;
        }

        var html = '';

        $.each(data.activities, function(i, activity){

            // An approved activity is also conducted once its day has passed
            var badge = activity.phase === 'conducted'
                ? '<span class="pj-status conducted"><i class="fas fa-check"></i> Conducted</span>'
                : activity.phase === 'today'
                    ? '<span class="pj-status ongoing">Today</span>'
                    : '<span class="pj-status ' + esc(activity.status) + '">' + esc(activity.status_label) + '</span>';

            var thumbs = '';
            $.each(activity.images.slice(0, 6), function(j, image){
                thumbs += '<span class="pj-thumb">' +
                              '<a href="' + esc(image.url) + '" target="_blank" rel="noopener" title="Open photo ' + (j + 1) + '">' +
                              '<img src="' + esc(image.url) + '" alt="Photo ' + (j + 1) + ' of ' + esc(activity.activity_name) + '"></a>' +
                              (activity.can_add_photos
                                  ? '<button type="button" class="pj-thumb-remove" data-remove-photo="' + image.id + '" title="Remove this photo" aria-label="Remove photo ' + (j + 1) + '">&times;</button>'
                                  : '') +
                          '</span>';
            });
            if(activity.images.length > 6){
                thumbs += '<span class="pj-thumb-more">+' + (activity.images.length - 6) + '</span>';
            }

            var actions = '';
            if(activity.status === 'approved'){
                actions += '<a class="pj-mini" target="_blank" href="faculty/generate_qr.php?id=' + activity.id + '">' +
                           '<i class="fas fa-qrcode"></i> QR Code</a>';
            }
            if(activity.can_add_photos){
                actions += '<button type="button" class="pj-mini go" data-add-photos="' + activity.id + '">' +
                           '<i class="fas fa-camera"></i> Add Photos</button>';
            }
            if(isAdmin && activity.evaluations > 0){
                actions += '<a class="pj-mini" href="index.php?page=evaluation_results&activity=' + activity.id + '">' +
                           '<i class="fas fa-chart-bar"></i> Results</a>';
            }

            html += '<div class="pj-activity">' +
                        '<div class="pj-activity-main">' +
                            '<div class="pj-doc-title">' + esc(activity.activity_name) + '</div>' +
                            '<div class="pj-doc-meta">' +
                                '<i class="far fa-calendar"></i> ' + esc(activity.date_display) + ' &middot; ' + esc(activity.time_display) +
                                (activity.venue ? ' &middot; <i class="fas fa-map-marker-alt"></i> ' + esc(activity.venue) : '') +
                                ' &middot; ' + esc(activity.ref) +
                            '</div>' +
                            (activity.revision_note
                                ? '<div class="pj-note"><b>Extension Office:</b> ' + nl2br(activity.revision_note) + '</div>'
                                : '') +
                            '<div class="pj-activity-stats">' +
                                '<span class="pj-stat"><i class="fas fa-star"></i> ' + plural(activity.evaluations, 'evaluation') + '</span>' +
                                '<span class="pj-stat"><i class="fas fa-images"></i> ' + plural(activity.images.length, 'photo') + '</span>' +
                            '</div>' +
                            (thumbs ? '<div class="pj-thumbs">' + thumbs + '</div>' : '') +
                        '</div>' +
                        '<div class="pj-activity-side">' + badge +
                            (actions ? '<div class="pj-doc-actions">' + actions + '</div>' : '') +
                        '</div>' +
                    '</div>';
        });

        $('#pj-activities').html(html);
    }

    /* Documentation photos of an approved activity, added by its coordinator
       after the activity (editing the activity itself is closed by then). */
    var photoInput = $('<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>').appendTo('body');
    var photoActivity = 0;

    $(document).on('click', '[data-add-photos]', function(){
        photoActivity = $(this).data('add-photos');
        photoInput.val('').trigger('click');
    });

    photoInput.on('change', function(){

        if(!this.files.length) return;

        var form = new FormData();
        form.append('id', photoActivity);
        $.each(this.files, function(i, file){ form.append('images[]', file); });

        Swal.fire({ title: 'Uploading photos...', allowOutsideClick: false, didOpen: function(){ Swal.showLoading(); } });

        $.ajax({ url: 'ajax.php?action=activity_add_photos', method: 'POST', data: form, processData: false, contentType: false })
            .done(function(resp){
                if($.trim(resp) === '1'){
                    Swal.fire({ icon: 'success', title: 'Photos added', timer: 1400, showConfirmButton: false });
                    load();
                }else{
                    Swal.fire({ icon: 'error', title: 'Not added', text: resp });
                }
            })
            .fail(function(xhr){
                Swal.fire({ icon: 'error', title: 'Not added', text: (xhr.responseJSON && xhr.responseJSON.error) || 'Please try again.' });
            });
    });

    $(document).on('click', '[data-remove-photo]', function(e){

        e.preventDefault();
        var id = $(this).data('remove-photo');

        Swal.fire({
            title: 'Remove this photo?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Remove',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then(function(result){
            if(!result.isConfirmed) return;
            $.post('ajax.php?action=activity_remove_photo', { image_id: id })
                .done(function(resp){
                    if($.trim(resp) === '1'){
                        load();
                    }else{
                        Swal.fire({ icon: 'error', title: 'Not removed', text: resp });
                    }
                })
                .fail(function(xhr){
                    Swal.fire({ icon: 'error', title: 'Not removed', text: (xhr.responseJSON && xhr.responseJSON.error) || 'Please try again.' });
                });
        });
    });

    /* POST-ACTIVITY: the project's reports, and its Impact Assessment with
       when it is due. */
    function report_row(report){

        var approved = report.status === 'Approved' && report.reviewed_at
            ? '<div class="pj-doc-meta"><i class="fas fa-check-circle"></i> Approved ' + esc(report.reviewed_at) +
              (report.reviewed_by ? ' by ' + esc(report.reviewed_by) : '') + '</div>'
            : '';

        return '<div class="pj-doc">' +
                   '<div class="pj-doc-main">' +
                       '<div class="pj-doc-title">' + esc(report.title) + '</div>' +
                       '<div class="pj-doc-meta">' + esc(report.ref) + ' &middot; Submitted ' + esc(report.uploaded_display) +
                       (report.period ? ' &middot; Period: ' + esc(report.period) : '') + ' &middot; ' + esc(report.coordinator) + '</div>' +
                       (report.description ? '<div class="pj-doc-meta">' + nl2br(report.description) + '</div>' : '') +
                       (report.remarks ? '<div class="pj-note"><b>Extension Office:</b> ' + nl2br(report.remarks) + '</div>' : '') +
                       approved +
                   '</div>' +
                   '<span class="pj-status ' + (REPORT_CLASS[report.status] || '') + '">' + esc(report.status_label) + '</span>' +
                   '<div class="pj-doc-actions">' +
                       '<a class="pj-mini" target="_blank" href="' + esc(report.file_url) + '"><i class="fas fa-eye"></i> View</a>' +
                       (report.can_review && report.status !== 'Approved'
                           ? '<button type="button" class="pj-mini go" data-review-report="' + report.id + '"><i class="fas fa-gavel"></i> Review</button>'
                           : '') +
                       (report.can_edit
                           ? '<button type="button" class="pj-mini" data-edit-report="' + report.id + '"><i class="fas fa-pen"></i> ' +
                             (report.status === 'Pending' ? 'Edit' : 'Edit &amp; Resubmit') + '</button>'
                           : '') +
                       (report.can_delete
                           ? '<button type="button" class="pj-mini danger" data-delete-report="' + report.id + '" title="Delete this report">' +
                             '<i class="fas fa-trash-alt"></i></button>'
                           : '') +
                   '</div>' +
               '</div>';
    }

    function render_post(){

        if(!data.links_ready){
            $('#pj-post').html('<div class="pj-empty"><i class="fas fa-database"></i>' +
                'Reports and the Impact Assessment appear here once the latest database update is applied (System Update).</div>');
            return;
        }

        var html = '<div class="pj-step-head">' +
                       '<h3 class="pj-section-title"><i class="fas fa-file-contract"></i> Progress and Terminal Reports</h3>' +
                       '<p class="pj-section-sub">Progress Reports while the project runs, and the Terminal Report at its end. ' +
                       'Approving the Terminal Report completes this stage.' +
                       (isAdmin ? ' The project\'s coordinator uploads them here; you review them.' : '') + '</p>' +
                   '</div>' +
                   '<div id="pj-unlinked"></div>';

        $.each([['Progress Report', 'fa-chart-line'], ['Terminal Report', 'fa-flag-checkered']], function(i, type){

            var list = data.reports.filter(function(r){ return r.type === type[0]; });

            html += '<div class="pj-sub-head">' +
                        '<h4 class="pj-sub-title"><i class="fas ' + type[1] + '"></i> ' + esc(type[0]) +
                        (list.length ? ' <span class="pj-count">' + list.length + '</span>' : '') + '</h4>' +
                        (!isAdmin
                            ? '<button type="button" class="pj-btn pj-btn-green pj-btn-sm" data-upload-report="' + esc(type[0]) + '">' +
                              '<i class="fas fa-cloud-upload-alt"></i> Upload ' + esc(type[0]) + '</button>'
                            : '') +
                    '</div>';

            html += list.length
                ? $.map(list, report_row).join('')
                : '<div class="pj-empty pj-empty-small">No ' + esc(type[0].toLowerCase()) + ' yet.</div>';
        });

        // The Impact Assessment
        var due = data.impact_due;
        var impacts = data.documents.impact || [];

        html += '<div class="pj-step-head mt-4">' +
                    '<h3 class="pj-section-title"><i class="fas fa-seedling"></i> Impact Assessment</h3>' +
                    '<p class="pj-section-sub">' + esc(DOCS.impact.blurb) + ' It is due ' +
                    plural(data.project.impact_years, 'year') + ' after the project is completed.</p>' +
                    (!impacts.length
                        ? '<button type="button" class="pj-btn pj-btn-green pj-step-add" data-add="impact"><i class="fas fa-plus"></i> Start the Impact Assessment</button>'
                        : '') +
                '</div>';

        if(due){
            var from = due.from === 'completion' ? 'counted from the completion date'
                     : due.from === 'end_date' ? 'counted from the project\'s end date until it is marked Completed'
                     : 'set once the project is completed';
            html += '<div class="pj-due ' + esc(due.state) + '">' +
                        '<i class="fas ' + (due.state === 'due' ? 'fa-bell' : 'fa-hourglass-half') + '"></i>' +
                        '<div><b>' + esc(impacts.length ? impacts[0].status_label : due.label) + '</b>' +
                        (due.date ? ' &middot; Due ' + esc(due.display) : '') +
                        '<small>The due date is ' + esc(from) + '.</small></div>' +
                    '</div>';
        }

        $.each(impacts, function(i, doc){
            html += doc_row('impact', doc);
        });

        $('#pj-post').html(html);
        load_unlinked();
    }

    /* Reports are uploaded right here by the project's coordinator, changed
       and resubmitted until they are approved, and reviewed by the Extension
       Office (report_save / report_review / report_delete). */
    function report_of(id){
        return $.grep(data.reports, function(r){ return r.id === Number(id); })[0];
    }

    function open_report_form(type, report){

        var form = $('#pj-report-form');

        form[0].reset();
        form.find('[name="id"]').val(report ? report.id : '');
        form.find('[name="project_id"]').val(projectId);

        $('#pjr-type').val(report ? report.type : type);

        if(report){
            $('#pjr-title').val(report.title);
            $('#pjr-category').val(report.category || '');
            $('#pjr-from').val(report.period_start || '');
            $('#pjr-to').val(report.period_end || '');
            $('#pjr-description').val(report.description || '');
        }

        $('#pjr-file-required').toggle(!report);
        $('#pjr-file-hint').text('PDF or Word (.doc, .docx), up to 20 MB.' + (report ? ' Leave it empty to keep the current file.' : ''));
        $('#pjr-submit span').text(report ? 'Save and Resubmit' : 'Submit Report');
        report_form_title();

        $('#pj-report-modal').modal('show');
    }

    function report_form_title(){
        var editing = !!$('#pj-report-form [name="id"]').val();
        $('#pj-report-modal .modal-title').text((editing ? 'Edit ' : 'Upload ') + $('#pjr-type').val());
    }

    $(document).on('change', '#pjr-type', report_form_title);

    $(document).on('click', '[data-upload-report]', function(){
        open_report_form($(this).data('upload-report'), null);
    });

    $(document).on('click', '[data-edit-report]', function(){
        var report = report_of($(this).data('edit-report'));
        if(report) open_report_form(null, report);
    });

    $(document).on('submit', '#pj-report-form', function(e){

        e.preventDefault();

        var form = this;
        var editing = !!$(form).find('[name="id"]').val();
        var file = $('#pjr-file')[0].files[0];
        var from = $('#pjr-from').val();
        var to = $('#pjr-to').val();

        var problem =
            !$.trim($('#pjr-title').val()) ? 'Please enter the report title.' :
            (!editing && !file) ? 'Please attach the report file.' :
            (file && !/\.(pdf|docx?)$/i.test(file.name)) ? 'Please attach the report as a PDF or Word document.' :
            (file && file.size > 20 * 1024 * 1024) ? 'The file is larger than 20 MB.' :
            (from && to && from > to) ? 'The reporting period ends before it starts.' : '';

        if(problem){
            Swal.fire({ icon: 'warning', title: 'Almost there', text: problem });
            return;
        }

        var button = $('#pjr-submit').prop('disabled', true);

        $.ajax({
            url: 'ajax.php?action=report_save',
            method: 'POST',
            data: new FormData(form),
            contentType: false,
            processData: false,
            dataType: 'json'
        }).done(function(resp){
            if(!resp.ok){
                Swal.fire({ icon: 'warning', title: 'Not submitted', text: resp.error });
                return;
            }
            $('#pj-report-modal').modal('hide');
            alert_toast(editing ? 'Resubmitted for review.' : 'Report submitted for review.', 'success');
            load();
        }).fail(function(xhr){
            Swal.fire({ icon: 'error', title: 'Not submitted', text: (xhr.responseJSON && xhr.responseJSON.error) || 'Please try again.' });
        }).always(function(){
            button.prop('disabled', false);
        });
    });

    $(document).on('click', '[data-delete-report]', function(){

        var report = report_of($(this).data('delete-report'));
        if(!report) return;

        Swal.fire({
            titleText: 'Delete "' + report.title + '"?',
            text: report.ref + ' · ' + report.type + '. The file is removed too. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then(function(result){
            if(!result.isConfirmed) return;
            $.post('ajax.php?action=report_delete', { id: report.id }, null, 'json')
                .done(function(resp){
                    if(resp.ok){ alert_toast('Report deleted.', 'success'); load(); }
                    else Swal.fire({ icon: 'warning', title: 'Not deleted', text: resp.error });
                })
                .fail(function(xhr){
                    Swal.fire({ icon: 'error', title: 'Not deleted', text: (xhr.responseJSON && xhr.responseJSON.error) || 'Please try again.' });
                });
        });
    });

    /* Reports uploaded before reports belonged to projects have none. Whoever
       may link one (the admin, or the coordinator who uploaded it) can link
       it to this project here; nothing is linked by guessing. */
    var unlinked = [];

    function load_unlinked(){
        $.getJSON('ajax.php?action=report_list', { project: 'none' }).done(function(resp){
            unlinked = resp.ok ? resp.data.filter(function(r){ return r.can_assign; }) : [];
            $('#pj-unlinked').html(unlinked.length
                ? '<div class="pj-unlinked"><i class="fas fa-unlink"></i>' +
                  '<span>' + plural(unlinked.length, 'report') + (unlinked.length === 1 ? ' is' : ' are') +
                  ' not linked to any project yet (uploaded before reports belonged to projects).</span>' +
                  '<button type="button" class="pj-mini go" id="pj-link-report"><i class="fas fa-link"></i> Link one to this project</button>' +
                  '</div>'
                : '');
        });
    }

    $(document).on('click', '#pj-link-report', function(){

        var choices = {};
        $.each(unlinked, function(i, r){
            // Option labels are read as HTML by SweetAlert, so the text is escaped
            choices[r.id] = esc(r.ref + ' · ' + r.type + ' · ' + r.title + ' · ' + r.coordinator + ' (' + r.uploaded_display + ')');
        });

        Swal.fire({
            title: 'Link a report to this project',
            text: 'Only link a report that really belongs to "' + data.project.title + '".',
            input: 'select',
            inputOptions: choices,
            inputPlaceholder: 'Choose the report',
            showCancelButton: true,
            confirmButtonText: 'Link',
            confirmButtonColor: '#1d5b42',
            cancelButtonColor: '#6c757d',
            inputValidator: function(value){ if(!value) return 'Please choose a report.'; }
        }).then(function(result){
            if(!result.isConfirmed) return;
            $.post('ajax.php?action=report_assign', { id: result.value, project_id: projectId }, null, 'json')
                .done(function(resp){
                    if(resp.ok){ alert_toast('Report linked to this project.', 'success'); load(); }
                    else Swal.fire({ icon: 'warning', title: 'Not linked', text: resp.error });
                })
                .fail(function(xhr){
                    Swal.fire({ icon: 'error', title: 'Not linked', text: (xhr.responseJSON && xhr.responseJSON.error) || 'Please try again.' });
                });
        });
    });

    // The Extension Office's decision on a report: the same choices as for documents
    $(document).on('click', '[data-review-report]', function(){

        var id = $(this).data('review-report');

        Swal.fire({
            title: 'Review Report',
            input: 'select',
            inputOptions: { approved: 'Approve', revision: 'Needs Revision', rejected: 'Reject' },
            inputPlaceholder: 'Choose a decision',
            html: '<textarea id="pj-report-remarks" class="form-control mt-3" placeholder="Remarks for the coordinator (required for Needs Revision)"></textarea>',
            showCancelButton: true,
            confirmButtonText: 'Save decision',
            confirmButtonColor: '#1d5b42',
            cancelButtonColor: '#6c757d',
            preConfirm: function(decision){
                if(!decision){
                    Swal.showValidationMessage('Please choose a decision.');
                    return false;
                }
                var remarks = $('#pj-report-remarks').val();
                if(decision === 'revision' && !$.trim(remarks)){
                    Swal.showValidationMessage('Please write what needs to be changed.');
                    return false;
                }
                return { decision: decision, remarks: remarks };
            }
        }).then(function(result){
            if(!result.isConfirmed) return;
            $.post('ajax.php?action=report_review', { id: id, decision: result.value.decision, remarks: result.value.remarks }, null, 'json')
                .done(function(resp){
                    if(resp.ok){ alert_toast('Decision saved.', 'success'); load(); }
                    else Swal.fire({ icon: 'warning', title: 'Not saved', text: resp.error });
                })
                .fail(function(xhr){
                    Swal.fire({ icon: 'error', title: 'Not saved', text: (xhr.responseJSON && xhr.responseJSON.error) || 'Please try again.' });
                });
        });
    });

    /* DOCUMENTS: every file in one place, grouped by stage. Nothing is copied:
       each file still opens from the record it belongs to. */
    function render_files(){

        function doc_file(type, doc, label){
            return '<div class="pj-doc">' +
                       '<div class="pj-doc-main">' +
                           '<div class="pj-doc-title">' + esc(doc.title || label) + '</div>' +
                           '<div class="pj-doc-meta">' + esc(label) + '</div>' +
                       '</div>' +
                       '<div class="pj-doc-actions">' +
                           '<button type="button" class="pj-mini" data-view="' + type + '" data-id="' + doc.id + '"><i class="fas fa-eye"></i> View</button>' +
                           '<a class="pj-mini" href="project_file.php?type=' + type + '&id=' + doc.id + '&download=1" title="Download"><i class="fas fa-download"></i></a>' +
                       '</div>' +
                   '</div>';
        }

        var pre = '';
        var conducting = '';
        var post = '';

        $.each(DOCS, function(type, config){
            if(config.post) return;
            $.each(data.documents[type] || [], function(i, doc){
                if(doc.file_name) pre += doc_file(type, doc, config.label);
            });
        });

        $.each(data.designations, function(i, person){
            if(!person.file_name) return;
            pre += '<div class="pj-doc">' +
                       '<div class="pj-doc-main">' +
                           '<div class="pj-doc-title">' + esc(person.personnel_name) + '</div>' +
                           '<div class="pj-doc-meta">Designation</div>' +
                       '</div>' +
                       '<div class="pj-doc-actions">' +
                           '<button type="button" class="pj-mini" data-view-person="' + person.id + '"><i class="fas fa-eye"></i> View</button>' +
                           '<a class="pj-mini" href="project_file.php?type=designation&id=' + person.id + '&download=1" title="Download"><i class="fas fa-download"></i></a>' +
                       '</div>' +
                   '</div>';
        });

        // Activity photos, one row per activity
        $.each(data.activities, function(i, activity){
            if(!activity.images.length) return;
            conducting += '<div class="pj-doc">' +
                              '<div class="pj-doc-main">' +
                                  '<div class="pj-doc-title">Photos: ' + esc(activity.activity_name) + '</div>' +
                                  '<div class="pj-doc-meta">Activity documentation &middot; ' + plural(activity.images.length, 'photo') +
                                  ' &middot; ' + esc(activity.date_display) + '</div>' +
                                  '<div class="pj-thumbs">' + $.map(activity.images, function(image, j){
                                      return '<a href="' + esc(image.url) + '" target="_blank" rel="noopener" title="Open photo ' + (j + 1) + '">' +
                                             '<img src="' + esc(image.url) + '" alt="Photo ' + (j + 1) + '"></a>';
                                  }).join('') + '</div>' +
                              '</div>' +
                          '</div>';
        });

        $.each(data.reports || [], function(i, report){
            post += '<div class="pj-doc">' +
                        '<div class="pj-doc-main">' +
                            '<div class="pj-doc-title">' + esc(report.title) + '</div>' +
                            '<div class="pj-doc-meta">' + esc(report.type) + ' &middot; ' + esc(report.status_label) + '</div>' +
                        '</div>' +
                        '<div class="pj-doc-actions">' +
                            '<a class="pj-mini" target="_blank" href="' + esc(report.file_url) + '"><i class="fas fa-eye"></i> View</a>' +
                            '<a class="pj-mini" href="' + esc(report.file_url) + '&download=1" title="Download"><i class="fas fa-download"></i></a>' +
                        '</div>' +
                    '</div>';
        });

        $.each(data.documents.impact || [], function(i, doc){
            if(doc.file_name) post += doc_file('impact', doc, 'Impact Assessment');
        });

        function group(title, icon, body, empty){
            return '<h4 class="pj-sub-title"><i class="fas ' + icon + '"></i> ' + title + '</h4>' +
                   (body || '<div class="pj-empty pj-empty-small">' + empty + '</div>');
        }

        $('#pj-files').html(
            group('Pre-Activity', 'fa-clipboard-list', pre, 'No pre-activity file yet.') +
            group('Conducting', 'fa-calendar-check', conducting, 'No activity photo yet.') +
            group('Post-Activity', 'fa-flag-checkered', post, 'No report or impact assessment file yet.')
        );
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

    // Only the Review buttons; the form's own submit buttons carry data-review too
    $(document).on('click', 'button[data-review]:not([type="submit"])', function(){

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

    // Opened at a tab, e.g. from the Reports page (&tab=post)
    var startTab = new URLSearchParams(location.search).get('tab');
    if(/^(overview|pre|conducting|post|documents)$/.test(startTab || '')){
        $('.pj-tab[data-panel="' + startTab + '"]').click();
    }

    load();

})(jQuery);
