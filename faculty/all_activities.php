
<?php
$faculty_id = $_SESSION['login_id'];
?>

<style>

/* =========================================================
   PAGE BACKGROUND
========================================================= */

body {
    background: #f4f8f6;
}


/* =========================================================
   DASHBOARD STATS
========================================================= */

.stats-card {
    position: relative;

    background: #ffffff;

    border: 1px solid #e7ece9;

    border-radius: 14px;

    padding: 20px;

    display: flex;
    align-items: center;

    min-height: 125px;

    box-shadow: 0 3px 12px rgba(0,0,0,.04);

    transition: all .2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);

    box-shadow: 0 7px 20px rgba(0,0,0,.07);
}


/* ICON */

.stats-icon {
    width: 52px;
    height: 52px;

    min-width: 52px;

    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 21px;

    margin-right: 14px;
}

.stats-icon.green {
    background: #e8f6ef;
    color: #198754;
}

.stats-icon.success {
    background: #e6f7ed;
    color: #198754;
}

.stats-icon.warning {
    background: #fff4d8;
    color: #d89b00;
}

.stats-icon.danger {
    background: #fdeaea;
    color: #dc3545;
}


/* CONTENT */

.stats-content {
    min-width: 0;
}

.stats-content small {
    display: block;

    color: #78847d;

    font-size: 12px;

    font-weight: 600;

    margin-bottom: 2px;
}

.stats-content h3 {
    margin: 0;

    color: #26352d;

    font-size: 26px;

    font-weight: 700;
}

.stats-content span {
    display: block;

    color: #929c96;

    font-size: 11px;

    margin-top: 2px;
}


/* CHART ICON */

.stats-chart {
    margin-left: auto;

    font-size: 22px;

    opacity: .45;
}


/* =========================================================
   MAIN ACTIVITY CARD
========================================================= */

.activity-card {
    border: none;

    border-radius: 16px;

    overflow: hidden;

    background: #ffffff;

    box-shadow: 0 4px 18px rgba(0,0,0,.05);
}


/* =========================================================
   MODERN HEADER
========================================================= */

.modern-header {
    padding: 18px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    border-bottom: 1px solid #edf1ef;

    background: #ffffff;
}


/* LEFT */

.header-left {
    display: flex;

    align-items: center;

    gap: 12px;

    min-width: 0;
}

.header-icon {
    width: 42px;
    height: 42px;

    min-width: 42px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e9f6ef;

    color: #198754;

    font-size: 18px;
}

.header-left h4 {
    margin: 0;

    color: #27362e;

    font-size: 18px;

    font-weight: 700;
}

.header-left p {
    margin: 3px 0 0;

    color: #89948e;

    font-size: 12px;
}


/* RIGHT */

.header-right {
    display: flex;

    align-items: center;

    gap: 10px;
}


/* =========================================================
   SEARCH
========================================================= */

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;

    left: 12px;

    top: 50%;

    transform: translateY(-50%);

    color: #9aa59f;

    font-size: 13px;
}

.search-box input {
    width: 230px;

    height: 38px;

    padding: 0 12px 0 34px;

    border: 1px solid #dfe6e2;

    border-radius: 9px;

    outline: none;

    font-size: 12px;

    color: #46544c;

    background: #fafcfb;

    transition: all .2s ease;
}

.search-box input:focus {
    border-color: #198754;

    background: #ffffff;

    box-shadow: 0 0 0 3px rgba(25,135,84,.08);
}

.search-box input::placeholder {
    color: #a5aea9;
}


/* =========================================================
   FILTER BUTTON
========================================================= */

.filter-btn {
    height: 38px;

    padding: 0 14px;

    border: 1px solid #dfe6e2;

    border-radius: 9px;

    background: #ffffff;

    color: #59665e;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;

    transition: all .2s ease;
}

.filter-btn i {
    margin-right: 5px;
}

.filter-btn:hover {
    background: #198754;

    border-color: #198754;

    color: #ffffff;
}


/* =========================================================
   CARD BODY
========================================================= */

.activity-card .card-body {
    padding: 18px;
}


/* =========================================================
   ACTIVITY LIST
========================================================= */

.activity-list {
    display: flex;

    flex-direction: column;

    gap: 14px;
}


/* =========================================================
   ACTIVITY ITEM
========================================================= */

.activity-item {
    display: flex;

    align-items: stretch;

    width: 100%;

    min-height: 145px;

    background: #ffffff;

    border: 1px solid #e6ece8;

    border-radius: 14px;

    overflow: hidden;

    transition: all .2s ease;
}

