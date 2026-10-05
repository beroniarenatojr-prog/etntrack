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

    'activity_assign_project'=> array('any',         'activity_assign_project'),
    'activity_add_photos'    => array('coordinator', 'activity_add_photos'),
    'activity_remove_photo'  => array('coordinator', 'activity_remove_photo'),

    // Reports, each belonging to a project (report_actions.php). The ones that
    // change something also need the page's security token (see below).
    'report_list'            => array('any',         'report_list'),
    'report_summary'         => array('any',         'report_summary'),
    'report_save'            => array('coordinator', 'report_save'),
    'report_review'          => array('admin',       'report_review'),
    'report_delete'          => array('any',         'report_delete'),
    'report_assign'          => array('any',         'report_assign'),
    'impact_list'            => array('any',         'impact_list'),

    // Projects and their pre-activity documents. Each method checks that the
    // project belongs to the caller before it changes anything.
    'project_list'           => array('any',   'project_list'),
    'project_detail'         => array('any',   'project_detail'),
    'project_save'           => array('any',   'project_save'),
    'project_delete'         => array('any',   'project_delete'),
    'project_set_status'     => array('admin', 'project_set_status'),
    'coordinator_options'    => array('any',   'coordinator_options'),
    'project_options'        => array('any',   'project_options'),
    'doc_save'               => array('any',   'doc_save'),
    'doc_delete'             => array('any',   'doc_delete'),
    'doc_review'             => array('admin', 'doc_review'),
    'designation_save'       => array('any',   'designation_save'),
    'designation_delete'     => array('any',   'designation_delete'),

    // Questionnaires (building, publishing, closing). Every one of these except
    // qn_get also needs the page's security token (see below).
    'qn_get'                 => array('admin', 'qn_get'),
    'qn_save'                => array('admin', 'qn_save'),
    'qn_delete'              => array('admin', 'qn_delete'),
    'qn_section_save'        => array('admin', 'qn_section_save'),
    'qn_section_delete'      => array('admin', 'qn_section_delete'),
    'qn_section_from_criteria' => array('admin', 'qn_section_from_criteria'),
    'qn_question_save'       => array('admin', 'qn_question_save'),
    'qn_question_delete'     => array('admin', 'qn_question_delete'),
    'qn_question_duplicate'  => array('admin', 'qn_question_duplicate'),
    'qn_layout_save'         => array('admin', 'qn_layout_save'),
    'qn_status'              => array('admin', 'qn_status'),
    'qn_template_save'       => array('admin', 'qn_template_save'),
    'qn_template_delete'     => array('admin', 'qn_template_delete'),
    'qn_template_order'      => array('admin', 'qn_template_order'),

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

/*
| Actions that change a questionnaire, a report, which project an activity
| belongs to or its documentation photos must also carry the security token the page was given
| (footer.php sends it with every request). A page on another website cannot
| read that token, so it cannot make a signed-in user's browser change
| anything here.
*/
$token_actions = array('report_save', 'report_review', 'report_delete', 'report_assign', 'activity_assign_project',
	'activity_add_photos', 'activity_remove_photo');

if((strpos($action, 'qn_') === 0 && $action !== 'qn_get') || in_array($action, $token_actions, true)){

    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');

    if(empty($_SESSION['csrf_token']) || !is_string($sent) || !hash_equals($_SESSION['csrf_token'], $sent)){
        // 403, a standard code: Apache turns unknown ones such as 419 into a 500
        http_response_code(403);
        echo json_encode(array(
            'ok' => false,
            'error' => 'This page has expired. Please reload it and try again.'
        ));
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
