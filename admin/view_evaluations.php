<?php

/*
| The old page for one evaluation. It was opened directly at
| admin/view_evaluations.php, where it could not reach the database. Responses
| now open inside the admin pages (index.php?page=view_response&id=N), so old
| links are sent there.
*/

$target = 'index.php?page=view_response&id='.(int)($_GET['evaluation_id'] ?? 0);

if (!headers_sent()) {
    $from_admin_folder = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin';
    header('Location: '.($from_admin_folder ? '../' : '').$target);
    exit;
}
?>
<script>location.href = <?php echo json_encode($target); ?>;</script>
