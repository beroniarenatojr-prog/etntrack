<?php
$faculty_id = $_SESSION['login_id'];
?>

<style>
.selected-file-card{

    border:2px solid #d7f1df;

    background:#f8fffa;

    border-radius:12px;

    padding:15px;

    margin-top:18px;

}

.selected-pdf-icon{

    width:50px;

    height:50px;

    border-radius:12px;

    background:#ffe9e9;

    color:#dc3545;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;

}

    .report-icon{
    width:45px;
    height:45px;
    border-radius:12px;
    background:#ffeaea;
    color:#dc3545;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

.badge-status{
    padding:7px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.badge-uploaded{
    background:#d1f7dd;
    color:#198754;
}

.badge-category{
    border-radius:20px;
    padding:6px 12px;
    font-size:12px;
}

.action-btn{
    width:35px;
    height:35px;
    border-radius:8px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-right:5px;
}


.stat-card{

background:white;

border-radius:18px;

padding:25px;

display:flex;

justify-content:space-between;

align-items:center;

box-shadow:0 10px 25px rgba(0,0,0,.08);

transition:.3s;

margin-bottom:20px;

}

.stat-card:hover{

transform:translateY(-5px);

box-shadow:0 18px 35px rgba(0,0,0,.12);

}

.stat-card h6{

color:#888;

margin-bottom:8px;

}

.stat-card h2{

font-weight:bold;

margin:0;

}

.stat-card i{

font-size:45px;

opacity:.85;

}

.green{

border-left:6px solid #198754;

}

.blue{

border-left:6px solid #0d6efd;

}

.orange{

border-left:6px solid #ffc107;

}

.purple{

border-left:6px solid #6f42c1;

}

.table-card{

border-radius:20px;

overflow:hidden;

}

.table tbody tr{

transition:.2s;

}

.table tbody tr:hover{

background:#f4fff7;

transform:scale(1.01);

}

.btn-view{

border-radius:30px;

padding:6px 18px;

}

.upload-card{

transition:.3s;

}

.upload-card:hover{

transform:translateY(-4px);

}

#selected-file{

background:#fff;

padding:12px;

border-radius:10px;

margin-top:15px;

box-shadow:0 2px 8px rgba(0,0,0,.08);

}

body{
    background:#f4f8f6;
}

.upload-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    margin-bottom:25px;
}

.upload-header{
    background:linear-gradient(135deg,#198754,#157347);
    color:#fff;
    padding:18px 25px;
}

.upload-area{

border:3px dashed #198754;

border-radius:15px;

padding:35px;

text-align:center;

cursor:pointer;

transition:.3s;

background:#f8fff9;

}

.upload-area:hover{

background:#e9f8ef;

border-color:#157347;

}

.upload-area.dragover{

background:#d1f2dc;

border-color:#146c43;

}

.upload-big-icon{

font-size:55px;

color:#198754;

margin-bottom:15px;

}

#browse-btn{

border-radius:25px;

padding:10px 25px;

font-weight:600;

}

#file-name{

font-weight:bold;

font-size:15px;

} 

.upload-header h4{
    margin:0;
    font-weight:600;
}

.upload-body{
    padding:30px;
}

.form-control{
    border-radius:10px;
    height:48px;
}

.form-control:focus{
    border-color:#198754;
    box-shadow:0 0 0 .15rem rgba(25,135,84,.2);
}

.btn-upload{
    background:#198754;
    color:#fff;
    border:none;
    border-radius:10px;
    height:48px;
    font-weight:bold;
    transition:.3s;
}

.btn-upload:hover{
    background:#157347;
}

.table-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.table thead{
    background:#198754;
    color:white;
}

.table td,
.table th{
    vertical-align:middle;
}

.btn-view{
    background:#198754;
    color:white;
    border-radius:8px;
}

.btn-view:hover{
    color:white;
    background:#146c43;
}

.upload-icon{
    font-size:40px;
    color:#198754;
    margin-bottom:10px;
}

@media(max-width:768px){

.upload-body{
padding:20px;
}

.upload-header h4{
font-size:18px;
}

.table{
font-size:13px;
}

}
</style>


