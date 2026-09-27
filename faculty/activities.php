<?php
$faculty_id = $_SESSION['login_id'];
?>

<style>



    .upload-box.dragging{

    background:#e8fff0;

    border-color:#198754;

    transform:scale(1.02);

}

.preview-box img{

    display:none;

    width:100%;

    height:100%;

    object-fit:cover;

}

#remove-image{

    display:none;

}

body{
    background:#f4f8f6;
}

.activity-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.card-header-green{
    background:#198754;
    color:#fff;
    padding:20px 25px;
    border-bottom:none;
}

.card-header-green h4{
    font-weight:600;
    margin:0;
    font-size:20px;
}

.card-header-green p{
    margin:4px 0 0;
    opacity:.85;
    font-size:14px;
}

.card-body{
    padding:25px;
}

.form-control{
    border-radius:10px;
}

.btn-save{
    background:#198754;
    color:#fff;
    font-weight:bold;
    border:none;
    border-radius:10px;
}

.btn-save:hover{
    background:#146c43;
    color:white;
}

.table thead{
    background:#198754;
    color:white;
}

.activity-img{
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:10px;
}

.stats-card{
    background:#fff;
    border-radius:18px;
    padding:20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    box-shadow:0 8px 20px rgba(0,0,0,.06);
    transition:.3s;
    height:110px;
}

.stats-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 30px rgba(0,0,0,.12);
}

.stats-icon{
    width:58px;
    height:58px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

.stats-icon.green{
    background:#e8f8ef;
    color:#1a9c5b;
}

.stats-icon.success{
    background:#e8f8ef;
    color:#28a745;
}

.stats-icon.warning{
    background:#fff5de;
    color:#f0ad4e;
}

.stats-icon.danger{
    background:#ffeaea;
    color:#dc3545;
}

.stats-content{
    flex:1;
    margin-left:15px;
}

.stats-content small{
    color:#999;
    display:block;
    font-weight:600;
}

.stats-content h3{
    margin:4px 0;
    font-size:30px;
    font-weight:700;
    color:#222;
}

.stats-content span{
    font-size:12px;
    color:#999;
}

.stats-chart{
    font-size:28px;
    opacity:.7;
}

/* ==========================
   CREATE ACTIVITY CARD
========================== */

.activity-card{
    border:none;
    border-radius:24px;
    overflow:hidden;
    background:#fff;
    box-shadow:0 10px 30px rgba(0,0,0,.06);
}

.card-header-green{
    background:#fff;
    padding:28px;
    border-bottom:1px solid #edf2f7;
}

.card-header-green h4{
    color:#1b1b1b;
    font-size:24px;
    font-weight:700;
    margin-bottom:5px;
}

.card-header-green p{
    color:#8d98a5;
    margin:0;
    font-size:14px;
}

.card-header-green i{
    width:50px;
    height:50px;
    border-radius:15px;
    background:#e9f8ef;
    color:#19a75d;
    display:inline-flex;
    justify-content:center;
    align-items:center;
    margin-right:12px;
    font-size:22px;
}

.card-body{
    padding:30px;
}

/* ==========================
LABEL
========================== */

.form-group label{
    font-size:13px;
    font-weight:700;
    color:#374151;
    margin-bottom:8px;
}

/* ==========================
INPUTS
========================== */

.form-control{
    height:48px;
    border-radius:12px;
    border:1px solid #e5e7eb;
    box-shadow:none;
    transition:.3s;
}

textarea.form-control{
    height:auto;
    resize:none;
}

.form-control:focus{
    border-color:#1ea65b;
    box-shadow:0 0 0 4px rgba(30,166,91,.12);
}

/* ==========================
DATE + VENUE
========================== */

.row-gap{
    margin-top:15px;
}

/* ==========================
UPLOAD BOX
========================== */

.upload-box{

height:220px;

border:2px dashed #1ea65b;

border-radius:18px;

display:flex;

flex-direction:column;

justify-content:center;

align-items:center;

cursor:pointer;

transition:.3s;

background:#fcfffd;

}

.upload-box:hover{

background:#f2fff6;

}

.upload-box i{

font-size:48px;

color:#1ea65b;

margin-bottom:12px;

}

.upload-box input{

display:none;

}

/* Preview */

.preview-box{

height:220px;

border:2px dashed #dfe6eb;

border-radius:18px;

display:flex;

justify-content:center;

align-items:center;

position:relative;

overflow:hidden;

background:white;

}

.preview-box img{

width:100%;

height:100%;

object-fit:cover;

display:none;

}

.preview-placeholder{

text-align:center;

color:#9aa4af;

}

.preview-placeholder i{

font-size:50px;

margin-bottom:10px;

}

#remove-image{

position:absolute;

bottom:10px;

right:10px;

display:none;

border-radius:20px;

}

