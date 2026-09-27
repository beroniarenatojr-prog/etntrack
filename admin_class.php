<?php
session_start();
ini_set('display_errors', 1);
Class Action {
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
		extract($_POST);
		$type = array("","users","faculty_list","student_list");
		$type2 = array("","admin","faculty","student");
			$qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM {$type[$login]} where email = '".$email."' and password = '".md5($password)."'  ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
					$_SESSION['login_type'] = $login;
					$_SESSION['login_view_folder'] = $type2[$login].'/';
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
			return 1;
		}
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

	function update_user(){
		extract($_POST);
		$data = "";
		$type = array("","users","faculty_list","student_list");
	foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','table','password')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$check = $this->db->query("SELECT * FROM {$type[$_SESSION['login_type']]} where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(!empty($password))
			$data .= " ,password=md5('$password') ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO {$type[$_SESSION['login_type']]} set $data");
		}else{
			echo "UPDATE {$type[$_SESSION['login_type']]} set $data where id = $id";
			$save = $this->db->query("UPDATE {$type[$_SESSION['login_type']]} set $data where id = $id");
		}

		if($save){
			foreach ($_POST as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id);
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

function approve_reportadmin(){

    extract($_POST);

    $save = $this->db->query("
        UPDATE uploaded_reports
        SET status='Approved'
        WHERE id='$id'
    ");

    return $save ? 1 : 0;
}


function reject_reportadmin(){

    extract($_POST);

    $save = $this->db->query("
        UPDATE uploaded_reports
        SET status='Rejected'
        WHERE id='$id'
    ");

    return $save ? 1 : 0;
}

/*
|--------------------------------------------------------------------------
| REPORT FILTERS
| Builds the WHERE conditions shared by the admin and coordinator report
| lists from the report type tab, status tab, search box and dropdowns.
|--------------------------------------------------------------------------
*/
private function report_filters($src, $prefix = '', $with_type = true){

    $report_type = isset($src['report_type']) && $src['report_type'] == 'Progress Report'
        ? 'Progress Report'
        : 'Terminal Report';

    $where = $with_type ? array("{$prefix}report_type = '$report_type'") : array();
    $filtered = false;
    $status = '';

    if(!empty($src['search'])){
        $search = $this->db->real_escape_string(trim($src['search']));
        $where[] = "({$prefix}report_title LIKE '%$search%' OR {$prefix}file_name LIKE '%$search%')";
        $filtered = true;
    }

    if(!empty($src['status']) && in_array($src['status'], array('Pending','Approved','Rejected'))){
        $status = $src['status'];
        $where[] = "{$prefix}status = '$status'";
    }

    if(!empty($src['category']) && in_array($src['category'], array('Research','Extension','Training'))){
        $where[] = "{$prefix}Category = '{$src['category']}'";
        $filtered = true;
    }

    if(!empty($src['coordinator'])){
        $where[] = "{$prefix}uploaded_by = ".(int)$src['coordinator'];
        $filtered = true;
    }

    // e.g. "pending terminal reports"
    $label = strtolower(trim($status.' '.$report_type)).'s';

    if($filtered){
        $empty = "No $label match your filters.";
    }elseif($status){
        $empty = "No $label.";
    }else{
        $empty = "No $label uploaded yet.";
    }

    return array(
        'where' => $where ? implode(' AND ', $where) : '1',
        'empty' => '<tr><td colspan="7" class="text-center text-muted py-4">'.$empty.'</td></tr>'
    );
}

/*
|--------------------------------------------------------------------------
| REPORT COUNTS
| Numbers for the report type and status tab badges (following the same
| search/category/coordinator filters as the list) and the overall totals.
| Coordinators only count their own reports.
|--------------------------------------------------------------------------
*/
function report_counts(){

    $src = $_POST;
    unset($src['status']);

    $scope = "1";

    if($_SESSION['login_type'] != 1){
        unset($src['coordinator']);
        $scope = "uploaded_by = ".(int)$_SESSION['login_id'];
    }

    $filters = $this->report_filters($src, '', false);

    $empty = array('Total' => 0, 'Pending' => 0, 'Approved' => 0, 'Rejected' => 0);

    $types = array(
        'Terminal Report' => $empty,
        'Progress Report' => $empty
    );

    $q = $this->db->query("
        SELECT report_type, status, COUNT(*) AS total
        FROM uploaded_reports
        WHERE $scope AND {$filters['where']}
        GROUP BY report_type, status
    ");

    while($r = $q->fetch_assoc()){

        $status = ucfirst(strtolower($r['status']));

        $types[$r['report_type']]['Total'] += $r['total'];

        if(isset($types[$r['report_type']][$status])){
            $types[$r['report_type']][$status] += $r['total'];
        }
    }

    $overall = $empty;

    $q = $this->db->query("
        SELECT status, COUNT(*) AS total
        FROM uploaded_reports
        WHERE $scope
        GROUP BY status
    ");

    while($r = $q->fetch_assoc()){

        $status = ucfirst(strtolower($r['status']));

        $overall['Total'] += $r['total'];

        if(isset($overall[$status])){
            $overall[$status] += $r['total'];
        }
    }

    return json_encode(array('types' => $types, 'overall' => $overall));
}

function list_reportsadmin(){

    $filters = $this->report_filters($_POST, 'r.');

    $output = "";
    $i = 1;

    $q = $this->db->query("
        SELECT r.*, CONCAT(f.firstname,' ',f.lastname) AS uploader
        FROM uploaded_reports r
        LEFT JOIN faculty_list f
            ON r.uploaded_by = f.id
        WHERE {$filters['where']}
        ORDER BY r.uploaded_at DESC
    ");

    if($q->num_rows == 0){
        return $filters['empty'];
    }

    while($r = $q->fetch_assoc()){
 
        if($r['status'] == "Approved"){ 
            $status = "<span class='badge badge-success'>Approved</span>"; 
        } 
        elseif($r['status'] == "Rejected"){ 
            $status = "<span class='badge badge-danger'>Rejected</span>"; 
        } 
        else{ 
            $status = "<span class='badge badge-warning'>Pending</span>"; 
        } 
 
        $output .= ' 
        <tr> 
            <td>'.$i++.'</td> 
 
            <td>'.$r['report_title'].'</td>
 
            <td>'.$r['file_name'].'</td> 
 
            <td>'.(!empty($r['uploader']) ? $r['uploader'] : 'Unknown').'</td> 
 
            <td>'.date("F d, Y h:i A",strtotime($r['uploaded_at'])).'</td> 
 
            <td>'.$status.'</td> 
 
            <td> 
 
                <a href="uploads/reports/'.$r['file_name'].'" 
                   target="_blank" 
                   class="btn btn-info btn-sm"> 
                    <i class="fa fa-eye"></i> View 
                </a>'; 
 
        /* =========================================
           PENDING  -> Approve or Reject
           REJECTED -> can still be Approved
        ========================================= */ 
 
        if($r['status'] != "Approved"){ 
 
            $output .= ' 
                <button class="btn btn-success btn-sm approve-report" 
                        data-id="'.$r['id'].'"> 
                    <i class="fa fa-check"></i> Approve 
                </button>'; 
        } 
 
        if($r['status'] == "Pending"){ 
 
            $output .= ' 
                <button class="btn btn-danger btn-sm reject-report" 
                        data-id="'.$r['id'].'"> 
                    <i class="fa fa-times"></i> Reject 
                </button>'; 
        } 
 
        /* =========================================
           DELETE
           Always available
        ========================================= */ 
 
        $output .= ' 
                <button class="btn btn-outline-danger btn-sm action-btn delete-report" 
                        data-id="'.$r['id'].'" 
                        title="Delete Report"> 
                    <i class="fa fa-trash"></i> 
                </button> 
 
            </td> 
        </tr>'; 
    } 
 
    return $output; 
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
	function save_criteria(){
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
		$chk = $this->db->query("SELECT * FROM criteria_list where (".str_replace(",",'and',$data).") and id != '{$id}' ")->num_rows;
		if($chk > 0){
			return 2;
		}
		
		if(empty($id)){
			$lastOrder= $this->db->query("SELECT * FROM criteria_list order by abs(order_by) desc limit 1");
		$lastOrder = $lastOrder->num_rows > 0 ? $lastOrder->fetch_array()['order_by'] + 1 : 0;
		$data .= ", order_by='$lastOrder' ";
			$save = $this->db->query("INSERT INTO criteria_list set $data");
		}else{
			$save = $this->db->query("UPDATE criteria_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_criteria(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM criteria_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_criteria_order(){
		extract($_POST);
		$data = "";
		foreach($criteria_id as $k => $v){
			$update[] = $this->db->query("UPDATE criteria_list set order_by = $k where id = $v");
		}
		if(isset($update) && count($update)){
			return 1;
		}
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
        SELECT a.*, CONCAT(f.firstname, ' ', f.lastname) AS coordinator
        FROM activities a
        LEFT JOIN faculty_list f ON f.id = a.faculty_id
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
                'purpose' => (string)$row['purpose']
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

    $activity_name = $this->db->real_escape_string($_POST['activity_name']);
    $purpose = $this->db->real_escape_string($_POST['purpose']);
    $description = $this->db->real_escape_string($_POST['description']);
    $venue = $this->db->real_escape_string($venue_text);

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
        )
    ");

    if(!$save){
        return $this->db->error;
    }

    $this->store_activity_images($this->db->insert_id, $files);

    return 1;
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

    $qry = $this->db->query("SELECT status FROM activities WHERE id = $id AND faculty_id = $faculty_id");

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

    $activity_name = $this->db->real_escape_string($_POST['activity_name']);
    $purpose = $this->db->real_escape_string($_POST['purpose']);
    $description = $this->db->real_escape_string($_POST['description']);
    $venue = $this->db->real_escape_string($venue_text);

    $save = $this->db->query("
        UPDATE activities SET
            activity_name = '$activity_name',
            purpose = '$purpose',
            description = '$description',
            activity_date = '$activity_date',
            venue = '$venue',
            status = 'pending',
            revision_note = NULL
        WHERE id = $id AND faculty_id = $faculty_id
    ");

    if(!$save){
        return $this->db->error;
    }

    if($remove){
        $this->delete_activity_images("activity_id = $id AND id IN (".implode(',', $remove).")");
    }

    $this->store_activity_images($id, $files);

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
        array('a.activity_name', "CONCAT(f.firstname, ' ', f.lastname)", 'a.venue'),
        'a.'
    );

    $qry = $this->db->query("
        SELECT a.*, CONCAT(f.firstname, ' ', f.lastname) AS implementer
        FROM activities a
        LEFT JOIN faculty_list f ON f.id = a.faculty_id
        WHERE 1 $filters ".($mine_only ? "AND a.faculty_id = $user_id" : "")."
        ORDER BY a.activity_date DESC, a.id DESC
    ");

    $rows = array();

    while($row = $qry->fetch_assoc()){

        $own = (int)$row['faculty_id'] === $user_id && !$is_admin;
        $rows[] = array(
            'id' => (int)$row['id'],
            'ref' => sprintf('ACT-%04d', $row['id']),
            'title' => $row['activity_name'],
            'date' => $row['activity_date'],
            'date_display' => date('M d, Y', strtotime($row['activity_date'])),
            'venue' => (string)$row['venue'],
            'implementer' => !empty($row['implementer']) ? $row['implementer'] : 'Unknown coordinator',
            'status' => $row['status'],
            'status_label' => $this->activity_status_label($row['status']),
            'revision_note' => $row['revision_note'],
            'purpose' => (string)$row['purpose'],
            'description' => (string)$row['description'],
            'images' => array(),   // filled below
            'own' => $own,
            'can_edit' => $own && in_array($row['status'], array('pending', 'revision')),
            'can_delete' => $is_admin || $own,
            'can_review' => $is_admin
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

    if($do == 'approve' || $do == 'reject'){

        if(!$is_admin){
            return "Only the admin can approve or reject activities.";
        }

        if($do == 'reject'){
            $this->db->query("UPDATE activities SET status = 'rejected', revision_note = NULL WHERE id IN ($in)");
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

            $this->db->query("UPDATE activities SET status = 'approved', revision_note = NULL WHERE id = ".(int)$row['id']);
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

        return 1;
    }

    return "Unknown action.";
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

    $qry = $this->db->query("SELECT status FROM activities WHERE id = $id");

    if($qry->num_rows == 0 || $qry->fetch_assoc()['status'] != 'pending'){
        return "Only pending activities can be sent back for revision.";
    }

    $note = $this->db->real_escape_string($note);

    $save = $this->db->query("UPDATE activities SET status = 'revision', revision_note = '$note' WHERE id = $id");

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


	function save_question(){
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
		
		if(empty($id)){
			$lastOrder= $this->db->query("SELECT * FROM question_list where academic_id = $academic_id order by abs(order_by) desc limit 1");
			$lastOrder = $lastOrder->num_rows > 0 ? $lastOrder->fetch_array()['order_by'] + 1 : 0;
			$data .= ", order_by='$lastOrder' ";
			$save = $this->db->query("INSERT INTO question_list set $data");
		}else{
			$save = $this->db->query("UPDATE question_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_question(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM question_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_question_order(){
		extract($_POST);
		$data = "";
		foreach($qid as $k => $v){
			$update[] = $this->db->query("UPDATE question_list set order_by = $k where id = $v");
		}
		if(isset($update) && count($update)){
			return 1;
		}
	}
	function save_faculty(){
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
		$check = $this->db->query("SELECT * FROM faculty_list where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		$check = $this->db->query("SELECT * FROM faculty_list where school_id ='$school_id' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 3;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
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
		extract($_POST);
		$delete = $this->db->query("DELETE FROM faculty_list where id = ".$id);
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

	function upload_report(){

    extract($_POST);

    if(!isset($_FILES['report']) || $_FILES['report']['error'] != 0){
        return "No file uploaded.";
    }

    if(!isset($report_type) || !in_array($report_type, array('Terminal Report','Progress Report'))){
        return "Invalid report type.";
    }

    $uploaded_by = $_SESSION['login_id'];

    $file = $_FILES['report'];
    $filename = time().'_'.$file['name'];

    if(!is_dir('uploads/reports')){
        mkdir('uploads/reports',0777,true);
    }

    if(move_uploaded_file($file['tmp_name'],'uploads/reports/'.$filename)){

        $save = $this->db->query("
            INSERT INTO uploaded_reports
            (report_title, report_type, file_name, uploaded_by, Category)
            VALUES
            ('$title', '$report_type', '$filename', '$uploaded_by', '$category')
        ");

        if($save){
            return 1;
        }else{
            return $this->db->error;
        }

    }

    return "Upload Failed";
}


function reject_report(){

    extract($_POST);

    $save = $this->db->query("
        UPDATE uploaded_reports
        SET status='Rejected'
        WHERE id='$id'
    ");

    return $save ? 1 : 0;
}

function list_reports(){

    $uploaded_by = $_SESSION['login_id'];

    // Coordinators only ever see their own reports
    $src = $_GET;
    unset($src['coordinator']);

    $filters = $this->report_filters($src);

    $sort = array(
        'newest' => 'uploaded_at DESC',
        'oldest' => 'uploaded_at ASC',
        'title'  => 'report_title ASC'
    );

    $order = isset($_GET['sort']) && isset($sort[$_GET['sort']])
        ? $sort[$_GET['sort']]
        : $sort['newest'];

    $output = "";
    $i = 1;

    $q = $this->db->query("
        SELECT *
        FROM uploaded_reports
        WHERE uploaded_by = '$uploaded_by'
        AND {$filters['where']}
        ORDER BY $order
    ");

    if($q->num_rows == 0){
        return $filters['empty'];
    }

    while($r = $q->fetch_assoc()){

        $file = "uploads/reports/".$r['file_name'];

        // File size
        $size = file_exists($file) ? round(filesize($file)/1048576,2)." MB" : "-";

        // Category
        $category = !empty($r['Category']) ? $r['Category'] : 'General';

        // Status Badge
        if(strtolower($r['status']) == "approved"){

            $status = '<span class="badge badge-success">
                            <i class="fa fa-check-circle"></i> Approved
                       </span>';

        }elseif(strtolower($r['status']) == "rejected"){

            $status = '<span class="badge badge-danger">
                            <i class="fa fa-times-circle"></i> Rejected
                       </span>';

        }elseif(strtolower($r['status']) == "pending"){

            $status = '<span class="badge badge-warning">
                            <i class="fa fa-clock"></i> Pending
                       </span>';

        }else{

            $status = '<span class="badge badge-secondary">'.$r['status'].'</span>';

        }

        $output .= '

        <tr>

            <td>'.$i++.'</td>

            <td>

                <div class="d-flex align-items-center">

                    <div class="report-icon mr-3">
                        <i class="fa fa-file-pdf text-danger"></i>
                    </div>

                    <div>

                        <strong>'.$r['report_title'].'</strong>

                        <br>

                        <small class="text-muted">'.$r['file_name'].'</small>

                    </div>

                </div>

            </td>

            <td>

                <span class="badge badge-info">
                    '.$category.'
                </span>

            </td>

            <td>

                '.date("M d, Y",strtotime($r['uploaded_at'])).'

                <br>

                <small class="text-muted">
                    '.date("h:i A",strtotime($r['uploaded_at'])).'
                </small>

            </td>

            <td>'.$size.'</td>

            <td>'.$status.'</td>

            <td>

                <a href="'.$file.'"
                   target="_blank"
                   class="btn btn-outline-success btn-sm action-btn">
                    <i class="fa fa-eye"></i>
                </a>

                <a href="'.$file.'"
                   download
                   class="btn btn-outline-primary btn-sm action-btn">
                    <i class="fa fa-download"></i>
                </a>

                <button
                    class="btn btn-outline-danger btn-sm action-btn"
                    onclick="delete_report('.$r['id'].')">
                    <i class="fa fa-trash"></i>
                </button>

            </td>

        </tr>';

    }

    return $output;
}
function approve_report(){

    extract($_POST);

    $save = $this->db->query("
        UPDATE uploaded_reports
        SET status='Approved'
        WHERE id='$id'
    ");

    return $save ? 1 : 0;
}


////admin 
function delete_report(){

    extract($_POST);

    $q = $this->db->query("SELECT file_name FROM uploaded_reports WHERE id='$id'");

    if($q->num_rows > 0){

        $row = $q->fetch_assoc();

        $file = "uploads/reports/".$row['file_name'];

        if(file_exists($file)){
            unlink($file);
        }

    }

    $delete = $this->db->query("DELETE FROM uploaded_reports WHERE id='$id'");

    if($delete){
        return 1;
    }else{
        return $this->db->error;
    }

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