<div class="container-fluid">
    <div class="row mb-4">

    <div class="col-md-3">
        <div class="stat-card green">
            <div>
                <h6>Total Reports</h6>
                <h2 id="total_reports">0</h2>
            </div>
            <i class="fa fa-file-alt"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card blue">
            <div>
                <h6>Uploaded Today</h6>
                <h2 id="today_reports">0</h2>
            </div>
            <i class="fa fa-cloud-upload-alt"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div>
            <div class="stat-card orange">
                <div>
                    <h6>Storage Used</h6>
                    <h2 id="storage_used">0 MB</h2>
                </div>
                <i class="fa fa-database"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card purple">
            <div>
                <h6>Latest Upload</h6>
                <small id="latest_upload">No upload</small>
            </div>
            <i class="fa fa-clock"></i>
        </div>
    </div>

</div>

<div class="row">

<div class="col-lg-5 col-xl-4">

<div class="card upload-card">

<div class="upload-header">

<h4>
<i class="fa fa-cloud-upload-alt"></i>
Upload Report
</h4>

</div>

<div class="upload-body text-center">

<div class="upload-icon">
<i class="fa fa-file-pdf"></i>
</div>

<form id="upload-report" enctype="multipart/form-data">

    <div class="form-group text-left">
        <label><b>Report Title</b></label>
        <input type="text" name="title" class="form-control" placeholder="Enter report title" required>
    </div>

    <div class="form-group text-left mt-3">
        <label><b>Category</b> <small class="text-muted">(optional)</small></label>
        <select class="form-control" name="category">
            <option value="">Select category</option>
            <option>Research</option>
            <option>Extension</option>
            <option>Training</option>
        </select>
    </div>

    <div class="form-group text-left mt-3">
        <label><b>Description</b> <small class="text-muted">(optional)</small></label>
        <textarea
            name="description"
            class="form-control"
            rows="3"
            placeholder="Enter description"></textarea>
    </div>

    <div class="form-group mt-3">

        <div class="upload-area" id="upload-area">

            <input
                type="file"
                id="report"
                name="report"
                accept=".pdf"
                hidden
                required>

            <i class="fa fa-cloud-upload upload-big-icon"></i>

            <h5 class="mt-2">Drag & Drop your PDF here</h5>

            <small class="text-muted d-block mb-3">
                or
            </small>

            <button type="button" class="btn btn-success px-4" id="browse-btn">
                <i class="fa fa-folder-open"></i>
                Browse Files
            </button>

            <div class="upload-info mt-3">
                Only PDF files allowed • Maximum size: 10 MB
            </div>

        </div>

    </div>

    <!-- FILE PREVIEW -->
    <div id="selected-file" style="display:none">

        <div class="selected-card">

            <div class="d-flex align-items-center">

                <div class="pdf-icon">
                    <i class="fa fa-file-pdf text-danger"></i>
                </div>

                <div class="ml-3">

                    <div id="file-name">Annual_Report.pdf</div>

                    <small id="file-size" class="text-muted">
                        0 KB
                    </small>

                </div>

            </div>

            <button
                type="button"
                class="btn btn-link text-danger p-0"
                id="remove-file">

                Remove

            </button>
            <div id="selected-file" style="display:none">

    <div class="selected-file-card">

        <div class="d-flex align-items-center">

            <div class="selected-pdf-icon">
                <i class="fa fa-file-pdf"></i>
            </div>

            <div class="ml-3 flex-grow-1">

                <div id="file-name"></div>

                <small id="file-size" class="text-muted"></small>

            </div>

            <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                id="remove-file">

                <i class="fa fa-times"></i>

            </button>

        </div>

    </div>

</div>

        </div>

    </div>

    <button class="btn btn-upload btn-block mt-4">
        <i class="fa fa-upload"></i>
        Upload Report
    </button>

</form>

</div>

</div>

</div>



<div class="col-lg-8">

<div class="card table-card">

<div class="upload-header">

<h4>

<i class="fa fa-folder-open"></i>

My Uploaded Reports

</h4>

</div>

<div class="card-body table-responsive">

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="input-group" style="max-width:300px">
        <div class="input-group-prepend">
            <span class="input-group-text bg-white">
                <i class="fa fa-search text-success"></i>
            </span>
        </div>
        <input type="text" class="form-control" id="search-report"
               placeholder="Search reports...">
    </div>

    <div>
        <select class="form-control" id="sort-report">
            <option value="newest">Sort by : Newest</option>
            <option value="oldest">Oldest</option>
            <option value="title">Title</option>
        </select>
    </div>

