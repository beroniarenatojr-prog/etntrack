<?php
session_start();
include '../db_connect.php';

if(isset($_GET['action']) && $_GET['action'] == 'upload_report'){

    if(!isset($_FILES['report']) || $_FILES['report']['error'] != 0){
        echo "No file uploaded.";
        exit;
    }

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $uploaded_by = $_SESSION['login_id']; // ID ng naka-login

    $file = $_FILES['report'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if($ext != "pdf"){
        echo "Only PDF files are allowed.";
        exit;
    }

    $folder = "uploads/reports/";

    if(!is_dir($folder)){
        mkdir($folder,0777,true);
    }

    $filename = time().'_'.$file['name'];

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