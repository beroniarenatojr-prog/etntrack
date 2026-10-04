/*
 * Questionnaire builder (admin/questionnaire_builder.php).
 *
 * Step 1 is a form drawn by the page; this file saves it. Steps 2 to 5 are
 * drawn here from ajax.php?action=qn_get and drawn again after every change,
 * so the page always shows what the server actually saved. The server checks
 * every change again (permissions, locking, validation).
 */
(function ($) {

    'use strict';

    var root = $('#qb');

    if (!root.length) {
        return;
    }

    var id = Number(root.data('id')) || 0;
    var step = Number(root.data('step')) || 1;
    var vocab = window.QB_VOCAB || {};
    var data = null;            // the latest qn_get answer
    var esc = QnStatus.esc;
    var errorText = QnStatus.errorText;

    var TYPE_ICONS = {
        likert: 'fa-sliders-h',
        single_choice: 'fa-dot-circle',
        multiple_choice: 'fa-check-square',
        yes_no: 'fa-toggle-on',
        rating: 'fa-star',
        short_text: 'fa-font',
        long_text: 'fa-align-left'
    };

    var TYPE_HELP = {
        likert: 'Respondents choose one answer on the agree-disagree scale. These answers make up the scores.',
        single_choice: 'Respondents choose one of the options you list.',
        multiple_choice: 'Respondents may tick more than one of the options you list.',
        yes_no: 'Respondents answer Yes or No.',
        rating: 'Respondents choose a number in a range, for example 1 to 5.',
        short_text: 'Respondents type a short, one-line answer.',
        long_text: 'Respondents type a longer answer, such as comments or suggestions.'
    };

    // Which checklist items each step takes care of
    var STEP_CHECKS = {
        1: ['year', 'semester', 'title', 'description', 'scale', 'dates'],
        2: ['sections'],
        3: ['questions', 'section_questions', 'types', 'options', 'ratings', 'required', 'order']
    };

    /* ------------------------------------------------------------------
       Talking to the server
    ------------------------------------------------------------------ */

    function api(action, payload, method) {
        return $.ajax({
            url: 'ajax.php?action=' + action,
            method: method || 'POST',
            data: payload,
            dataType: 'json'
        });
    }

    function stepLink(number) {
        return 'index.php?page=questionnaire_builder&id=' + id + '&step=' + number;
    }

    function toast(message) {
        alert_toast(message, 'success');
    }

    function problem(message) {
        Swal.fire({ icon: 'warning', title: 'Not saved', text: message });
    }

    function load() {
        return api('qn_get', { id: id }, 'GET')
            .done(function (resp) {
                if (!resp.ok) {
                    loadFailed(resp.error);
                    return;
                }
                data = resp;
                markSteps();
                render();
            })
            .fail(function (xhr) {
                loadFailed(errorText(xhr));
            });
    }

    function loadFailed(message) {
        $('#qb-step-body').html(
            '<div class="qb-banner qb-banner-warning"><i class="fas fa-exclamation-triangle"></i>' +
            '<div><b>The questionnaire could not be loaded.</b> ' + esc(message) +
            ' <a href="#" data-act="reload">Try again</a></div></div>'
        );
    }

    function check(key) {
        var found = null;
        (data.checks || []).forEach(function (item) {
            if (item.key === key) {
                found = item;
            }
        });
        return found;
    }

    function stepPasses(keys) {
        return keys.every(function (key) {
            var item = check(key);
            return !item || item.ok || item.severity !== 'error';
        });
    }

    // Ticks the steps that are complete
    function markSteps() {

        var done = {
            1: stepPasses(STEP_CHECKS[1]),
            2: stepPasses(STEP_CHECKS[2]),
            3: stepPasses(STEP_CHECKS[3])
        };

        done[4] = done[1] && done[2] && done[3];
        done[5] = ['active', 'closed', 'archived'].indexOf(data.questionnaire.status) !== -1;

        $('.qb-step').each(function () {
            $(this).toggleClass('done', !!done[$(this).data('step')]);
        });
    }

    function render() {
        var body = $('#qb-step-body');
        if (step === 2) {
            renderSections(body);
        } else if (step === 3) {
            renderQuestions(body);
        } else if (step === 4) {
            renderPreview(body);
        } else if (step === 5) {
            renderReview(body);
        }
    }

    function plural(count, word) {
        return count + ' ' + word + (count === 1 ? '' : 's');
    }

    function rowActions(items) {
        return '<div class="qb-row-actions">' + items.map(function (item) {
            return '<button type="button" class="qb-icon-btn' + (item.danger ? ' qb-icon-danger' : '') + '"' +
                ' data-act="' + item.act + '" title="' + item.label + '" aria-label="' + item.label + '"' +
                (item.disabled ? ' disabled' : '') + '>' +
                '<i class="fas ' + item.icon + '"></i></button>';
        }).join('') + '</div>';
    }

    function showAlert(form, message) {
        form.find('.qb-alert').first().html(message).prop('hidden', false);
    }

    function hideAlert(form) {
        form.find('.qb-alert').first().prop('hidden', true).empty();
        form.find('.is-invalid').removeClass('is-invalid');
    }

    function busy(form, on) {
        form.find('button[type=submit]').prop('disabled', on);
    }

    /*
     * Bootstrap ignores 'hide' while a dialog is still opening, so a save
     * that comes back quickly (Enter right after opening) left the dialog
     * open. Close it once it has finished opening instead.
     */
    $('.qb-modal')
        .on('show.bs.modal', function () { $(this).data('qb-opening', true); })
        .on('shown.bs.modal', function () { $(this).data('qb-opening', false); });

    function closeModal(selector) {
        var modal = $(selector);
        if (modal.data('qb-opening')) {
            modal.one('shown.bs.modal', function () { modal.modal('hide'); });
        } else {
            modal.modal('hide');
        }
    }

    /* ------------------------------------------------------------------
       The order of sections and questions
    ------------------------------------------------------------------ */

    // [{section: id, questions: [id, ...]}, ...] as currently saved
    function savedLayout() {
        return data.structure.sections.map(function (section) {
            return {
                section: section.id,
                questions: section.questions.map(function (question) { return question.id; })
            };
        });
    }

    function saveLayout(layout) {
        if (JSON.stringify(layout) === JSON.stringify(savedLayout())) {
            return;
        }
        api('qn_layout_save', { questionnaire_id: id, layout: JSON.stringify(layout) })
            .done(function (resp) {
                if (!resp.ok) {
                    problem(resp.error);
                }
            })
            .fail(function (xhr) {
                problem(errorText(xhr));
            })
            .always(load);
    }

    function moveSection(sectionId, direction) {
        var layout = savedLayout();
        var at = layout.findIndex(function (group) { return group.section === sectionId; });
        var to = at + direction;
        if (at === -1 || to < 0 || to >= layout.length) {
            return;
        }
        layout.splice(to, 0, layout.splice(at, 1)[0]);
        saveLayout(layout);
    }

    // Up or down one place; past the top or bottom of a section it moves to the next section
    function moveQuestion(questionId, direction) {

        var layout = savedLayout();

        for (var s = 0; s < layout.length; s++) {

            var list = layout[s].questions;
            var at = list.indexOf(questionId);

            if (at === -1) {
                continue;
            }

            var to = at + direction;

            if (to >= 0 && to < list.length) {
                list.splice(to, 0, list.splice(at, 1)[0]);
            } else if (direction < 0 && s > 0) {
                list.splice(at, 1);
                layout[s - 1].questions.push(questionId);
            } else if (direction > 0 && s < layout.length - 1) {
                list.splice(at, 1);
                layout[s + 1].questions.unshift(questionId);
            } else {
                return;
            }

            saveLayout(layout);
            return;
        }
    }

    /* ------------------------------------------------------------------
       STEP 2: SECTIONS & CRITERIA
    ------------------------------------------------------------------ */

    function renderSections(body) {

        var sections = data.structure.sections;
        var editable = data.editable;
        var html = '<div class="row"><div class="col-lg-8">';

        html += '<div class="qb-card">' +
            '<div class="qb-card-head"><div><h3>Sections</h3><p>' +
            (editable
                ? 'Respondents see the sections in this order. Drag <i class="fas fa-grip-vertical"></i> or use the arrows to change it.'
                : 'The sections respondents see, in order.') +
            '</p></div>' +
            (editable ? '<button type="button" class="qb-btn qb-btn-primary" data-act="section-add"><i class="fas fa-plus"></i> Add Section</button>' : '') +
            '</div>';

        if (!sections.length) {
            html += '<div class="qb-empty-state"><i class="fas fa-layer-group"></i>' +
                '<p>No sections yet.</p>' +
                '<small>Add a section, or add an evaluation criterion as a section from the list beside this one.</small></div>';
        } else {
            html += '<ol class="qb-sections" id="qb-sections">';

            sections.forEach(function (section, index) {

                var count = section.questions.length;

                html += '<li class="qb-section' + (section.is_active ? '' : ' is-hidden') + '" data-id="' + section.id + '">' +
                    (editable ? '<span class="qb-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>' : '') +
                    '<span class="qb-num">' + (index + 1) + '</span>' +
                    '<div class="qb-item-body">' +
                    '<div class="qb-item-title">' + esc(section.title) + '</div>' +
                    (section.description ? '<p class="qb-item-desc">' + esc(section.description) + '</p>' : '') +
                    '<div class="qb-meta">' +
                    '<span class="qb-chip qb-chip-plain"><i class="fas fa-list-ol"></i> ' + plural(count, 'question') + '</span>' +
                    (section.criteria_id ? '<span class="qb-chip"><i class="fas fa-sliders-h"></i> From criteria</span>' : '') +
                    (section.is_active ? '' : '<span class="qb-chip qb-chip-muted"><i class="fas fa-eye-slash"></i> Hidden from respondents</span>') +
                    '</div></div>' +
                    (editable ? rowActions([
                        { act: 'section-up', icon: 'fa-arrow-up', label: 'Move up', disabled: index === 0 },
                        { act: 'section-down', icon: 'fa-arrow-down', label: 'Move down', disabled: index === sections.length - 1 },
                        { act: 'section-edit', icon: 'fa-pen', label: 'Edit section' },
                        { act: 'section-delete', icon: 'fa-trash-alt', label: 'Delete section', danger: true }
                    ]) : '') +
                    '</li>';
            });

            html += '</ol>';
        }

        html += '</div></div><div class="col-lg-4">';

        // The criteria a section can be made from
        var criteria = data.criteria.filter(function (item) { return item.is_active; });

        html += '<div class="qb-card"><div class="qb-card-head"><div><h3>Evaluation Criteria</h3>' +
            '<p>Add a criterion as a section. Its template questions come with it.</p></div></div>';

        if (!criteria.length) {
            html += '<p class="text-muted mb-3">There are no active criteria yet.</p>';
        } else {
            html += '<ul class="qb-criteria">';
            criteria.forEach(function (item) {
                html += '<li class="qb-criterion">' +
                    '<div class="qb-item-title">' + esc(item.criteria) + '</div>' +
                    (item.description ? '<p class="qb-item-desc">' + esc(item.description) + '</p>' : '') +
                    '<div class="qb-criterion-foot">' +
                    '<span class="text-muted">' + plural(item.templates, 'template question') + '</span>' +
                    (item.used
                        ? '<span class="qb-chip"><i class="fas fa-check"></i> Added</span>'
                        : (editable ? '<button type="button" class="qb-btn qb-btn-small" data-act="criterion-add" data-id="' + item.id + '"><i class="fas fa-plus"></i> Add as section</button>' : '')) +
                    '</div></li>';
            });
            html += '</ul>';
        }

        html += '<a href="index.php?page=questionnaire#criteria" class="qb-link"><i class="fas fa-cog"></i> Manage evaluation criteria</a>' +
            '</div></div></div>';

        body.html(html);

        if (editable) {
            $('#qb-sections').sortable({
                handle: '.qb-handle',
                items: '> .qb-section',
                axis: 'y',
                placeholder: 'qb-placeholder',
                forcePlaceholderSize: true,
                stop: function () {
                    var byId = {};
                    savedLayout().forEach(function (group) { byId[group.section] = group; });
                    saveLayout($('#qb-sections > .qb-section').map(function () {
                        return byId[Number($(this).data('id'))];
                    }).get());
                }
            });
        }
    }

    function sectionById(sectionId) {
        var found = null;
        data.structure.sections.forEach(function (section) {
            if (section.id === sectionId) {
                found = section;
            }
        });
        return found;
    }

    function openSection(section) {
        var form = $('#qb-section-form');
        hideAlert(form);
        form.find('[name=id]').val(section ? section.id : '');
        form.find('[name=title]').val(section ? section.title : '');
        form.find('[name=description]').val(section ? section.description : '');
        form.find('[name=is_active]').prop('checked', section ? !!section.is_active : true);
        $('#qb-section-modal-title').text(section ? 'Edit Section' : 'Add Section');
        $('#qb-section-modal').modal('show');
    }

    $('#qb-section-modal').on('shown.bs.modal', function () {
        $('#qb-section-title').trigger('focus');
    });

    $('#qb-section-form').on('submit', function (e) {

        e.preventDefault();

        var form = $(this);
        var title = $.trim(form.find('[name=title]').val());

        hideAlert(form);

        if (!title) {
            form.find('[name=title]').addClass('is-invalid').trigger('focus');
            showAlert(form, 'Section title is required.');
            return;
        }

        busy(form, true);

        api('qn_section_save', {
            questionnaire_id: id,
            id: form.find('[name=id]').val(),
            title: title,
            description: $.trim(form.find('[name=description]').val()),
            is_active: form.find('[name=is_active]').is(':checked') ? '1' : '0'
        })
            .done(function (resp) {
                if (resp.ok) {
                    closeModal('#qb-section-modal');
                    toast('Section saved.');
                    load();
                } else {
                    showAlert(form, esc(resp.error));
                }
            })
            .fail(function (xhr) {
                showAlert(form, esc(errorText(xhr)));
            })
            .always(function () {
                busy(form, false);
            });
    });

    function deleteSection(section) {

        var count = section.questions.length;

        Swal.fire({
            title: 'Delete Section?',
            html: '<b>' + esc(section.title) + '</b><br><br>' +
                (count ? 'Its ' + plural(count, 'question') + ' will be deleted too.' : 'It has no questions.'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            focusCancel: true
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            api('qn_section_delete', { questionnaire_id: id, id: section.id })
                .done(function (resp) {
                    if (resp.ok) {
                        toast('Section deleted.');
                    } else {
                        problem(resp.error);
                    }
                })
                .fail(function (xhr) {
                    problem(errorText(xhr));
                })
                .always(load);
        });
    }

    function addCriterion(button) {
        button.prop('disabled', true);
        api('qn_section_from_criteria', { questionnaire_id: id, criteria_id: button.data('id') })
            .done(function (resp) {
                if (resp.ok) {
                    toast('Section added with ' + plural(resp.questions, 'question') + '.');
                } else {
                    problem(resp.error);
                }
            })
            .fail(function (xhr) {
                problem(errorText(xhr));
            })
            .always(load);
    }

    /* ------------------------------------------------------------------
       STEP 3: QUESTIONS
    ------------------------------------------------------------------ */

    function answerSummary(question) {

        var settings = question.settings || {};

        switch (question.question_type) {
            case 'likert':
                return 'Answered on the ' + data.questionnaire.scale.length + '-point scale';
            case 'single_choice':
            case 'multiple_choice':
                if (!question.options.length) {
                    return '<span class="qb-warn-text"><i class="fas fa-exclamation-circle"></i> No options yet</span>';
                }
                return question.options.map(function (option) {
                    return '<span class="qb-option-chip">' + esc(option.label) + '</span>';
                }).join(' ');
            case 'yes_no':
                return 'Yes &middot; No' + (settings.allow_na ? ' &middot; Not Applicable' : '');
            case 'rating':
                return 'A number from ' + (settings.min != null ? settings.min : 1) + ' to ' + (settings.max != null ? settings.max : 5);
            case 'short_text':
                return 'A short typed answer';
            case 'long_text':
                return 'A longer typed answer';
        }
        return '';
    }

    function questionRow(question, number, editable, first, last) {
        return '<li class="qb-question' + (question.is_active ? '' : ' is-hidden') + '" data-id="' + question.id + '">' +
            (editable ? '<span class="qb-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>' : '') +
            '<span class="qb-qno">' + number + '</span>' +
            '<div class="qb-item-body">' +
            '<div class="qb-item-title qb-question-text">' + esc(question.question) + '</div>' +
            '<div class="qb-meta">' +
            '<span class="qb-type"><i class="fas ' + (TYPE_ICONS[question.question_type] || 'fa-question') + '"></i> ' +
            esc(data.types[question.question_type] || question.question_type) + '</span>' +
            (question.is_required ? '<span class="qb-chip qb-chip-required">Required</span>' : '<span class="qb-chip qb-chip-muted">Optional</span>') +
            (question.is_active ? '' : '<span class="qb-chip qb-chip-muted"><i class="fas fa-eye-slash"></i> Hidden</span>') +
            '</div>' +
            '<div class="qb-answers">' + answerSummary(question) + '</div>' +
            '</div>' +
            (editable ? rowActions([
                { act: 'question-up', icon: 'fa-arrow-up', label: 'Move up', disabled: first },
                { act: 'question-down', icon: 'fa-arrow-down', label: 'Move down', disabled: last },
                { act: 'question-edit', icon: 'fa-pen', label: 'Edit question' },
                { act: 'question-duplicate', icon: 'fa-clone', label: 'Duplicate question' },
                { act: 'question-delete', icon: 'fa-trash-alt', label: 'Delete question', danger: true }
            ]) : '') +
            '</li>';
    }

    function renderQuestions(body) {

        var sections = data.structure.sections;
        var editable = data.editable;
        var total = 0;
        var required = 0;

        sections.forEach(function (section) {
            section.questions.forEach(function (question) {
                total++;
                required += question.is_required ? 1 : 0;
            });
        });

        var html = '<div class="qb-card">' +
            '<div class="qb-card-head"><div><h3>Questions</h3><p>' +
            (editable
                ? 'Drag <i class="fas fa-grip-vertical"></i> to reorder questions or to move one into another section.'
                : 'The questions respondents answer, in order.') +
            '</p></div>' +
            '<div class="qb-counts"><span><b>' + total + '</b> ' + (total === 1 ? 'question' : 'questions') + '</span>' +
            '<span><b>' + required + '</b> required</span></div></div>';

        if (!sections.length) {
            html += '<div class="qb-empty-state"><i class="fas fa-layer-group"></i>' +
                '<p>Add a section first.</p><small>Questions are grouped into sections.</small>' +
                '<a class="qb-btn qb-btn-primary" href="' + stepLink(2) + '">Go to Sections &amp; Criteria</a></div>';
        }

        var number = 0;
        var lastSection = sections.length - 1;

        sections.forEach(function (section, s) {

            html += '<div class="qb-qsection' + (section.is_active ? '' : ' is-hidden') + '" data-id="' + section.id + '">' +
                '<div class="qb-qsection-head">' +
                '<span class="qb-num">' + (s + 1) + '</span>' +
                '<div class="qb-qsection-title">' + esc(section.title) +
                (section.is_active ? '' : ' <span class="qb-chip qb-chip-muted"><i class="fas fa-eye-slash"></i> Hidden</span>') +
                '</div>' +
                (editable ? '<button type="button" class="qb-btn qb-btn-small" data-act="question-add" data-section="' + section.id + '"><i class="fas fa-plus"></i> Add Question</button>' : '') +
                '</div>' +
                '<ul class="qb-questions" data-section="' + section.id + '">';

            section.questions.forEach(function (question, q) {
                number++;
                html += questionRow(question, number, editable,
                    s === 0 && q === 0,
                    s === lastSection && q === section.questions.length - 1);
            });

            html += '<li class="qb-questions-empty"' + (section.questions.length ? ' hidden' : '') + '>' +
                'No questions in this section yet.' + (editable ? ' Add one, or drag one here.' : '') + '</li>' +
                '</ul></div>';
        });

        // Questions that lost their section (should not happen, but never hide them)
        if (data.structure.unsectioned.length) {
            html += '<div class="qb-banner qb-banner-warning"><i class="fas fa-exclamation-triangle"></i><div>' +
                '<b>' + plural(data.structure.unsectioned.length, 'question') + ' not in any section.</b> ' +
                'Respondents do not see them. Edit each one and choose its section.<ul class="qb-loose">' +
                data.structure.unsectioned.map(function (question) {
                    return '<li data-id="' + question.id + '">' + esc(question.question) +
                        (editable ? ' <button type="button" class="qb-link-btn" data-act="question-edit">Choose a section</button>' : '') +
                        '</li>';
                }).join('') +
                '</ul></div></div>';
        }

        html += '</div>';

        body.html(html);

        if (editable) {
            $('.qb-questions').sortable({
                handle: '.qb-handle',
                items: '> .qb-question',
                connectWith: '.qb-questions',
                placeholder: 'qb-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                over: function () {
                    $(this).find('.qb-questions-empty').prop('hidden', true);
                },
                stop: function () {
                    saveLayout($('.qb-qsection').map(function () {
                        return {
                            section: Number($(this).data('id')),
                            questions: $(this).find('.qb-question').map(function () {
                                return Number($(this).data('id'));
                            }).get()
                        };
                    }).get());
                }
            });
        }
    }

    function questionById(questionId) {
        var found = null;
        data.structure.sections.concat([{ questions: data.structure.unsectioned }]).forEach(function (section) {
            section.questions.forEach(function (question) {
                if (question.id === questionId) {
                    found = question;
                }
            });
        });
        return found;
    }

    function addOptionRow(label) {
        var row = $(
            '<li class="qb-option">' +
            '<span class="qb-handle" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>' +
            '<input type="text" class="form-control" name="options[]" maxlength="255" placeholder="Option text" aria-label="Option text">' +
            '<button type="button" class="qb-icon-btn qb-option-remove" title="Remove this option" aria-label="Remove this option"><i class="fas fa-times"></i></button>' +
            '</li>'
        );
        row.find('input').val(label || '');
        $('#qb-options').append(row);
        return row;
    }

    function showTypeConfig() {
        var type = $('#qb-question-type').val();
        $('#qb-type-help').text(TYPE_HELP[type] || '');
        $('#qb-question-form .qb-config').each(function () {
            $(this).prop('hidden', String($(this).data('types')).split(' ').indexOf(type) === -1);
        });
    }

    function openQuestion(question, sectionId) {

        var form = $('#qb-question-form');
        var sections = data.structure.sections;

        if (!sections.length) {
            problem('Add a section first. Questions are grouped into sections.');
            return;
        }

        form[0].reset();
        hideAlert(form);

        var select = form.find('[name=section_id]').empty();
        sections.forEach(function (section, index) {
            select.append($('<option>').val(section.id).text((index + 1) + '. ' + section.title));
        });

        var type = question ? question.question_type : 'likert';
        var settings = (question && question.settings) || {};

        form.find('[name=id]').val(question ? question.id : '');
        select.val(String(question && question.section_id ? question.section_id : (sectionId || sections[0].id)));
        if (!select.val()) {
            select.val(String(sections[0].id));
        }
        form.find('[name=question]').val(question ? question.question : '');
        form.find('[name=question_type]').val(type);
        form.find('[name=is_required]').prop('checked', question ? !!question.is_required : true);
        form.find('[name=is_active]').prop('checked', question ? !!question.is_active : true);
        form.find('[name=allow_na]').prop('checked', type === 'yes_no' && !!settings.allow_na);
        form.find('[name=rating_min]').val(type === 'rating' && settings.min != null ? String(settings.min) : '1');
        form.find('[name=rating_max]').val(type === 'rating' && settings.max != null ? String(settings.max) : '5');

        $('#qb-options').empty();
        var labels = (type === 'single_choice' || type === 'multiple_choice') && question
            ? question.options.map(function (option) { return option.label; })
            : [];
        while (labels.length < 2) {
            labels.push('');
        }
        labels.forEach(addOptionRow);

        $('#qb-config-scale').html(data.questionnaire.scale.map(function (scaleStep) {
            return '<li><b>' + esc(scaleStep.value) + '</b> ' + esc(scaleStep.label) + '</li>';
        }).join(''));

        showTypeConfig();

        $('#qb-question-modal-title').text(question ? 'Edit Question' : 'Add Question');
        $('#qb-question-modal').modal('show');
    }

    $('#qb-question-modal').on('shown.bs.modal', function () {
        $('#qb-question-text').trigger('focus');
    });

    $('#qb-question-type').on('change', function () {
        showTypeConfig();
        if ($(this).val() === 'single_choice' || $(this).val() === 'multiple_choice') {
            var empty = $('#qb-options input').filter(function () { return !$.trim(this.value); });
            if (empty.length) {
                empty.first().trigger('focus');
            }
        }
    });

    $('#qb-option-add').on('click', function () {
        addOptionRow('').find('input').trigger('focus');
    });

    $('#qb-options').on('click', '.qb-option-remove', function () {
        var row = $(this).closest('.qb-option');
        if ($('#qb-options .qb-option').length > 2) {
            row.remove();
        } else {
            row.find('input').val('').trigger('focus');
        }
    });

    // Enter in an option adds the next one instead of saving the question
    $('#qb-options').on('keydown', 'input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var next = $(this).closest('.qb-option').next('.qb-option');
            (next.length ? next : addOptionRow('')).find('input').trigger('focus');
        }
    });

    $('#qb-options').sortable({ handle: '.qb-handle', axis: 'y', items: '> .qb-option' });

    $('#qb-question-form').on('submit', function (e) {

        e.preventDefault();

        var form = $(this);
        var type = form.find('[name=question_type]').val();
        var payload = {
            questionnaire_id: id,
            id: form.find('[name=id]').val(),
            section_id: form.find('[name=section_id]').val(),
            question: $.trim(form.find('[name=question]').val()),
            question_type: type,
            is_required: form.find('[name=is_required]').is(':checked') ? '1' : '0',
            is_active: form.find('[name=is_active]').is(':checked') ? '1' : '0'
        };

        hideAlert(form);

        if (!payload.question) {
            form.find('[name=question]').addClass('is-invalid').trigger('focus');
            showAlert(form, 'Question text is required.');
            return;
        }

        if (type === 'single_choice' || type === 'multiple_choice') {

            payload.options = form.find('[name="options[]"]').map(function () {
                return $.trim(this.value);
            }).get().filter(function (label) { return label !== ''; });

            if (payload.options.length < 2) {
                showAlert(form, 'Add at least two options for this question.');
                form.find('[name="options[]"]').filter(function () { return !$.trim(this.value); }).first().trigger('focus');
                return;
            }

            var seen = {};
            for (var i = 0; i < payload.options.length; i++) {
                var key = payload.options[i].toLowerCase();
                if (seen[key]) {
                    showAlert(form, 'Two options have the same text.');
                    return;
                }
                seen[key] = true;
            }
        }

        if (type === 'yes_no') {
            payload.allow_na = form.find('[name=allow_na]').is(':checked') ? '1' : '0';
        }

        if (type === 'rating') {
            payload.rating_min = form.find('[name=rating_min]').val();
            payload.rating_max = form.find('[name=rating_max]').val();
        }

        busy(form, true);

        api('qn_question_save', payload)
            .done(function (resp) {
                if (resp.ok) {
                    closeModal('#qb-question-modal');
                    toast('Question saved.');
                    load();
                } else {
                    showAlert(form, esc(resp.error));
                }
            })
            .fail(function (xhr) {
                showAlert(form, esc(errorText(xhr)));
            })
            .always(function () {
                busy(form, false);
            });
    });

    function duplicateQuestion(question) {
        api('qn_question_duplicate', { questionnaire_id: id, id: question.id })
            .done(function (resp) {
                if (resp.ok) {
                    toast('Question duplicated. The copy is right below it.');
                } else {
                    problem(resp.error);
                }
            })
            .fail(function (xhr) {
                problem(errorText(xhr));
            })
            .always(load);
    }

    function deleteQuestion(question) {
        Swal.fire({
            title: 'Delete Question?',
            html: '<b>' + esc(question.question) + '</b>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            focusCancel: true
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            api('qn_question_delete', { questionnaire_id: id, id: question.id })
                .done(function (resp) {
                    if (resp.ok) {
                        toast('Question deleted.');
                    } else {
                        problem(resp.error);
                    }
                })
                .fail(function (xhr) {
                    problem(errorText(xhr));
                })
                .always(load);
        });
    }

    /* ------------------------------------------------------------------
       STEP 4: PREVIEW
    ------------------------------------------------------------------ */

    function renderPreview(body) {

        var url = 'questionnaire_preview.php?id=' + id;

        body.html(
            '<div class="qb-card">' +
            '<div class="qb-card-head"><div><h3>Preview</h3>' +
            '<p>What respondents will see. Nothing you enter here is saved.</p></div>' +
            '<div class="qb-preview-tools">' +
            '<div class="qb-segmented" role="group" aria-label="Preview size">' +
            '<button type="button" class="active" data-width="">Computer</button>' +
            '<button type="button" data-width="400">Phone</button>' +
            '</div>' +
            '<a class="qb-btn qb-btn-light qb-btn-small" target="_blank" rel="noopener" href="' + url + '">' +
            '<i class="fas fa-external-link-alt"></i> Open in a new tab</a>' +
            '</div></div>' +
            '<div class="qb-preview-stage"><iframe id="qb-preview-frame" src="' + url + '" title="Questionnaire preview"></iframe></div>' +
            '</div>'
        );

        var frame = $('#qb-preview-frame');

        function fit() {
            try {
                var doc = frame[0].contentWindow.document;
                frame.height(Math.max(500, doc.documentElement.scrollHeight));
            } catch (e) {
                frame.height(900);
            }
        }

        frame.on('load', function () {
            fit();
            // Pictures and fonts can change the height after loading
            setTimeout(fit, 400);
        });

        // The width changes smoothly; measure the height again once it has
        frame.on('transitionend', fit);

        body.find('.qb-segmented button').on('click', function () {
            $(this).addClass('active').siblings().removeClass('active');
            var width = $(this).data('width');
            frame.css('width', width ? width + 'px' : '100%');
            setTimeout(fit, 400);
        });
    }

    /* ------------------------------------------------------------------
       STEP 5: REVIEW & PUBLISH
    ------------------------------------------------------------------ */

    var CHECK_STEP = {};
    $.each(STEP_CHECKS, function (number, keys) {
        keys.forEach(function (key) { CHECK_STEP[key] = Number(number); });
    });

    function formatDate(value, withTime) {
        if (!value) {
            return '';
        }
        // A date alone is read as local midnight, not UTC
        var text = String(value);
        var date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(text) ? text + 'T00:00:00' : text.replace(' ', 'T'));
        if (isNaN(date.getTime())) {
            return esc(value);
        }
        var shown = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        if (withTime) {
            shown += ', ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        }
        return shown;
    }

    function renderReview(body) {

        var q = data.questionnaire;
        var sections = data.structure.sections.filter(function (section) { return section.is_active; });
        var total = 0;
        var required = 0;
        var types = {};

        sections.forEach(function (section) {
            section.questions.forEach(function (question) {
                if (!question.is_active) {
                    return;
                }
                total++;
                required += question.is_required ? 1 : 0;
                types[question.question_type] = (types[question.question_type] || 0) + 1;
            });
        });

        var period = q.start_date || q.end_date
            ? (q.start_date ? formatDate(q.start_date) : 'Publishing') + ' to ' + (q.end_date ? formatDate(q.end_date) : 'closing')
            : 'From publishing until it is closed';

        var rows = [
            ['Title', esc(q.title)],
            ['Description', q.description ? esc(q.description) : '<span class="text-muted">Not written yet</span>'],
            ['Academic Year', q.academic_year ? esc(q.academic_year) : '<span class="text-muted">Not set</span>'],
            ['Semester', q.semester ? esc(q.semester) : '<span class="text-muted">Not set</span>'],
            ['Evaluation Type', esc((vocab.evaluation_types || {})[q.evaluation_type] || q.evaluation_type)],
            ['Target Respondents', esc((vocab.respondents || {})[q.target_respondent] || q.target_respondent)],
            ['Used For', q.project_id ? 'Only the activities of <b>' + esc(q.project_title) + '</b>' : 'All extension activities in ' + esc(((q.academic_year || '') + ' ' + (q.semester || '')).trim() || 'the chosen term')],
            ['Evaluation Period', period],
            ['Sections', String(sections.length)],
            ['Questions', total + ' (' + required + ' required)' + (Object.keys(types).length ? '<div class="qb-type-list">' +
                Object.keys(types).map(function (type) {
                    return '<span class="qb-type"><i class="fas ' + (TYPE_ICONS[type] || 'fa-question') + '"></i> ' + esc(data.types[type] || type) + ' &times; ' + types[type] + '</span>';
                }).join('') + '</div>' : '')],
            ['Likert Scale', '<ol class="qb-review-scale">' + q.scale.map(function (scaleStep) {
                return '<li><b>' + esc(scaleStep.value) + '</b> ' + esc(scaleStep.label) + '</li>';
            }).join('') + '</ol>'],
            ['Responses', String(data.counts.responses)]
        ];

        if (q.published_at) {
            rows.push(['Published', formatDate(q.published_at, true)]);
        }
        if (q.closed_at) {
            rows.push(['Closed', formatDate(q.closed_at, true)]);
        }

        var html = '<div class="row"><div class="col-lg-7">' +
            '<div class="qb-card"><div class="qb-card-head"><div><h3>Summary</h3><p>Everything respondents and reports will use.</p></div>' +
            (data.editable ? '<a href="' + stepLink(1) + '" class="qb-btn qb-btn-light qb-btn-small"><i class="fas fa-pen"></i> Edit</a>' : '') +
            '</div><dl class="qb-summary">' +
            rows.map(function (row) { return '<dt>' + row[0] + '</dt><dd>' + row[1] + '</dd>'; }).join('') +
            '</dl></div>' + historyCard() + '</div><div class="col-lg-5">';

        // The checklist
        var errors = data.checks.filter(function (item) { return !item.ok && item.severity === 'error'; }).length;

        html += '<div class="qb-card"><div class="qb-card-head"><div><h3>Validation Checklist</h3><p>' +
            (errors ? errors + ' item' + (errors === 1 ? ' needs' : 's need') + ' attention before publishing.' : 'Everything needed is in place.') +
            '</p></div></div><ul class="qb-checklist">';

        data.checks.forEach(function (item) {
            var state = item.ok ? 'ok' : (item.severity === 'warning' ? 'warning' : 'error');
            var icon = state === 'ok' ? 'fa-check-circle' : (state === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
            var fix = !item.ok && data.editable && CHECK_STEP[item.key]
                ? ' <a href="' + stepLink(CHECK_STEP[item.key]) + '" class="qb-fix">Fix this</a>'
                : '';
            html += '<li class="qb-check qb-check-' + state + '"><i class="fas ' + icon + '"></i><div>' +
                '<div>' + esc(item.label) + '</div>' +
                (item.ok ? '' : '<small>' + esc(item.message) + fix + '</small>') +
                '</div></li>';
        });

        html += '</ul></div>';

        // What can happen next
        html += '<div class="qb-card qb-publish-card">' + publishPanel(q) + '</div>';

        html += '</div></div>';

        body.html(html);
    }

    var HISTORY_WORDS = {
        questionnaire_created: 'Created',
        questionnaire_updated: 'Basic information changed',
        section_added: 'Section added',
        section_updated: 'Section changed',
        section_deleted: 'Section deleted',
        question_added: 'Question added',
        question_updated: 'Question changed',
        question_deleted: 'Question deleted',
        questions_reordered: 'Order changed',
        questionnaire_marked_ready: 'Marked as Ready',
        questionnaire_back_to_draft: 'Back to Draft',
        questionnaire_published: 'Published',
        questionnaire_closed: 'Closed',
        questionnaire_archived: 'Archived'
    };

    // Who changed the questionnaire, and when (from the audit log)
    function historyCard() {

        var items = data.history || [];

        var html = '<div class="qb-card"><div class="qb-card-head"><div><h3>History</h3>' +
            '<p>Who changed this questionnaire, and when.' + (items.length === 30 ? ' The latest 30 changes are shown.' : '') + '</p></div></div>';

        if (!items.length) {
            return html + '<p class="text-muted mb-0">Nothing has been recorded yet.</p></div>';
        }

        return html + '<ul class="qb-history">' + items.map(function (item) {
            return '<li><div><b>' + esc(HISTORY_WORDS[item.action] || item.action) + '</b>' +
                (item.details ? ' <span class="qb-history-detail">' + esc(item.details) + '</span>' : '') + '</div>' +
                '<small>' + esc(item.name || 'Someone') + ' &middot; ' + formatDate(item.created_at, true) + '</small></li>';
        }).join('') + '</ul></div>';
    }

    function publishPanel(q) {

        var results = '<a href="index.php?page=evaluation_results&questionnaire=' + id + '" class="qb-btn qb-btn-light"><i class="fas fa-chart-bar"></i> View Results</a>';
        var blocked = '<p class="qb-blocked"><i class="fas fa-info-circle"></i> Fix the items marked in the checklist first.</p>';

        switch (q.status) {

            case 'draft':
                return '<h3>Ready to publish?</h3>' +
                    '<p>Mark it as Ready when the questions are final. You can still change them until you publish.</p>' +
                    (data.ready ? '' : blocked) +
                    '<div class="qb-publish-actions">' +
                    '<button type="button" class="qb-btn qb-btn-primary" data-status-to="ready"' + (data.ready ? '' : ' disabled') + '>' +
                    '<i class="fas fa-check-circle"></i> Mark as Ready</button></div>';

            case 'ready':
                return '<h3>Publish</h3>' +
                    '<p>Publishing opens the questionnaire to respondents. Its questions are locked from then on.</p>' +
                    (data.ready ? '' : blocked) +
                    '<div class="qb-publish-actions">' +
                    '<button type="button" class="qb-btn qb-btn-primary" data-status-to="active"' + (data.ready ? '' : ' disabled') + '>' +
                    '<i class="fas fa-paper-plane"></i> Publish Questionnaire</button>' +
                    '<button type="button" class="qb-btn qb-btn-light" data-status-to="draft"><i class="fas fa-undo"></i> Back to Draft</button>' +
                    '</div>';

            case 'active':
                return '<h3>Open for responses</h3>' +
                    '<p>Published ' + formatDate(q.published_at, true) + '. Respondents can answer it now. ' +
                    'Close it when the evaluation period is over.</p>' +
                    '<div class="qb-publish-actions">' +
                    '<button type="button" class="qb-btn qb-btn-danger" data-status-to="closed"><i class="fas fa-lock"></i> Close Evaluation</button>' +
                    results + '</div>';

            case 'closed':
                return '<h3>Closed</h3>' +
                    '<p>Closed ' + formatDate(q.closed_at, true) + '. No new responses are accepted. ' +
                    'Archive it to keep it read-only with its results.</p>' +
                    '<div class="qb-publish-actions">' +
                    '<button type="button" class="qb-btn qb-btn-light" data-status-to="archived"><i class="fas fa-archive"></i> Archive</button>' +
                    results + '</div>';
        }

        return '<h3>Archived</h3><p>This questionnaire is read-only. Its responses and results stay available.</p>' +
            '<div class="qb-publish-actions">' + results + '</div>';
    }

    /* ------------------------------------------------------------------
       Clicks inside the drawn steps
    ------------------------------------------------------------------ */

    root.on('click', '[data-act]', function (e) {

        var button = $(this);
        var act = button.data('act');
        var sectionId = Number(button.closest('[data-id]').data('id'));
        var questionId = sectionId;

        if (act !== 'reload' && !data) {
            return;
        }

        switch (act) {
            case 'reload':
                e.preventDefault();
                load();
                break;
            case 'section-add':
                openSection(null);
                break;
            case 'section-edit':
                openSection(sectionById(sectionId));
                break;
            case 'section-delete':
                deleteSection(sectionById(sectionId));
                break;
            case 'section-up':
                moveSection(sectionId, -1);
                break;
            case 'section-down':
                moveSection(sectionId, 1);
                break;
            case 'criterion-add':
                addCriterion(button);
                break;
            case 'question-add':
                openQuestion(null, Number(button.data('section')));
                break;
            case 'question-edit':
                openQuestion(questionById(questionId));
                break;
            case 'question-duplicate':
                duplicateQuestion(questionById(questionId));
                break;
            case 'question-delete':
                deleteQuestion(questionById(questionId));
                break;
            case 'question-up':
                moveQuestion(questionId, -1);
                break;
            case 'question-down':
                moveQuestion(questionId, 1);
                break;
        }
    });

    root.on('click', '[data-status-to]', function () {
        QnStatus.change(id, data.questionnaire.title, $(this).data('status-to'), function () {
            setTimeout(function () { location.reload(); }, 700);
        });
    });

    /* ------------------------------------------------------------------
       STEP 1: BASIC INFORMATION
    ------------------------------------------------------------------ */

    function initBasic() {

        var form = $('#qb-basic');

        if (!form.length) {
            return;
        }

        var scale = $('#qb-scale');

        function renumberScale() {
            var steps = scale.children('.qb-scale-step');
            steps.each(function (index) {
                var value = steps.length - index;
                $(this).find('.qb-scale-value').text(value);
                $(this).find('input').attr('aria-label', 'Label for scale value ' + value);
            });
            $('#qb-scale-add').prop('disabled', steps.length >= 7);
            scale.find('.qb-scale-remove').prop('disabled', steps.length <= 2);
        }

        function addScaleStep(label) {
            var row = $(
                '<li class="qb-scale-step">' +
                '<span class="qb-scale-value"></span>' +
                '<input type="text" name="scale[]" class="form-control" maxlength="120">' +
                '<button type="button" class="qb-icon-btn qb-scale-remove" title="Remove this step" aria-label="Remove this step"><i class="fas fa-times"></i></button>' +
                '</li>'
            );
            row.find('input').val(label || '');
            scale.append(row);
            return row;
        }

        $('#qb-scale-add').on('click', function () {
            var row = addScaleStep('');
            renumberScale();
            row.find('input').trigger('focus');
        });

        scale.on('click', '.qb-scale-remove', function () {
            $(this).closest('.qb-scale-step').remove();
            renumberScale();
        });

        $('#qb-scale-reset').on('click', function () {
            scale.empty();
            (vocab.official_scale || []).forEach(function (scaleStep) {
                addScaleStep(scaleStep.label);
            });
            renumberScale();
        });

        renumberScale();

        // Remember which button sent the form
        form.on('click', 'button[type=submit]', function () {
            form.data('then', $(this).data('then'));
        });

        form.on('input change', '.is-invalid', function () {
            $(this).removeClass('is-invalid');
        });

        form.on('submit', function (e) {

            e.preventDefault();
            hideAlert(form);

            var problems = [];
            var mark = function (field, message) {
                form.find(field).addClass('is-invalid');
                problems.push(message);
            };

            if (!form.find('[name=academic_year]').val()) {
                mark('[name=academic_year]', 'Academic year is required.');
            }
            if (!form.find('[name=semester]').val()) {
                mark('[name=semester]', 'Semester is required.');
            }
            if (!$.trim(form.find('[name=title]').val())) {
                mark('[name=title]', 'Questionnaire title is required.');
            }

            var start = form.find('[name=start_date]').val();
            var end = form.find('[name=end_date]').val();
            if (start && end && start > end) {
                mark('[name=end_date]', 'The end date must be on or after the start date.');
            }

            var blank = scale.find('input').filter(function () { return !$.trim(this.value); });
            if (blank.length) {
                blank.addClass('is-invalid');
                problems.push('Every step of the Likert scale needs a label.');
            }

            if (problems.length) {
                showAlert(form, problems.length === 1 ? esc(problems[0])
                    : '<ul>' + problems.map(function (text) { return '<li>' + esc(text) + '</li>'; }).join('') + '</ul>');
                form.find('.is-invalid').first().trigger('focus');
                return;
            }

            var then = form.data('then') || 'next';

            busy(form, true);

            api('qn_save', form.serialize())
                .done(function (resp) {

                    if (!resp.ok) {
                        showAlert(form, esc(resp.error));
                        busy(form, false);
                        return;
                    }

                    if (then === 'stay' && id) {
                        toast('Saved.');
                        $('.qb-head h1').text($.trim(form.find('[name=title]').val()));
                        busy(form, false);
                        load();
                        return;
                    }

                    id = resp.id;
                    location.href = stepLink(then === 'stay' ? 1 : 2);
                })
                .fail(function (xhr) {
                    showAlert(form, esc(errorText(xhr)));
                    busy(form, false);
                });
        });
    }

    initBasic();

    if (id) {
        load();
    }

})(jQuery);
