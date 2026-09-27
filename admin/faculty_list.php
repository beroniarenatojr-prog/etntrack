
<?php include 'db_connect.php' ?>

<div class="col-lg-12 coordinator-page">

    <!-- =========================
         MAIN CARD
    ========================== -->

    <div class="card coordinator-card">


        <!-- =========================
             HEADER
        ========================== -->

        <div class="coordinator-header">

            <div class="header-content">

                <div class="header-icon">

                    <i class="fas fa-users"></i>

                </div>

                <div>

                    <h3>
                        Extension Coordinators
                    </h3>

                    <p>
                        Manage registered extension coordinators and their accounts.
                    </p>

                </div>

            </div>


            <a
                href="./index.php?page=new_faculty"
                class="btn btn-add"
            >

                <i class="fas fa-user-plus"></i>

                Add Coordinator

            </a>

        </div>



        <!-- =========================
             TABLE BODY
        ========================== -->

        <div class="card-body coordinator-body">


            <!-- TABLE INFORMATION -->

            <div class="table-toolbar">

                <div class="toolbar-title">

                    <div class="toolbar-icon">

                        <i class="fas fa-list"></i>

                    </div>

                    <div>

                        <strong>
                            Coordinator List
                        </strong>

                        <span>
                            All registered extension evaluators
                        </span>

                    </div>

                </div>

            </div>



            <div class="table-wrapper">

                <table
                    class="table coordinator-table"
                    id="list"
                >

                    <thead>

                        <tr>

                            <th class="text-center number-column">
                                #
                            </th>

                            <th>
                                School ID
                            </th>

                            <th>
                                Coordinator
                            </th>

                            <th>
                                Email Address
                            </th>

                            <th class="text-center action-column">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php

                        $i = 1;

                        $qry = $conn->query("
                            SELECT *,
                            CONCAT(firstname,' ',lastname) AS name
                            FROM faculty_list
                            ORDER BY CONCAT(firstname,' ',lastname) ASC
                        ");

                        while($row = $qry->fetch_assoc()):

                            $initials =
                                strtoupper(
                                    substr($row['firstname'],0,1) .
                                    substr($row['lastname'],0,1)
                                );

                        ?>

                        <tr>


                            <!-- NUMBER -->

                            <td class="text-center">

                                <span class="row-number">

                                    <?php echo $i++; ?>

                                </span>

                            </td>



                            <!-- SCHOOL ID -->

                            <td>

                                <span class="school-id">

                                    <i class="fas fa-id-card"></i>

                                    <?php echo htmlspecialchars($row['school_id']); ?>

                                </span>

                            </td>



                            <!-- NAME -->

                            <td>

                                <div class="coordinator-profile">


                                    <div class="profile-avatar">

                                        <?php echo $initials; ?>

                                    </div>


                                    <div class="profile-details">

                                        <strong>

                                            <?php
                                            echo ucwords(
                                                htmlspecialchars($row['name'])
                                            );
                                            ?>

                                        </strong>

                                        <span>
                                            Extension Coordinator
                                        </span>

                                    </div>


                                </div>

                            </td>



                            <!-- EMAIL -->

                            <td>

                                <div class="email-cell">

                                    <div class="email-icon">

                                        <i class="fas fa-envelope"></i>

                                    </div>

                                    <span>

                                        <?php
                                        echo htmlspecialchars($row['email']);
                                        ?>

                                    </span>

                                </div>

                            </td>



                            <!-- ACTION -->

                            <td class="text-center">


                                <div class="action-wrapper">


                                    <button
                                        type="button"
                                        class="action-button"
                                        data-toggle="dropdown"
                                        aria-expanded="false"
                                    >

                                        <span>
                                            Actions
                                        </span>

                                        <i class="fas fa-chevron-down"></i>

                                    </button>



                                    <div class="dropdown-menu action-dropdown">


                                        <a
                                            class="dropdown-item view_faculty"
                                            href="javascript:void(0)"
                                            data-id="<?php echo $row['id']; ?>"
                                        >

                                            <span class="dropdown-icon view-icon">

                                                <i class="fas fa-eye"></i>

                                            </span>

                                            <span>

                                                <strong>View</strong>

                                                <small>
                                                    View coordinator details
                                                </small>

                                            </span>

                                        </a>



                                        <a
                                            class="dropdown-item"
                                            href="./index.php?page=edit_faculty&id=<?php echo $row['id']; ?>"
                                        >

                                            <span class="dropdown-icon edit-icon">

                                                <i class="fas fa-edit"></i>

                                            </span>

                                            <span>

                                                <strong>Edit</strong>

                                                <small>
                                                    Update account information
                                                </small>

                                            </span>

                                        </a>



                                        <div class="dropdown-divider"></div>



                                        <a
                                            class="dropdown-item delete-item delete_faculty"
                                            href="javascript:void(0)"
                                            data-id="<?php echo $row['id']; ?>"
                                        >

                                            <span class="dropdown-icon delete-icon">

                                                <i class="fas fa-trash-alt"></i>

                                            </span>

                                            <span>

                                                <strong>Delete</strong>

                                                <small>
                                                    Remove this coordinator
                                                </small>

                                            </span>

                                        </a>


                                    </div>


                                </div>


                            </td>


                        </tr>


                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>


        </div>

    </div>

</div>



<!-- =========================
     CSS
========================== -->

<style>

:root{

    --coordinator-green:#198754;
    --coordinator-dark:#146c43;
    --coordinator-light:#eaf8f0;

    --coordinator-border:#e8ecef;

    --coordinator-text:#1f2937;
    --coordinator-muted:#6b7280;

}


/* =========================
   PAGE
========================= */

.coordinator-page{

    padding:10px 5px 30px;

}


/* =========================
   MAIN CARD
========================= */

.coordinator-card{

    border:none!important;

    border-radius:20px!important;

    overflow:visible;

    background:#fff;

    box-shadow:
        0 12px 35px rgba(0,0,0,.07);

}


/* =========================
   HEADER
========================= */

.coordinator-header{

    background:
        linear-gradient(
            135deg,
            #198754,
            #146c43
        );

    color:#fff;

    padding:25px 30px;

    border-radius:20px 20px 0 0;

    display:flex;

    justify-content:space-between;

    align-items:center;

    box-shadow:
        0 8px 20px rgba(25,135,84,.15);

}


.header-content{

    display:flex;

    align-items:center;

    gap:16px;

}


.header-icon{

    width:55px;

    height:55px;

    border-radius:15px;

    background:rgba(255,255,255,.15);

    border:1px solid rgba(255,255,255,.15);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:23px;

}


.coordinator-header h3{

    margin:0;

    font-size:24px;

    font-weight:700;

}


.coordinator-header p{

    margin:5px 0 0;

    font-size:13px;

    opacity:.85;

}


/* =========================
   ADD BUTTON
========================= */

.btn-add{

    background:#fff;

    color:var(--coordinator-green)!important;

    border:none;

    border-radius:11px;

    padding:11px 19px;

    font-size:13px;

    font-weight:700;

    box-shadow:
        0 6px 18px rgba(0,0,0,.12);

    transition:.25s;

}


.btn-add:hover{

    background:#f0fdf4;

    color:var(--coordinator-dark)!important;

    transform:translateY(-2px);

    text-decoration:none;

    box-shadow:
        0 9px 22px rgba(0,0,0,.15);

}


.btn-add i{

    margin-right:5px;

}


/* =========================
   BODY
========================= */

.coordinator-body{

    padding:25px 28px 30px!important;

    border-radius:0 0 20px 20px;

}


/* =========================
   TOOLBAR
========================= */

.table-toolbar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:18px;

}


