/*
 * Reports page (admin/report.php and faculty/result.php)
 *
 * Every report belongs to a project. The list comes from report_list as JSON
 * (filtered by search, project, coordinator and category on the server); the
 * type tabs (Terminal / Progress / Impact Assessment) and the status tabs
 * filter it here, so the counts always match what is listed.
 *
 *   var page = ReportsPage({
 *       admin: true|false,
 *       stats: function(reports){ ... },   // fill the page's cards
 *       edit:  function(report){ ... }     // coordinators: open the form
 *   });
 *   page.reload(); page.projects (a promise of project_options); page.show(type, status)
 */
(function($){

    var TYPES = { terminal: "Terminal Report", progress: "Progress Report" };
    var TAB_OF = { "Terminal Report": "terminal", "Progress Report": "progress" };
    var BADGE = { Pending: "badge-warning", Approved: "badge-success", Revision: "badge-revision", Rejected: "badge-danger" };
    var DOC_BADGE = {
        draft: "badge-light", submitted: "badge-warning", under_review: "badge-warning",
        approved: "badge-success", revision: "badge-revision", rejected: "badge-danger"
    };
    var STATUS_WORD = { Pending: "pending", Revision: "returned for revision", Approved: "approved", Rejected: "rejected", All: "" };

    function esc(value){
        return String(value == null ? "" : value).replace(/[&<>"']/g, function(c){
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
        });
    }

    function nl2br(value){
        return esc(value).replace(/\n/g, "<br>");
    }

    function post(action, data){
        return $.ajax({ url: "ajax.php?action=" + action, method: "POST", data: data, dataType: "json" });
    }

    function failed(xhr){
        var resp = xhr && xhr.responseJSON;
        Swal.fire({ icon: "error", title: "Something went wrong", text: (resp && resp.error) || "Please refresh the page and try again." });
    }

    window.ReportsPage = function(options){

        var isAdmin = !!options.admin;
        var params = new URLSearchParams(location.search);
        var reports = [];
        var impacts = [];
        var tab = /^#(terminal|progress|impact)$/.test(location.hash) ? location.hash.slice(1) : "terminal";
        var status = "Pending";
        var request = 0;
        var columns = isAdmin
            ? ["#", "Report", "Project", "Uploaded By", "Uploaded At", "Status", "Action"]
            : ["#", "Report", "Project", "Category", "Date Uploaded", "Size", "Status", "Action"];

        /* ---------------- loading ---------------- */

        function filters(){
            return {
                search: $.trim($("#search-report").val() || ""),
                project: $("#filter-project").val() || "",
                coordinator: $("#filter-coordinator").val() || "",
                category: $("#filter-category").val() || "",
                sort: $("#sort-report").val() || "newest"
            };
        }

        function reload(){

            var mine = ++request;

            $.getJSON("ajax.php?action=report_list", filters()).done(function(resp){
                if(mine !== request) return;
                if(!resp.ok){
                    reports = [];
                    render(resp.error);
                    return;
                }
                reports = resp.data;
                render();
            }).fail(function(){
                if(mine !== request) return;
                reports = [];
                render("The reports could not be loaded. Please refresh the page.");
            });

            $.getJSON("ajax.php?action=impact_list").done(function(resp){
                impacts = resp.ok ? resp.data : [];
                if(mine === request) render();
            });
        }

        // The projects someone may file a report under (all of them for the admin)
        var projects = $.getJSON("ajax.php?action=project_options").then(function(list){

            var select = $("#filter-project");
            var wanted = params.get("project");

            $.each(list, function(i, p){
                select.append($("<option>").val(p.id).text(p.ref + " · " + p.title));
            });

            if(wanted && select.find('option[value="' + parseInt(wanted, 10) + '"]').length){
                select.val(String(parseInt(wanted, 10)));
                reload();
            }

            return list;
        });

        /* ---------------- rendering ---------------- */

        // Impact assessments follow the project and search filters too
        function visible_impacts(){
            var f = filters();
            var search = f.search.toLowerCase();
            return impacts.filter(function(item){
                if(f.project === "none") return false;
                if(f.project && String(item.project_id) !== f.project) return false;
                if(search && (item.title + " " + item.project_title + " " + item.coordinator).toLowerCase().indexOf(search) === -1) return false;
                return true;
            });
        }

        function render(error){

            var counts = { terminal: 0, progress: 0 };
            $.each(reports, function(i, r){ counts[TAB_OF[r.type]]++; });

            $("#count-terminal").text(counts.terminal);
            $("#count-progress").text(counts.progress);
            $("#count-impact").text(visible_impacts().length);

            $(".report-tabs .nav-link").removeClass("active").filter('[href="#' + tab + '"]').addClass("active");
            $(".report-type-label").text(tab === "impact" ? "Impact Assessment" : TYPES[tab]);

            if(options.stats){
                options.stats(reports);
            }

            render_unassigned();

            if(tab === "impact"){
                $(".status-tabs").hide();
                $("#impact-note").show();
                render_impacts();
                return;
            }

            $(".status-tabs").show();
            $("#impact-note").hide();

            var ofType = reports.filter(function(r){ return r.type === TYPES[tab]; });
            var byStatus = { Pending: 0, Revision: 0, Approved: 0, Rejected: 0, All: ofType.length };
            $.each(ofType, function(i, r){ byStatus[r.status] = (byStatus[r.status] || 0) + 1; });

            $.each(byStatus, function(key, value){ $("#count-" + key).text(value); });
            $(".status-tabs .nav-link").removeClass("active").filter('[data-status="' + status + '"]').addClass("active");

            var list = status === "All" ? ofType : ofType.filter(function(r){ return r.status === status; });

            $("#report-head").html("<tr>" + $.map(columns, function(c){ return "<th>" + c + "</th>"; }).join("") + "</tr>");

            if(error){
                $("#report-list").html(empty_row(columns.length, '<i class="fas fa-exclamation-triangle"></i> ' + esc(error)));
                return;
            }

            if(!list.length){
                var word = STATUS_WORD[status];
                $("#report-list").html(empty_row(columns.length,
                    '<i class="far fa-folder-open"></i> No ' + (word ? word + " " : "") + esc(TYPES[tab].toLowerCase()) + "s" +
                    (filters().search || filters().project || filters().category || filters().coordinator ? " match these filters." : " yet.")));
                return;
            }

            $("#report-list").html($.map(list, function(r, i){ return report_row(r, i); }).join(""));
        }

        function empty_row(span, html){
            return '<tr class="rp-empty-row"><td colspan="' + span + '"><div class="rp-empty">' + html + "</div></td></tr>";
        }

        // Reports that have no project yet, with a way to list just those
        function render_unassigned(){

            var count = reports.filter(function(r){ return !r.project_id; }).length;
            var showing = $("#filter-project").val() === "none";

            if(!count || showing){
                $("#unassigned-note").hide();
                return;
            }

            $("#unassigned-note").show().html(
                '<i class="fas fa-unlink"></i> <b>' + count + " report" + (count === 1 ? " has" : "s have") + " no project yet.</b> " +
                (isAdmin
                    ? "They were uploaded before reports belonged to projects. Link each one to its project."
                    : "Link each one to the project it belongs to.") +
                ' <button type="button" class="rp-link-btn" id="show-unassigned">Show them</button>'
            );
        }

        function project_cell(r){
            if(!r.project_id){
                return '<span class="rp-unassigned"><i class="fas fa-unlink"></i> Unassigned project</span>' +
                       (r.can_assign ? '<button type="button" class="rp-link-btn" data-assign="1">Link</button>' : "");
            }
            return '<a class="rp-project" href="index.php?page=project_detail&id=' + r.project_id + '&tab=post" title="Open the project">' +
                       '<span class="rp-ref">' + esc(r.project_ref) + "</span>" + esc(r.project_title) +
                   "</a>";
        }

        function status_cell(r){
            var html = '<span class="badge ' + (BADGE[r.status] || "badge-light") + '">' + esc(r.status_label) + "</span>";
            if(r.remarks){
                html += '<div class="rp-remarks"><b>Extension Office:</b> ' + nl2br(r.remarks) + "</div>";
            }
            if(r.reviewed_at && r.status !== "Pending"){
                html += '<div class="rp-meta">' + esc(r.reviewed_at) + (r.reviewed_by ? " · " + esc(r.reviewed_by) : "") + "</div>";
            }
            return html;
        }

        function actions(r){

            var html = '<a class="btn btn-sm action-btn rp-view" href="' + esc(r.file_url) + '" target="_blank" title="View the file"><i class="fas fa-eye"></i></a>';

            if(r.can_review && r.status !== "Approved"){
                html += '<button type="button" class="btn btn-sm action-btn rp-approve" data-review="approved" title="Approve"><i class="fas fa-check"></i></button>' +
                        '<button type="button" class="btn btn-sm action-btn rp-revise" data-review="revision" title="Needs Revision"><i class="fas fa-undo"></i></button>' +
                        '<button type="button" class="btn btn-sm action-btn rp-reject" data-review="rejected" title="Reject"><i class="fas fa-times"></i></button>';
            }

            if(r.can_edit){
                html += '<button type="button" class="btn btn-sm action-btn rp-edit" data-edit="1" title="Edit and resubmit"><i class="fas fa-pen"></i></button>';
            }

            if(r.can_assign && r.project_id){
                html += '<button type="button" class="btn btn-sm action-btn rp-move" data-assign="1" title="Move to another project"><i class="fas fa-link"></i></button>';
            }

            if(r.can_delete){
                html += '<button type="button" class="btn btn-sm action-btn delete-report" data-delete="1" title="Delete"><i class="fas fa-trash"></i></button>';
            }

            return '<div class="rp-actions">' + html + "</div>";
        }

        function report_row(r, i){

            var report = '<div class="rp-title">' + esc(r.title) + "</div>" +
                         '<div class="rp-meta">' + esc(r.ref) + (isAdmin && r.category ? " · " + esc(r.category) : "") +
                         (r.period ? " · Period: " + esc(r.period) : "") + "</div>" +
                         (r.description ? '<div class="rp-desc">' + nl2br(r.description) + "</div>" : "");

            var cells = ["<td>" + (i + 1) + "</td>", "<td>" + report + "</td>", "<td>" + project_cell(r) + "</td>"];

            if(isAdmin){
                cells.push("<td>" + esc(r.coordinator) + "</td>");
                cells.push('<td class="text-nowrap">' + esc(r.uploaded_display) + '<div class="rp-meta">' + esc(r.uploaded_time) + "</div></td>");
            }else{
                cells.push("<td>" + (r.category ? '<span class="badge badge-light badge-category">' + esc(r.category) + "</span>" : "") + "</td>");
                cells.push('<td class="text-nowrap">' + esc(r.uploaded_display) + '<div class="rp-meta">' + esc(r.uploaded_time) + "</div></td>");
                cells.push('<td class="text-nowrap">' + esc(r.size) + "</td>");
            }

            cells.push("<td>" + status_cell(r) + "</td>");
            cells.push("<td>" + actions(r) + "</td>");

            return '<tr data-id="' + r.id + '">' + cells.join("") + "</tr>";
        }

        function render_impacts(){

            var head = ["#", "Impact Assessment", "Project", "Coordinator", "Last Updated", "Status", "Action"];
            var list = visible_impacts();

            $("#report-head").html("<tr>" + $.map(head, function(c){ return "<th>" + c + "</th>"; }).join("") + "</tr>");

            if(!list.length){
                $("#report-list").html(empty_row(head.length,
                    '<i class="fas fa-seedling"></i> No impact assessments yet. Each one is written in its project\'s Post-Activity tab ' +
                    "once the project is completed."));
                return;
            }

            $("#report-list").html($.map(list, function(item, i){
                return "<tr>" +
                           "<td>" + (i + 1) + "</td>" +
                           '<td><div class="rp-title">' + esc(item.title || "Impact Assessment") + "</div>" +
                               (item.has_file ? '<div class="rp-meta"><i class="fas fa-paperclip"></i> File attached</div>' : "") + "</td>" +
                           '<td><a class="rp-project" href="index.php?page=project_detail&id=' + item.project_id + '&tab=post">' +
                               '<span class="rp-ref">' + esc(item.project_ref) + "</span>" + esc(item.project_title) + "</a></td>" +
                           "<td>" + esc(item.coordinator) + "</td>" +
                           '<td class="text-nowrap">' + esc(item.updated_display) + "</td>" +
                           '<td><span class="badge ' + (DOC_BADGE[item.status] || "badge-light") + '">' + esc(item.status_label) + "</span></td>" +
                           '<td><a class="btn btn-sm rp-open" href="index.php?page=project_detail&id=' + item.project_id + '&tab=post">' +
                               '<i class="fas fa-external-link-alt"></i> Open</a></td>' +
                       "</tr>";
            }).join(""));
        }

        /* ---------------- actions ---------------- */

        function row_of(el){
            var id = parseInt($(el).closest("tr").data("id"), 10);
            return reports.filter(function(r){ return r.id === id; })[0];
        }

        function done(message){
            return function(resp){
                if(!resp.ok){
                    Swal.fire({ icon: "error", title: "Not saved", text: resp.error });
                    return;
                }
                Swal.fire({ icon: "success", title: message, timer: 1500, showConfirmButton: false });
                reload();
            };
        }

        // The Extension Office's decision; Needs Revision must say what to change
        $(document).on("click", "#report-list [data-review]", function(){

            var r = row_of(this);
            var decision = $(this).data("review");
            var setup = {
                approved: { title: "Approve this report?", text: "An approved report is locked. Remarks are optional.", button: "Approve", color: "#28a745", placeholder: "Remarks (optional)", done: "Approved" },
                revision: { title: "Send back for revision?", text: "The coordinator sees your remarks, changes the report and resubmits it.", button: "Send back", color: "#0d6efd", placeholder: "What needs to be changed?", done: "Sent back for revision", required: true },
                rejected: { title: "Reject this report?", text: "The report is kept, marked Rejected.", button: "Reject", color: "#dc3545", placeholder: "Reason (optional)", done: "Rejected" }
            }[decision];

            if(!r || !setup) return;

            Swal.fire({
                title: setup.title,
                html: '<div class="rp-swal-sub">' + esc(r.ref + " · " + r.title) + "</div>" + esc(setup.text),
                input: "textarea",
                inputPlaceholder: setup.placeholder,
                showCancelButton: true,
                confirmButtonText: setup.button,
                confirmButtonColor: setup.color,
                cancelButtonColor: "#6c757d",
                inputValidator: function(value){
                    if(setup.required && !$.trim(value)) return "Please write what needs to be changed.";
                }
            }).then(function(result){
                if(!result.isConfirmed) return;
                post("report_review", { id: r.id, decision: decision, remarks: result.value || "" }).done(done(setup.done)).fail(failed);
            });
        });

        $(document).on("click", "#report-list [data-delete]", function(){

            var r = row_of(this);
            if(!r) return;

            Swal.fire({
                title: "Delete this report?",
                html: '<div class="rp-swal-sub">' + esc(r.ref + " · " + r.title) + "</div>The file is removed too. This cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Delete",
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d"
            }).then(function(result){
                if(!result.isConfirmed) return;
                post("report_delete", { id: r.id }).done(done("Deleted")).fail(failed);
            });
        });

        // Linking a report to its project, chosen from the projects you may use
        $(document).on("click", "#report-list [data-assign]", function(){

            var r = row_of(this);
            if(!r) return;

            projects.done(function(list){

                if(!list.length){
                    Swal.fire({ icon: "info", title: "No project to choose", text: "Create the project first on the Projects page." });
                    return;
                }

                var choices = {};
                $.each(list, function(i, p){
                    // Option labels are read as HTML by SweetAlert, so the text is escaped
                    choices[p.id] = esc(p.ref + " · " + p.title + (p.term ? " (" + p.term + ")" : "") + (isAdmin ? " · " + p.coordinator : ""));
                });

                Swal.fire({
                    title: r.project_id ? "Move to another project" : "Which project is this report for?",
                    html: '<div class="rp-swal-sub">' + esc(r.ref + " · " + r.title + " · " + r.coordinator) + "</div>",
                    input: "select",
                    inputOptions: choices,
                    inputValue: r.project_id ? String(r.project_id) : "",
                    inputPlaceholder: "Choose the project",
                    showCancelButton: true,
                    confirmButtonText: "Link",
                    confirmButtonColor: "#198754",
                    cancelButtonColor: "#6c757d",
                    inputValidator: function(value){ if(!value) return "Please choose a project."; }
                }).then(function(result){
                    if(!result.isConfirmed) return;
                    post("report_assign", { id: r.id, project_id: result.value }).done(done("Linked to the project")).fail(failed);
                });
            });
        });

        $(document).on("click", "#report-list [data-edit]", function(){
            var r = row_of(this);
            if(r && options.edit) options.edit(r);
        });

        /* ---------------- tabs and filters ---------------- */

        $(".report-tabs .nav-link").click(function(e){
            e.preventDefault();
            tab = $(this).attr("href").slice(1);
            history.replaceState(null, "", location.pathname + location.search + "#" + tab);
            render();
        });

        $(".status-tabs .nav-link").click(function(e){
            e.preventDefault();
            status = $(this).data("status");
            render();
        });

        var searchTimer;

        $("#search-report").on("input", function(){
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reload, 300);
        });

        $(".report-filter").change(reload);

        $(document).on("click", "#show-unassigned", function(){
            $("#filter-project").val("none");
            status = "All";
            reload();
        });

        reload();

        return {
            reload: reload,
            projects: projects,
            // Show one type and status, e.g. after submitting a report
            show: function(type, newStatus){
                if(TAB_OF[type]) tab = TAB_OF[type];
                if(newStatus) status = newStatus;
                history.replaceState(null, "", location.pathname + location.search + "#" + tab);
                reload();
            },
            type: function(){ return TYPES[tab] || ""; }
        };
    };

})(jQuery);
