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


function all_list_activity(){

    $output = "";

    $qry = $this->db->query("
        SELECT 
            a.*,
            CONCAT(f.firstname, ' ', f.lastname) AS faculty_name
        FROM activities a
        LEFT JOIN faculty_list f 
            ON a.faculty_id = f.id
        ORDER BY a.activity_date DESC, a.id DESC
    ");

    if(!$qry){

        return '
        <div class="no-activities">

            <i class="fa fa-exclamation-circle"></i>

            <h5>
                Unable to load activities
            </h5>

            <p>
                '.$this->db->error.'
            </p>

        </div>';

    }


    if($qry->num_rows == 0){

        return '
        <div class="no-activities">

            <i class="fa fa-calendar-o"></i>

            <h5>
                No Activities Found
            </h5>

            <p>
                There are no submitted activities yet.
            </p>

        </div>';

    }


    while($row = $qry->fetch_assoc()){


        /* =====================================================
           BASIC DATA
        ===================================================== */

        $id = (int)$row['id'];

        $activity_name = htmlspecialchars(
            $row['activity_name'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $purpose = htmlspecialchars(
            $row['purpose'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $description = htmlspecialchars(
            $row['description'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $venue = htmlspecialchars(
            $row['venue'] ?? 'No venue specified',
            ENT_QUOTES,
            'UTF-8'
        );


        /* =====================================================
           DATE
        ===================================================== */

        if(!empty($row['activity_date'])){

            $activity_date = date(
                "M d, Y",
                strtotime($row['activity_date'])
            );

        }else{

            $activity_date = "No date";

        }


        /* =====================================================
           FACULTY
        ===================================================== */

        $faculty_name = !empty($row['faculty_name'])
            ? htmlspecialchars(
                $row['faculty_name'],
                ENT_QUOTES,
                'UTF-8'
            )
            : "Unknown Faculty";


        /* =====================================================
           STATUS
        ===================================================== */

        $status = strtolower(
            trim($row['status'] ?? 'pending')
        );


        if($status == "approved"){

            $status_class = "approved";
            $status_text = "Approved";

        }
        elseif($status == "rejected"){

            $status_class = "rejected";
            $status_text = "Rejected";

        }
        else{

            $status_class = "pending";
            $status_text = "Pending";

        }


        /* =====================================================
           IMAGE
        ===================================================== */

        if(!empty($row['image'])){

            $image = htmlspecialchars(
                $row['image'],
                ENT_QUOTES,
                'UTF-8'
            );

            $image_html = '

                <img
                    src="uploads/activities/'.$image.'"
                    alt="'.$activity_name.'"
                    onerror="this.style.display=\'none\';">

            ';

        }else{

            $image_html = '

                <div class="activity-no-image">

                    <i class="fa fa-image"></i>

                    <span>
                        No Image
                    </span>

                </div>

            ';

        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        if(empty(trim($description))){

            $description = "No description provided.";

        }


        /* =====================================================
           CARD
        ===================================================== */

        $output .= '

        <div class="activity-item">


            <!-- IMAGE -->

            <div class="activity-item-image">

                '.$image_html.'

            </div>



            <!-- CONTENT -->

            <div class="activity-item-content">


                <!-- TITLE + STATUS -->

                <div class="activity-item-top">

                    <h5 class="activity-item-title">

                        '.$activity_name.'

                    </h5>


                    <span class="activity-badge '.$status_class.'">

                        '.$status_text.'

                    </span>

                </div>



                <!-- PURPOSE -->

                <div class="activity-item-purpose">

                    <strong>
                        Purpose:
                    </strong>

                    '.$purpose.'

                </div>



                <!-- DATE + VENUE + FACULTY -->

                <div class="activity-details">


                    <div class="activity-detail">

                        <i class="fa fa-calendar"></i>

                        '.$activity_date.'

                    </div>


                    <div class="activity-detail">

                        <i class="fa fa-map-marker"></i>

                        '.$venue.'

                    </div>


                    <div class="activity-detail">

                        <i class="fa fa-user"></i>

                        '.$faculty_name.'

                    </div>


                </div>



                <!-- DESCRIPTION -->

                <div class="activity-description">

                    <span class="activity-description-label">

                        Description:

                    </span>

                    <span class="activity-description-text">

                        '.$description.'

                    </span>

                </div>



                <!-- BOTTOM -->

                <div class="activity-item-bottom">


                    <span class="activity-submitted">

                        Submitted by '.$faculty_name.'

                    </span>



                    <!-- ACTIONS -->

                   

                      


                    </div>


                </div>


            </div>


        </div>

        ';

    }


    return $output;

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
function save_activity(){

    $faculty_id = $_SESSION['login_id'];

    $activity_name = $this->db->real_escape_string($_POST['activity_name']);
    $purpose = $this->db->real_escape_string($_POST['purpose']);
    $description = $this->db->real_escape_string($_POST['description']);
    $activity_date = $_POST['activity_date'];
    $venue = $this->db->real_escape_string($_POST['venue']);

    $image = "";

    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

        $image = time()."_".rand(1000,9999).".".$ext;

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "uploads/activities/".$image
        );
    }

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
            '$image'
        )
    ");

    if($save){
        return 1;
    }else{
        return $this->db->error;
    }

}


function delete_activity_admin(){

    if(!isset($_POST['id']) || empty($_POST['id'])){
        return 0;
    }

    $id = (int) $_POST['id'];

    // Get image filename before deleting the activity
    $qry = $this->db->query("
        SELECT image 
        FROM activities 
        WHERE id = $id
        LIMIT 1
    ");

    if($qry && $qry->num_rows > 0){

        $row = $qry->fetch_assoc();

        // Delete uploaded image
        if(!empty($row['image'])){

            $imagePath = "uploads/activities/" . basename($row['image']);

            if(file_exists($imagePath)){
                unlink($imagePath);
            }
        }
    }

    // Delete activity record
    $delete = $this->db->query("
        DELETE FROM activities 
        WHERE id = $id
    ");

    if($delete){
        return 1;
    }

    return 0;
}








	function list_activity(){

	    $faculty_id = $_SESSION['login_id'];

	    $qry = $this->db->query("
	        SELECT *
	        FROM activities
	        WHERE faculty_id='$faculty_id'
	        ORDER BY activity_date DESC
	    ");

	    $i = 1;

	    while($row = $qry->fetch_assoc()){

	        $img = !empty($row['image'])
	            ? "uploads/activities/".$row['image']
	            : "assets/img/no-image.png";

	        // Status Badge
	        if($row['status'] == "approved"){
	            $status = "<span class='badge badge-success'>✔ Approved</span>";
	        }elseif($row['status'] == "rejected"){
	            $status = "<span class='badge badge-danger'>✖ Rejected</span>";
	        }else{
	            $status = "<span class='badge badge-warning'>⏳ Pending</span>";
	        }

	        echo "
	        <tr>

	            <td>".$i++."</td>

	            <td>
	                <img src='".$img."' class='activity-img'>
	            </td>

	            <td>
	                <strong>".$row['activity_name']."</strong>
	            </td>

	            <td>".date("M d, Y",strtotime($row['activity_date']))."</td>

	            <td>".$row['venue']."</td>

	            <td>".$status."</td>

	            <td>";

	        
	            echo "
	                <button class='action-btn delete-btn'
        onclick='delete_activity(".$row['id'].")'
        title='Delete'>
    <i class='fa fa-trash'></i>
</button>

	            </td>

	        </tr>";

	    }

	}

function update_activity_status(){

    extract($_POST);

    include 'db_connect.php';

    $save = $conn->query("UPDATE activities SET status='$status' WHERE id=$id");

    if($save){
        return 1;
    }
}


function delete_activity(){

    $id = $_GET['id'];

    $qry = $this->db->query("SELECT image FROM activities WHERE id='$id'");

    if($qry->num_rows > 0){

        $row = $qry->fetch_assoc();

        if(!empty($row['image']) && file_exists("uploads/activities/".$row['image'])){
            unlink("uploads/activities/".$row['image']);
        }

    }

    $delete = $this->db->query("DELETE FROM activities WHERE id='$id'");

    if($delete){
        return 1;
    }

}	function save_question(){
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