/* ==========================
BUTTON
========================== */

.btn-save{

    height:52px;

    border:none;

    border-radius:12px;

    background:linear-gradient(90deg,#1fa15d,#14894d);

    color:white;

    font-size:16px;

    font-weight:600;

    transition:.3s;

}

.btn-save:hover{

    transform:translateY(-2px);

    box-shadow:0 12px 20px rgba(25,135,84,.25);

}


/* =======================
   TABLE CARD
=======================*/

.activity-card{

    border:none;

    border-radius:22px;

    background:white;

    box-shadow:0 10px 30px rgba(0,0,0,.05);

}

/* Header */

.modern-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:25px;

    border-bottom:1px solid #edf1f7;

}

.header-left{

    display:flex;

    align-items:center;

}

.header-icon{

    width:50px;

    height:50px;

    border-radius:15px;

    background:#eaf8ef;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#19a85d;

    font-size:22px;

    margin-right:15px;

}

.header-left h4{

    margin:0;

    font-size:24px;

    font-weight:700;

}

.header-left p{

    margin:0;

    color:#9aa4af;

    font-size:13px;

}

/* Search */

.header-right{

    display:flex;

    align-items:center;

    gap:15px;

}

.search-box{

    display:flex;

    align-items:center;

    background:#fff;

    border:1px solid #e5e7eb;

    border-radius:12px;

    padding:0 15px;

    height:45px;

}

.search-box i{

    color:#999;

}

.search-box input{

    border:none;

    outline:none;

    padding-left:10px;

    width:230px;

}

/* Filter */

.filter-btn{

    height:45px;

    padding:0 20px;

    border-radius:12px;

    background:white;

    border:1px solid #e5e7eb;

    font-weight:600;

}

/* Table */

.table{

    margin-bottom:0;

}

.table thead th{

    background:#179653;

    color:white;

    border:none;

    font-weight:600;

    padding:15px;

}

.table thead th:first-child{

    border-top-left-radius:12px;

}

.table thead th:last-child{

    border-top-right-radius:12px;

}

.table td{

    vertical-align:middle;

    padding:18px 15px;

    border-top:1px solid #f3f3f3;

}

/* Thumbnail */

.activity-img{

    width:85px;

    height:65px;

    border-radius:12px;

    object-fit:cover;

}

/* Activity */

.activity-title{

    font-weight:700;

    font-size:15px;

}

.activity-desc{

    color:#8d97a5;

    font-size:12px;

    margin-top:5px;

}

/* Badges */

.badge-approved{

    background:#e8f9ef;

    color:#1b9f58;

    padding:7px 16px;

    border-radius:20px;

    font-weight:600;

}

.badge-pending{

    background:#fff5d9;

    color:#e39a00;

    padding:7px 16px;

    border-radius:20px;

    font-weight:600;

}

.badge-rejected{

    background:#ffeaea;

    color:#ea3c3c;

    padding:7px 16px;

    border-radius:20px;

    font-weight:600;

}

/* Action Buttons */

.action-btn{

    width:38px;

    height:38px;

    border:none;

    border-radius:50%;

    margin-right:8px;

    transition:.3s;

}

