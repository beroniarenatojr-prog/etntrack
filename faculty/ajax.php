<?php
session_start();

// Older upload endpoint. The Reports page now uploads through the main
// ajax.php (action=upload_report); this is kept only in case an older page
// still points here, and it allows signed-in coordinators only.
if(empty($_SESSION['login_id'])){
    http_response_code(401);
    echo "Your session has expired. Please log in again.";
    exit;
}

if(($_SESSION['login_type'] ?? 0) != 2){
    http_response_code(403);
    echo "You are not allowed to do that.";
    exit;
}

include '../db_connect.php';

if(isset($_GET['action']) && $_GET['action'] == 'upload_report'){

    if(!isset($_FILES['report']) || $_FILES['report']['error'] != 0 || !is_uploaded_file($_FILES['report']['tmp_name'])){
        echo "No file uploaded.";
        exit;
    }

    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $uploaded_by = (int)$_SESSION['login_id'];

    $file = $_FILES['report'];

    // The file's own contents decide, not its name
    if(strtolower((string)file_get_contents($file['tmp_name'], false, null, 0, 5)) !== '%pdf-'){
        echo "Only PDF files are allowed.";
        exit;
    }

    $folder = "uploads/reports/";

    if(!is_dir($folder)){
        mkdir($folder,0755,true);
    }

    $filename = time().'_'.bin2hex(random_bytes(6)).'.pdf';

    if(move_uploaded_file($file['tmp_name'],$folder.$filename)){

        $sql = "INSERT INTO uploaded_reports
                (report_title, file_name, uploaded_by)
                VALUES
                ('$title', '$filename', '$uploaded_by')";

        if($conn->query($sql)){
            echo 1;
        }else{
            echo $conn->error;
        }

    }else{
        echo "Failed to upload file.";
    }

    exit;
}
?>