</div>

<table class="table table-hover align-middle">

    <thead class="bg-light">

        <tr>

            <th>#</th>

            <th>Report</th>

            <th>Category</th>

            <th> Date Uploaded</th>

          <th>Size</th>

            <th>Status</th>

            <th width="180">Action</th>

        </tr>

    </thead>

    <tbody id="report-list">

    </tbody>

</table>







</div>

</div>




</div>

</div>

</div>


<script>
const uploadArea = document.getElementById("upload-area");
const fileInput = document.getElementById("report");
const browseBtn = document.getElementById("browse-btn");
const fileName = document.getElementById("file-name");
const fileSize = document.getElementById("file-size");
const selectedFile = document.getElementById("selected-file");
const removeFile = document.getElementById("remove-file");

// Open file browser
browseBtn.addEventListener("click", function () {
    fileInput.click();
});

// Display selected file name
fileInput.addEventListener("change", function () {

    if(this.files.length){

        showSelectedFile(this.files[0]);

    }

});
// Drag effects
uploadArea.addEventListener("dragover", function (e) {
    e.preventDefault();
    uploadArea.classList.add("dragover");
});

uploadArea.addEventListener("dragleave", function () {
    uploadArea.classList.remove("dragover");
});

// Drop file
uploadArea.addEventListener("drop", function (e) {
    e.preventDefault();
    uploadArea.classList.remove("dragover");

    const files = e.dataTransfer.files;

    if (files.length > 0) {

        if (files[0].type !== "application/pdf") {
            Swal.fire({
                icon: "error",
                title: "Invalid File",
                text: "Only PDF files are allowed."
            });
            return;
        }

       fileInput.files = files;
showSelectedFile(files[0]);
    }
});

$(document).ready(function () {

    load_reports();

    $("#upload-report").submit(function (e) {

        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({

            url: "ajax.php?action=upload_report",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,

            beforeSend: function () {

                $(".btn-upload")
                    .prop("disabled", true)
                    .html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

            },

            success: function (resp) {

                if ($.trim(resp) == "1") {

                    Swal.fire({
                        icon: "success",
                        title: "Uploaded!",
                        text: "Report uploaded successfully.",
                        timer: 1800,
                        showConfirmButton: false
                    });

                    $("#upload-report")[0].reset();
                    $("#file-name").html("");
                    $("#selected-file").hide();

                    load_reports();

                } else {

                    Swal.fire({
                        icon: "error",
                        title: "Upload Failed",
                        text: resp
                    });

                }

            },

            error: function (xhr, status, error) {

                Swal.fire({
                    icon: "error",
                    title: "AJAX Error",
                    text: error
                });

                console.log(xhr.responseText);

            },

            complete: function () {

                $(".btn-upload")
                    .prop("disabled", false)
                    .html('<i class="fa fa-upload"></i> Upload Report');

            }

        });

    });

});

function showSelectedFile(file){

    fileName.innerHTML = file.name;

    fileSize.innerHTML = (file.size/1024/1024).toFixed(2)+" MB";

    selectedFile.style.display="block";

}


removeFile.addEventListener("click",function(){

    fileInput.value="";

    selectedFile.style.display="none";

    fileName.innerHTML="";

    fileSize.innerHTML="";

});



function delete_report(id){

    Swal.fire({
        title: 'Delete Report?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete'
    }).then((result)=>{

        if(result.isConfirmed){

            $.ajax({

                url:"ajax.php?action=delete_report",
                method:"POST",
                data:{id:id},

                success:function(resp){

                    if($.trim(resp)=="1"){

                        Swal.fire(
                            "Deleted!",
                            "Report deleted successfully.",
                            "success"
                        );

                        load_reports();

                    }else{

                        Swal.fire(
                            "Error",
                            resp,
                            "error"
                        );

                    }

                }

            });

        }

    });

}



function load_reports() {

    $.ajax({

        url: "ajax.php?action=list_reports",

        success: function (resp) {

            $("#report-list").html(resp);

        },

        error: function (xhr) {

            console.log(xhr.responseText);

        }

    });

}
</script>