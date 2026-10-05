<?php
session_start();

// Show PHP errors while developing on XAMPP, but never to visitors of the live
// site, where an error message would reveal database details.
$on_localhost = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', array('localhost', '127.0.0.1', '::1'));
ini_set('display_errors', $on_localhost ? '1' : '0');
ini_set('log_errors', '1');

require_once __DIR__.'/project_actions.php';
require_once __DIR__.'/questionnaire_actions.php';
require_once __DIR__.'/report_actions.php';

Class Action {

	use ProjectActions;         // projects and their pre-activity documents
	use QuestionnaireActions;   // building, publishing and closing questionnaires
	use ReportActions;          // Progress and Terminal Reports, each belonging to a project

	/*
	| Records who did what, and when, in audit_log. A failure here never stops
	| the action itself (for example, before the database update adds the table).
	*/
	protected function audit($action, $entity, $entity_id = null, $details = ''){
		try {
			$user = (int)($_SESSION['login_id'] ?? 0) ?: null;
			$type = (int)($_SESSION['login_type'] ?? 0) ?: null;
			$entity_id = $entity_id !== null ? (int)$entity_id : null;
			$details = mb_substr((string)$details, 0, 2000);

			$stmt = $this->db->prepare("
				INSERT INTO audit_log (user_id, user_type, action, entity, entity_id, details)
				VALUES (?, ?, ?, ?, ?, ?)
			");
			$stmt->bind_param('iissis', $user, $type, $action, $entity, $entity_id, $details);
			$stmt->execute();
		} catch (Throwable $e) {
			error_log('audit: '.$e->getMessage());
		}
	}

	private $db;

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){

		$type = array("","users","faculty_list","student_list");
		$type2 = array("","admin","faculty","student");

		$login = isset($_POST['login']) ? (int)$_POST['login'] : 0;
		$email = trim($_POST['email'] ?? '');
		$password = (string)($_POST['password'] ?? '');

		if(!isset($type[$login]) || $login < 1 || $email === '' || $password === ''){
			return 2;
		}

		$stmt = $this->db->prepare("SELECT *, concat(firstname,' ',lastname) as name FROM {$type[$login]} WHERE email = ? LIMIT 1");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$qry = $stmt->get_result();

		$row = $qry->num_rows > 0 ? $qry->fetch_assoc() : null;

		// Accounts made before this change hold an MD5 hash; those still work and
		// are re-saved in the modern format the first time the owner signs in.
		$stored = $row ? (string)$row['password'] : '';
		$is_old = $stored !== '' && strlen($stored) === 32 && ctype_xdigit($stored);
		$ok = $row && ($is_old ? hash_equals($stored, md5($password)) : password_verify($password, $stored));

		if($ok && $is_old){
			$new = password_hash($password, PASSWORD_DEFAULT);
			$update = $this->db->prepare("UPDATE {$type[$login]} SET password = ? WHERE id = ?");
			$update->bind_param('si', $new, $row['id']);
			$update->execute();
		}

		if($ok){
			foreach ($row as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
					$_SESSION['login_type'] = $login;
					$_SESSION['login_view_folder'] = $type2[$login].'/';
					session_regenerate_id(true);   // a new session id on sign-in
		$academic = $this->db->query("SELECT * FROM academic_list where is_default = 1 ");
		if($academic->num_rows > 0){
			foreach($academic->fetch_array() as $k => $v){
				if(!is_numeric($k))
					$_SESSION['academic'][$k] = $v;
			}
		}
				return 1;
		}else{
			return 2;
		}
	}
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	function login2(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM students where student_code = '".$student_code."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['rs_'.$key] = $value;
			}
				return 1;
		}else{
			return 3;
		}
	}
	function save_user(){

		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$firstname = trim($_POST['firstname'] ?? '');
		$lastname = trim($_POST['lastname'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = (string)($_POST['password'] ?? '');

		if($firstname === '' || $lastname === '' || $email === ''){
			return "Please fill in the name and email.";
		}

		$data = " firstname = '".$this->db->real_escape_string($firstname)."'"
			.", lastname = '".$this->db->real_escape_string($lastname)."'"
			.", email = '".$this->db->real_escape_string($email)."'";

		if($password !== ''){
			$data .= ", password = '".password_hash($password, PASSWORD_DEFAULT)."'";
		}
		$check = $this->db->query("SELECT id FROM users WHERE email = '".$this->db->real_escape_string($email)."'".($id ? " AND id != $id" : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$verified = $this->require_email_otp('user', $email);
			if($verified !== true){
				return $verified;
			}
		}
		if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name'])){

			list($fname, $upload_error) = $this->store_avatar($_FILES['img']);

			if($upload_error){
				return $upload_error;
			}

			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}

	// New accounts must prove they own their email: the first save emails a
	// 6-digit code, the next save has to include that code as `otp`.
	// Returns true once verified, otherwise the response for the page.
	private function require_email_otp($scope, $email){
		if(!isset($_SESSION['login_id']) || ($_SESSION['login_type'] ?? '') != 1){
			return 'unauthorized';
		}
		$email = strtolower(trim($email));
		$otp = trim($_POST['otp'] ?? '');
		$pending = $_SESSION['account_otp'][$scope] ?? null;

		if($otp === ''){
			return $this->send_email_otp($scope, $email, $pending);
		}
		if(!$pending || $pending['email'] !== $email || time() > $pending['expires']){
			unset($_SESSION['account_otp'][$scope]);
			return 'otp_expired';
		}
		if(!password_verify($otp, $pending['hash'])){
			$_SESSION['account_otp'][$scope]['attempts']++;
			if($_SESSION['account_otp'][$scope]['attempts'] >= 5){
				unset($_SESSION['account_otp'][$scope]);
				return 'otp_locked';
			}
			return 'otp_invalid';
		}
		unset($_SESSION['account_otp'][$scope]);
		return true;
	}

	private function send_email_otp($scope, $email, $pending){
		// Within the resend cooldown the code already sent stays valid
		if($pending && $pending['email'] === $email && time() - $pending['sent_at'] < 60){
			return 'otp_wait|'.(60 - (time() - $pending['sent_at']));
		}
		$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

		$html = "<div style=\"font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px\">"
			."<h2 style=\"color:#1d5b42;margin:0 0 12px\">ExtenTrack Analytics</h2>"
			."<p>Your email verification code is:</p>"
			."<p style=\"font-size:32px;font-weight:bold;letter-spacing:8px;color:#1d5b42;margin:16px 0\">$code</p>"
			."<p>Give this code to the ISU Ilagan Extension Office administrator to finish creating your account. It expires in 10 minutes.</p>"
			."<p style=\"color:#6b7280;font-size:13px\">If you did not expect this email, you can ignore it.</p>"
			."</div>";
		$text = "Your ExtenTrack verification code is $code.\n\n"
			."Give this code to the ISU Ilagan Extension Office administrator to finish creating your account. It expires in 10 minutes.\n\n"
			."If you did not expect this email, you can ignore it.";

		require_once __DIR__.'/mailer.php';
		$sent = send_mail($email, 'Your ExtenTrack verification code', $html, $text);
		if($sent !== true){
			return 'mail_error|'.$sent;
		}

		$_SESSION['account_otp'][$scope] = array(
			'email' => $email,
			'hash' => password_hash($code, PASSWORD_DEFAULT),
			'expires' => time() + 600,
			'sent_at' => time(),
			'attempts' => 0
		);
		return 'otp_sent';
	}

	function signup(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
				if($k =='password'){
					if(empty($v))
						continue;
					$v = md5($v);

				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}

		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");

		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			if(empty($id))
				$id = $this->db->insert_id;
			foreach ($_POST as $key => $value) {
				if(!in_array($key, array('id','cpass','password')) && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
					$_SESSION['login_id'] = $id;
				if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}

	// Saves the signed-in user's own profile. The row is always the one in the
	// session, never an id sent by the browser, so nobody can edit another account.
	function update_user(){

		$tables = array(1 => 'users', 2 => 'faculty_list', 3 => 'student_list');
		$type = (int)$_SESSION['login_type'];

		if(!isset($tables[$type]) || empty($_SESSION['login_id'])){
			return "Please log in again.";
		}

		$table = $tables[$type];
		$id = (int)$_SESSION['login_id'];

		$firstname = trim($_POST['firstname'] ?? '');
		$lastname = trim($_POST['lastname'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = (string)($_POST['password'] ?? '');

		if($firstname === '' || $lastname === '' || $email === ''){
			return "Please fill in your name and email.";
		}

		$check = $this->db->query("SELECT id FROM $table WHERE email = '".$this->db->real_escape_string($email)."' AND id != $id")->num_rows;

		if($check > 0){
			return 2;
		}

		$data = " firstname = '".$this->db->real_escape_string($firstname)."'"
			.", lastname = '".$this->db->real_escape_string($lastname)."'"
			.", email = '".$this->db->real_escape_string($email)."'";

		$avatar = '';

		if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name'])){

			list($avatar, $error) = $this->store_avatar($_FILES['img']);

			if($error){
				return $error;
			}

			$data .= ", avatar = '$avatar'";
		}

		if($password !== ''){
			$data .= ", password = '".password_hash($password, PASSWORD_DEFAULT)."'";
		}

		$save = $this->db->query("UPDATE $table SET $data WHERE id = $id");

		if($save){
			$_SESSION['login_firstname'] = $firstname;
			$_SESSION['login_lastname'] = $lastname;
			$_SESSION['login_email'] = $email;
			$_SESSION['login_name'] = $firstname.' '.$lastname;
			if($avatar !== ''){
				$_SESSION['login_avatar'] = $avatar;
			}
			return 1;
		}

		return "Could not save your profile. Please try again.";
	}

	/*
	|--------------------------------------------------------------------------
	| PROFILE PICTURE
	| Checks the file's actual contents (a renamed script is refused), gives it
	| a random name and stores it. Returns array(filename, error message).
	|--------------------------------------------------------------------------
	*/
	private function store_avatar($file){

		if(!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])){
			return array('', "The picture could not be uploaded.");
		}

		if($file['size'] > 5242880){
			return array('', "The picture is larger than 5 MB.");
		}

		$types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp');
		$info = @getimagesize($file['tmp_name']);

		if(!$info || !isset($types[$info[2]])){
			return array('', "The picture must be a JPG, PNG, GIF or WEBP file.");
		}

		if(!is_dir('assets/uploads')){
			mkdir('assets/uploads', 0755, true);
		}

		$name = time().'_'.bin2hex(random_bytes(6)).'.'.$types[$info[2]];

		if(!move_uploaded_file($file['tmp_name'], 'assets/uploads/'.$name)){
			return array('', "The picture could not be saved.");
		}

		return array($name, '');
	}
	function delete_user(){
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id <= 0){
			return "User not found.";
		}
		if($id === (int)$_SESSION['login_id']){
			return "You cannot delete the account you are signed in with.";
		}
		$delete = $this->db->query("DELETE FROM users WHERE id = $id");
		if($delete)
			return 1;
	}
	function save_system_settings(){
		extract($_POST);
		$data = '';
		foreach($_POST as $k => $v){
			if(!is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if($_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", cover_img = '$fname' ";

		}
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set $data");
		}
		if($save){
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if($_FILES['cover']['tmp_name'] != ''){
				$_SESSION['system']['cover_img'] = $fname;
			}
			return 1;
		}
	}
	function save_image(){
		extract($_FILES['file']);
		if(!empty($tmp_name)){
			$fname = strtotime(date("Y-m-d H:i"))."_".(str_replace(" ","-",$name));
			$move = move_uploaded_file($tmp_name,'assets/uploads/'. $fname);
			$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
			$hostName = $_SERVER['HTTP_HOST'];
			$path =explode('/',$_SERVER['PHP_SELF']);
			$currentPath = '/'.$path[1]; 
			if($move){
				return $protocol.'://'.$hostName.$currentPath.'/assets/uploads/'.$fname;
			}
		}
	}
	function save_subject(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','user_ids')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM subject_list where code = '$code' and id != '{$id}' ")->num_rows;
		if($chk > 0){
			return 2;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO subject_list set $data");
		}else{
			$save = $this->db->query("UPDATE subject_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_subject(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM subject_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_class(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','user_ids')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM class_list where (".str_replace(",",'and',$data).") and id != '{$id}' ")->num_rows;
		if($chk > 0){
			return 2;
		}
		if(isset($user_ids)){
			$data .= ", user_ids='".implode(',',$user_ids)."' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO class_list set $data");
		}else{
			$save = $this->db->query("UPDATE class_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_class(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM class_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_academic(){

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $year = trim($_POST['year'] ?? '');
    $semester = isset($_POST['semester']) ? (int)$_POST['semester'] : 0;

    $status = isset($_POST['status'])
        ? (int)$_POST['status']
        : 0;

    $is_default = isset($_POST['is_default'])
        ? (int)$_POST['is_default']
        : 0;


    /* =====================================================
       VALIDATION
    ===================================================== */

    if($year == '' || $semester <= 0){

        return 0;

    }


    $year = $this->db->real_escape_string($year);


    /* =====================================================
       CHECK DUPLICATE ACADEMIC YEAR + SEMESTER
    ===================================================== */

    $check = $this->db->query("
        SELECT id
        FROM academic_list
        WHERE year = '$year'
        AND semester = $semester
        AND id != $id
        LIMIT 1
    ");


    if(!$check){

        return "SQL Error: ".$this->db->error;

    }


    if($check->num_rows > 0){

        return 2;

    }


    /* =====================================================
       DEFAULT ACADEMIC YEAR
    ===================================================== */

    if($is_default == 1){

        // Remove default from all other academic years
        $unset = $this->db->query("
            UPDATE academic_list
            SET is_default = 0
        ");

        if(!$unset){

            return "SQL Error: ".$this->db->error;

        }

    }
    else{

        // If there is no default academic year,
        // automatically make this one default.

        $hasDefault = $this->db->query("
            SELECT id
            FROM academic_list
            WHERE is_default = 1
            LIMIT 1
        ");

        if(!$hasDefault){

            return "SQL Error: ".$this->db->error;

        }


        if($hasDefault->num_rows == 0){

            $is_default = 1;

        }

    }


    /* =====================================================
       UPDATE
    ===================================================== */

    if($id > 0){

        $save = $this->db->query("
            UPDATE academic_list
            SET
                year = '$year',
                semester = $semester,
                status = $status,
                is_default = $is_default
            WHERE id = $id
        ");

    }

    /* =====================================================
       INSERT
    ===================================================== */

    else{

        $save = $this->db->query("
            INSERT INTO academic_list
            (
                year,
                semester,
                status,
                is_default
            )
            VALUES
            (
                '$year',
                $semester,
                $status,
                $is_default
            )
        ");

    }


    /* =====================================================
       RESULT
    ===================================================== */

    if($save){

        // Update current session academic information
        if($is_default == 1){

            $academic = $this->db->query("
                SELECT *
                FROM academic_list
                WHERE id = ".($id > 0 ? $id : $this->db->insert_id)."
                LIMIT 1
            ");

            if($academic && $academic->num_rows > 0){

                foreach($academic->fetch_assoc() as $key => $value){

                    if(!is_numeric($key)){

                        $_SESSION['academic'][$key] = $value;

                    }

                }

            }

        }

        return 1;

    }


    return "SQL Error: ".$this->db->error;

}

/*
|--------------------------------------------------------------------------
| ACTIVITY FILTERS
| Extra WHERE conditions for the search box, status dropdown, date range
| and coordinator dropdown on the coordinator Activities page ("My
| Activities" and "All Activities" tabs). $prefix is the table alias.
|--------------------------------------------------------------------------
*/
private function activity_filters($src, $search_columns, $prefix = ''){

    $where = "";

    if(!empty($src['search'])){

        $search = $this->db->real_escape_string(trim($src['search']));

        $matches = array();

        foreach($search_columns as $column){
            $matches[] = "$column LIKE '%$search%'";
        }

        $where .= " AND (".implode(" OR ", $matches).")";
    }

    if(!empty($src['status']) && in_array($src['status'], array('approved','pending','rejected','revision'))){
        $where .= " AND {$prefix}status = '{$src['status']}'";
    }

    // Date range on the activity date (YYYY-MM-DD from the date pickers)
    if(!empty($src['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $src['date_from'])){
        $where .= " AND {$prefix}activity_date >= '{$src['date_from']}'";
    }

    if(!empty($src['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $src['date_to'])){
        $where .= " AND {$prefix}activity_date <= '{$src['date_to']}'";
    }

    if(!empty($src['coordinator'])){
        $where .= " AND {$prefix}faculty_id = ".(int)$src['coordinator'];
    }

    // A project, or "none" for activities that have no project yet
    if(isset($src['project']) && $src['project'] !== ''){
        $where .= $src['project'] === 'none'
            ? " AND {$prefix}project_id IS NULL"
            : " AND {$prefix}project_id = ".(int)$src['project'];
    }

    return $where;
}

/*
|--------------------------------------------------------------------------
| ACTIVITY COUNTS
| Stat card numbers for the coordinator Activities page: the logged-in
| coordinator's own activities ("mine") and everyone's ("all").
|--------------------------------------------------------------------------
*/
function activity_counts(){

    $faculty_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;

    $empty = array('total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'revision' => 0);

    $counts = array('mine' => $empty, 'all' => $empty);

    if(!$faculty_id){
        return json_encode($counts);
    }

    $qry = $this->db->query("
        SELECT status, faculty_id = $faculty_id AS mine, COUNT(*) AS total
        FROM activities
        GROUP BY status, mine
    ");

    while($row = $qry->fetch_assoc()){

        $status = strtolower($row['status']);

        $scopes = $row['mine'] ? array('all', 'mine') : array('all');

        foreach($scopes as $scope){

            $counts[$scope]['total'] += $row['total'];

            if(isset($counts[$scope][$status])){
                $counts[$scope][$status] += $row['total'];
            }
        }
    }

    return json_encode($counts);
}







	function delete_academic(){

    $id = isset($_POST['id'])
        ? (int)$_POST['id']
        : 0;


    if($id <= 0){

        return 0;

    }


    /* =====================================================
       CHECK IF RECORD EXISTS
    ===================================================== */

    $check = $this->db->query("
        SELECT *
        FROM academic_list
        WHERE id = $id
        LIMIT 1
    ");


    if(!$check){

        return "SQL Error: ".$this->db->error;

    }


    if($check->num_rows == 0){

        return 0;

    }


    /* =====================================================
       DELETE
    ===================================================== */

    $delete = $this->db->query("
        DELETE FROM academic_list
        WHERE id = $id
    ");


    if($delete){

        /*
         * If the deleted academic year was the current
         * session academic year, load another default.
         */

        if(
            isset($_SESSION['academic']['id']) &&
            (int)$_SESSION['academic']['id'] == $id
        ){

            unset($_SESSION['academic']);

            $newDefault = $this->db->query("
                SELECT *
                FROM academic_list
                WHERE is_default = 1
                LIMIT 1
            ");

            if($newDefault && $newDefault->num_rows > 0){

                foreach($newDefault->fetch_assoc() as $key => $value){

                    if(!is_numeric($key)){

                        $_SESSION['academic'][$key] = $value;

                    }

                }

            }

        }


        return 1;

    }


    /* =====================================================
       SHOW ACTUAL DATABASE ERROR
    ===================================================== */

    return "SQL Error: ".$this->db->error;

}


	function make_default(){

    $id = isset($_POST['id'])
        ? (int)$_POST['id']
        : 0;


    if($id <= 0){

        return 0;

    }


    /* =====================================================
       CHECK RECORD
    ===================================================== */

    $check = $this->db->query("
        SELECT *
        FROM academic_list
        WHERE id = $id
        LIMIT 1
    ");


    if(!$check){

        return "SQL Error: ".$this->db->error;

    }


    if($check->num_rows == 0){

        return 0;

    }


    /* =====================================================
       REMOVE CURRENT DEFAULT
    ===================================================== */

    $update = $this->db->query("
        UPDATE academic_list
        SET is_default = 0
    ");


    if(!$update){

        return "SQL Error: ".$this->db->error;

    }


    /* =====================================================
       SET NEW DEFAULT
    ===================================================== */

    $update1 = $this->db->query("
        UPDATE academic_list
        SET is_default = 1
        WHERE id = $id
    ");


    if(!$update1){

        return "SQL Error: ".$this->db->error;

    }


    /* =====================================================
       UPDATE SESSION
    ===================================================== */

    $qry = $this->db->query("
        SELECT *
        FROM academic_list
        WHERE id = $id
        LIMIT 1
    ");


    if($qry && $qry->num_rows > 0){

        foreach($qry->fetch_assoc() as $key => $value){

            if(!is_numeric($key)){

                $_SESSION['academic'][$key] = $value;

            }

        }

    }


    return 1;

}
	/*
	| EVALUATION CRITERIA
	| A criterion has a name, an optional description, and can be switched off
	| so new questionnaires no longer offer it. Its template questions are in
	| criteria_questions (questionnaire_actions.php).
	| Answers: 1 saved, 2 the name is already used, 0 not saved.
	*/
	function save_criteria(){

		$id = (int)($_POST['id'] ?? 0);
		$name = trim((string)($_POST['criteria'] ?? ''));

		if($name === ''){
			return 0;
		}

		$name = mb_substr($name, 0, 255);

		// The same name twice is refused, whatever the capital letters
		$stmt = $this->db->prepare("SELECT id FROM criteria_list WHERE LOWER(TRIM(criteria)) = LOWER(?) AND id <> ?");
		$stmt->bind_param('si', $name, $id);
		$stmt->execute();

		if($stmt->get_result()->num_rows > 0){
			return 2;
		}

		$values = array('criteria' => $name);

		// Description and on/off arrive with the questionnaire database update
		if($this->db->query("SHOW COLUMNS FROM criteria_list LIKE 'description'")->num_rows){
			$values['description'] = trim((string)($_POST['description'] ?? ''));
		}

		if(isset($_POST['is_active']) && $this->db->query("SHOW COLUMNS FROM criteria_list LIKE 'is_active'")->num_rows){
			$values['is_active'] = $_POST['is_active'] === '1' ? 1 : 0;
		}

		if($id){

			if(!$this->db->query("SELECT id FROM criteria_list WHERE id = $id")->num_rows){
				return 0;
			}

			$this->qn_write('criteria_list', $values, $id);
			$this->audit('criteria_updated', 'criteria', $id, $name);

		}else{

			$values['order_by'] = (int)$this->db->query("SELECT COALESCE(MAX(order_by), -1) + 1 AS n FROM criteria_list")->fetch_assoc()['n'];
			$id = $this->qn_write('criteria_list', $values);
			$this->audit('criteria_added', 'criteria', $id, $name);
		}

		return 1;
	}

	/*
	| A criterion that questions or questionnaire sections already use is
	| kept, so their results keep their grouping; it can be switched off
	| instead. Answers: 1 deleted, 3 in use, 0 not found.
	*/
	function delete_criteria(){

		$id = (int)($_POST['id'] ?? 0);
		$criterion = $this->db->query("SELECT * FROM criteria_list WHERE id = $id")->fetch_assoc();

		if(!$criterion){
			return 0;
		}

		$used = (int)$this->db->query("SELECT COUNT(*) AS c FROM question_list WHERE criteria_id = $id")->fetch_assoc()['c'];

		if($this->questionnaire_tables_ready()){
			$used += (int)$this->db->query("SELECT COUNT(*) AS c FROM questionnaire_sections WHERE criteria_id = $id")->fetch_assoc()['c'];
		}

		if($used > 0){
			return 3;
		}

		$this->db->query("DELETE FROM criteria_list WHERE id = $id");

		if($this->questionnaire_tables_ready()){
			$this->db->query("DELETE FROM criteria_questions WHERE criteria_id = $id");
		}

		$this->audit('criteria_deleted', 'criteria', $id, $criterion['criteria']);

		return 1;
	}

	function save_criteria_order(){

		$ids = isset($_POST['criteria_id']) && is_array($_POST['criteria_id']) ? $_POST['criteria_id'] : array();

		if(!$ids){
			return 0;
		}

		foreach(array_values($ids) as $position => $criteria_id){
			$this->db->query("UPDATE criteria_list SET order_by = ".(int)$position." WHERE id = ".(int)$criteria_id);
		}

		return 1;
	}
/*
|--------------------------------------------------------------------------
| ACTIVITY BOOKINGS
| An activity books its venue for the whole day. Venue names match when
| they are the same apart from capital letters and extra spaces. Approved,
| pending and "Needs Revision" activities hold a booking; rejected ones
| free the date and venue again.
|--------------------------------------------------------------------------
*/
private function activity_status_label($status){
    $labels = array(
        'approved' => 'Approved',
        'pending' => 'Pending',
        'rejected' => 'Rejected',
        'revision' => 'Needs Revision'
    );
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}

private function normalize_venue($venue){
    return preg_replace('/\s+/', ' ', strtolower(trim((string)$venue)));
}

// The activity already holding this date + venue, or null when it's free
private function find_booking_conflict($date, $venue, $exclude_id = 0){

    $venue_key = $this->normalize_venue($venue);

    if($venue_key === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)){
        return null;
    }

    $qry = $this->db->query("
        SELECT a.id, a.activity_name, a.venue, a.activity_date,
               CONCAT(f.firstname, ' ', f.lastname) AS coordinator
        FROM activities a
        LEFT JOIN faculty_list f ON f.id = a.faculty_id
        WHERE a.activity_date = '$date'
        AND a.status IN ('approved', 'pending', 'revision')
        AND a.id <> ".(int)$exclude_id."
    ");

    while($row = $qry->fetch_assoc()){
        if($this->normalize_venue($row['venue']) === $venue_key){
            return $row;
        }
    }

    return null;
}

private function conflict_message($booking){

    $coordinator = !empty($booking['coordinator']) ? $booking['coordinator'] : 'another coordinator';

    return trim($booking['venue'])." is already booked on "
        .date("M d, Y", strtotime($booking['activity_date']))
        ." for '".$booking['activity_name']."' (".$coordinator."). Choose another date or venue.";
}

// Live check for the Create Activity form
function check_activity_conflict(){

    if(empty($_SESSION['login_id'])){
        return json_encode(array('conflict' => false, 'message' => ''));
    }

    // exclude_id = the activity being edited, so it doesn't clash with its own booking
    $booking = $this->find_booking_conflict(
        isset($_GET['activity_date']) ? $_GET['activity_date'] : '',
        isset($_GET['venue']) ? $_GET['venue'] : '',
        isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0
    );

    return json_encode(array(
        'conflict' => $booking !== null,
        'message' => $booking ? $this->conflict_message($booking) : ''
    ));
}

/*
|--------------------------------------------------------------------------
| CALENDAR
| The activities table is the booking calendar: every activity shows up as
| an all-day event. Green = approved, yellow = pending, orange = needs
| revision, red = rejected or a conflict (two bookings on the same day and
| venue, which can only come from data saved before conflict checking).
|--------------------------------------------------------------------------
*/
private function calendar_activities($statuses){

    $in = "'".implode("','", $statuses)."'";

    $qry = $this->db->query("
        SELECT a.*, CONCAT(f.firstname, ' ', f.lastname) AS coordinator, p.title AS project_title
        FROM activities a
        LEFT JOIN faculty_list f ON f.id = a.faculty_id
        LEFT JOIN projects p ON p.id = a.project_id
        WHERE a.status IN ($in)
        ORDER BY a.activity_date, a.id
    ");

    $rows = array();

    while($row = $qry->fetch_assoc()){
        $row['status'] = strtolower($row['status']);
        $rows[] = $row;
    }

    return $rows;
}

// Events for the Calendar page (FullCalendar event objects)
function calendar_events(){

    if(empty($_SESSION['login_id'])){
        return json_encode(array());
    }

    $rows = $this->calendar_activities(array('approved', 'pending', 'revision', 'rejected'));

    // How many bookings (approved/pending/revision) hold each day + venue
    $bookings = array();

    foreach($rows as $row){
        if($row['status'] != 'rejected' && $this->normalize_venue($row['venue']) !== ''){
            $key = $row['activity_date'].'|'.$this->normalize_venue($row['venue']);
            $bookings[$key] = isset($bookings[$key]) ? $bookings[$key] + 1 : 1;
        }
    }

    $colors = array('approved' => '#28a745', 'pending' => '#ffc107', 'revision' => '#fd7e14', 'rejected' => '#dc3545');

    $events = array();

    foreach($rows as $row){

        $key = $row['activity_date'].'|'.$this->normalize_venue($row['venue']);

        $conflict = $row['status'] != 'rejected' && isset($bookings[$key]) && $bookings[$key] > 1;

        $color = $conflict ? $colors['rejected'] : $colors[$row['status']];

        $events[] = array(
            'id' => (int)$row['id'],
            'title' => ($conflict ? 'Conflict: ' : '').$row['activity_name'],
            'start' => $row['activity_date'],
            'allDay' => true,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $color == $colors['pending'] ? '#212529' : '#ffffff',
            'extendedProps' => array(
                'name' => $row['activity_name'],
                'status' => $this->activity_status_label($row['status']),
                'status_key' => $row['status'],
                'conflict' => $conflict,
                'venue' => (string)$row['venue'],
                'coordinator' => !empty($row['coordinator']) ? $row['coordinator'] : 'Unknown coordinator',
                'purpose' => (string)$row['purpose'],
                'project' => (string)($row['project_title'] ?? ''),
                'time' => $this->time_range($row['start_time'] ?? null, $row['end_time'] ?? null)
            )
        );
    }

    return json_encode($events);
}

// Booked activities (approved, pending, needs revision) as an .ics file for Google Calendar / Outlook
function calendar_ics(){

    if(empty($_SESSION['login_id'])){
        http_response_code(403);
        return "Please log in to download the calendar.";
    }

    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//ISU Extension Office//ExtenTrack//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:ExtenTrack Activities'
    );

    $stamp = gmdate('Ymd\THis\Z');

    foreach($this->calendar_activities(array('approved', 'pending', 'revision')) as $row){

        $coordinator = !empty($row['coordinator']) ? $row['coordinator'] : 'Unknown coordinator';

        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:activity-'.$row['id'].'@extentrack';
        $lines[] = 'DTSTAMP:'.$stamp;
        $lines[] = 'DTSTART;VALUE=DATE:'.date('Ymd', strtotime($row['activity_date']));
        $lines[] = 'DTEND;VALUE=DATE:'.date('Ymd', strtotime($row['activity_date'].' +1 day'));
        $lines[] = 'SUMMARY:'.$this->ics_text($row['activity_name']);

        if(trim((string)$row['venue']) !== ''){
            $lines[] = 'LOCATION:'.$this->ics_text($row['venue']);
        }

        $lines[] = 'DESCRIPTION:'.$this->ics_text(
            "Coordinator: ".$coordinator."\nStatus: ".$this->activity_status_label($row['status'])."\nPurpose: ".$row['purpose']
        );
        $lines[] = 'STATUS:'.($row['status'] == 'approved' ? 'CONFIRMED' : 'TENTATIVE');
        $lines[] = 'END:VEVENT';
    }

    $lines[] = 'END:VCALENDAR';

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="extentrack-activities.ics"');

    return implode("\r\n", array_map(array($this, 'ics_fold'), $lines))."\r\n";
}

// Escapes text for an .ics property value
private function ics_text($text){
    return str_replace(
        array("\\", ";", ",", "\r\n", "\n", "\r"),
        array("\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"),
        (string)$text
    );
}

// .ics lines may be at most 75 bytes; longer ones continue on a line starting with a space
private function ics_fold($line){

    $out = '';
    $limit = 75;

    while(strlen($line) > $limit){

        $cut = $limit;

        // Don't split a multi-byte (UTF-8) character
        while($cut > 0 && (ord($line[$cut]) & 0xC0) == 0x80){
            $cut--;
        }

        $out .= substr($line, 0, $cut)."\r\n ";
        $line = substr($line, $cut);
        $limit = 74;
    }

    return $out.$line;
}

// Why a date + venue can't be booked ('' when it can). $exclude_id = the activity being edited.
private function booking_error($activity_date, $venue_text, $exclude_id = 0){

    // A booking needs a date and a venue, and the venue must be free that day
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $activity_date) || $venue_text === ''){
        return "Please enter the activity date and venue.";
    }

    $booking = $this->find_booking_conflict($activity_date, $venue_text, $exclude_id);

    return $booking ? $this->conflict_message($booking) : '';
}

/*
|--------------------------------------------------------------------------
| ACTIVITY IMAGES
| Up to 10 pictures per activity (JPG, PNG, GIF or WEBP, 5 MB each), stored
| in uploads/activities/ and listed in the activity_images table. Files are
| checked by their content, not their name, and saved under a random name
| with the matching extension. uploads/activities/.htaccess also refuses to
| serve script files.
|--------------------------------------------------------------------------
*/
const ACTIVITY_MAX_IMAGES = 10;
const ACTIVITY_MAX_IMAGE_BYTES = 5242880;   // 5 MB

// PHP's post_max_size in bytes (a bigger form arrives completely empty)
private function post_limit_bytes(){

    $value = trim(ini_get('post_max_size'));
    $number = (float)$value;

    switch(strtoupper(substr($value, -1))){
        case 'G': return (int)($number * 1073741824);
        case 'M': return (int)($number * 1048576);
        case 'K': return (int)($number * 1024);
    }

    return (int)$number;
}

// True when the form was too big for PHP to accept (so $_POST and $_FILES are empty)
private function post_too_large(){
    return empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $this->post_limit_bytes();
}

// Checks the pictures sent as images[]. Returns array(pictures to save, error message or '').
private function validate_activity_uploads(){

    $files = array();
    $errors = array();

    if(empty($_FILES['images']) || !is_array($_FILES['images']['name'])){
        return array($files, '');
    }

    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp');

    foreach($_FILES['images']['name'] as $i => $name){

        $error = $_FILES['images']['error'][$i];

        if($error == UPLOAD_ERR_NO_FILE){
            continue;
        }

        $name = basename((string)$name);
        $tmp = $_FILES['images']['tmp_name'][$i];

        if($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE
            || ($error == UPLOAD_ERR_OK && $_FILES['images']['size'][$i] > self::ACTIVITY_MAX_IMAGE_BYTES)){
            $errors[] = "$name is larger than 5 MB.";
            continue;
        }

        if($error != UPLOAD_ERR_OK || !is_uploaded_file($tmp)){
            $errors[] = "$name could not be uploaded.";
            continue;
        }

        // The file's content decides, not its name: "photo.php" renamed "photo.jpg" is refused
        $info = @getimagesize($tmp);

        if(!$info || !isset($types[$info[2]])){
            $errors[] = "$name is not a JPG, PNG, GIF or WEBP picture.";
            continue;
        }

        $files[] = array('tmp' => $tmp, 'ext' => $types[$info[2]]);
    }

    return array($files, implode("\n", $errors));
}

// Moves checked pictures into place, after the activity's existing ones
private function store_activity_images($activity_id, $files){

    $activity_id = (int)$activity_id;

    $next = (int)$this->db->query("
        SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order
        FROM activity_images
        WHERE activity_id = $activity_id
    ")->fetch_assoc()['next_order'];

    foreach($files as $file){

        $name = time()."_".bin2hex(random_bytes(6)).".".$file['ext'];

        if(move_uploaded_file($file['tmp'], "uploads/activities/".$name)){
            $this->db->query("INSERT INTO activity_images (activity_id, file_name, sort_order) VALUES ($activity_id, '$name', ".$next++.")");
        }
    }
}

// Pictures of the given activities whose files exist: array(activity_id => array(array('id' => .., 'url' => ..), ...))
private function activity_image_rows($activity_ids){

    $images = array();
    $ids = array_filter(array_map('intval', (array)$activity_ids));

    if(!$ids){
        return $images;
    }

    $qry = $this->db->query("
        SELECT id, activity_id, file_name
        FROM activity_images
        WHERE activity_id IN (".implode(',', $ids).")
        ORDER BY sort_order, id
    ");

    while($row = $qry->fetch_assoc()){

        $path = "uploads/activities/".basename($row['file_name']);

        if(is_file($path)){
            $images[$row['activity_id']][] = array('id' => (int)$row['id'], 'url' => $path);
        }
    }

    return $images;
}

// Deletes pictures (files and rows); $where picks the activity_images rows, e.g. "activity_id IN (3,4)"
private function delete_activity_images($where){

    $qry = $this->db->query("SELECT file_name FROM activity_images WHERE $where");

    while($row = $qry->fetch_assoc()){

        $path = "uploads/activities/".basename($row['file_name']);

        if(is_file($path)){
            unlink($path);
        }
    }

    $this->db->query("DELETE FROM activity_images WHERE $where");
}

function save_activity(){

    if($this->post_too_large()){
        return "The pictures are too large to send together (up to ".ini_get('post_max_size')."B per save). Add fewer now and the rest with Edit.";
    }

    $faculty_id = $_SESSION['login_id'];

    $activity_date = isset($_POST['activity_date']) ? trim($_POST['activity_date']) : '';
    $venue_text = isset($_POST['venue']) ? trim($_POST['venue']) : '';

    $error = $this->booking_error($activity_date, $venue_text);

    if($error){
        return $error;
    }

    list($files, $upload_error) = $this->validate_activity_uploads();

    if($upload_error){
        return $upload_error;
    }

    if(count($files) > self::ACTIVITY_MAX_IMAGES){
        return "An activity can have up to ".self::ACTIVITY_MAX_IMAGES." pictures.";
    }

    list($project_id, $start_time, $end_time, $project_error) = $this->activity_project_and_times();

    if($project_error){
        return $project_error;
    }

    $activity_name = $this->db->real_escape_string($_POST['activity_name']);
    $purpose = $this->db->real_escape_string($_POST['purpose']);
    $description = $this->db->real_escape_string($_POST['description']);
    $venue = $this->db->real_escape_string($venue_text);

    // The project and times, once the database update has added them
    $link_columns = $project_id ? ", project_id, start_time, end_time" : "";
    $link_values = $project_id
        ? ", $project_id, ".($start_time ? "'$start_time'" : "NULL").", ".($end_time ? "'$end_time'" : "NULL")
        : "";

    $save = $this->db->query("
        INSERT INTO activities
        (
            faculty_id,
            activity_name,
            purpose,
            description,
            activity_date,
            venue,
            image
            $link_columns
        )
        VALUES
        (
            '$faculty_id',
            '$activity_name',
            '$purpose',
            '$description',
            '$activity_date',
            '$venue',
            ''
            $link_values
        )
    ");

    if(!$save){
        return $this->db->error;
    }

    $this->store_activity_images($this->db->insert_id, $files);

    if($project_id){
        $this->refresh_lifecycle_status($project_id);
    }

    return 1;
}

/*
| The project an activity belongs to, and its start and end time.
| The project is required and must be one of the coordinator's own projects;
| the times are optional, but the activity must end after it starts.
| Returns [project_id, start_time, end_time, error]. Before the database
| update that adds the times, nothing is asked: [null, null, null, ''].
*/
private function activity_project_and_times(){

    if(!$this->links_ready()){
        return array(null, null, null, '');
    }

    $project_id = (int)($_POST['project_id'] ?? 0);

    if($project_id <= 0){
        return array(0, null, null, "Choose the project this activity belongs to.");
    }

    if(!$this->project_for_user($project_id)){
        return array(0, null, null, "You can only add activities to your own projects.");
    }

    $times = array();

    foreach(array('start_time' => 'start', 'end_time' => 'end') as $field => $word){
        $value = trim((string)($_POST[$field] ?? ''));
        if($value === ''){
            $times[$field] = null;
            continue;
        }
        if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)){
            return array(0, null, null, "Please enter the $word time like 08:00 AM.");
        }
        $times[$field] = substr($value, 0, 5);
    }

    if($times['end_time'] && !$times['start_time']){
        return array(0, null, null, "Please enter the start time as well.");
    }

    if($times['start_time'] && $times['end_time'] && $times['end_time'] <= $times['start_time']){
        return array(0, null, null, "The activity must end after it starts.");
    }

    return array($project_id, $times['start_time'], $times['end_time'], '');
}

// Coordinator edits their own pending / "Needs Revision" activity; saving resubmits it as Pending.
// POST remove_images[] = ids of this activity's pictures to delete; images[] = new pictures.
function update_activity(){

    if($this->post_too_large()){
        return "The pictures are too large to send together (up to ".ini_get('post_max_size')."B per save). Add fewer at a time.";
    }

    if(empty($_SESSION['login_id'])){
        return "Please log in again.";
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $faculty_id = (int)$_SESSION['login_id'];

    $qry = $this->db->query("SELECT status, project_id FROM activities WHERE id = $id AND faculty_id = $faculty_id");

    if($qry->num_rows == 0){
        return "You can only edit your own activities.";
    }

    $current = $qry->fetch_assoc();

    if(!in_array($current['status'], array('pending', 'revision'))){
        return "Only pending activities or ones that need revision can be edited.";
    }

    $activity_date = isset($_POST['activity_date']) ? trim($_POST['activity_date']) : '';
    $venue_text = isset($_POST['venue']) ? trim($_POST['venue']) : '';

    $error = $this->booking_error($activity_date, $venue_text, $id);

    if($error){
        return $error;
    }

    list($files, $upload_error) = $this->validate_activity_uploads();

    if($upload_error){
        return $upload_error;
    }

    // Only this activity's own pictures can be removed
    $remove = array_values(array_filter(array_map('intval', isset($_POST['remove_images']) ? (array)$_POST['remove_images'] : array())));

    $existing = $this->activity_image_rows(array($id));
    $existing = isset($existing[$id]) ? $existing[$id] : array();

    $kept = 0;

    foreach($existing as $image){
        if(!in_array($image['id'], $remove)){
            $kept++;
        }
    }

    if($kept + count($files) > self::ACTIVITY_MAX_IMAGES){
        return "An activity can have up to ".self::ACTIVITY_MAX_IMAGES." pictures. Remove some before adding more.";
    }

    list($project_id, $start_time, $end_time, $project_error) = $this->activity_project_and_times();

    if($project_error){
        return $project_error;
    }

    $activity_name = $this->db->real_escape_string($_POST['activity_name']);
    $purpose = $this->db->real_escape_string($_POST['purpose']);
    $description = $this->db->real_escape_string($_POST['description']);
    $venue = $this->db->real_escape_string($venue_text);

    $links = $project_id
        ? ", project_id = $project_id, start_time = ".($start_time ? "'$start_time'" : "NULL").", end_time = ".($end_time ? "'$end_time'" : "NULL")
        : "";

    $save = $this->db->query("
        UPDATE activities SET
            activity_name = '$activity_name',
            purpose = '$purpose',
            description = '$description',
            activity_date = '$activity_date',
            venue = '$venue',
            status = 'pending',
            revision_note = NULL
            $links
        WHERE id = $id AND faculty_id = $faculty_id
    ");

    if(!$save){
        return $this->db->error;
    }

    if($remove){
        $this->delete_activity_images("activity_id = $id AND id IN (".implode(',', $remove).")");
    }

    $this->store_activity_images($id, $files);

    // Both projects' stages, when it moved to another project
    foreach(array_unique(array_filter(array((int)$current['project_id'], (int)$project_id))) as $pid){
        $this->refresh_lifecycle_status($pid);
    }

    return 1;
}

/*
|--------------------------------------------------------------------------
| ACTIVITY TABLE
| Rows for the shared activities table (admin Activities page and the
| coordinator's My / All Activities tabs), with what the viewer may do to
| each row. GET scope = "mine" (coordinator's own) or "all", plus filters.
|--------------------------------------------------------------------------
*/
function activity_table(){

    if(empty($_SESSION['login_id'])){
        return json_encode(array('data' => array()));
    }

    $user_id = (int)$_SESSION['login_id'];
    $is_admin = $_SESSION['login_type'] == 1;

    $mine_only = !$is_admin && isset($_GET['scope']) && $_GET['scope'] == 'mine';

    $src = $_GET;

    if($mine_only){
        unset($src['coordinator']);
    }

    $filters = $this->activity_filters(
        $src,
        array('a.activity_name', "CONCAT(f.firstname, ' ', f.lastname)", 'a.venue', 'p.title'),
        'a.'
    );

    $qry = $this->db->query("
        SELECT a.*, CONCAT(f.firstname, ' ', f.lastname) AS implementer,
            p.title AS project_title, p.faculty_id AS project_faculty, p.created_by AS project_creator,
            (SELECT COUNT(DISTINCT ea.evaluation_id) FROM evaluation_answers ea WHERE ea.activity_id = a.id) AS evaluations
        FROM activities a
        LEFT JOIN faculty_list f ON f.id = a.faculty_id
        LEFT JOIN projects p ON p.id = a.project_id
        WHERE 1 $filters ".($mine_only ? "AND a.faculty_id = $user_id" : "")."
        ORDER BY a.activity_date DESC, a.id DESC
    ");

    $rows = array();
    $linked = $this->links_ready();
    $today = date('Y-m-d');

    while($row = $qry->fetch_assoc()){

        $own = (int)$row['faculty_id'] === $user_id && !$is_admin;
        $project_id = $row['project_id'] !== null && $row['project_title'] !== null ? (int)$row['project_id'] : null;
        $start = $linked ? $row['start_time'] : null;
        $end = $linked ? $row['end_time'] : null;

        $rows[] = array(
            'id' => (int)$row['id'],
            'ref' => sprintf('ACT-%04d', $row['id']),
            'title' => $row['activity_name'],
            'date' => $row['activity_date'],
            'date_display' => date('M d, Y', strtotime($row['activity_date'])),
            'start_time' => $start ? substr($start, 0, 5) : '',
            'end_time' => $end ? substr($end, 0, 5) : '',
            'time_display' => $this->time_range($start, $end),
            'venue' => (string)$row['venue'],
            'implementer' => !empty($row['implementer']) ? $row['implementer'] : 'Unknown coordinator',
            'project_id' => $project_id,
            'project_ref' => $project_id ? sprintf('PRJ-%04d', $project_id) : '',
            'project_title' => $project_id ? $row['project_title'] : '',
            // Only someone who may open the project gets a link to it
            'can_open_project' => $project_id && ($is_admin
                || (int)$row['project_faculty'] === $user_id || (int)$row['project_creator'] === $user_id),
            'status' => $row['status'],
            'status_label' => $this->activity_status_label($row['status']),
            'phase' => $row['status'] !== 'approved' ? ''
                : ($row['activity_date'] < $today ? 'conducted' : ($row['activity_date'] === $today ? 'today' : 'upcoming')),
            'revision_note' => $row['revision_note'],
            'purpose' => (string)$row['purpose'],
            'description' => (string)$row['description'],
            'evaluations' => (int)$row['evaluations'],
            'images' => array(),   // filled below
            'own' => $own,
            'can_edit' => $own && in_array($row['status'], array('pending', 'revision')),
            'can_delete' => $is_admin || $own,
            'can_review' => $is_admin,
            // Linking to a project: the admin always; a coordinator their own activity that has none
            'can_assign' => $linked && ($is_admin || ($own && !$project_id))
        );
    }

    // All the pictures in one query: [{id, url}, ...] per activity, in order
    $images = $this->activity_image_rows(array_map(function($row){ return $row['id']; }, $rows));

    foreach($rows as $i => $row){
        if(isset($images[$row['id']])){
            $rows[$i]['images'] = $images[$row['id']];
        }
    }

    return json_encode(array('data' => $rows));
}

/*
|--------------------------------------------------------------------------
| BULK / SINGLE ACTIVITY ACTIONS
| POST do = approve | reject | delete, ids[] = activity ids.
| Approve / reject: admin only. Delete: admin any, coordinator own only.
| Returns 1, or a message saying what was refused or skipped.
|--------------------------------------------------------------------------
*/
function bulk_activity_action(){

    if(empty($_SESSION['login_id'])){
        return "Please log in again.";
    }

    $is_admin = $_SESSION['login_type'] == 1;
    $user_id = (int)$_SESSION['login_id'];

    $do = isset($_POST['do']) ? $_POST['do'] : '';

    $ids = array_values(array_filter(array_map('intval', isset($_POST['ids']) ? (array)$_POST['ids'] : array())));

    if(!$ids){
        return "Select at least one activity.";
    }

    $in = implode(',', $ids);

    // The projects these activities belong to, so their stages can follow
    $projects = array();
    $qry = $this->db->query("SELECT DISTINCT project_id FROM activities WHERE id IN ($in) AND project_id IS NOT NULL");
    while($row = $qry->fetch_row()){
        $projects[] = (int)$row[0];
    }

    // Who reviewed it and when, once the database update has added those columns
    $reviewed = $this->links_ready() ? ", reviewed_by = $user_id, reviewed_at = NOW()" : "";

    if($do == 'approve' || $do == 'reject'){

        if(!$is_admin){
            return "Only the admin can approve or reject activities.";
        }

        if($do == 'reject'){
            // The reason, when one is given, is kept for the coordinator to read
            $note = trim((string)($_POST['note'] ?? ''));
            $note_sql = $note === '' ? "NULL" : "'".$this->db->real_escape_string($note)."'";
            $this->db->query("UPDATE activities SET status = 'rejected', revision_note = $note_sql $reviewed WHERE id IN ($in)");
            foreach($projects as $pid){
                $this->refresh_lifecycle_status($pid);
            }
            return 1;
        }

        // A rejected activity gave up its booking; approving it again needs the slot to still be free
        $skipped = array();
        $qry = $this->db->query("SELECT id, activity_name, activity_date, venue, status FROM activities WHERE id IN ($in)");

        while($row = $qry->fetch_assoc()){

            if($row['status'] == 'rejected'){

                $booking = $this->find_booking_conflict($row['activity_date'], $row['venue'], $row['id']);

                if($booking){
                    $skipped[] = "'".$row['activity_name']."': ".$this->conflict_message($booking);
                    continue;
                }
            }

            $this->db->query("UPDATE activities SET status = 'approved', revision_note = NULL $reviewed WHERE id = ".(int)$row['id']);
        }

        foreach($projects as $pid){
            $this->refresh_lifecycle_status($pid);
        }

        return $skipped ? "Not approved -\n".implode("\n", $skipped) : 1;
    }

    if($do == 'delete'){

        $owner_sql = $is_admin ? "" : " AND faculty_id = $user_id";

        $qry = $this->db->query("SELECT id, image FROM activities WHERE id IN ($in) $owner_sql");

        if(!$is_admin && $qry->num_rows < count($ids)){
            return "You can only delete your own activities.";
        }

        $allowed = array();

        while($row = $qry->fetch_assoc()){

            $allowed[] = (int)$row['id'];

            // Picture saved before activity_images existed (the old single-image column)
            if(!empty($row['image']) && file_exists("uploads/activities/".basename($row['image']))){
                unlink("uploads/activities/".basename($row['image']));
            }
        }

        if($allowed){
            $this->delete_activity_images("activity_id IN (".implode(',', $allowed).")");
        }

        $this->db->query("DELETE FROM activities WHERE id IN ($in) $owner_sql");

        foreach($projects as $pid){
            $this->refresh_lifecycle_status($pid);
        }

        return 1;
    }

    return "Unknown action.";
}

/*
| Links an activity to its project: the admin for any activity (also moving
| it to another project), a coordinator for their own activity that has no
| project yet, and only to one of their own projects. Nothing is guessed.
*/
function activity_assign_project(){

    if(empty($_SESSION['login_id'])){
        return "Please log in again.";
    }

    if(!$this->links_ready()){
        return "Apply the latest database update in System Update first.";
    }

    $id = (int)($_POST['id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);
    $activity = $this->db->query("SELECT id, faculty_id, project_id, activity_name FROM activities WHERE id = $id")->fetch_assoc();

    if(!$activity){
        return "That activity was not found.";
    }

    if(!$this->is_admin()){
        if((int)$activity['faculty_id'] !== (int)$_SESSION['login_id'] || $activity['project_id']){
            return "Only the Extension Office can move an activity to another project.";
        }
    }

    if(!$this->project_for_user($project_id)){
        return "Choose one of your projects.";
    }

    $this->db->query("UPDATE activities SET project_id = $project_id WHERE id = $id");

    $this->refresh_lifecycle_status($project_id);
    if($activity['project_id'] && (int)$activity['project_id'] !== $project_id){
        $this->refresh_lifecycle_status((int)$activity['project_id']);
    }

    $this->audit_project('activity_linked', $project_id, $activity['activity_name']);

    return 1;
}

/*
| Documentation photos for an approved activity. Editing an activity is only
| possible while it waits for approval, but the photos of the day are taken
| once it is approved and conducted, so its coordinator may add them (and
| remove one added by mistake) without sending the activity back for review.
*/
private function own_approved_activity($id){

    $id = (int)$id;
    $faculty_id = (int)($_SESSION['login_id'] ?? 0);

    return $this->db->query("
        SELECT id, project_id, activity_name, status FROM activities
        WHERE id = $id AND faculty_id = $faculty_id AND status = 'approved'
    ")->fetch_assoc();
}

function activity_add_photos(){

    if($this->post_too_large()){
        return "The pictures are too large to send together (up to ".ini_get('post_max_size')."B per save). Add fewer at a time.";
    }

    $activity = $this->own_approved_activity($_POST['id'] ?? 0);

    if(!$activity){
        return "You can add photos to your own approved activities.";
    }

    list($files, $upload_error) = $this->validate_activity_uploads();

    if($upload_error){
        return $upload_error;
    }

    if(!$files){
        return "Choose at least one picture.";
    }

    $id = (int)$activity['id'];
    $existing = (int)$this->db->query("SELECT COUNT(*) AS c FROM activity_images WHERE activity_id = $id")->fetch_assoc()['c'];

    if($existing + count($files) > self::ACTIVITY_MAX_IMAGES){
        return "An activity can have up to ".self::ACTIVITY_MAX_IMAGES." pictures. It has $existing already.";
    }

    $this->store_activity_images($id, $files);

    if($activity['project_id']){
        $this->refresh_lifecycle_status((int)$activity['project_id']);
        $this->audit_project('activity_photos_added', (int)$activity['project_id'], $activity['activity_name'].': '.count($files).' photo(s)');
    }

    return 1;
}

function activity_remove_photo(){

    $image_id = (int)($_POST['image_id'] ?? 0);
    $row = $this->db->query("SELECT activity_id FROM activity_images WHERE id = $image_id")->fetch_assoc();
    $activity = $row ? $this->own_approved_activity($row['activity_id']) : null;

    if(!$activity){
        return "You can remove photos from your own approved activities.";
    }

    $this->delete_activity_images("id = $image_id");

    if($activity['project_id']){
        $this->refresh_lifecycle_status((int)$activity['project_id']);
    }

    return 1;
}

// Admin sends a pending activity back to its coordinator with a note
function set_activity_revision(){

    if(empty($_SESSION['login_id']) || $_SESSION['login_type'] != 1){
        return "Only the admin can request a revision.";
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';

    if($note === ''){
        return "Please write what the coordinator should change.";
    }

    $qry = $this->db->query("SELECT status, project_id FROM activities WHERE id = $id");
    $activity = $qry->fetch_assoc();

    if(!$activity || $activity['status'] != 'pending'){
        return "Only pending activities can be sent back for revision.";
    }

    $note = $this->db->real_escape_string($note);
    $reviewed = $this->links_ready() ? ", reviewed_by = ".(int)$_SESSION['login_id'].", reviewed_at = NOW()" : "";

    $save = $this->db->query("UPDATE activities SET status = 'revision', revision_note = '$note' $reviewed WHERE id = $id");

    if($save && $activity['project_id']){
        $this->refresh_lifecycle_status((int)$activity['project_id']);
    }

    return $save ? 1 : $this->db->error;
}











function update_activity_status(){

    // Only the admin reviews activities, and only to a known status
    if(empty($_SESSION['login_id']) || $_SESSION['login_type'] != 1){
        return "Only the admin can change an activity's status.";
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if(!in_array($status, array('approved', 'pending', 'rejected'))){
        return "Unknown status.";
    }

    $save = $this->db->query("UPDATE activities SET status = '$status', revision_note = NULL WHERE id = $id");

    if($save){
        return 1;
    }
}


	/*
	| Used by the older per-academic-year question page, only until the
	| database update that adds questionnaires has been applied. After that,
	| questions are changed in the Questionnaire Builder, which keeps a
	| published questionnaire's questions locked, so these refuse.
	*/
	private function legacy_questions_closed(){
		return $this->questionnaire_tables_ready()
			? "Questions are now managed in the Questionnaire Builder (Questionnaires, then Edit or Manage Questions)."
			: null;
	}

	function save_question(){

		if($closed = $this->legacy_questions_closed()){
			return $closed;
		}

		$id = (int)($_POST['id'] ?? 0);
		$academic_id = (int)($_POST['academic_id'] ?? 0);
		$criteria_id = (int)($_POST['criteria_id'] ?? 0);
		$question = trim((string)($_POST['question'] ?? ''));

		if($question === '' || $criteria_id <= 0){
			return "Please enter the question and choose its criteria.";
		}

		$text = $this->db->real_escape_string($question);

		if($id){
			$save = $this->db->query("
				UPDATE question_list
				SET question = '$text', criteria_id = $criteria_id
				WHERE id = $id
			");
		}else{
			$next = (int)$this->db->query("
				SELECT COALESCE(MAX(order_by), -1) + 1 AS next_order FROM question_list WHERE academic_id = $academic_id
			")->fetch_assoc()['next_order'];

			$save = $this->db->query("
				INSERT INTO question_list
				SET academic_id = $academic_id, criteria_id = $criteria_id, question = '$text', order_by = $next
			");
		}

		return $save ? 1 : "The question could not be saved.";
	}

	// Whether the database update that adds questionnaires has been applied
	private function questionnaire_tables_ready(){
		return $this->db->query("
			SELECT 1 FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'questionnaires'
		")->num_rows > 0;
	}

	// A question that has been answered is kept, or its answers would be lost
	function delete_question(){

		if($closed = $this->legacy_questions_closed()){
			return $closed;
		}

		$id = (int)($_POST['id'] ?? 0);

		if($id <= 0){
			return "Question not found.";
		}

		$answers = (int)$this->db->query("SELECT COUNT(*) AS c FROM evaluation_answers WHERE question_id = $id")->fetch_assoc()['c'];

		if($answers > 0){
			return "This question has already been answered $answers time".($answers == 1 ? "" : "s")
				.", so deleting it would lose those answers.";
		}

		$delete = $this->db->query("DELETE FROM question_list WHERE id = $id");

		if($delete && $this->questionnaire_tables_ready()){
			$this->db->query("DELETE FROM question_options WHERE question_id = $id");
		}

		return $delete ? 1 : "The question could not be deleted.";
	}

	function save_question_order(){

		if($closed = $this->legacy_questions_closed()){
			return $closed;
		}

		$ids = isset($_POST['qid']) && is_array($_POST['qid']) ? $_POST['qid'] : array();

		if(!$ids){
			return "Nothing to reorder.";
		}

		foreach(array_values($ids) as $position => $id){
			$this->db->query("UPDATE question_list SET order_by = ".(int)$position." WHERE id = ".(int)$id);
		}

		return 1;
	}
	function save_faculty(){

		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$school_id = trim($_POST['school_id'] ?? '');
		$firstname = trim($_POST['firstname'] ?? '');
		$lastname = trim($_POST['lastname'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = (string)($_POST['password'] ?? '');

		if($school_id === '' || $firstname === '' || $lastname === '' || $email === ''){
			return "Please fill in the School ID, name and email.";
		}

		$data = " school_id = '".$this->db->real_escape_string($school_id)."'"
			.", firstname = '".$this->db->real_escape_string($firstname)."'"
			.", lastname = '".$this->db->real_escape_string($lastname)."'"
			.", email = '".$this->db->real_escape_string($email)."'";

		if($password !== ''){
			$data .= ", password = '".password_hash($password, PASSWORD_DEFAULT)."'";
		}
		$check = $this->db->query("SELECT id FROM faculty_list WHERE email = '".$this->db->real_escape_string($email)."'".($id ? " AND id != $id" : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		$check = $this->db->query("SELECT id FROM faculty_list WHERE school_id = '".$this->db->real_escape_string($school_id)."'".($id ? " AND id != $id" : ''))->num_rows;
		if($check > 0){
			return 3;
			exit;
		}
		if(empty($id)){
			$verified = $this->require_email_otp('faculty', $email);
			if($verified !== true){
				return $verified;
			}
		}
		if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name'])){

			list($fname, $upload_error) = $this->store_avatar($_FILES['img']);

			if($upload_error){
				return $upload_error;
			}

			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO faculty_list set $data");
		}else{
			$save = $this->db->query("UPDATE faculty_list set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}
	function delete_faculty(){
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id <= 0){
			return "Coordinator not found.";
		}
		$delete = $this->db->query("DELETE FROM faculty_list WHERE id = $id");
		if($delete)
			return 1;
	}
	function save_student(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(!empty($password)){
					$data .= ", password=md5('$password') ";

		}
		$check = $this->db->query("SELECT * FROM student_list where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO student_list set $data");
		}else{
			$save = $this->db->query("UPDATE student_list set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}
	function delete_student(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM student_list where id = ".$id);
		if($delete)
			return 1;
	}
	function save_task(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if($k == 'description')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO task_list set $data");
		}else{
			$save = $this->db->query("UPDATE task_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_task(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM task_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_progress(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if($k == 'progress')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(!isset($is_complete))
			$data .= ", is_complete=0 ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO task_progress set $data");
		}else{
			$save = $this->db->query("UPDATE task_progress set $data where id = $id");
		}
		if($save){
		if(!isset($is_complete))
			$this->db->query("UPDATE task_list set status = 1 where id = $task_id ");
		else
			$this->db->query("UPDATE task_list set status = 2 where id = $task_id ");
			return 1;
		}
	}
	function delete_progress(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM task_progress where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_restriction(){
		extract($_POST);
		$filtered = implode(",",array_filter($rid));
		if(!empty($filtered))
			$this->db->query("DELETE FROM restriction_list where id not in ($filtered) and academic_id = $academic_id");
		else
			$this->db->query("DELETE FROM restriction_list where  academic_id = $academic_id");
		foreach($rid as $k => $v){
			$data = " academic_id = $academic_id ";
			$data .= ", faculty_id = {$faculty_id[$k]} ";
			$data .= ", class_id = {$class_id[$k]} ";
			$data .= ", subject_id = {$subject_id[$k]} ";
			if(empty($v)){
				$save[] = $this->db->query("INSERT INTO restriction_list set $data ");
			}else{
				$save[] = $this->db->query("UPDATE restriction_list set $data where id = $v ");
			}
		}
			return 1;
	}

	function save_evaluation(){
		extract($_POST);
		$data = " student_id = {$_SESSION['login_id']} ";
		$data .= ", academic_id = $academic_id ";
		$data .= ", subject_id = $subject_id ";
		$data .= ", class_id = $class_id ";
		$data .= ", restriction_id = $restriction_id ";
		$data .= ", faculty_id = $faculty_id ";
		$save = $this->db->query("INSERT INTO evaluation_list set $data");
		if($save){
			$eid = $this->db->insert_id;
			foreach($qid as $k => $v){
				$data = " evaluation_id = $eid ";
				$data .= ", question_id = $v ";
				$data .= ", rate = {$rate[$v]} ";
				$ins[] = $this->db->query("INSERT INTO evaluation_answers set $data ");
			}
			if(isset($ins))
				return 1;
		}
	}
	function get_class(){
		extract($_POST);
		$data = array();
		$get = $this->db->query("SELECT c.id,concat(c.curriculum,' ',c.level,' - ',c.section) as class,s.id as sid,concat(s.code,' - ',s.subject) as subj FROM restriction_list r inner join class_list c on c.id = r.class_id inner join subject_list s on s.id = r.subject_id where r.faculty_id = {$fid} and academic_id = {$_SESSION['academic']['id']} ");
		while($row= $get->fetch_assoc()){
			$data[]=$row;
		}
		return json_encode($data);

	}
	function get_report(){
		extract($_POST);
		$data = array();
		$get = $this->db->query("SELECT * FROM evaluation_answers where evaluation_id in (SELECT evaluation_id FROM evaluation_list where academic_id = {$_SESSION['academic']['id']} and faculty_id = $faculty_id and subject_id = $subject_id and class_id = $class_id ) ");
		$answered = $this->db->query("SELECT * FROM evaluation_list where academic_id = {$_SESSION['academic']['id']} and faculty_id = $faculty_id and subject_id = $subject_id and class_id = $class_id");
			$rate = array();
		while($row = $get->fetch_assoc()){
			if(!isset($rate[$row['question_id']][$row['rate']]))
			$rate[$row['question_id']][$row['rate']] = 0;
			$rate[$row['question_id']][$row['rate']] += 1;

		}
		// $data[]= $row;
		$ta = $answered->num_rows;
		$r = array();
		foreach($rate as $qk => $qv){
			foreach($qv as $rk => $rv){
			$r[$qk][$rk] =($rate[$qk][$rk] / $ta) *100;
		}
	}
	$data['tse'] = $ta;
	$data['data'] = $r;
		
		return json_encode($data);

	}
}
