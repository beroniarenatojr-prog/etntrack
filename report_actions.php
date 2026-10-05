<?php

/*
|--------------------------------------------------------------------------
| REPORTS (Progress and Terminal Reports)
|--------------------------------------------------------------------------
| Every report belongs to a project and shows up in that project's
| Post-Activity tab as well as on the global Reports page. Used by the Action
| class in admin_class.php (see `use ReportActions`); ajax.php says who may
| call each action.
|
| Review, as with the pre-activity documents:
|   Pending  -> Approved            (locked from then on)
|            -> Revision            (sent back with remarks; editing it resubmits it as Pending)
|            -> Rejected            (kept, with the reason)
|
| Files are PDF or Word only, checked by their contents, saved under a random
| name in uploads/reports/ and handed out by report_file.php to people allowed
| to see them.
|
| Reports saved before projects were linked have no project; they show as
| "Unassigned project" until someone links them (report_assign).
*/

trait ReportActions {

	private function report_types(){
		return array('Progress Report', 'Terminal Report');
	}

	private function report_categories(){
		return array('Research', 'Extension', 'Training');
	}

	private function report_status_label($status){
		$labels = array(
			'Pending' => 'Pending Review',
			'Approved' => 'Approved',
			'Revision' => 'Needs Revision',
			'Rejected' => 'Rejected'
		);
		return $labels[$status] ?? $status;
	}

	private function report_json($ok, $extra = array()){
		return json_encode(array_merge(array('ok' => $ok), $extra));
	}

	private function report_fail($message){
		return $this->report_json(false, array('error' => $message));
	}

	// One report as the pages show it
	private function report_row($row){

		$user = (int)($_SESSION['login_id'] ?? 0);
		$is_admin = $this->is_admin();
		$own = (int)$row['uploaded_by'] === $user && !$is_admin;
		$status = $row['status'];
		$project_id = isset($row['project_id']) && $row['project_id'] !== null ? (int)$row['project_id'] : null;
		$path = 'uploads/reports/'.basename((string)$row['file_name']);

		$period = '';
		if(!empty($row['period_start']) && !empty($row['period_end'])){
			$period = date('M j, Y', strtotime($row['period_start'])).' – '.date('M j, Y', strtotime($row['period_end']));
		}elseif(!empty($row['period_start'])){
			$period = 'From '.date('M j, Y', strtotime($row['period_start']));
		}

		return array(
			'id' => (int)$row['id'],
			'ref' => sprintf('RPT-%04d', $row['id']),
			'title' => (string)$row['report_title'],
			'type' => $row['report_type'],
			'category' => (string)$row['Category'],
			'description' => (string)($row['description'] ?? ''),
			'period_start' => $row['period_start'] ?? null,
			'period_end' => $row['period_end'] ?? null,
			'period' => $period,
			'project_id' => $project_id,
			'project_ref' => $project_id ? sprintf('PRJ-%04d', $project_id) : '',
			'project_title' => (string)($row['project_title'] ?? ''),
			'coordinator' => !empty($row['uploader']) ? $row['uploader'] : 'Unknown coordinator',
			'uploaded_at' => $row['uploaded_at'],
			'uploaded_date' => date('Y-m-d', strtotime($row['uploaded_at'])),
			'uploaded_display' => date('M d, Y', strtotime($row['uploaded_at'])),
			'uploaded_time' => date('h:i A', strtotime($row['uploaded_at'])),
			'status' => $status,
			'status_label' => $this->report_status_label($status),
			'remarks' => (string)($row['admin_remarks'] ?? ''),
			'reviewed_by' => (string)($row['reviewer'] ?? ''),
			'reviewed_at' => !empty($row['reviewed_at']) ? date('M d, Y', strtotime($row['reviewed_at'])) : '',
			'file_url' => 'report_file.php?id='.(int)$row['id'],
			'file_kind' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
			'size' => is_file($path) ? round(filesize($path) / 1048576, 2).' MB' : '-',
			'bytes' => is_file($path) ? (int)filesize($path) : 0,
			'own' => $own,
			'can_edit' => $own && $status !== 'Approved',
			'can_delete' => $is_admin || ($own && $status !== 'Approved'),
			'can_review' => $is_admin,
			'can_assign' => $is_admin || ((int)$row['uploaded_by'] === $user && !$project_id)
		);
	}