.view-btn{

    background:#eaf8ef;

    color:#199d58;

}

.edit-btn{

    background:#fff5da;

    color:#d89b00;

}

.delete-btn{

    background:#ffeaea;

    color:#e03b3b;

}

.action-btn:hover{

    transform:scale(1.1);

}



</style>

<div class="container-fluid">

<!-- Dashboard Summary -->
<div class="row mb-4">

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon green">
                <i class="fa fa-calendar"></i>
            </div>

            <div class="stats-content">
                <small>Total Activities</small>
                <h3 id="total_activity">12</h3>
                <span>All submitted activities</span>
            </div>

            <div class="stats-chart text-success">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon success">
                <i class="fa fa-check-circle"></i>
            </div>

            <div class="stats-content">
                <small>Approved</small>
                <h3 id="approved_activity">9</h3>
                <span>75% of total</span>
            </div>

            <div class="stats-chart text-success">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon warning">
                <i class="fa fa-clock-o"></i>
            </div>

            <div class="stats-content">
                <small>Pending</small>
                <h3 id="pending_activity">2</h3>
                <span>16.7% of total</span>
            </div>

            <div class="stats-chart text-warning">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="stats-icon danger">
                <i class="fa fa-times-circle"></i>
            </div>

            <div class="stats-content">
                <small>Rejected</small>
                <h3 id="rejected_activity">1</h3>
                <span>8.3% of total</span>
            </div>

            <div class="stats-chart text-danger">
                <i class="fa fa-line-chart"></i>
            </div>
        </div>
    </div>

</div>






<div class="row">

<div class="col-md-4">

<div class="card activity-card">

<div class="card-header-green">

<h4>
<i class="fa fa-calendar-plus mr-2"></i>
Create Activity
</h4>

<p>Submit extension activities for approval.</p>

</div>
<div class="card-body">

<form id="activity-form" enctype="multipart/form-data">

<div class="form-group">

<label>Upload Image <small class="text-muted">(Optional)</small></label>

<div class="row">

<div class="col-md-6">

<label class="upload-box" id="drop-area">

    <i class="fa fa-cloud-upload"></i>

    <h6>Drag & Drop</h6>

    <small>or click to browse</small>

    <input
        type="file"
        id="activity_image"
        name="image"
        accept="image/*">

</label>

</div>

<div class="col-md-6">

<div class="preview-box">

    <img id="preview-image" src="" alt="Preview">

    <div class="preview-placeholder" id="preview-placeholder">

        <i class="fa fa-image"></i>

        <p>No image selected</p>

    </div>

    <button
        type="button"
        id="remove-image"
        class="btn btn-danger btn-sm">

        <i class="fa fa-times"></i>

        Remove

    </button>

</div>

</div>

</div>

</div>


<div class="form-group">

<label>Activity Name</label>

<textarea
name="activity_name"
class="form-control"
rows="3"
required></textarea>

</div>

<div class="form-group">

<label>Purpose</label>

<textarea
name="purpose"
class="form-control"
rows="3"
required></textarea>

</div>

<div class="form-group">

<label>Description</label>

<textarea
name="description"
class="form-control"
rows="4"></textarea>

</div>

<div class="form-group">

<label>Activity Date</label>

<input type="date"
name="activity_date"
class="form-control"
required>

</div>

<div class="form-group">

<label>Venue</label>

<input
type="text"
name="venue"
class="form-control">

</div>


<button type="submit" class="btn btn-save btn-block">

<i class="fa fa-paper-plane mr-2"></i>

Submit Activity

</button>

</form>

</div>

</div>

</div>

<div class="col-md-8">

<div class="card activity-card">

<div class="modern-header">

    <div class="header-left">

        <div class="header-icon">
            <i class="fa fa-list"></i>
        </div>

        <div>

            <h4>My Activities</h4>

            <p>Monitor the status of your submitted activities.</p>

        </div>

    </div>

    <div class="header-right">

        <div class="search-box">

            <i class="fa fa-search"></i>

            <input type="text" placeholder="Search activities...">

        </div>

        <button class="filter-btn">

            <i class="fa fa-filter"></i>

            Filter

        </button>

    </div>

