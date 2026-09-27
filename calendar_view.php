<?php
/*
|--------------------------------------------------------------------------
| ACTIVITY CALENDAR (shared page)
| Included by admin/calendar.php and faculty/calendar.php. Shows every
| activity as an all-day booking: green = approved, yellow = pending,
| red = rejected or a conflict. Data comes from ajax.php?action=calendar_events.
|--------------------------------------------------------------------------
*/
if(empty($_SESSION['login_id'])){
    header('Location: login.php');
    exit;
}
?>

<link rel="stylesheet" href="assets/plugins/fullcalendar/main.min.css">

<style>

.calendar-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.calendar-header{
    display:flex;
    flex-wrap:wrap;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    padding:20px 25px;
    background:linear-gradient(135deg,#198754,#157347);
    color:#fff;
}

.calendar-header h4{
    margin:0;
    font-weight:700;
}

.calendar-header p{
    margin:4px 0 0;
    opacity:.85;
    font-size:13px;
}

.btn-ics{
    background:#fff;
    color:#198754;
    font-weight:600;
    border-radius:10px;
    padding:10px 18px;
}

.btn-ics:hover{
    background:#e9f8ef;
    color:#146c43;
}

.calendar-legend{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:10px 20px;
    padding:14px 25px;
    border-bottom:1px solid #edf1f7;
    background:#fafcfb;
    font-size:13px;
    font-weight:600;
    color:#495057;
}

.legend-dot{
    display:inline-block;
    width:12px;
    height:12px;
    border-radius:50%;
    margin-right:6px;
    vertical-align:-1px;
}

.legend-dot.approved{ background:#28a745; }
.legend-dot.pending{ background:#ffc107; }
.legend-dot.revision{ background:#fd7e14; }
.legend-dot.rejected{ background:#dc3545; }

.calendar-legend .import-help{
    margin-left:auto;
    font-weight:400;
    color:#8a939c;
    font-size:12px;
}

.calendar-card .card-body{
    padding:20px 25px;
}

/* FullCalendar in the site's green */

.fc .fc-button-primary{
    background:#198754;
    border-color:#198754;
}

.fc .fc-button-primary:hover{
    background:#157347;
    border-color:#157347;
}

.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active{
    background:#146c43;
    border-color:#146c43;
}

.fc .fc-toolbar-title{
    font-size:20px;
    font-weight:700;
}

.fc-event{
    cursor:pointer;
}

/* Event details popup */

.calendar-details{
    text-align:left;
    font-size:14px;
}

.calendar-details p{
    margin:0 0 8px;
}

.calendar-details .detail-status{
    display:inline-block;
    padding:2px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:700;
}

.detail-status.approved{ background:#d1f7dd; color:#198754; }
.detail-status.pending{ background:#fff3cd; color:#b45309; }
.detail-status.revision{ background:#ffe5d0; color:#c2410c; }
.detail-status.rejected{ background:#fde2e2; color:#dc3545; }

.calendar-details .detail-conflict{
    margin-bottom:12px;
    padding:8px 12px;
    border-radius:8px;
    background:#fde2e2;
    color:#dc3545;
    font-weight:600;
}

@media(max-width:768px){

    .calendar-legend .import-help{
        margin-left:0;
    }

    .fc .fc-toolbar{
        flex-direction:column;
        gap:10px;
    }

}

</style>

<div class="container-fluid">

    <div class="card calendar-card">

        <div class="calendar-header">

            <div>
                <h4><i class="fas fa-calendar-alt mr-2"></i>Activity Calendar</h4>
                <p>Every submitted activity books its venue for the whole day.</p>
            </div>

            <a href="ajax.php?action=calendar_ics" class="btn btn-ics">
                <i class="fas fa-download mr-1"></i> Download .ics
            </a>

        </div>

        <div class="calendar-legend">

            <span><i class="legend-dot approved"></i>Approved</span>
            <span><i class="legend-dot pending"></i>Pending</span>
            <span><i class="legend-dot revision"></i>Needs Revision</span>
            <span><i class="legend-dot rejected"></i>Rejected / Conflict</span>

            <span class="import-help">
                Import the .ics file in Google Calendar (Settings &rsaquo; Import &amp; export)
                or Outlook (File &rsaquo; Open &amp; Export &rsaquo; Import/Export).
            </span>

        </div>

        <div class="card-body">
            <div id="activity-calendar"></div>
        </div>

    </div>

</div>

<script src="assets/plugins/fullcalendar/main.min.js"></script>

<script>

$(function(){

    var calendar = new FullCalendar.Calendar(document.getElementById("activity-calendar"), {

        initialView: "dayGridMonth",
        height: "auto",
        dayMaxEvents: 3,

        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,listMonth"
        },

        buttonText: {
            today: "Today",
            month: "Month",
            list: "List"
        },

        noEventsContent: "No activities this month",

        events: {
            url: "ajax.php?action=calendar_events",
            failure: function(){
                Swal.fire({
                    icon: "error",
                    title: "Could not load the calendar",
                    text: "Please refresh the page and try again."
                });
            }
        },

        eventClick: function(info){
            show_event_details(info.event);
        }

    });

    calendar.render();

    // The calendar only resizes with the window, so redraw it when the sidebar opens or closes
    $(document).on("collapsed.lte.pushmenu shown.lte.pushmenu", function(){
        setTimeout(function(){ calendar.updateSize(); }, 300);
    });

});

// Escapes text before it goes into the popup's HTML
function calendar_escape(text){
    return $("<div>").text(text == null ? "" : text).html();
}

function show_event_details(event){

    var p = event.extendedProps;

    var date = event.start.toLocaleDateString("en-US", {
        month: "long",
        day: "numeric",
        year: "numeric"
    });

    var html =
        '<div class="calendar-details">' +
            (p.conflict
                ? '<div class="detail-conflict"><i class="fas fa-exclamation-triangle mr-1"></i>' +
                  'Another activity is booked at this venue on the same day.</div>'
                : '') +
            '<p><b>Status:</b> <span class="detail-status ' + calendar_escape(p.status_key) + '">' + calendar_escape(p.status) + '</span></p>' +
            '<p><b>Date:</b> ' + calendar_escape(date) + '</p>' +
            '<p><b>Venue:</b> ' + calendar_escape(p.venue || "No venue") + '</p>' +
            '<p><b>Coordinator:</b> ' + calendar_escape(p.coordinator) + '</p>' +
            '<p><b>Purpose:</b> ' + calendar_escape(p.purpose || "No purpose given") + '</p>' +
        '</div>';

    Swal.fire({
        titleText: p.name,
        html: html,
        confirmButtonText: "Close",
        confirmButtonColor: "#198754"
    });

}

</script>
