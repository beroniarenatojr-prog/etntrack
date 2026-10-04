<?php
/*
| SYSTEM UPDATE
| Shows which database updates have been applied and runs the ones that have
| not. Updates only add tables, columns and data; nothing is deleted.
*/

if(($_SESSION['login_type'] ?? 0) != 1){
    echo '<div class="alert alert-danger m-4">This page is for administrators only.</div>';
    return;
}

include 'db_connect.php';

define('MIGRATOR_INCLUDED', true);   // keeps migrate.php from running on its own
require_once 'migrate.php';

$migrator = new Migrator($conn);
$applied = array();
$errors = array();

if(isset($_POST['run_updates'])){
    list($applied, $errors) = $migrator->migrate();
}

$pending = $migrator->pending();

$history = array();
$qry = $conn->query("SELECT filename, applied_at FROM schema_migrations ORDER BY id");
while($row = $qry->fetch_assoc()){
    $history[] = $row;
}

// Turns 002_activity_academic_year.php into "Activity academic year"
function update_title($filename){
    $name = preg_replace('/^\d+_|\.php$/', '', $filename);
    return ucfirst(str_replace('_', ' ', $name));
}
?>

<div class="su-wrap">

    <div class="su-header">
        <div class="su-header-icon"><i class="fas fa-database"></i></div>
        <div>
            <h1>System Update</h1>
            <p>Apply database updates that come with a new version of ExtenTrack.</p>
        </div>
    </div>


    <?php if($errors): ?>
        <div class="alert alert-danger">
            <b><i class="fas fa-exclamation-triangle mr-2"></i>An update could not be applied.</b>
            <?php foreach($errors as $error): ?>
                <div class="mt-2"><?php echo htmlspecialchars(update_title($error['file'])); ?>: <?php echo htmlspecialchars($error['message']); ?></div>
            <?php endforeach; ?>
            <div class="mt-2">Nothing after it was applied. Your existing data has not been changed.</div>
        </div>
    <?php endif; ?>


    <?php if($applied): ?>
        <div class="alert alert-success">
            <b><i class="fas fa-check-circle mr-2"></i><?php echo count($applied); ?> update<?php echo count($applied) == 1 ? '' : 's'; ?> applied.</b>
            <?php foreach($applied as $step): ?>
                <div class="mt-2">
                    <?php echo htmlspecialchars(update_title($step['file'])); ?>
                    <ul class="mb-0">
                        <?php foreach($step['details'] as $line): ?>
                            <li><?php echo htmlspecialchars($line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <div class="su-card">

        <?php if($pending): ?>

            <div class="su-status su-status-pending">
                <i class="fas fa-clock"></i>
                <div>
                    <b><?php echo count($pending); ?> update<?php echo count($pending) == 1 ? '' : 's'; ?> waiting to be applied</b>
                    <span>Your database needs these before the newest features work.</span>
                </div>
            </div>

            <ul class="su-list">
                <?php foreach($pending as $file): ?>
                    <li>
                        <i class="far fa-circle"></i>
                        <?php echo htmlspecialchars(update_title(basename($file))); ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form method="post" id="su-form">
                <input type="hidden" name="run_updates" value="1">
                <button type="submit" class="btn su-btn">
                    <i class="fas fa-play mr-2"></i>
                    Apply <?php echo count($pending); ?> update<?php echo count($pending) == 1 ? '' : 's'; ?>
                </button>
            </form>

            <p class="su-note">
                <i class="fas fa-shield-alt"></i>
                Updates only add new tables and columns. No existing record is deleted or emptied.
            </p>

        <?php else: ?>

            <div class="su-status su-status-ok">
                <i class="fas fa-check-circle"></i>
                <div>
                    <b>Your database is up to date</b>
                    <span>There is nothing to apply.</span>
                </div>
            </div>

        <?php endif; ?>

    </div>


    <?php if($history): ?>
        <div class="su-card">
            <h2 class="su-subtitle">Already applied</h2>
            <table class="su-table">
                <thead>
                    <tr>
                        <th>Update</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(update_title($row['filename'])); ?></td>
                            <td><?php echo date('M d, Y g:i A', strtotime($row['applied_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>


<style>
.su-wrap {
    max-width: 900px;
    padding: 4px 0 40px;
}

.su-header {
    display: flex;
    align-items: center;
    gap: 18px;
    background: linear-gradient(135deg, #1d5b42 0%, #2f7b64 100%);
    color: #fff;
    border-radius: 18px;
    padding: 24px 28px;
    margin-bottom: 22px;
}

.su-header-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.su-header h1 {
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 4px;
}

.su-header p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.su-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px 26px;
    margin-bottom: 20px;
    box-shadow: 0 10px 30px rgba(17, 56, 97, 0.07);
}

.su-status {
    display: flex;
    align-items: center;
    gap: 14px;
    border-radius: 12px;
    padding: 16px 18px;
}

.su-status i {
    font-size: 24px;
}

.su-status div {
    display: flex;
    flex-direction: column;
}

.su-status span {
    font-size: 14px;
    color: #5f6f83;
}

.su-status-ok {
    background: #ecfdf5;
    color: #1d5b42;
}

.su-status-pending {
    background: #fff8e6;
    color: #a16207;
}

.su-list {
    list-style: none;
    padding: 0;
    margin: 18px 0 22px;
}

.su-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 2px;
    border-bottom: 1px solid #eef2f7;
    color: #344a6e;
    font-size: 15px;
}

.su-list li:last-child {
    border-bottom: none;
}

.su-list i {
    color: #a16207;
}

.su-btn {
    background: #1d5b42;
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 13px 24px;
    font-weight: 700;
}

.su-btn:hover {
    color: #fff;
    filter: brightness(1.08);
}

.su-note {
    margin: 16px 0 0;
    font-size: 13px;
    color: #5f6f83;
    display: flex;
    align-items: center;
    gap: 8px;
}

.su-subtitle {
    font-size: 17px;
    font-weight: 700;
    color: #102a43;
    margin: 0 0 14px;
}

.su-table {
    width: 100%;
    border-collapse: collapse;
}

.su-table th {
    text-align: left;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #7b8a9c;
    padding: 0 0 10px;
    border-bottom: 1px solid #eef2f7;
}

.su-table td {
    padding: 12px 0;
    border-bottom: 1px solid #f4f7fb;
    color: #344a6e;
    font-size: 15px;
}

.su-table tr:last-child td {
    border-bottom: none;
}

@media (max-width: 640px) {
    .su-header {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px;
    }

    .su-card {
        padding: 20px 18px;
    }
}
</style>


<script>
$(function(){

    $('#su-form').on('submit', function(){

        $(this).find('button')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin mr-2"></i> Applying...');

    });

});
</script>
