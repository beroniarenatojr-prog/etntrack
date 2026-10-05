<?php
session_start();

// Older upload endpoint, no longer used. Reports are now submitted on the
// Reports page (ajax.php?action=report_save), where each one must belong to a
// project and is checked before it is saved. This refuses every request so
// nothing can be uploaded here without a project.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');

echo empty($_SESSION['login_id'])
    ? "Your session has expired. Please log in again."
    : "Reports are now submitted on the Reports page, under the project they belong to.";
