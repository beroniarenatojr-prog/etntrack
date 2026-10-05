<?php

/*
|--------------------------------------------------------------------------
| PROJECT LIFECYCLE
|--------------------------------------------------------------------------
| Everything to do with an extension project and the documents that belong
| to it. Used by the Action class in admin_class.php (see `use ProjectActions`).
|
| A project is the parent record. Its pre-activity documents all follow the
| same review workflow, so one set of functions handles all six types; the
| differences live in doc_types() below.
*/

trait ProjectActions {

	/* =====================================================================
	   THE DOCUMENT TYPES
	   Each entry says which table it lives in, what it is called, and which
	   fields it has. Adding a type here is all that is needed to make it
	   save, review and show up in the lifecycle.
	===================================================================== */
	private function doc_types(){
		return array(

			'assessment' => array(
				'table' => 'assessment_reports',
				'label' => 'Assessment Report',
				'stage' => 'Assessment',
				'text'  => array('title','community','location','assessors','identified_needs','findings','recommendations','target_beneficiaries'),
				'dates' => array('assessment_date'),
				'numbers' => array()
			),

			'moa' => array(
				'table' => 'memorandum_agreements',
				'label' => 'Memorandum of Agreement',
				'stage' => 'MOA',
				'text'  => array('title','partner','description','school_responsibilities','partner_responsibilities','signatories'),
				'dates' => array('start_date','end_date','moa_date'),
				'numbers' => array()
			),

			'capsule' => array(
				'table' => 'capsules',
				'label' => 'Capsule',
				'stage' => 'Capsule',
				'text'  => array('title','rationale','objectives','target_beneficiaries','location','major_activities','expected_outputs','expected_outcomes','duration','prepared_by'),
				'dates' => array('date_prepared'),
				'numbers' => array()
			),

			'proposal' => array(
				'table' => 'proposals',
				'label' => 'Proposal',
				'stage' => 'Proposal',
				'text'  => array('title','background','problem_statement','objectives','target_beneficiaries','location','methodology','activities','timeline','expected_outputs','expected_outcomes','monitoring_plan','funding_source','sustainability_plan','prepared_by'),
				'dates' => array(),
				'numbers' => array('budget')
			),

			'preparation' => array(
				'table' => 'conduct_preparations',
				'label' => 'Conduct Preparation',
				'stage' => 'Conduct Preparation',
				'text'  => array('venue','target_beneficiaries','materials','equipment','logistics','assigned_personnel','notes'),
				'dates' => array('final_date'),
				'numbers' => array('expected_participants'),
				'times' => array('start_time','end_time'),
				'checks' => array('venue_confirmed','personnel_assigned','materials_prepared')
			)
		) + ($this->links_ready() ? array(
			// The last stage, years after the project: reviewed like the documents above
			'impact' => array(
				'table' => 'impact_assessments',
				'label' => 'Impact Assessment',
				'stage' => 'Impact Assessment',
				'group' => 'post',
				'text'  => array('title','findings','outcomes','beneficiary_impact','sustainability','recommendations'),
				'dates' => array('period_start','period_end','assessment_date'),
				'numbers' => array()
			)
		) : array());
	}

	/*
	| Whether the database update that links activities and reports to their
	| project (007_project_links) has been applied. New code reaches the server
	| as soon as it is pushed, but that update is applied by hand afterwards,
	| so until then the pages keep working without the new parts.
	*/
	private function links_ready(){
		static $ready = null;
		if($ready === null){
			$ready = $this->db->query("
				SELECT 1 FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'uploaded_reports' AND COLUMN_NAME = 'project_id'
			")->num_rows > 0 && $this->db->query("
				SELECT 1 FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'impact_assessments'
			")->num_rows > 0;
		}
		return $ready;
	}

	/*
	| When the impact assessment is due: the date the project was completed
	| (or, until then, its end date) plus its impact period, 3 years unless
	| the project says otherwise.
	|   state: not_due | due | unknown (no date to count from yet)
	*/
	private function impact_due($project){

		$years = max(1, (int)($project['impact_years'] ?? 3));
		$from = !empty($project['completed_at']) ? $project['completed_at'] : (!empty($project['end_date']) ? $project['end_date'] : null);

		if(!$from){
			return array('date' => null, 'display' => '', 'years' => $years, 'state' => 'unknown',
				'label' => 'Due '.$years.' year'.($years == 1 ? '' : 's').' after the project is completed',
				'from' => null);
		}

		$due = date('Y-m-d', strtotime($from.' +'.$years.' years'));
		$state = $due <= date('Y-m-d') ? 'due' : 'not_due';

		return array(
			'date' => $due,
			'display' => date('F j, Y', strtotime($due)),
			'years' => $years,
			'state' => $state,
			'label' => $state === 'due' ? 'Due for Assessment' : 'Not Yet Due',
			'from' => !empty($project['completed_at']) ? 'completion' : 'end_date'
		);
	}

	// The order the lifecycle is shown in, and which document each stage needs
	private function lifecycle_stages(){
		return array(
			array('key' => 'assessment',  'label' => 'Assessment'),
			array('key' => 'moa',         'label' => 'MOA'),
			array('key' => 'capsule',     'label' => 'Capsule'),
			array('key' => 'proposal',    'label' => 'Proposal'),
			array('key' => 'designation', 'label' => 'Designation'),
			array('key' => 'preparation', 'label' => 'Conduct Preparation')
		);
	}

	private function status_label($status){
		$labels = array(
			'draft' => 'Draft',
			'submitted' => 'Submitted',
			'under_review' => 'Under Review',
			'approved' => 'Approved',
			'revision' => 'Revision Required',
			'rejected' => 'Rejected',
			'active' => 'Active',
			'ended' => 'Ended'
		);
		return $labels[$status] ?? ucfirst($status);
	}

	private function lifecycle_label($status){
		$labels = array(
			'draft' => 'Draft',
			'pre_activity' => 'Pre-Activity',
			'for_approval' => 'For Approval',
			'ready_for_conduct' => 'Ready for Conduct',
			'ongoing' => 'Ongoing',
			'conducted' => 'Conducted',
			'post_activity' => 'Post-Activity',
			'completed' => 'Completed',
			'impact_monitoring' => 'Impact Monitoring',
			'closed' => 'Closed'
		);
		return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
	}

	/* =====================================================================
	   WHO MAY TOUCH WHAT
	===================================================================== */

	private function is_admin(){
		return ($_SESSION['login_type'] ?? 0) == 1;
	}

	// The project row, but only if this user is allowed to see it
	private function project_for_user($project_id, $must_edit = false){

		$project_id = (int)$project_id;
		$user = (int)($_SESSION['login_id'] ?? 0);

		if($project_id <= 0 || $user <= 0){
			return null;
		}

		$row = $this->db->query("SELECT * FROM projects WHERE id = $project_id")->fetch_assoc();

		if(!$row){
			return null;
		}

		if($this->is_admin()){
			return $row;
		}

		// A coordinator only ever reaches their own projects
		$own = (int)$row['faculty_id'] === $user || (int)$row['created_by'] === $user;

		return $own ? $row : null;
	}

	/* =====================================================================
	   FILE ATTACHMENTS
	   Saved under a random name, checked by the file's own contents.
	===================================================================== */
	private function store_document_file($file){

		if(empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])){
			return array('', "The file could not be uploaded.");
		}