.activity-item:hover {
    border-color: #cbd8d1;

    box-shadow: 0 6px 18px rgba(0,0,0,.06);

    transform: translateY(-1px);
}


/* =========================================================
   ACTIVITY IMAGE
========================================================= */

.activity-item-image {
    width: 145px;

    min-width: 145px;

    height: 145px;

    padding: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f3f7f5;
}


/* ACTUAL IMAGE */

.activity-item-image img {
    width: 100%;

    height: 100%;

    display: block;

    object-fit: cover;

    border-radius: 10px;

    border: 1px solid #e0e7e3;
}


/* =========================================================
   NO IMAGE
========================================================= */

.activity-no-image {
    width: 100%;

    height: 100%;

    border-radius: 10px;

    border: 1px dashed #cbd6d0;

    background: #e9f0ec;

    color: #8b9891;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;
}

.activity-no-image i {
    font-size: 27px;

    margin-bottom: 5px;
}

.activity-no-image span {
    font-size: 10px;
}


/* =========================================================
   ACTIVITY CONTENT
========================================================= */

.activity-item-content {
    flex: 1;

    min-width: 0;

    padding: 15px 18px;

    display: flex;

    flex-direction: column;
}


/* =========================================================
   ACTIVITY TOP
========================================================= */

.activity-item-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 5px;
}


/* TITLE */