</div>

<div class="card-body">

<table class="table table-hover">

<thead>
<tr>
    <th>#</th>
    <th>Image</th>
    <th>Activity</th>
    <th>Date</th>
    <th>Venue</th>
    <th>Status</th>
    <th width="180">Action</th>
</tr>
</thead>

<tbody id="activity-list">

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>





<script>

$(document).ready(function(){

load_activity();

$("#activity-form").submit(function(e){

e.preventDefault();

var formData = new FormData(this);

$.ajax({

url:"ajax.php?action=save_activity",

type:"POST",

data:formData,

processData:false,

contentType:false,

success:function(resp){

    if($.trim(resp)=="1"){

        Swal.fire({
            icon:"success",
            title:"Success!",
            text:"Activity submitted successfully.",
            timer:1800,
            showConfirmButton:false
        });

        $("#activity-form")[0].reset();

        $("#preview-image").attr("src","").hide();
        $("#preview-placeholder").show();
        $("#remove-image").hide();
        $("#activity_image").val("");

        load_activity();

    }else{

        Swal.fire({
            icon:"error",
            title:"Upload Failed",
            text:resp
        });

    }

}

});

});

});

function previewFile(file){

    if(!file) return;

    if(!file.type.startsWith("image/")){
        alert("Please select an image.");
        return;
    }

    const reader = new FileReader();

    reader.onload = function(e){

        $("#preview-image")
            .attr("src",e.target.result)
            .fadeIn();

        $("#preview-placeholder").hide();

        $("#remove-image").show();

    }

    reader.readAsDataURL(file);

}

// normal browse
$("#activity_image").on("change",function(){

    previewFile(this.files[0]);

});

// remove
$("#remove-image").click(function(){

    $("#activity_image").val("");

    $("#preview-image")
        .attr("src","")
        .hide();

    $("#preview-placeholder").show();

    $(this).hide();

});

// drag and drop
const dropArea=document.getElementById("drop-area");

["dragenter","dragover"].forEach(eventName=>{

dropArea.addEventListener(eventName,function(e){

e.preventDefault();

dropArea.classList.add("dragging");

});

});

["dragleave","drop"].forEach(eventName=>{

dropArea.addEventListener(eventName,function(e){

e.preventDefault();

dropArea.classList.remove("dragging");

});

});

dropArea.addEventListener("drop",function(e){

const files=e.dataTransfer.files;

if(files.length){

$("#activity_image")[0].files=files;

previewFile(files[0]);

}

});





function load_activity(){

$.ajax({

url:"ajax.php?action=list_activity",

success:function(resp){

$("#activity-list").html(resp);

}

});

}

function delete_activity(id){

    Swal.fire({

        title:'Delete Activity?',

        text:'This action cannot be undone.',

        icon:'warning',

        showCancelButton:true,

        confirmButtonColor:'#dc3545',

        cancelButtonColor:'#6c757d',

        confirmButtonText:'Delete',

        cancelButtonText:'Cancel'

    }).then((result)=>{

        if(result.isConfirmed){

            $.ajax({

                url:"ajax.php?action=delete_activity&id="+id,

                success:function(resp){

                    if($.trim(resp)=="1"){

                        Swal.fire({

                            icon:'success',

                            title:'Deleted!',

                            text:'Activity deleted successfully.',

                            timer:1800,

                            showConfirmButton:false

                        });

                        load_activity();

                    }else{

                        Swal.fire({

                            icon:'error',

                            title:'Delete Failed',

                            text:resp

                        });

                    }

                },

                error:function(){

                    Swal.fire({

                        icon:'error',

                        title:'Server Error',

                        text:'Unable to delete the activity.'

                    });

                }

            });

        }

    });

}

</script>