	/*
	| The reports someone may see: the admin all of them, a coordinator their
	| own and any report that belongs to one of their projects.
	*/
	private function report_scope($alias = 'r'){
		if($this->is_admin()){
			return "1";
		}
		$user = (int)($_SESSION['login_id'] ?? 0);
		return "($alias.uploaded_by = $user OR $alias.project_id IN
			(SELECT id FROM projects WHERE faculty_id = $user OR created_by = $user))";
	}

	// Filters from the Reports page: type, status, project, coordinator, category, search
	private function report_where($src){

		$where = array($this->report_scope('r'));

		if(!empty($src['type']) && in_array($src['type'], $this->report_types(), true)){
			$where[] = "r.report_type = '".$src['type']."'";
		}

		if(!empty($src['status']) && in_array($src['status'], array('Pending', 'Approved', 'Revision', 'Rejected'), true)){
			$where[] = "r.status = '".$src['status']."'";
		}

		if(isset($src['project']) && $src['project'] !== ''){
			$where[] = $src['project'] === 'none' ? "r.project_id IS NULL" : "r.project_id = ".(int)$src['project'];
		}

		if(!empty($src['coordinator']) && $this->is_admin()){
			$where[] = "r.uploaded_by = ".(int)$src['coordinator'];
		}

		if(!empty($src['category']) && in_array($src['category'], $this->report_categories(), true)){
			$where[] = "r.Category = '".$src['category']."'";
		}

		if(!empty($src['search'])){
			$search = $this->db->real_escape_string(trim($src['search']));
			$where[] = "(r.report_title LIKE '%$search%' OR p.title LIKE '%$search%'
				OR CONCAT(f.firstname, ' ', f.lastname) LIKE '%$search%')";
		}

		return implode(' AND ', $where);
	}

	function report_list(){

		if(!$this->links_ready()){
			return $this->report_fail('Apply the latest database update in System Update to see reports.');
		}

		$sort = array('newest' => 'r.uploaded_at DESC', 'oldest' => 'r.uploaded_at ASC', 'title' => 'r.report_title ASC');
		$order = $sort[$_GET['sort'] ?? 'newest'] ?? $sort['newest'];

		$qry = $this->db->query("
			SELECT r.*, p.title AS project_title,
				CONCAT(f.firstname, ' ', f.lastname) AS uploader,
				CONCAT(u.firstname, ' ', u.lastname) AS reviewer
			FROM uploaded_reports r
			LEFT JOIN projects p ON p.id = r.project_id
			LEFT JOIN faculty_list f ON f.id = r.uploaded_by
			LEFT JOIN users u ON u.id = r.reviewed_by
			WHERE ".$this->report_where($_GET)."
			ORDER BY $order, r.id DESC
		");

		$rows = array();

		while($row = $qry->fetch_assoc()){
			$rows[] = $this->report_row($row);
		}

		return $this->report_json(true, array('data' => $rows));
	}

	// Numbers for the cards and tabs, with the same filters except type and status
	function report_summary(){

		if(!$this->links_ready()){
			return $this->report_json(true, array('types' => array(), 'overall' => array(), 'unassigned' => 0, 'impact' => 0));
		}

		$src = $_GET;
		unset($src['type'], $src['status']);
		$where = $this->report_where($src);

		$empty = array('Total' => 0, 'Pending' => 0, 'Approved' => 0, 'Revision' => 0, 'Rejected' => 0);
		$types = array('Progress Report' => $empty, 'Terminal Report' => $empty);
		$overall = $empty;

		$qry = $this->db->query("
			SELECT r.report_type, r.status, COUNT(*) AS total
			FROM uploaded_reports r
			LEFT JOIN projects p ON p.id = r.project_id
			LEFT JOIN faculty_list f ON f.id = r.uploaded_by
			WHERE $where
			GROUP BY r.report_type, r.status
		");

		while($row = $qry->fetch_assoc()){
			$type = $row['report_type'];
			$status = $row['status'];
			$count = (int)$row['total'];
			if(!isset($types[$type])) continue;
			$types[$type]['Total'] += $count;
			$overall['Total'] += $count;
			if(isset($types[$type][$status])){
				$types[$type][$status] += $count;
				$overall[$status] += $count;
			}
		}

		$unassigned = (int)$this->db->query("
			SELECT COUNT(*) AS c FROM uploaded_reports r WHERE ".$this->report_scope('r')." AND r.project_id IS NULL
		")->fetch_assoc()['c'];

		return $this->report_json(true, array(
			'types' => $types,
			'overall' => $overall,
			'unassigned' => $unassigned,
			'impact' => count($this->impact_rows())
		));
	}

	/*
	| Saves a new report (coordinators) or changes one of yours that is not
	| approved yet. Changing a report sends it back for review as Pending.
	*/
	function report_save(){

		if(!$this->links_ready()){
			return $this->report_fail('Apply the latest database update in System Update first.');
		}

		if($this->is_admin()){
			return $this->report_fail('Reports are submitted by the coordinator of the project.');
		}

		$user = (int)$_SESSION['login_id'];
		$id = (int)($_POST['id'] ?? 0);
		$existing = null;

		if($id){
			$existing = $this->db->query("SELECT * FROM uploaded_reports WHERE id = $id AND uploaded_by = $user")->fetch_assoc();
			if(!$existing){
				return $this->report_fail('You can only change your own reports.');
			}
			if($existing['status'] === 'Approved'){
				return $this->report_fail('This report is already approved. Ask the Extension Office if it needs changing.');
			}
		}

		$project_id = (int)($_POST['project_id'] ?? 0);

		if($project_id <= 0){
			return $this->report_fail('Choose the project this report is for.');
		}

		if(!$this->project_for_user($project_id)){
			return $this->report_fail('You can only submit reports for your own projects.');
		}

		$type = (string)($_POST['report_type'] ?? '');

		if(!in_array($type, $this->report_types(), true)){
			return $this->report_fail('Choose the type of report.');
		}

		$title = trim((string)($_POST['title'] ?? ''));

		if($title === ''){
			return $this->report_fail('Please enter the report title.');
		}

		$category = in_array($_POST['category'] ?? '', $this->report_categories(), true) ? $_POST['category'] : 'Extension';

		$dates = array();
		foreach(array('period_start', 'period_end') as $field){
			$value = trim((string)($_POST[$field] ?? ''));
			$dates[$field] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
		}

		if($dates['period_start'] && $dates['period_end'] && $dates['period_start'] > $dates['period_end']){
			return $this->report_fail('The reporting period ends before it starts.');
		}

		$file_name = $existing ? $existing['file_name'] : '';
		$new_file = isset($_FILES['report']) && !empty($_FILES['report']['tmp_name']);

		if(!$existing && !$new_file){
			return $this->report_fail('Please attach the report file.');
		}

		if($new_file){
			list($stored, $error) = $this->store_report_file($_FILES['report']);
			if($error){
				return $this->report_fail($error);
			}
			if($existing){
				$this->remove_report_file($existing['file_name']);
			}
			$file_name = $stored;
		}

		$values = array(
			'project_id' => $project_id,
			'report_type' => $type,
			'report_title' => mb_substr($title, 0, 255),
			'Category' => $category,
			'description' => trim((string)($_POST['description'] ?? '')),
			'period_start' => $dates['period_start'],
			'period_end' => $dates['period_end'],
			'file_name' => $file_name,
			'status' => 'Pending',
			'updated_at' => date('Y-m-d H:i:s')
		);

		if($existing){
			$this->report_write($values, $id);
		}else{
			$values['uploaded_by'] = $user;
			$id = $this->report_write($values);
		}

		$this->refresh_lifecycle_status($project_id);
		if($existing && (int)$existing['project_id'] && (int)$existing['project_id'] !== $project_id){
			$this->refresh_lifecycle_status((int)$existing['project_id']);
		}

		$this->audit_project($existing ? 'report_updated' : 'report_submitted', $project_id, $type.': '.$title);

		return $this->report_json(true, array('id' => $id));
	}

	// Insert or update with a prepared statement; null stays NULL
	private function report_write($values, $id = null){

		$columns = array_keys($values);
		$types = '';
		$params = array();

		foreach($values as $value){
			$types .= is_int($value) ? 'i' : 's';
			$params[] = $value;
		}

		if($id === null){
			$sql = "INSERT INTO uploaded_reports (`".implode('`, `', $columns)."`) VALUES ("
				.implode(', ', array_fill(0, count($columns), '?')).")";
		}else{
			$sql = "UPDATE uploaded_reports SET ".implode(', ', array_map(function($c){ return "`$c` = ?"; }, $columns))." WHERE id = ?";
			$types .= 'i';
			$params[] = (int)$id;
		}

		$stmt = $this->db->prepare($sql);
		$stmt->bind_param($types, ...$params);
		$stmt->execute();

		return $id === null ? (int)$this->db->insert_id : (int)$id;
	}

	// PDF or Word only, judged by the file's contents; saved under a random name
	private function store_report_file($file){

		if(empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])){
			return array('', 'The file could not be uploaded.');
		}

		if($file['size'] > 20971520){
			return array('', 'The file is larger than 20 MB.');
		}

		$head = (string)file_get_contents($file['tmp_name'], false, null, 0, 8);
		$named = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
		$ext = '';

		if(strncmp($head, '%PDF-', 5) === 0){
			$ext = 'pdf';
		}elseif(strncmp($head, "PK\x03\x04", 4) === 0 && $named === 'docx'){
			$ext = 'docx';
		}elseif(strncmp($head, "\xD0\xCF\x11\xE0", 4) === 0 && $named === 'doc'){
			$ext = 'doc';
		}

		if($ext === ''){
			return array('', 'Please attach the report as a PDF or Word document.');
		}

		if(!is_dir('uploads/reports')){
			mkdir('uploads/reports', 0755, true);
		}

		$name = time().'_'.bin2hex(random_bytes(8)).'.'.$ext;

		if(!move_uploaded_file($file['tmp_name'], 'uploads/reports/'.$name)){
			return array('', 'The file could not be saved.');
		}

		return array($name, '');
	}

	private function remove_report_file($file_name){
		$path = 'uploads/reports/'.basename((string)$file_name);
		if($file_name !== '' && is_file($path)){
			unlink($path);
		}
	}

	// The Extension Office's decision: approved, revision (remarks required) or rejected
	function report_review(){

		if(!$this->is_admin()){
			return $this->report_fail('Only the Extension Office can review reports.');
		}

		$id = (int)($_POST['id'] ?? 0);
		$decision = (string)($_POST['decision'] ?? '');
		$remarks = trim((string)($_POST['remarks'] ?? ''));

		$map = array('approved' => 'Approved', 'revision' => 'Revision', 'rejected' => 'Rejected');

		if(!isset($map[$decision])){
			return $this->report_fail('Unknown decision.');
		}

		if($decision === 'revision' && $remarks === ''){
			return $this->report_fail('Please write what needs to be changed.');
		}

		$report = $this->db->query("SELECT * FROM uploaded_reports WHERE id = $id")->fetch_assoc();

		if(!$report){
			return $this->report_fail('That report was not found.');
		}

		$stmt = $this->db->prepare("
			UPDATE uploaded_reports
			SET status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW()
			WHERE id = ?
		");
		$status = $map[$decision];
		$reviewer = (int)$_SESSION['login_id'];
		$stmt->bind_param('ssii', $status, $remarks, $reviewer, $id);
		$stmt->execute();

		if($report['project_id']){
			$this->refresh_lifecycle_status((int)$report['project_id']);
			$this->audit_project('report_'.$decision, (int)$report['project_id'], $report['report_type'].': '.$report['report_title']);
		}

		return $this->report_json(true, array('status' => $status));
	}

	// The admin may delete any report; a coordinator their own until it is approved
	function report_delete(){

		$id = (int)($_POST['id'] ?? 0);
		$report = $this->db->query("SELECT * FROM uploaded_reports WHERE id = $id")->fetch_assoc();

		if(!$report){
			return $this->report_fail('That report was not found.');
		}

		if(!$this->is_admin()){
			if((int)$report['uploaded_by'] !== (int)$_SESSION['login_id']){
				return $this->report_fail('You can only delete your own reports.');
			}
			if($report['status'] === 'Approved'){
				return $this->report_fail('An approved report can only be removed by the Extension Office.');
			}
		}

		$this->remove_report_file($report['file_name']);
		$this->db->query("DELETE FROM uploaded_reports WHERE id = $id");

		if($this->links_ready() && $report['project_id']){
			$this->refresh_lifecycle_status((int)$report['project_id']);
		}

		return $this->report_json(true);
	}

	/*
	| Links a report to its project: the admin for any report, a coordinator
	| for their own report that has no project yet (and only to their own
	| project). Nothing is linked by guessing.
	*/
	function report_assign(){

		if(!$this->links_ready()){
			return $this->report_fail('Apply the latest database update in System Update first.');
		}

		$id = (int)($_POST['id'] ?? 0);
		$project_id = (int)($_POST['project_id'] ?? 0);
		$report = $this->db->query("SELECT * FROM uploaded_reports WHERE id = $id")->fetch_assoc();

		if(!$report){
			return $this->report_fail('That report was not found.');
		}

		if(!$this->is_admin()){
			if((int)$report['uploaded_by'] !== (int)$_SESSION['login_id'] || $report['project_id']){
				return $this->report_fail('Only the Extension Office can move a report to another project.');
			}
		}

		if(!$this->project_for_user($project_id)){
			return $this->report_fail('Choose one of your projects.');
		}

		$this->db->query("UPDATE uploaded_reports SET project_id = $project_id WHERE id = $id");

		$this->refresh_lifecycle_status($project_id);
		if($report['project_id'] && (int)$report['project_id'] !== $project_id){
			$this->refresh_lifecycle_status((int)$report['project_id']);
		}

		$this->audit_project('report_linked', $project_id, $report['report_type'].': '.$report['report_title']);

		return $this->report_json(true);
	}

	/*
	| Impact assessments across the projects someone may see, for the
	| Impact Assessment tab of the Reports page. Each one is written and
	| reviewed inside its project.
	*/
	private function impact_rows(){

		if(!$this->links_ready()){
			return array();
		}

		$user = (int)($_SESSION['login_id'] ?? 0);
		$scope = $this->is_admin() ? "1" : "(p.faculty_id = $user OR p.created_by = $user)";

		$qry = $this->db->query("
			SELECT i.*, p.title AS project_title, CONCAT(f.firstname, ' ', f.lastname) AS coordinator
			FROM impact_assessments i
			INNER JOIN projects p ON p.id = i.project_id
			LEFT JOIN faculty_list f ON f.id = p.faculty_id
			WHERE $scope
			ORDER BY i.updated_at DESC, i.id DESC
		");

		$rows = array();

		while($row = $qry->fetch_assoc()){
			$rows[] = array(
				'id' => (int)$row['id'],
				'title' => $row['title'],
				'project_id' => (int)$row['project_id'],
				'project_ref' => sprintf('PRJ-%04d', $row['project_id']),
				'project_title' => $row['project_title'],
				'coordinator' => $row['coordinator'] ?: 'Not assigned',
				'status' => $row['status'],
				'status_label' => $this->status_label($row['status']),
				'updated_display' => date('M d, Y', strtotime($row['updated_at'])),
				'has_file' => !empty($row['file_name'])
			);
		}

		return $rows;
	}

	function impact_list(){
		return $this->report_json(true, array('data' => $this->impact_rows()));
	}
}