.toolbar-title{

    display:flex;

    align-items:center;

    gap:11px;

}


.toolbar-icon{

    width:38px;

    height:38px;

    border-radius:10px;

    background:var(--coordinator-light);

    color:var(--coordinator-green);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:14px;

}


.toolbar-title strong{

    display:block;

    color:var(--coordinator-text);

    font-size:15px;

}


.toolbar-title span{

    display:block;

    margin-top:2px;

    color:#9ca3af;

    font-size:11px;

}


/* =========================
   TABLE WRAPPER
========================= */

.table-wrapper{

    width:100%;

    overflow-x:auto;

    border:1px solid var(--coordinator-border);

    border-radius:15px;

}


/* =========================
   TABLE
========================= */

.coordinator-table{

    margin:0!important;

    min-width:800px;

}


.coordinator-table thead{

    background:#f7faf8;

}


.coordinator-table thead th{

    border:none!important;

    border-bottom:1px solid var(--coordinator-border)!important;

    padding:15px 16px;

    color:#4b5563;

    font-size:12px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:.3px;

    white-space:nowrap;

}


.coordinator-table tbody td{

    border-top:1px solid #f0f2f3!important;

    padding:15px 16px;

    vertical-align:middle!important;

    color:#4b5563;

    font-size:13px;

}


