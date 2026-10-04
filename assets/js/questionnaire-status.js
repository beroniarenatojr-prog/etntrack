/*
 * Questionnaire status changes: Mark Ready, Publish, Back to Draft, Close and
 * Archive, plus deleting a draft. Each one asks first, then the server checks
 * again whether it is allowed. Used by the Questionnaires list and by the
 * builder's Review & Publish step, so both ask the same questions.
 *
 *   QnStatus.change(id, title, to, onDone)   to: ready, active, draft, closed, archived
 *   QnStatus.remove(id, title, onDone)
 */
(function ($) {

    'use strict';

    function esc(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    var confirmations = {
        ready: {
            title: 'Mark as Ready?',
            text: 'The questionnaire is checked for anything missing. It can still be edited until it is published.',
            button: 'Mark Ready',
            color: '#047857',
            icon: 'question',
            done: 'The questionnaire is ready to publish.'
        },
        active: {
            title: 'Publish Questionnaire?',
            text: 'This questionnaire will become available to eligible respondents. Questions will be locked after publishing.',
            button: 'Publish',
            color: '#047857',
            icon: 'question',
            done: 'The questionnaire is published and open for responses.'
        },
        draft: {
            title: 'Back to Draft?',
            text: 'It goes back to being worked on, and must be marked Ready again before it is published.',
            button: 'Back to Draft',
            color: '#6c757d',
            icon: 'question',
            done: 'The questionnaire is a draft again.'
        },
        closed: {
            title: 'Close Evaluation?',
            text: 'Are you sure you want to close this evaluation? Respondents will no longer be able to submit responses.',
            button: 'Close Evaluation',
            color: '#dc3545',
            icon: 'warning',
            done: 'The evaluation is closed.'
        },
        archived: {
            title: 'Archive Questionnaire?',
            text: 'It becomes read-only. Its responses and results stay available.',
            button: 'Archive',
            color: '#6c757d',
            icon: 'question',
            done: 'The questionnaire is archived.'
        }
    };

    // The failed checklist items, as a list under the message
    function failedChecks(checks) {
        var failed = (checks || []).filter(function (check) {
            return !check.ok && check.severity === 'error';
        });
        if (!failed.length) {
            return '';
        }
        return '<ul class="qn-fail-list">' + failed.map(function (check) {
            return '<li>' + esc(check.message || check.label) + '</li>';
        }).join('') + '</ul>';
    }

    function errorText(xhr) {
        var resp = xhr && xhr.responseJSON;
        if (resp && resp.error) {
            return resp.error;
        }
        if (xhr && xhr.status === 401) {
            return 'You were signed out. Please sign in again.';
        }
        return 'Something went wrong. Please try again.';
    }

    function change(id, title, to, onDone) {

        var words = confirmations[to];

        if (!words) {
            return;
        }

        Swal.fire({
            title: words.title,
            html: '<b>' + esc(title) + '</b><br><br>' + esc(words.text),
            icon: words.icon,
            showCancelButton: true,
            confirmButtonText: words.button,
            confirmButtonColor: words.color,
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#6c757d',
            focusCancel: to === 'closed',
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function () {
                return $.post('ajax.php?action=qn_status', { id: id, to: to }, null, 'json')
                    .then(function (resp) { return resp; }, function (xhr) {
                        return { ok: false, error: errorText(xhr) };
                    });
            }
        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }

            var resp = result.value || {};

            if (resp.ok) {
                alert_toast(words.done, 'success');
                if (onDone) {
                    onDone(resp);
                }
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Not changed',
                html: esc(resp.error) + failedChecks(resp.checks)
            });
        });
    }

    function remove(id, title, onDone) {

        Swal.fire({
            title: 'Delete Questionnaire?',
            html: '<b>' + esc(title) + '</b><br><br>Its sections and questions are deleted too. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            focusCancel: true,
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function () {
                return $.post('ajax.php?action=qn_delete', { id: id }, null, 'json')
                    .then(function (resp) { return resp; }, function (xhr) {
                        return { ok: false, error: errorText(xhr) };
                    });
            }
        }).then(function (result) {

            if (!result.isConfirmed) {
                return;
            }

            var resp = result.value || {};

            if (resp.ok) {
                alert_toast('Questionnaire deleted.', 'success');
                if (onDone) {
                    onDone(resp);
                }
                return;
            }

            Swal.fire({ icon: 'warning', title: 'Not deleted', text: resp.error });
        });
    }

    window.QnStatus = {
        change: change,
        remove: remove,
        esc: esc,
        errorText: errorText
    };

})(jQuery);