.activity-item-title {
    margin: 0;

    color: #27362e;

    font-size: 17px;

    font-weight: 700;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* =========================================================
   PURPOSE
========================================================= */

.activity-item-purpose {
    color: #69766f;

    font-size: 12px;

    line-height: 1.4;

    margin-bottom: 10px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.activity-item-purpose strong {
    color: #3e4c44;
}


/* =========================================================
   ACTIVITY DETAILS
========================================================= */

.activity-details {
    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 17px;

    margin-bottom: 9px;
}

.activity-detail {
    display: flex;

    align-items: center;

    gap: 6px;

    color: #6d7972;

    font-size: 11px;
}

.activity-detail i {
    color: #198754;

    font-size: 12px;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.activity-description {
    display: flex;

    gap: 5px;

    color: #7b867f;

    font-size: 11px;

    line-height: 1.4;

    margin-bottom: 7px;
}

.activity-description-label {
    flex-shrink: 0;

    color: #4e5b53;

    font-weight: 600;
}

.activity-description-text {
    min-width: 0;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* =========================================================
   BOTTOM
========================================================= */

.activity-item-bottom {
    margin-top: auto;

    padding-top: 7px;

    border-top: 1px solid #edf1ef;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;
}

.activity-submitted {
    color: #929c96;

    font-size: 10px;
}


/* =========================================================
   STATUS
========================================================= */

.activity-badge {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 700;

    white-space: nowrap;
}

.activity-badge.approved {
    color: #198754;

    background: #e7f7ee;
}

.activity-badge.pending {
    color: #b77900;

    background: #fff4d6;
}

.activity-badge.rejected {
    color: #dc3545;

    background: #fdeaea;
}


/* =========================================================
   ACTION BUTTONS
========================================================= */

.activity-actions {
    display: flex;

    align-items: center;

    gap: 6px;
}

.activity-action {
    width: 31px;

    height: 31px;

    display: flex;

    align-items: center;

    justify-content: center;

    border: none;

    border-radius: 8px;

    cursor: pointer;

    text-decoration: none;

    transition: all .2s ease;
}


/* VIEW */

.activity-view {
    background: #edf7f1;

    color: #198754;
}

.activity-view:hover {
    background: #198754;

    color: #ffffff;
}


/* DELETE */

.activity-delete {
    background: #fff0f0;

    color: #dc3545;
}

.activity-delete:hover {
    background: #dc3545;

    color: #ffffff;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.no-activities {
    padding: 60px 20px;

    text-align: center;

    color: #89958e;
}

.no-activities i {
    display: block;

    margin-bottom: 12px;

    color: #b7c2bc;

    font-size: 42px;
}

.no-activities h5 {
    margin: 0 0 5px;

    color: #536158;

    font-size: 15px;
}

.no-activities p {
    margin: 0;

    font-size: 12px;
}


/* =========================================================
   TABLET
========================================================= */

@media(max-width: 992px) {

    .modern-header {
        align-items: flex-start;

        flex-direction: column;
    }

    .header-right {
        width: 100%;
    }

    .search-box {
        flex: 1;
    }

    .search-box input {
        width: 100%;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width: 768px) {

    .activity-card .card-body {
        padding: 12px;
    }

    .activity-item {
        flex-direction: column;

        min-height: auto;
    }

    .activity-item-image {
        width: 100%;

        min-width: 100%;

        height: 170px;

        padding: 10px;
    }

    .activity-item-image img {
        height: 150px;
    }

    .activity-no-image {
        height: 150px;
    }

    .activity-item-content {
        padding: 14px;
    }

    .activity-item-title {
        font-size: 15px;

        white-space: normal;
    }

    .activity-details {
        gap: 10px;
    }

    .activity-description {
        display: block;
    }

    .activity-description-label {
        display: block;

        margin-bottom: 3px;
    }

    .activity-description-text {
        display: block;

        white-space: normal;
    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media(max-width: 480px) {

    .modern-header {
        padding: 15px;
    }

    .header-left h4 {
        font-size: 16px;
    }

    .header-left p {
        font-size: 11px;
    }

    .header-right {
        flex-direction: column;

        align-items: stretch;
    }

    .filter-btn {
        width: 100%;
    }

    .activity-item-image {
        height: 150px;
    }

    .activity-item-image img {
        height: 130px;
    }

    .activity-no-image {
        height: 130px;
    }

    .activity-item-bottom {
        flex-direction: column;

        align-items: flex-start;
    }

}

</style>


<div class="container-fluid">

    <!-- =========================================================
         DASHBOARD SUMMARY
    ========================================================= -->

    <div class="row mb-4">

        <!-- TOTAL -->
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


        <!-- APPROVED -->
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="stats-card">

                <div class="stats-icon success">
                    <i class="fa fa-check-circle"></i>
                </div>

                <div class="stats-content">

                    <small>Approved</small>

                    <h3 id="approved_activity">9</h3>

                    <span>Approved activities</span>

                </div>

                <div class="stats-chart text-success">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>


        <!-- PENDING -->
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="stats-card">

                <div class="stats-icon warning">
                    <i class="fa fa-clock-o"></i>
                </div>

                <div class="stats-content">

                    <small>Pending</small>

                    <h3 id="pending_activity">2</h3>

                    <span>Pending activities</span>

                </div>

                <div class="stats-chart text-warning">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>


        <!-- REJECTED -->
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="stats-card">

                <div class="stats-icon danger">
                    <i class="fa fa-times-circle"></i>
                </div>

                <div class="stats-content">

                    <small>Rejected</small>

                    <h3 id="rejected_activity">1</h3>

                    <span>Rejected activities</span>

                </div>

                <div class="stats-chart text-danger">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         ALL ACTIVITIES
    ========================================================= -->

    <div class="row">

        <div class="col-12">

            <div class="card activity-card">

                <!-- HEADER -->

                <div class="modern-header">

                    <div class="header-left">

                        <div class="header-icon">
                            <i class="fa fa-list"></i>
                        </div>

                        <div>

                            <h4>
                                All Activities
                            </h4>

                            <p>
                                View and monitor all submitted extension activities.
                            </p>

                        </div>

                    </div>


                    <div class="header-right">

                        <div class="search-box">

                            <i class="fa fa-search"></i>

                            <input
                                type="text"
                                id="activity-search"
                                placeholder="Search activities...">

                        </div>


                        <button
                            type="button"
                            class="filter-btn">

                            <i class="fa fa-filter"></i>

                            Filter

                        </button>

                    </div>

                </div>


                <!-- ACTIVITY LIST -->

                <div class="card-body">

                    <div
                        id="activity-list"
                        class="activity-list">
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   DOCUMENT READY
========================================================= */

$(document).ready(function(){

    load_activity();

});


/* =========================================================
   LOAD ALL ACTIVITIES
========================================================= */

function load_activity(){

    $.ajax({

        url:"ajax.php?action=all_list_activity",

        success:function(resp){

            $("#activity-list").html(resp);

        },

        error:function(xhr){

            console.log(xhr.responseText);

            $("#activity-list").html(`

                <div class="no-activities">

                    <i class="fa fa-exclamation-circle"></i>

                    <h5>
                        Unable to load activities
                    </h5>

                    <p>
                        Please try again.
                    </p>

                </div>

            `);

        }

    });

}


/* =========================================================
   SEARCH
========================================================= */

$(document).on(
    "keyup",
    "#activity-search",
    function(){

        var value = $(this)
            .val()
            .toLowerCase();


        $(".activity-item").filter(function(){

            $(this).toggle(

                $(this)
                    .text()
                    .toLowerCase()
                    .indexOf(value) > -1

            );

        });

    }
);


/* =========================================================
   DELETE ACTIVITY
========================================================= */

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

