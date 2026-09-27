/*
 * Shared activities table
 * -----------------------
 * Used by the admin Activities page and the coordinator's My / All Activities tabs.
 * Rows come from ajax.php?action=activity_table as JSON; DataTables sorts, pages and
 * searches them in the browser. Each row says what the viewer may do with it
 * (can_edit / can_delete / can_review) and the server checks again on every action.
 *
 *   var table = ActivityTable.create({
 *       table: "#my-activity-table",   // empty <table>; the header is built here
 *       scope: "mine",                 // "mine" or "all" (the admin always gets all)
 *       selectable: true,              // checkbox column + bulk bar
 *       showImplementer: true,
 *       search: "#my-search",          // text box that searches the table
 *       bulkBar: "#my-bulk",           // has .at-bulk-count and [data-bulk] buttons
 *       filters: function(){ return { status: "..." }; },  // sent to the server
 *       onEdit: function(row){ ... },  // coordinator Edit
 *       onChange: function(){ ... },   // after every reload (e.g. refresh stat cards)
 *       emptyText: "No activities yet."
 *   });
 *   table.reload();
 */
var ActivityTable = (function($){

    // Escapes text before it goes into HTML
    function esc(text){
        return $("<div>").text(text == null ? "" : String(text)).html();
    }

    function status_badge(row){
        var html = '<span class="at-status ' + esc(row.status) + '">' + esc(row.status_label) + '</span>';
        if(row.revision_note){
            html += ' <i class="fas fa-comment-dots at-note-icon" title="' + esc("Admin note: " + row.revision_note) + '"></i>';
        }
        return html;
    }

    /* ---------------- ⋮ menu (one shared menu, fixed to the window) ---------------- */

    var $menu = null;

    function close_menu(){
        if($menu){
            $menu.remove();
            $menu = null;
        }
    }

    $(document).on("click", function(e){
        if($menu && !$(e.target).closest(".at-menu, .at-menu-btn").length){
            close_menu();
        }
    });

    $(document).on("keydown", function(e){
        if(e.key === "Escape"){ close_menu(); }
    });

    // The menu is fixed to the window, so close it when anything scrolls or resizes
    $(window).on("resize", close_menu);
    document.addEventListener("scroll", close_menu, true);

    function menu_items(row, options){

        var items = [{ act: "view", icon: "fa-eye", label: "View" }];

        if(row.can_edit && options.onEdit){
            items.push({ act: "edit", icon: "fa-pen", label: "Edit" });
        }

        if(row.can_review){
            if(row.status !== "approved"){
                items.push({ act: "approve", icon: "fa-check", label: "Approve", css: "text-success" });
            }
            if(row.status === "pending"){
                items.push({ act: "revision", icon: "fa-undo", label: "Needs Revision", css: "text-orange" });
            }
            if(row.status === "pending" || row.status === "revision"){
                items.push({ act: "reject", icon: "fa-times", label: "Reject", css: "text-danger" });
            }
            if(row.status === "approved"){
                items.push({ act: "qr", icon: "fa-qrcode", label: "Generate QR" });
            }
        }

        if(row.can_delete){
            items.push({ divider: true });
            items.push({ act: "delete", icon: "fa-trash-alt", label: "Delete", css: "text-danger" });
        }

        return items;
    }

    function open_menu(button, row, options, handle){

        close_menu();

        $menu = $('<div class="dropdown-menu show at-menu" role="menu"></div>');

        $.each(menu_items(row, options), function(i, item){
            if(item.divider){
                $menu.append('<div class="dropdown-divider"></div>');
                return;
            }
            $('<a href="#" class="dropdown-item" role="menuitem"></a>')
                .addClass(item.css || "")
                .html('<i class="fas ' + item.icon + ' fa-fw mr-2"></i>' + esc(item.label))
                .on("click", function(e){
                    e.preventDefault();
                    close_menu();
                    handle(item.act, row);
                })
                .appendTo($menu);
        });

        $("body").append($menu);

        // Line the menu up under the button (or above it when there's no room below)
        var box = button.getBoundingClientRect();
        var width = $menu.outerWidth();
        var height = $menu.outerHeight();
        var top = box.bottom + 4;

        if(top + height > window.innerHeight && box.top - height - 4 > 0){
            top = box.top - height - 4;
        }

        $menu.css({
            top: top,
            left: Math.max(8, box.right - width)
        });
    }

    /* ---------------- popups ---------------- */

    var viewing = null;   // the activity whose details popup is open
    var gallery = null;   // {row, index} while the picture-only view is open

    function view_popup(row){

        viewing = row;

        var images = row.images || [];
        var html = '<div class="at-view">';

        // First picture large; with several, a strip of all of them underneath
        if(images.length){

            html += '<img src="' + esc(images[0].url) + '" alt="" class="at-view-image" data-index="0" title="Click to see the whole picture">';

            if(images.length > 1){
                html += '<div class="at-view-thumbs">';
                $.each(images, function(i, img){
                    html += '<img src="' + esc(img.url) + '" alt="" class="at-view-thumb" data-index="' + i + '" title="Picture ' + (i + 1) + ' of ' + images.length + '">';
                });
                html += '</div>';
            }
        }

        if(row.revision_note){
            html += '<div class="at-view-note"><b>Admin note:</b> ' + esc(row.revision_note) + '</div>';
        }

        html +=
            '<table class="at-view-details">' +
                '<tr><th>ID</th><td>' + esc(row.ref) + '</td></tr>' +
                '<tr><th>Status</th><td>' + status_badge(row) + '</td></tr>' +
                '<tr><th>Date &amp; Time</th><td>' + esc(row.date_display) + ' · All day</td></tr>' +
                '<tr><th>Venue</th><td>' + esc(row.venue || "No venue") + '</td></tr>' +
                '<tr><th>Implementer</th><td>' + esc(row.implementer) + '</td></tr>' +
                '<tr><th>Purpose</th><td>' + esc(row.purpose || "None given") + '</td></tr>' +
                '<tr><th>Description</th><td>' + esc(row.description || "None given") + '</td></tr>' +
            '</table>' +
        '</div>';

        Swal.fire({
            titleText: row.title,
            html: html,
            width: 600,
            confirmButtonText: "Close",
            confirmButtonColor: "#198754"
        });
    }

    // One picture on its own, whole and uncropped, with ‹ › (and the ← → keys) for the others.
    // It stays open until "Back to details" or "Close" (clicking outside won't close it).
    function image_popup(row, index){

        var images = row.images;
        var several = images.length > 1;

        gallery = { row: row, index: index };

        Swal.fire({
            html:
                '<div class="at-gallery">' +
                    (several ? '<button type="button" class="at-gallery-prev" aria-label="Previous picture">&lsaquo;</button>' : '') +
                    '<img src="' + esc(images[index].url) + '" alt="' + esc(row.title) + '" class="at-gallery-image">' +
                    (several ? '<button type="button" class="at-gallery-next" aria-label="Next picture">&rsaquo;</button>' : '') +
                '</div>' +
                (several ? '<div class="at-gallery-count">' + (index + 1) + ' of ' + images.length + '</div>' : ''),
            width: "min(95vw, 1100px)",
            padding: "15px",
            customClass: { popup: "at-image-popup" },
            allowOutsideClick: false,
            showCancelButton: true,
            confirmButtonText: "Back to details",
            confirmButtonColor: "#198754",
            cancelButtonText: "Close",
            cancelButtonColor: "#6c757d"
        }).then(function(result){
            gallery = null;
            if(result.isConfirmed){
                view_popup(row);
            }
        });
    }

    // step = -1 (previous) or 1 (next); wraps around at the ends
    function show_picture(step){

        if(!gallery) return;

        var images = gallery.row.images;

        gallery.index = (gallery.index + step + images.length) % images.length;

        $(".at-gallery-image").attr("src", images[gallery.index].url);
        $(".at-gallery-count").text((gallery.index + 1) + " of " + images.length);
    }

    $(document).on("click", ".at-view-image, .at-view-thumb", function(){
        if(viewing && viewing.images && viewing.images.length){
            image_popup(viewing, Number($(this).attr("data-index")) || 0);
        }
    });

    $(document).on("click", ".at-gallery-prev", function(){ show_picture(-1); });
    $(document).on("click", ".at-gallery-next", function(){ show_picture(1); });

    $(document).on("keydown", function(e){
        if(!gallery) return;
        if(e.key === "ArrowLeft") show_picture(-1);
        if(e.key === "ArrowRight") show_picture(1);
    });

    function confirm_action(title, text, button_text, color){
        return Swal.fire({
            title: title,
            text: text,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: button_text,
            confirmButtonColor: color,
            cancelButtonColor: "#6c757d"
        });
    }

    /* ---------------- the table ---------------- */

    function create(options){

        var $table = $(options.table);
        var columns = [];

        if(options.selectable){
            columns.push({
                data: null,
                title: '<input type="checkbox" class="at-check-all" aria-label="Select all on this page">',
                orderable: false,
                searchable: false,
                className: "at-select",
                render: function(data, type, row){
                    return row.can_delete
                        ? '<input type="checkbox" class="at-check" value="' + row.id + '" aria-label="Select ' + esc(row.title) + '">'
                        : "";
                }
            });
        }

        columns.push(
            { data: "ref", title: "ID", className: "at-ref", render: function(d){ return esc(d); } },
            {
                data: "title",
                title: "Activity Title",
                render: function(d, type){ return type === "display" ? '<span class="at-title">' + esc(d) + "</span>" : d; }
            },
            {
                data: "date",
                title: "Date &amp; Time",
                className: "text-nowrap",
                // Sort by the real date, show "Sep 01, 2026 · All day"
                render: function(d, type, row){
                    return type === "sort" || type === "type" ? d : esc(row.date_display) + ' <span class="at-muted">· All day</span>';
                }
            },
            { data: "venue", title: "Venue", render: function(d){ return esc(d); } }
        );

        if(options.showImplementer !== false){
            columns.push({ data: "implementer", title: "Implementer", render: function(d){ return esc(d); } });
        }

        columns.push(
            {
                data: "status_label",
                title: "Status",
                render: function(d, type, row){ return type === "display" ? status_badge(row) : d; }
            },
            {
                data: null,
                title: '<span class="sr-only">Actions</span>',
                orderable: false,
                searchable: false,
                className: "text-right",
                render: function(data, type, row){
                    return '<button type="button" class="at-menu-btn" aria-label="More options for ' + esc(row.title) + '">' +
                           '<i class="fas fa-ellipsis-v"></i></button>';
                }
            }
        );

        var date_column = options.selectable ? 3 : 2;

        var dt = $table.DataTable({
            ajax: {
                url: "ajax.php?action=activity_table",
                data: function(d){
                    return $.extend({ scope: options.scope || "all" }, options.filters ? options.filters() : {});
                },
                dataSrc: "data"
            },
            columns: columns,
            order: [[date_column, "desc"]],
            pageLength: 10,
            lengthMenu: [10, 15, 25, 50],
            autoWidth: false,
            dom: "rt<'at-footer'lip>",
            language: {
                emptyTable: options.emptyText || "No activities yet.",
                zeroRecords: "No activities match your search.",
                info: "Showing _START_ to _END_ of _TOTAL_ activities",
                infoEmpty: "No activities",
                infoFiltered: "(filtered from _MAX_)",
                lengthMenu: "Show _MENU_",
                loadingRecords: "Loading activities...",
                paginate: { previous: "&lsaquo;", next: "&rsaquo;" }
            }
        });

        /* ----- search ----- */

        if(options.search){
            $(options.search).on("input", function(){
                dt.search(this.value).draw();
            });
        }

        /* ----- selection + bulk bar ----- */

        function selected_ids(){
            return $(dt.rows().nodes()).find(".at-check:checked").map(function(){ return this.value; }).get();
        }

        function update_bulk_bar(){
            if(!options.bulkBar) return;
            var count = selected_ids().length;
            $(options.bulkBar).toggleClass("is-active", count > 0).find(".at-bulk-count").text(count + " selected");
        }

        $table.on("change", ".at-check", update_bulk_bar);

        $table.closest(".dataTables_wrapper").on("change", ".at-check-all", function(){
            $(dt.rows({ page: "current" }).nodes()).find(".at-check").prop("checked", this.checked);
            update_bulk_bar();
        });

        $table.on("draw.dt", function(){
            $table.closest(".dataTables_wrapper").find(".at-check-all").prop("checked", false);
            update_bulk_bar();
        });

        $table.on("xhr.dt", function(){
            // Fresh rows: nothing is selected any more
            setTimeout(function(){
                update_bulk_bar();
                if(options.onChange) options.onChange();
            }, 0);
        });

        if(options.bulkBar){
            $(options.bulkBar).on("click", "[data-bulk]", function(){
                var action = $(this).data("bulk");
                var ids = selected_ids();
                var words = { approve: ["Approve", "#28a745"], reject: ["Reject", "#dc3545"], delete: ["Delete", "#dc3545"] }[action];

                confirm_action(
                    words[0] + " " + ids.length + " selected " + (ids.length === 1 ? "activity" : "activities") + "?",
                    action === "delete" ? "This can't be undone." : "",
                    words[0],
                    words[1]
                ).then(function(result){
                    if(result.isConfirmed) run(action, ids);
                });
            });
        }

        /* ----- server calls ----- */

        function reload(){
            close_menu();
            dt.ajax.reload(null, false);
        }

        function finished(resp, success_text){
            if($.trim(resp) === "1"){
                Swal.fire({ icon: "success", title: success_text, timer: 1500, showConfirmButton: false });
            }else{
                Swal.fire({ icon: "warning", title: "Not everything was done", text: resp });
            }
            reload();
        }

        function run(action, ids){
            var done = { approve: "Approved", reject: "Rejected", delete: "Deleted" }[action];
            $.post("ajax.php?action=bulk_activity_action", { do: action, ids: ids })
                .done(function(resp){ finished(resp, done); })
                .fail(function(){ Swal.fire({ icon: "error", title: "Server error", text: "Please try again." }); });
        }

        function request_revision(row){
            Swal.fire({
                title: "Needs Revision",
                text: "Tell " + row.implementer + " what to change in \"" + row.title + "\". They can edit and resubmit it.",
                input: "textarea",
                inputPlaceholder: "e.g. Please attach the program flow and budget.",
                showCancelButton: true,
                confirmButtonText: "Send back",
                confirmButtonColor: "#fd7e14",
                inputValidator: function(value){
                    if(!$.trim(value)) return "Please write a note for the coordinator.";
                }
            }).then(function(result){
                if(!result.isConfirmed) return;
                $.post("ajax.php?action=set_activity_revision", { id: row.id, note: result.value })
                    .done(function(resp){ finished(resp, "Sent back for revision"); });
            });
        }

        /* ----- ⋮ menu actions ----- */

        function handle(action, row){
            switch(action){
                case "view":
                    view_popup(row);
                    break;
                case "edit":
                    options.onEdit(row);
                    break;
                case "revision":
                    request_revision(row);
                    break;
                case "qr":
                    window.open("faculty/generate_qr.php?id=" + row.id, "_blank");
                    break;
                case "approve":
                case "reject":
                case "delete":
                    var words = { approve: ["Approve", "#28a745", ""], reject: ["Reject", "#dc3545", ""], delete: ["Delete", "#dc3545", "This can't be undone."] }[action];
                    confirm_action(words[0] + " \"" + row.title + "\"?", words[2], words[0], words[1]).then(function(result){
                        if(result.isConfirmed) run(action, [row.id]);
                    });
                    break;
            }
        }

        $table.on("click", ".at-menu-btn", function(e){
            e.stopPropagation();
            var row = dt.row($(this).closest("tr")).data();
            if($menu && $menu.data("for") === row.id){
                close_menu();
                return;
            }
            open_menu(this, row, options, handle);
            $menu.data("for", row.id);
        });

        return { reload: reload, dt: dt };
    }

    return { create: create, escape: esc };

})(jQuery);