		if($file['size'] > 10485760){
			return array('', "The file is larger than 10 MB.");
		}

		$head = (string)file_get_contents($file['tmp_name'], false, null, 0, 8);
		$ext = '';

		if(strncmp($head, '%PDF-', 5) === 0){
			$ext = 'pdf';
		}elseif(strncmp($head, "PK\x03\x04", 4) === 0){
			// A .docx/.xlsx is a zip; trust the name only to tell them apart
			$named = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
			$ext = in_array($named, array('docx','xlsx','pptx')) ? $named : '';
		}elseif(strncmp($head, "\xD0\xCF\x11\xE0", 4) === 0){
			$named = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
			$ext = in_array($named, array('doc','xls','ppt')) ? $named : '';
		}else{
			$types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
			$info = @getimagesize($file['tmp_name']);
			$ext = ($info && isset($types[$info[2]])) ? $types[$info[2]] : '';
		}

		if($ext === ''){
			return array('', "Please attach a PDF, Word document or picture.");
		}

		if(!is_dir('uploads/documents')){
			mkdir('uploads/documents', 0755, true);
		}

		$name = time().'_'.bin2hex(random_bytes(8)).'.'.$ext;

		if(!move_uploaded_file($file['tmp_name'], 'uploads/documents/'.$name)){
			return array('', "The file could not be saved.");
		}