.coordinator-table tbody tr{

    transition:.2s;

    background:#fff;

}


.coordinator-table tbody tr:hover{

    background:#fafffb;

}


.number-column{

    width:60px;

}


.action-column{

    width:140px;

}


/* =========================
   ROW NUMBER
========================= */

.row-number{

    width:30px;

    height:30px;

    border-radius:9px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    background:#f1f8f4;

    color:var(--coordinator-green);

    font-weight:700;

    font-size:12px;

}


/* =========================
   SCHOOL ID
========================= */

.school-id{

    display:inline-flex;

    align-items:center;

    gap:7px;

    background:#f7faf8;

    border:1px solid #e5eee8;

    padding:7px 11px;

    border-radius:9px;

    color:#374151;

    font-weight:600;

    font-size:12px;

}


.school-id i{

    color:var(--coordinator-green);

    font-size:12px;

}


/* =========================
   PROFILE
========================= */

.coordinator-profile{

    display:flex;

    align-items:center;

    gap:11px;

}


.profile-avatar{

    width:42px;

    height:42px;

    min-width:42px;

    border-radius:12px;

    background:
        linear-gradient(
            135deg,
            #dff7e9,
            #bfe8cf
        );

    color:var(--coordinator-dark);

    display:flex;

    align-items:center;

    justify-content:center;

    font-weight:800;

    font-size:13px;

}


.profile-details strong{

    display:block;

    color:#263238;

    font-size:13px;

}


.profile-details span{

    display:block;

    color:#9ca3af;

    font-size:10px;

    margin-top:2px;

}


/* =========================
   EMAIL
========================= */

.email-cell{

    display:flex;

    align-items:center;

    gap:9px;

}


.email-icon{

    width:32px;

    height:32px;

    border-radius:9px;

    background:#eef7ff;

    color:#3b82f6;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:12px;

}


.email-cell span{

    color:#4b5563;

    font-size:12px;

}


/* =========================
   ACTION BUTTON
========================= */

.action-wrapper{

    position:relative;

    display:inline-block;

}


.action-button{

    border:1px solid #dce9e0;

    background:#f5faf7;

    color:var(--coordinator-green);

    border-radius:9px;

    padding:7px 11px;

    font-size:12px;

    font-weight:700;

    cursor:pointer;

    transition:.2s;

}


.action-button:hover,
.action-button:focus{

    background:#e8f5ed;

    color:var(--coordinator-dark);

    border-color:#b9dcc6;

    outline:none;

}


.action-button i{

    margin-left:5px;

    font-size:9px;

}


/* =========================
   DROPDOWN
========================= */

.action-dropdown{

    min-width:245px;

    padding:7px;

    margin-top:7px;

    border:none!important;

    border-radius:13px;

    box-shadow:
        0 15px 35px rgba(0,0,0,.14);

}


.action-dropdown .dropdown-item{

    display:flex;

    align-items:center;

    gap:10px;

    padding:10px;

    border-radius:9px;

    color:#374151;

    white-space:normal;

    transition:.2s;

}


.action-dropdown .dropdown-item:hover{

    background:#f4faf6;

    color:var(--coordinator-dark);

}


.action-dropdown .dropdown-item > span:last-child{

    display:flex;

    flex-direction:column;

}


.action-dropdown .dropdown-item strong{

    font-size:12px;

    font-weight:700;

}


.action-dropdown .dropdown-item small{

    font-size:10px;

    color:#9ca3af;

    margin-top:2px;

}


.dropdown-icon{

    width:35px;

    height:35px;

    min-width:35px;

    border-radius:9px;

    display:flex;

    align-items:center;

    justify-content:center;

}


.view-icon{

    background:#eaf3ff;

    color:#3b82f6;

}


