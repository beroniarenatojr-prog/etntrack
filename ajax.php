<?php
ob_start();
date_default_timezone_set("Asia/Manila");

/*
|--------------------------------------------------------------------------
| AJAX ENTRY POINT
|--------------------------------------------------------------------------
| Every request from the pages comes through here as ajax.php?action=<name>.
|
| $actions lists each allowed action as: 'action' => array(who, method)
|
|   who = 'public'      anyone, even signed out (only the login form)
|         'any'         any signed-in user (the method itself checks ownership)
|         'admin'       administrators only
|         'coordinator' extension coordinators only
|
| An action that is NOT in this list is refused, so a new action has to be
| added here on purpose before it can be used. Actions that no page calls
| are left out deliberately (login2, logout2, signup, update_activity_status,
| save_criteria_question, get_class, get_report).
*/

$actions = array(

    // Sign in / out
    'login'                  => array('public', 'login'),
    'logout'                 => array('any',    'logout'),

    // Activities
    'activity_counts'        => array('any',         'activity_counts'),
    'activity_table'         => array('any',         'activity_table'),
    'check_activity_conflict'=> array('any',         'check_activity_conflict'),
    'calendar_events'        => array('any',         'calendar_events'),
    'calendar_ics'           => array('any',         'calendar_ics'),
    'bulk_activity_action'   => array('any',         'bulk_activity_action'),
    'save_activity'          => array('coordinator', 'save_activity'),
    'update_activity'        => array('coordinator', 'update_activity'),
    'set_activity_revision'  => array('admin',       'set_activity_revision'),

    // Reports
    'report_counts'          => array('any',         'report_counts'),
    'delete_report'          => array('any',         'delete_report'),
    'list_reports'           => array('coordinator', 'list_reports'),
    'upload_report'          => array('coordinator', 'upload_report'),
    'list_reportsadmin'      => array('admin',       'list_reportsadmin'),
    'approve_reportadmin'    => array('admin',       'approve_report'),
    'reject_reportadmin'     => array('admin',       'reject_report'),

    // Own profile
    'update_user'            => array('any', 'update_user'),

    // Accounts
    'save_user'              => array('admin', 'save_user'),
    'delete_user'            => array('admin', 'delete_user'),
    'save_faculty'           => array('admin', 'save_faculty'),
    'delete_faculty'         => array('admin', 'delete_faculty'),

    // Questionnaire setup
    'save_criteria'          => array('admin', 'save_criteria'),
    'delete_criteria'        => array('admin', 'delete_criteria'),
    'save_criteria_order'    => array('admin', 'save_criteria_order'),
    'save_question'          => array('admin', 'save_question'),
    'delete_question'        => array('admin', 'delete_question'),
    'save_question_order'    => array('admin', 'save_question_order'),

    // Pages kept from the original template; not in the menu, admin only
    'make_default'           => array('admin', 'make_default'),
    'save_academic'          => array('admin', 'save_academic'),
    'delete_academic'        => array('admin', 'delete_academic'),
    'save_class'             => array('admin', 'save_class'),
    'delete_class'           => array('admin', 'delete_class'),
    'save_subject'           => array('admin', 'save_subject'),
    'delete_subject'         => array('admin', 'delete_subject'),
    'save_student'           => array('admin', 'save_student'),
    'delete_student'         => array('admin', 'delete_student'),
    'save_restriction'       => array('admin', 'save_restriction'),
    'save_evaluation'        => array('admin', 'save_evaluation')
);

$action = isset($_GET['action']) ? (string)$_GET['action'] : '';

if(!isset($actions[$action])){
    http_response_code(404);
    echo "Unknown action.";
    ob_end_flush();
    exit;
}

list($who, $method) = $actions[$action];

include 'admin_class.php';   // starts the session and defines the Action class

$login_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
$login_type = isset($_SESSION['login_type']) ? (int)$_SESSION['login_type'] : 0;   // 1 = administrator, 2 = coordinator

if($who !== 'public'){

    if($login_id <= 0){
        http_response_code(401);
        echo "Your session has expired. Please log in again.";
        ob_end_flush();
        exit;
    }

    if(($who === 'admin' && $login_type !== 1) || ($who === 'coordinator' && $login_type !== 2)){
        http_response_code(403);
        echo "You are not allowed to do that.";
        ob_end_flush();
        exit;
    }
}

$crud = new Action();
$result = $crud->$method();

if($result){
    echo $result;
}

ob_end_flush();
?>