		return array($name, '');
	}

	// Deletes the file a record is holding now, before a new one replaces it,
	// so replaced files do not pile up on the server
	private function remove_old_file($table, $id){

		$row = $this->db->query("SELECT file_name FROM `$table` WHERE id = ".(int)$id)->fetch_assoc();

		if(!$row || empty($row['file_name'])){
			return;
		}

		$path = 'uploads/documents/'.basename($row['file_name']);

		if(is_file($path)){
			unlink($path);
		}
	}

	/* =====================================================================
	   PROJECTS
	===================================================================== */

	// Rows for the projects table. GET scope=mine limits it to your own.
	function project_list(){

		$user = (int)($_SESSION['login_id'] ?? 0);
		$where = "1";

		if(!$this->is_admin()){
			$where = "(p.faculty_id = $user OR p.created_by = $user)";
		}elseif(($_GET['scope'] ?? '') === 'mine'){
			$where = "p.created_by = $user";
		}

		foreach(array('academic_year' => 'p.academic_year', 'semester' => 'p.semester',
			'category' => 'p.category', 'lifecycle_status' => 'p.lifecycle_status') as $key => $column){
			if(!empty($_GET[$key])){
				$where .= " AND $column = '".$this->db->real_escape_string($_GET[$key])."'";
			}
		}

		if(!empty($_GET['coordinator'])){
			$where .= " AND p.faculty_id = ".(int)$_GET['coordinator'];
		}

		if(!empty($_GET['search'])){
			$search = $this->db->real_escape_string(trim($_GET['search']));
			$where .= " AND (p.title LIKE '%$search%' OR p.location LIKE '%$search%'
				OR CONCAT(f.firstname,' ',f.lastname) LIKE '%$search%')";
		}

		$qry = $this->db->query("
			SELECT p.*,
				CONCAT(f.firstname, ' ', f.lastname) AS coordinator,
				(SELECT COUNT(*) FROM activities a WHERE a.project_id = p.id) AS activity_count
			FROM projects p
			LEFT JOIN faculty_list f ON f.id = p.faculty_id
			WHERE $where
			ORDER BY p.created_at DESC, p.id DESC
		");

		$rows = array();

		while($row = $qry->fetch_assoc()){
			$progress = $this->project_progress((int)$row['id']);
			$rows[] = array(
				'id' => (int)$row['id'],
				'ref' => sprintf('PRJ-%04d', $row['id']),
				'title' => $row['title'],
				'category' => $row['category'],
				'coordinator' => $row['coordinator'] ?: 'Not assigned',
				'location' => (string)$row['location'],
				'academic_year' => (string)$row['academic_year'],
				'semester' => (string)$row['semester'],
				'status' => $row['lifecycle_status'],
				'status_label' => $this->lifecycle_label($row['lifecycle_status']),
				'activity_count' => (int)$row['activity_count'],
				'progress' => $progress['percent'],
				'progress_text' => $progress['done'].' of '.$progress['total'].' pre-activity steps',
				'can_edit' => true
			);
		}

		return json_encode(array('data' => $rows));
	}

	// How many pre-activity stages are approved / completed
	private function project_progress($project_id){

		$stages = $this->lifecycle(array('id' => $project_id));
		$total = 0;
		$done = 0;

		foreach($stages as $stage){
			if($stage['group'] !== 'pre') continue;
			$total++;
			if($stage['state'] === 'done') $done++;
		}

		return array(
			'done' => $done,
			'total' => $total,
			'percent' => $total ? (int)round($done / $total * 100) : 0
		);
	}

	function project_save(){

		$id = (int)($_POST['id'] ?? 0);
		$title = trim($_POST['title'] ?? '');

		if($title === ''){
			return "Please enter the project title.";
		}

		if($id){
			if(!$this->project_for_user($id)){
				return "You can only edit your own projects.";
			}
		}

		$faculty_id = $this->is_admin()
			? (int)($_POST['faculty_id'] ?? 0)
			: (int)$_SESSION['login_id'];

		$fields = array(
			'title' => $title,
			'description' => trim($_POST['description'] ?? ''),
			'category' => in_array($_POST['category'] ?? '', array('Research','Extension','Training')) ? $_POST['category'] : 'Extension',
			'location' => trim($_POST['location'] ?? ''),
			'target_beneficiaries' => trim($_POST['target_beneficiaries'] ?? ''),
			'academic_year' => trim($_POST['academic_year'] ?? ''),
			'semester' => trim($_POST['semester'] ?? '')
		);

		$set = array();

		foreach($fields as $column => $value){
			$set[] = "`$column` = '".$this->db->real_escape_string($value)."'";
		}

		foreach(array('start_date','end_date') as $column){
			$value = trim($_POST[$column] ?? '');
			$set[] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
				? "`$column` = '$value'"
				: "`$column` = NULL";
		}

		$set[] = "faculty_id = ".($faculty_id ?: 'NULL');

		// How many years after completion the impact is assessed (3 unless changed)
		if($this->links_ready() && isset($_POST['impact_years'])){
			$set[] = "impact_years = ".max(1, min(10, (int)$_POST['impact_years']));
		}

		if($id){
			$ok = $this->db->query("UPDATE projects SET ".implode(', ', $set)." WHERE id = $id");
			return $ok ? 1 : $this->db->error;
		}

		$set[] = "created_by = ".(int)$_SESSION['login_id'];
		$set[] = "lifecycle_status = 'pre_activity'";

		$ok = $this->db->query("INSERT INTO projects SET ".implode(', ', $set));

		return $ok ? (string)$this->db->insert_id : $this->db->error;
	}

	function project_delete(){

		$id = (int)($_POST['id'] ?? 0);
		$project = $this->project_for_user($id);

		if(!$project){
			return "You can only delete your own projects.";
		}

		// Activities and reports stay, they simply stop belonging to a project
		$this->db->query("UPDATE activities SET project_id = NULL WHERE project_id = $id");
		if($this->links_ready()){
			$this->db->query("UPDATE uploaded_reports SET project_id = NULL WHERE project_id = $id");
		}

		$tables = array('designations');

		foreach($this->doc_types() as $type){
			$tables[] = $type['table'];
		}

		foreach($tables as $table){

			// Delete the attached files too, or they stay on the server for good
			$qry = $this->db->query("SELECT file_name FROM `$table` WHERE project_id = $id AND file_name IS NOT NULL AND file_name <> ''");

			while($row = $qry->fetch_assoc()){
				$path = 'uploads/documents/'.basename($row['file_name']);
				if(is_file($path)){
					unlink($path);
				}
			}

			$this->db->query("DELETE FROM `$table` WHERE project_id = $id");
		}

		$this->db->query("DELETE FROM projects WHERE id = $id");

		return 1;
	}

	/* =====================================================================
	   THE LIFECYCLE
	   Every stage's state comes from the database, never from a guess.
	   state: done | current | waiting | attention
	===================================================================== */
	private function lifecycle($project){

		$id = (int)$project['id'];
		$stages = array();

		foreach($this->doc_types() as $key => $type){

			// The impact assessment has its own stage at the end
			if(($type['group'] ?? 'pre') !== 'pre'){
				continue;
			}

			$row = $this->db->query("
				SELECT status FROM {$type['table']}
				WHERE project_id = $id
				ORDER BY id DESC LIMIT 1
			")->fetch_assoc();

			$status = $row['status'] ?? null;

			$stages[$key] = array(
				'key' => $key,
				'label' => $type['stage'],
				'group' => 'pre',
				'status' => $status,
				'status_label' => $status ? $this->status_label($status) : 'Not started',
				'state' => $status === 'approved' ? 'done'
					: ($status === 'revision' || $status === 'rejected' ? 'attention'
					: ($status ? 'current' : 'waiting'))
			);
		}

		// Designation is a list of people rather than one document
		$people = (int)$this->db->query("SELECT COUNT(*) c FROM designations WHERE project_id = $id AND status = 'active'")->fetch_assoc()['c'];

		$stages['designation'] = array(
			'key' => 'designation',
			'label' => 'Designation',
			'group' => 'pre',
			'status' => $people ? 'active' : null,
			'status_label' => $people ? $people.' assigned' : 'Nobody assigned yet',
			'state' => $people ? 'done' : 'waiting'
		);

		// Conducting and after: taken from the activities themselves
		$activities = $this->db->query("
			SELECT COUNT(*) AS total,
				SUM(status = 'approved') AS approved,
				SUM(status = 'approved' AND activity_date < CURDATE()) AS past
			FROM activities WHERE project_id = $id
		")->fetch_assoc();

		$evaluations = (int)$this->db->query("
			SELECT COUNT(DISTINCT ea.evaluation_id) c
			FROM evaluation_answers ea
			INNER JOIN activities a ON a.id = ea.activity_id
			WHERE a.project_id = $id
		")->fetch_assoc()['c'];

		$images = (int)$this->db->query("
			SELECT COUNT(*) c FROM activity_images ai
			INNER JOIN activities a ON a.id = ai.activity_id
			WHERE a.project_id = $id
		")->fetch_assoc()['c'];

		$stages['conducting'] = array(
			'key' => 'conducting', 'label' => 'Conducting', 'group' => 'conduct',
			'status' => null,
			'status_label' => $activities['total']
				? $activities['approved'].' of '.$activities['total'].' activities approved'
				: 'No activity yet',
			'state' => $activities['past'] ? 'done' : ($activities['approved'] ? 'current' : 'waiting')
		);

		$stages['evaluation'] = array(
			'key' => 'evaluation', 'label' => 'Evaluation', 'group' => 'conduct',
			'status' => null,
			'status_label' => $evaluations ? $evaluations.' evaluation'.($evaluations == 1 ? '' : 's').' received' : 'No evaluation yet',
			'state' => $evaluations ? 'done' : 'waiting'
		);

		$stages['documentation'] = array(
			'key' => 'documentation', 'label' => 'Documentation', 'group' => 'conduct',
			'status' => null,
			'status_label' => $images ? $images.' photo'.($images == 1 ? '' : 's') : 'No photo yet',
			'state' => $images ? 'done' : 'waiting'
		);

		// The pre-activity steps in their working order (Designation before Conduct Preparation)
		$ordered = array();
		foreach($this->lifecycle_stages() as $step){
			if(isset($stages[$step['key']])){
				$ordered[$step['key']] = $stages[$step['key']];
			}
		}
		$stages = $ordered + $stages;

		if(!$this->links_ready()){
			foreach(array('reports' => 'Terminal / Progress Report', 'impact' => 'Impact Assessment') as $key => $label){
				$stages[$key] = array('key' => $key, 'label' => $label, 'group' => 'post', 'status' => null,
					'status_label' => 'After the database update', 'state' => 'waiting');
			}
			return array_values($stages);
		}

		// Reports: done once the Terminal Report is approved
		$reports = $this->db->query("
			SELECT COUNT(*) AS total,
				SUM(status = 'Approved') AS approved,
				SUM(report_type = 'Terminal Report' AND status = 'Approved') AS terminal,
				SUM(status IN ('Revision', 'Rejected')) AS returned
			FROM uploaded_reports WHERE project_id = $id
		")->fetch_assoc();

		$total = (int)$reports['total'];

		if((int)$reports['terminal'] > 0){
			$state = 'done';
			$label = 'Terminal Report approved';
		}elseif($total === 0){
			$state = 'waiting';
			$label = 'No report yet';
		}elseif((int)$reports['returned'] > 0 && (int)$reports['approved'] === 0){
			$state = 'attention';
			$label = 'Report sent back';
		}else{
			$state = 'current';
			$label = (int)$reports['approved'].' of '.$total.' report'.($total == 1 ? '' : 's').' approved';
		}

		$stages['reports'] = array(
			'key' => 'reports', 'label' => 'Progress / Terminal Report', 'group' => 'post',
			'status' => null, 'status_label' => $label, 'state' => $state
		);

		// Impact assessment: its own record once written, otherwise when it is due
		if(!isset($project['impact_years'])){
			$project = $this->db->query("SELECT * FROM projects WHERE id = $id")->fetch_assoc() ?: $project;
		}

		$impact = $this->db->query("SELECT status FROM impact_assessments WHERE project_id = $id ORDER BY id DESC LIMIT 1")->fetch_assoc();
		$due = $this->impact_due($project);

		if($impact){
			$status = $impact['status'];
			$state = $status === 'approved' ? 'done'
				: ($status === 'revision' || $status === 'rejected' ? 'attention' : 'current');
			$label = $this->status_label($status);
		}elseif($due['state'] === 'due'){
			$state = 'current';
			$label = 'Due for assessment';
		}elseif($due['state'] === 'not_due'){
			$state = 'waiting';
			$label = 'Due '.date('M Y', strtotime($due['date']));
		}else{
			$state = 'waiting';
			$label = 'Not yet due';
		}

		$stages['impact'] = array(
			'key' => 'impact', 'label' => 'Impact Assessment', 'group' => 'post',
			'status' => $impact ? $impact['status'] : null, 'status_label' => $label, 'state' => $state
		);

		return array_values($stages);
	}

	// Everything the Project Detail page shows
	function project_detail(){

		$project = $this->project_for_user($_GET['id'] ?? 0);

		if(!$project){
			return json_encode(array('error' => 'This project was not found, or it is not yours to open.'));
		}

		$id = (int)$project['id'];

		$coordinator = $this->db->query("
			SELECT CONCAT(firstname,' ',lastname) AS name FROM faculty_list WHERE id = ".(int)$project['faculty_id']
		)->fetch_assoc();

		$documents = array();

		foreach($this->doc_types() as $key => $type){
			$qry = $this->db->query("SELECT * FROM {$type['table']} WHERE project_id = $id ORDER BY id DESC");
			$list = array();
			while($row = $qry->fetch_assoc()){
				$row['status_label'] = $this->status_label($row['status']);
				$row['doc_type'] = $key;
				$row['type_label'] = $type['label'];
				$list[] = $row;
			}
			$documents[$key] = $list;
		}

		$designations = array();
		$qry = $this->db->query("SELECT * FROM designations WHERE project_id = $id ORDER BY id");
		while($row = $qry->fetch_assoc()){
			$designations[] = $row;
		}

		$activities = $this->project_activities($id);
		$reports = $this->links_ready() ? $this->project_reports($id) : array();
		$progress = $this->project_progress($id);

		// The numbers at the top of the project: each stage at a glance
		$evaluations = 0;
		$photos = 0;
		foreach($activities as $activity){
			$evaluations += $activity['evaluations'];
			$photos += count($activity['images']);
		}
		$approved_reports = count(array_filter($reports, function($r){ return $r['status'] === 'Approved'; }));
		$due = $this->links_ready() ? $this->impact_due($project) : null;
		$impact = $documents['impact'][0] ?? null;

		$summary = array(
			'pre' => array('done' => $progress['done'], 'total' => $progress['total']),
			'activities' => array('total' => count($activities),
				'approved' => count(array_filter($activities, function($a){ return $a['status'] === 'approved'; }))),
			'evaluations' => $evaluations,
			'photos' => $photos,
			'reports' => array('total' => count($reports), 'approved' => $approved_reports),
			'impact' => $impact ? $this->status_label($impact['status']) : ($due ? $due['label'] : '')
		);

		return json_encode(array(
			'project' => array(
				'id' => $id,
				'ref' => sprintf('PRJ-%04d', $id),
				'title' => $project['title'],
				'description' => (string)$project['description'],
				'category' => $project['category'],
				'coordinator' => $coordinator['name'] ?? 'Not assigned',
				'faculty_id' => (int)$project['faculty_id'],
				'location' => (string)$project['location'],
				'target_beneficiaries' => (string)$project['target_beneficiaries'],
				'start_date' => $project['start_date'],
				'end_date' => $project['end_date'],
				'academic_year' => (string)$project['academic_year'],
				'semester' => (string)$project['semester'],
				'status' => $project['lifecycle_status'],
				'status_label' => $this->lifecycle_label($project['lifecycle_status']),
				'impact_years' => (int)($project['impact_years'] ?? 3),
				'completed_at' => $project['completed_at'] ?? null
			),
			'lifecycle' => $this->lifecycle($project),
			'progress' => $progress,
			'summary' => $summary,
			'documents' => $documents,
			'designations' => $designations,
			'activities' => $activities,
			'reports' => $reports,
			'impact_due' => $due,
			'suggestion' => $this->is_admin() ? $this->stage_suggestion($project) : null,
			'links_ready' => $this->links_ready(),
			'is_admin' => $this->is_admin()
		));
	}

	/*
	| A project's activities, with what the Conducting tab shows for each:
	| its time, how many evaluations came in and its photos.
	|   phase (approved ones): upcoming | today | conducted
	*/
	private function project_activities($project_id){

		$project_id = (int)$project_id;
		$times = $this->links_ready() ? ", a.start_time, a.end_time" : ", NULL AS start_time, NULL AS end_time";

		$qry = $this->db->query("
			SELECT a.id, a.faculty_id, a.activity_name, a.activity_date, a.venue, a.status, a.revision_note $times,
				(SELECT COUNT(DISTINCT ea.evaluation_id) FROM evaluation_answers ea WHERE ea.activity_id = a.id) AS evaluations
			FROM activities a
			WHERE a.project_id = $project_id
			ORDER BY a.activity_date, a.id
		");

		$rows = array();
		$today = date('Y-m-d');
		$user = $this->is_admin() ? 0 : (int)($_SESSION['login_id'] ?? 0);

		while($row = $qry->fetch_assoc()){
			$row['id'] = (int)$row['id'];
			// Its coordinator adds the documentation photos once it is approved
			$row['can_add_photos'] = $user && (int)$row['faculty_id'] === $user && $row['status'] === 'approved';
			unset($row['faculty_id']);
			$row['ref'] = sprintf('ACT-%04d', $row['id']);
			$row['date_display'] = date('M d, Y', strtotime($row['activity_date']));
			$row['time_display'] = $this->time_range($row['start_time'], $row['end_time']);
			$row['status_label'] = $this->activity_status_label($row['status']);
			$row['evaluations'] = (int)$row['evaluations'];
			$row['phase'] = $row['status'] !== 'approved' ? ''
				: ($row['activity_date'] < $today ? 'conducted' : ($row['activity_date'] === $today ? 'today' : 'upcoming'));
			$rows[] = $row;
		}

		$images = $this->activity_image_rows(array_map(function($row){ return $row['id']; }, $rows));

		foreach($rows as $i => $row){
			$rows[$i]['images'] = $images[$row['id']] ?? array();
		}

		return $rows;
	}

	// "8:00 AM – 12:00 PM", "From 8:00 AM", or "All day"
	private function time_range($start, $end){
		$fmt = function($time){ return date('g:i A', strtotime($time)); };
		if($start && $end){
			return $fmt($start).' – '.$fmt($end);
		}
		if($start){
			return 'From '.$fmt($start);
		}
		return 'All day';
	}

	// A project's Progress and Terminal Reports, newest first
	private function project_reports($project_id){

		$qry = $this->db->query("
			SELECT r.*, CONCAT(f.firstname, ' ', f.lastname) AS uploader
			FROM uploaded_reports r
			LEFT JOIN faculty_list f ON f.id = r.uploaded_by
			WHERE r.project_id = ".(int)$project_id."
			ORDER BY r.uploaded_at DESC, r.id DESC
		");

		$rows = array();

		while($row = $qry->fetch_assoc()){
			$rows[] = $this->report_row($row);
		}

		return $rows;
	}

	/*
	| What the admin is likely to do next with a project's stage, for the
	| stages that are set by hand: completing it once the Terminal Report is
	| approved, monitoring impact once the assessment is due, closing it once
	| the impact assessment is approved. Never applied by itself.
	*/
	private function stage_suggestion($project){

		if(!$this->links_ready()){
			return null;
		}

		$id = (int)$project['id'];
		$status = $project['lifecycle_status'];

		if(!in_array($status, array('completed', 'impact_monitoring', 'closed'))){
			$terminal = (int)$this->db->query("
				SELECT COUNT(*) AS c FROM uploaded_reports
				WHERE project_id = $id AND report_type = 'Terminal Report' AND status = 'Approved'
			")->fetch_assoc()['c'];
			return $terminal ? array('status' => 'completed', 'label' => 'Completed',
				'reason' => 'The Terminal Report is approved.') : null;
		}

		$impact = $this->db->query("SELECT status FROM impact_assessments WHERE project_id = $id ORDER BY id DESC LIMIT 1")->fetch_assoc();

		if($status === 'completed' && ($impact || $this->impact_due($project)['state'] === 'due')){
			return array('status' => 'impact_monitoring', 'label' => 'Impact Monitoring',
				'reason' => $impact ? 'The impact assessment has been started.' : 'The impact assessment is now due.');
		}

		if($status === 'impact_monitoring' && $impact && $impact['status'] === 'approved'){
			return array('status' => 'closed', 'label' => 'Closed', 'reason' => 'The impact assessment is approved.');
		}

		return null;
	}

	/* =====================================================================
	   PRE-ACTIVITY DOCUMENTS
	===================================================================== */

	function doc_save(){

		$types = $this->doc_types();
		$key = $_POST['doc_type'] ?? '';

		if(!isset($types[$key])){
			return "Unknown document type.";
		}

		$type = $types[$key];
		$id = (int)($_POST['id'] ?? 0);
		$project_id = (int)($_POST['project_id'] ?? 0);

		if(!$this->project_for_user($project_id)){
			return "You can only add documents to your own projects.";
		}

		// An approved document is locked unless the admin reopens it
		if($id){
			$current = $this->db->query("SELECT status FROM {$type['table']} WHERE id = $id AND project_id = $project_id")->fetch_assoc();
			if(!$current){
				return "That document was not found.";
			}
			if($current['status'] === 'approved' && !$this->is_admin()){
				return "This document is already approved. Ask the Extension Office to reopen it before editing.";
			}
		}

		$set = array();

		foreach($type['text'] as $field){
			$set[] = "`$field` = '".$this->db->real_escape_string(trim($_POST[$field] ?? ''))."'";
		}

		foreach($type['dates'] as $field){
			$value = trim($_POST[$field] ?? '');
			$set[] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? "`$field` = '$value'" : "`$field` = NULL";
		}

		foreach(($type['times'] ?? array()) as $field){
			$value = trim($_POST[$field] ?? '');
			$set[] = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? "`$field` = '$value'" : "`$field` = NULL";
		}

		foreach($type['numbers'] as $field){
			$value = trim($_POST[$field] ?? '');
			$set[] = $value === '' ? "`$field` = NULL" : "`$field` = ".(float)$value;
		}

		// A tick box sends nothing when it is not ticked, which means 0 here
		foreach(($type['checks'] ?? array()) as $field){
			$set[] = "`$field` = ".(empty($_POST[$field]) ? 0 : 1);
		}

		if(isset($_FILES['document']) && !empty($_FILES['document']['tmp_name'])){
			list($file_name, $error) = $this->store_document_file($_FILES['document']);
			if($error){
				return $error;
			}
			if($id){
				$this->remove_old_file($type['table'], $id);
			}
			$set[] = "file_name = '$file_name'";
		}

		// Saving from the form submits it for review; "Save draft" keeps it a draft
		$status = ($_POST['submit_for_review'] ?? '') == '1' ? 'submitted' : 'draft';
		$set[] = "status = '$status'";

		if($id){
			$ok = $this->db->query("UPDATE {$type['table']} SET ".implode(', ', $set)." WHERE id = $id AND project_id = $project_id");
		}else{
			$set[] = "project_id = $project_id";
			$set[] = "created_by = ".(int)$_SESSION['login_id'];
			$ok = $this->db->query("INSERT INTO {$type['table']} SET ".implode(', ', $set));
		}

		if(!$ok){
			return $this->db->error;
		}

		$this->refresh_lifecycle_status($project_id);

		return 1;
	}

	// Admin decision on a document: approve, reject or send back for revision
	function doc_review(){

		if(!$this->is_admin()){
			return "Only the Extension Office can review documents.";
		}

		$types = $this->doc_types();
		$key = $_POST['doc_type'] ?? '';

		if(!isset($types[$key])){
			return "Unknown document type.";
		}

		$decision = $_POST['decision'] ?? '';
		$allowed = array('approved', 'rejected', 'revision', 'under_review');

		if(!in_array($decision, $allowed)){
			return "Unknown decision.";
		}

		$id = (int)($_POST['id'] ?? 0);
		$remarks = $this->db->real_escape_string(trim($_POST['remarks'] ?? ''));

		if($decision === 'revision' && $remarks === ''){
			return "Please write what needs to be changed.";
		}

		$table = $types[$key]['table'];
		$row = $this->db->query("SELECT project_id FROM $table WHERE id = $id")->fetch_assoc();

		if(!$row){
			return "That document was not found.";
		}

		$ok = $this->db->query("
			UPDATE $table
			SET status = '$decision',
				admin_remarks = '$remarks',
				reviewed_by = ".(int)$_SESSION['login_id'].",
				reviewed_at = NOW()
			WHERE id = $id
		");

		if(!$ok){
			return $this->db->error;
		}

		$this->refresh_lifecycle_status((int)$row['project_id']);

		return 1;
	}

	function doc_delete(){

		$types = $this->doc_types();
		$key = $_POST['doc_type'] ?? '';

		if(!isset($types[$key])){
			return "Unknown document type.";
		}

		$id = (int)($_POST['id'] ?? 0);
		$table = $types[$key]['table'];

		$row = $this->db->query("SELECT project_id, file_name, status FROM $table WHERE id = $id")->fetch_assoc();

		if(!$row || !$this->project_for_user($row['project_id'])){
			return "That document was not found.";
		}

		if($row['status'] === 'approved' && !$this->is_admin()){
			return "An approved document can only be removed by the Extension Office.";
		}

		if(!empty($row['file_name'])){
			$path = 'uploads/documents/'.basename($row['file_name']);
			if(is_file($path)){
				unlink($path);
			}
		}

		$this->db->query("DELETE FROM $table WHERE id = $id");
		$this->refresh_lifecycle_status((int)$row['project_id']);

		return 1;
	}

	/* =====================================================================
	   DESIGNATIONS
	===================================================================== */

	function designation_save(){

		$project_id = (int)($_POST['project_id'] ?? 0);

		if(!$this->project_for_user($project_id)){
			return "You can only assign people to your own projects.";
		}

		$name = trim($_POST['personnel_name'] ?? '');
		$role = trim($_POST['role'] ?? '');

		if($name === '' || $role === ''){
			return "Please enter the person's name and their role.";
		}

		$id = (int)($_POST['id'] ?? 0);
		$faculty_id = (int)($_POST['faculty_id'] ?? 0);

		$set = array(
			"personnel_name = '".$this->db->real_escape_string($name)."'",
			"role = '".$this->db->real_escape_string($role)."'",
			"responsibility = '".$this->db->real_escape_string(trim($_POST['responsibility'] ?? ''))."'",
			"faculty_id = ".($faculty_id ?: 'NULL'),
			"status = '".(($_POST['status'] ?? '') === 'ended' ? 'ended' : 'active')."'"
		);

		foreach(array('start_date','end_date') as $field){
			$value = trim($_POST[$field] ?? '');
			$set[] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? "`$field` = '$value'" : "`$field` = NULL";
		}

		if(isset($_FILES['document']) && !empty($_FILES['document']['tmp_name'])){
			list($file_name, $error) = $this->store_document_file($_FILES['document']);
			if($error){
				return $error;
			}
			if($id){
				$this->remove_old_file('designations', $id);
			}
			$set[] = "file_name = '$file_name'";
		}

		if($id){
			$ok = $this->db->query("UPDATE designations SET ".implode(', ', $set)." WHERE id = $id AND project_id = $project_id");
		}else{
			$set[] = "project_id = $project_id";
			$set[] = "created_by = ".(int)$_SESSION['login_id'];
			$ok = $this->db->query("INSERT INTO designations SET ".implode(', ', $set));
		}

		if(!$ok){
			return $this->db->error;
		}

		$this->refresh_lifecycle_status($project_id);

		return 1;
	}

	function designation_delete(){

		$id = (int)($_POST['id'] ?? 0);
		$row = $this->db->query("SELECT project_id, file_name FROM designations WHERE id = $id")->fetch_assoc();

		if(!$row || !$this->project_for_user($row['project_id'])){
			return "That assignment was not found.";
		}

		if(!empty($row['file_name'])){
			$path = 'uploads/documents/'.basename($row['file_name']);
			if(is_file($path)){
				unlink($path);
			}
		}

		$this->db->query("DELETE FROM designations WHERE id = $id");
		$this->refresh_lifecycle_status((int)$row['project_id']);

		return 1;
	}

	/* =====================================================================
	   PROJECT STATUS
	   Worked out from the records themselves, so it is never out of step
	   with what is actually there.
	===================================================================== */
	private function refresh_lifecycle_status($project_id){

		$project_id = (int)$project_id;
		$project = $this->db->query("SELECT * FROM projects WHERE id = $project_id")->fetch_assoc();

		if(!$project){
			return;
		}

		// A status the admin set by hand at the end is left alone
		if(in_array($project['lifecycle_status'], array('completed', 'impact_monitoring', 'closed'))){
			return;
		}

		$stages = $this->lifecycle($project);
		$pre = array_filter($stages, function($s){ return $s['group'] === 'pre'; });

		$approved = 0;
		$started = 0;

		foreach($pre as $stage){
			if($stage['state'] === 'done') $approved++;
			if($stage['state'] !== 'waiting') $started++;
		}

		$activities = $this->db->query("
			SELECT COUNT(*) AS total,
				SUM(status = 'approved') AS approved,
				SUM(status = 'approved' AND activity_date < CURDATE()) AS past,
				SUM(status = 'approved' AND activity_date = CURDATE()) AS today
			FROM activities WHERE project_id = $project_id
		")->fetch_assoc();

		// After the activities: a report that was not turned down means post-activity
		$reports = $this->links_ready() ? (int)$this->db->query("
			SELECT COUNT(*) AS c FROM uploaded_reports WHERE project_id = $project_id AND status <> 'Rejected'
		")->fetch_assoc()['c'] : 0;

		if((int)$activities['past'] > 0 && $reports > 0){
			$status = 'post_activity';
		}elseif((int)$activities['today'] > 0){
			$status = 'ongoing';
		}elseif((int)$activities['past'] > 0){
			$status = 'conducted';
		}elseif((int)$activities['approved'] > 0){
			$status = 'ready_for_conduct';
		}elseif((int)$activities['total'] > 0){
			$status = 'for_approval';
		}elseif($approved === count($pre) && count($pre) > 0){
			$status = 'ready_for_conduct';
		}elseif($started > 0){
			$status = 'pre_activity';
		}else{
			$status = 'draft';
		}

		$this->db->query("UPDATE projects SET lifecycle_status = '$status' WHERE id = $project_id");
	}

	// The admin can also set the status by hand (e.g. mark a project completed)
	function project_set_status(){

		if(!$this->is_admin()){
			return "Only the Extension Office can change a project's stage.";
		}

		$id = (int)($_POST['id'] ?? 0);
		$status = $_POST['status'] ?? '';
		$allowed = array('draft','pre_activity','for_approval','ready_for_conduct','ongoing',
			'conducted','post_activity','completed','impact_monitoring','closed');

		if(!in_array($status, $allowed) || !$this->project_for_user($id)){
			return "That stage is not valid for this project.";
		}

		// The day it was completed is when the impact assessment period starts
		$completed = '';
		if($this->links_ready()){
			$completed = in_array($status, array('completed', 'impact_monitoring', 'closed'))
				? ", completed_at = COALESCE(completed_at, CURDATE())"
				: ", completed_at = NULL";
		}

		$this->db->query("UPDATE projects SET lifecycle_status = '$status' $completed WHERE id = $id");

		$this->audit_project('project_stage_set', $id, $this->lifecycle_label($status));

		return 1;
	}

	// The change history; does nothing before the questionnaire update made the table
	private function audit_project($action, $project_id, $details = ''){
		if(method_exists($this, 'audit')){
			$this->audit($action, 'project', $project_id, $details);
		}
	}

	/*
	| Projects for a "Project" choice (activity requests, reports, linking):
	| a coordinator's own projects, or every project for the admin.
	*/
	function project_options(){

		$user = (int)($_SESSION['login_id'] ?? 0);
		$where = $this->is_admin() ? "1" : "(p.faculty_id = $user OR p.created_by = $user)";

		$qry = $this->db->query("
			SELECT p.id, p.title, p.academic_year, p.semester, p.lifecycle_status,
				CONCAT(f.firstname, ' ', f.lastname) AS coordinator
			FROM projects p
			LEFT JOIN faculty_list f ON f.id = p.faculty_id
			WHERE $where
			ORDER BY p.created_at DESC, p.id DESC
		");

		$rows = array();

		while($row = $qry->fetch_assoc()){
			$rows[] = array(
				'id' => (int)$row['id'],
				'ref' => sprintf('PRJ-%04d', $row['id']),
				'title' => $row['title'],
				'term' => trim($row['academic_year'].' '.$row['semester']),
				'coordinator' => $row['coordinator'] ?: 'Not assigned',
				'stage' => $this->lifecycle_label($row['lifecycle_status'])
			);
		}

		return json_encode($rows);
	}

	// Coordinators the admin can assign a project to
	function coordinator_options(){

		$rows = array();
		$qry = $this->db->query("SELECT id, CONCAT(firstname,' ',lastname) AS name FROM faculty_list ORDER BY firstname");

		while($row = $qry->fetch_assoc()){
			$rows[] = array('id' => (int)$row['id'], 'name' => $row['name']);
		}

		return json_encode($rows);
	}
}