.edit-icon{

    background:#fff7e6;

    color:#f59e0b;

}


.delete-icon{

    background:#fff0f0;

    color:#dc3545;

}


.delete-item:hover{

    background:#fff5f5!important;

    color:#dc3545!important;

}


.action-dropdown .dropdown-divider{

    border-top:1px solid #f0f1f2;

    margin:5px 4px;

}


/* =========================
   DATATABLE
========================= */

.dataTables_wrapper{

    padding-top:0!important;

}


.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter{

    margin-bottom:15px;

}


.dataTables_wrapper .dataTables_filter input{

    border:1px solid #dfe5e1;

    border-radius:9px;

    padding:7px 11px;

    outline:none;

    transition:.2s;

}


.dataTables_wrapper .dataTables_filter input:focus{

    border-color:var(--coordinator-green);

    box-shadow:
        0 0 0 3px rgba(25,135,84,.08);

}


.dataTables_wrapper .dataTables_length select{

    border:1px solid #dfe5e1;

    border-radius:8px;

    padding:5px;

}


.dataTables_wrapper .dataTables_paginate .paginate_button{

    border-radius:8px!important;

}


.dataTables_wrapper .dataTables_paginate .paginate_button.current{

    background:var(--coordinator-green)!important;

    border-color:var(--coordinator-green)!important;

    color:white!important;

}


/* =========================
   MOBILE
========================= */

@media(max-width:768px){

    .coordinator-page{

        padding:5px;

    }


    .coordinator-header{

        padding:20px;

        flex-direction:column;

        align-items:flex-start;

        gap:16px;

    }


    .header-content{

        width:100%;

    }


    .coordinator-header h3{

        font-size:20px;

    }


    .coordinator-header p{

        font-size:11px;

    }


    .btn-add{

        width:100%;

        text-align:center;

    }


    .coordinator-body{

        padding:18px!important;

    }


    .table-wrapper{

        border-radius:12px;

    }

}


/* =========================
   SMALL MOBILE
========================= */

@media(max-width:480px){

    .header-icon{

        width:48px;

        height:48px;

    }


    .coordinator-header h3{

        font-size:18px;

    }


    .toolbar-title span{

        font-size:10px;

    }

}

</style>



<!-- =========================
     JAVASCRIPT
========================== -->

<script>

$(document).ready(function(){


    /* =========================
       VIEW FACULTY
    ========================== */

    $('.view_faculty').click(function(){

        var id = $(this).attr('data-id');


        uni_modal(

            "<i class='fa fa-id-card'></i> Coordinator Details",

            "<?php echo $_SESSION['login_view_folder'] ?>" +
            "view_faculty.php?id=" +
            id

        );

    });



    /* =========================
       DELETE FACULTY
    ========================== */

    $('.delete_faculty').click(function(){

        var id = $(this).attr('data-id');


        _conf(

            "Are you sure you want to delete this coordinator?",

            "delete_faculty",

            [id]

        );

    });



    /* =========================
       DATATABLE
    ========================== */

    $('#list').dataTable({

        pageLength:10,

        lengthMenu:[
            [10,25,50,-1],
            [10,25,50,"All"]
        ],

        order:[],

        language:{

            search:"",

            searchPlaceholder:"Search coordinators...",

            lengthMenu:"Show _MENU_ coordinators",

            zeroRecords:
                "No coordinators found",

            info:
                "Showing _START_ to _END_ of _TOTAL_ coordinators",

            infoEmpty:
                "No coordinators available",

            paginate:{

                previous:"‹",

                next:"›"

            }

        }

    });

});



/* =========================
   DELETE FUNCTION
========================== */

function delete_faculty($id){

    start_load();


    $.ajax({

        url:'ajax.php?action=delete_faculty',

        method:'POST',

        data:{
            id:$id
        },

        success:function(resp){

            if(resp == 1){

                alert_toast(
                    "Coordinator successfully deleted.",
                    "success"
                );


                setTimeout(function(){

                    location.reload();

                },1200);

            }else{

                alert_toast(
                    "Unable to delete coordinator.",
                    "error"
                );

                end_load();

            }

        },

        error:function(){

            alert_toast(
                "An unexpected error occurred.",
                "error"
            );

            end_load();

        }

    });

}

</script>
