<?php include 'db_connect.php'; ?>

<div class="container-fluid user-page">

    <!-- MAIN CARD -->
    <div class="card user-card">

        <!-- HEADER -->
        <div class="user-header">

            <div class="header-content">

                <div class="header-icon">
                    <i class="fas fa-users"></i>
                </div>

                <div>
                    <h3>User Management</h3>
                    <p>Manage system administrators and registered users.</p>
                </div>

            </div>

            <a href="./index.php?page=new_user" class="btn btn-add-user">
                <i class="fas fa-user-plus mr-2"></i>
                Add New User
            </a>

        </div>


        <!-- BODY -->
        <div class="card-body user-body">

            <!-- TABLE TOP -->
            <div class="table-intro">

                <div>
                    <h5>
                        <i class="fas fa-user-cog mr-2"></i>
                        System Users
                    </h5>

                    <span>
                        View, edit, or remove registered users.
                    </span>
                </div>

                <div class="user-count">
                    <i class="fas fa-users"></i>

                    <?php
                    $total_users = $conn->query("SELECT id FROM users")->num_rows;
                    echo $total_users;
                    ?>

                    <span>Users</span>
                </div>

            </div>


            <!-- TABLE -->
            <div class="table-wrapper">

                <table class="table modern-user-table" id="list">

                    <thead>

                        <tr>

                            <th class="text-center number-column">
                                #
                            </th>

                            <th>
                                User
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
                        FROM users
                        ORDER BY CONCAT(firstname,' ',lastname) ASC
                    ");

                    while($row = $qry->fetch_assoc()):

                        $firstname = !empty($row['firstname']) ? $row['firstname'] : '';
                        $lastname  = !empty($row['lastname']) ? $row['lastname'] : '';

                        $initials = strtoupper(
                            substr($firstname, 0, 1) .
                            substr($lastname, 0, 1)
                        );

                        if(empty($initials)){
                            $initials = 'U';
                        }

                    ?>

                    <tr>

                        <!-- NUMBER -->
                        <td class="text-center">

                            <span class="row-number">
                                <?php echo $i++; ?>
                            </span>

                        </td>


                        <!-- USER -->
                        <td>

                            <div class="user-info">

                                <div class="user-avatar">
                                    <?php echo $initials; ?>
                                </div>

                                <div class="user-details">

                                    <strong>
                                        <?php
                                        echo ucwords(
                                            htmlspecialchars($row['name'])
                                        );
                                        ?>
                                    </strong>

                                    <small>
                                        System User
                                    </small>

                                </div>

                            </div>

                        </td>


                        <!-- EMAIL -->
                        <td>

                            <div class="email-wrapper">

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

                            <div class="dropdown action-dropdown">

                                <button
                                    type="button"
                                    class="action-button"
                                    data-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false">

                                    <i class="fas fa-ellipsis-v"></i>

                                </button>


                                <div class="dropdown-menu dropdown-menu-right modern-dropdown">

                                    <div class="dropdown-title">
                                        User Actions
                                    </div>


                                    <a
                                        class="dropdown-item view_user"
                                        href="javascript:void(0)"
                                        data-id="<?php echo $row['id']; ?>">

                                        <span class="menu-icon view-icon">
                                            <i class="fas fa-eye"></i>
                                        </span>

                                        <span>
                                            View Details
                                        </span>

                                    </a>


                                    <a
                                        class="dropdown-item"
                                        href="./index.php?page=edit_user&id=<?php echo $row['id']; ?>">

                                        <span class="menu-icon edit-icon">
                                            <i class="fas fa-edit"></i>
                                        </span>

                                        <span>
                                            Edit User
                                        </span>

                                    </a>


                                    <div class="dropdown-divider"></div>


                                    <a
                                        class="dropdown-item delete-item delete_user"
                                        href="javascript:void(0)"
                                        data-id="<?php echo $row['id']; ?>">

                                        <span class="menu-icon delete-icon">
                                            <i class="fas fa-trash"></i>
                                        </span>

                                        <span>
                                            Delete User
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


<style>

/* =========================================================
   USER MANAGEMENT
========================================================= */

.user-page{
    padding:15px;
}


/* =========================================================
   MAIN CARD
========================================================= */

.user-card{

    border:none !important;

    border-radius:22px !important;

    overflow:hidden;

    background:#fff;

    box-shadow:
        0 12px 35px rgba(0,0,0,.08);

}


/* =========================================================
   HEADER
========================================================= */

.user-header{

    background:
        linear-gradient(
            135deg,
            #16a34a 0%,
            #15803d 55%,
            #166534 100%
        );

    color:#fff;

    padding:25px 30px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

}


.header-content{

    display:flex;

    align-items:center;

    gap:15px;

}


.header-icon{

    width:52px;

    height:52px;

    border-radius:15px;

    background:rgba(255,255,255,.16);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:22px;

    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.12);

}


.user-header h3{

    margin:0;

    font-size:24px;

    font-weight:700;

    letter-spacing:.2px;

}


.user-header p{

    margin:5px 0 0;

    font-size:14px;

    opacity:.88;

}


/* =========================================================
   ADD USER BUTTON
========================================================= */

.btn-add-user{

    background:#fff;

    color:#15803d;

    border:none;

    border-radius:12px;

    padding:11px 20px;

    font-weight:700;

    white-space:nowrap;

    box-shadow:
        0 6px 18px rgba(0,0,0,.14);

    transition:.25s ease;

}


.btn-add-user:hover{

    background:#f0fdf4;

    color:#166534;

    transform:translateY(-2px);

    box-shadow:
        0 9px 22px rgba(0,0,0,.18);

}


/* =========================================================
   BODY
========================================================= */

.user-body{

    padding:25px 28px 30px;

}


/* =========================================================
   TABLE INTRO
========================================================= */

.table-intro{

    display:flex;

    align-items:center;

    justify-content:space-between;

    margin-bottom:20px;

    gap:20px;

}


.table-intro h5{

    margin:0;

    color:#1f2937;

    font-size:18px;

    font-weight:700;

}


.table-intro h5 i{

    color:#16a34a;

}


.table-intro span{

    display:block;

    margin-top:4px;

    color:#8a94a6;

    font-size:13px;

}


/* USER COUNT */

.user-count{

    display:flex;

    align-items:center;

    gap:7px;

    background:#ecfdf5;

    color:#15803d;

    border-radius:30px;

    padding:8px 15px;

    font-size:14px;

    font-weight:700;

}


.user-count i{

    font-size:14px;

}


.user-count span{

    display:inline;

    margin:0;

    color:#15803d;

    font-size:13px;

}


/* =========================================================
   TABLE WRAPPER
========================================================= */

.table-wrapper{

    width:100%;

    overflow-x:auto;

    border:1px solid #edf0f2;

    border-radius:16px;

}


/* =========================================================
   TABLE
========================================================= */

.modern-user-table{

    margin:0 !important;

    min-width:700px;

}


.modern-user-table thead{

    background:#f0fdf4;

}


.modern-user-table thead th{

    border:none !important;

    color:#166534;

    font-size:13px;

    font-weight:700;

    padding:15px 16px;

    text-transform:uppercase;

    letter-spacing:.4px;

    white-space:nowrap;

}


.modern-user-table tbody td{

    padding:15px 16px;

    vertical-align:middle;

    border-top:1px solid #f0f2f4;

    color:#4b5563;

    font-size:14px;

}


.modern-user-table tbody tr{

    background:#fff;

    transition:.2s ease;

}


.modern-user-table tbody tr:hover{

    background:#f8fffa;

}


.number-column{

    width:60px;

}


.action-column{

    width:110px;

}


/* =========================================================
   ROW NUMBER
========================================================= */

.row-number{

    width:30px;

    height:30px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    background:#f3f4f6;

    color:#6b7280;

    border-radius:9px;

    font-size:12px;

    font-weight:700;

}


/* =========================================================
   USER INFO
========================================================= */

.user-info{

    display:flex;

    align-items:center;

    gap:12px;

}


.user-avatar{

    width:44px;

    height:44px;

    min-width:44px;

    border-radius:13px;

    background:
        linear-gradient(
            135deg,
            #22c55e,
            #15803d
        );

    color:#fff;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:14px;

    font-weight:800;

    box-shadow:
        0 5px 12px rgba(22,163,74,.18);

}


.user-details{

    display:flex;

    flex-direction:column;

}


.user-details strong{

    color:#1f2937;

    font-size:14px;

    font-weight:700;

}


.user-details small{

    margin-top:2px;

    color:#9ca3af;

    font-size:11px;

}


/* =========================================================
   EMAIL
========================================================= */

.email-wrapper{

    display:flex;

    align-items:center;

    gap:10px;

}


.email-icon{

    width:34px;

    height:34px;

    border-radius:10px;

    background:#effdf4;

    color:#16a34a;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:13px;

}


.email-wrapper span{

    color:#4b5563;

    font-size:13px;

}


/* =========================================================
   ACTION BUTTON
========================================================= */

.action-button{

    width:38px;

    height:38px;

    border:none;

    border-radius:11px;

    background:#f0fdf4;

    color:#15803d;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    cursor:pointer;

    transition:.2s ease;

}


.action-button:hover{

    background:#dcfce7;

    color:#166534;

    transform:translateY(-1px);

}


.action-button:focus{

    outline:none;

    box-shadow:
        0 0 0 3px rgba(34,197,94,.15);

}


/* =========================================================
   DROPDOWN
========================================================= */

.modern-dropdown{

    min-width:190px;

    padding:8px;

    border:none !important;

    border-radius:14px;

    box-shadow:
        0 12px 30px rgba(0,0,0,.14);

}


.dropdown-title{

    padding:7px 12px 9px;

    color:#9ca3af;

    font-size:11px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:.6px;

}


.modern-dropdown .dropdown-item{

    display:flex;

    align-items:center;

    gap:10px;

    padding:9px 10px;

    border-radius:9px;

    color:#374151;

    font-size:13px;

    font-weight:500;

    transition:.2s ease;

}


.modern-dropdown .dropdown-item:hover{

    background:#f0fdf4;

    color:#15803d;

}


.menu-icon{

    width:30px;

    height:30px;

    border-radius:8px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:12px;

}


.view-icon{

    background:#eff6ff;

    color:#2563eb;

}


.edit-icon{

    background:#f0fdf4;

    color:#16a34a;

}


.delete-icon{

    background:#fef2f2;

    color:#dc2626;

}


.delete-item:hover{

    background:#fef2f2 !important;

    color:#dc2626 !important;

}


.modern-dropdown .dropdown-divider{

    border-top:1px solid #f0f0f0;

    margin:7px 3px;

}


/* =========================================================
   DATATABLE
========================================================= */

.dataTables_wrapper{

    padding-top:0;

}


.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter{

    margin-bottom:15px;

}


.dataTables_wrapper .dataTables_filter input{

    border:1px solid #dfe5e9;

    border-radius:10px;

    padding:7px 12px;

    margin-left:6px;

    outline:none;

}


.dataTables_wrapper .dataTables_filter input:focus{

    border-color:#22c55e;

    box-shadow:
        0 0 0 3px rgba(34,197,94,.10);

}


.dataTables_wrapper .dataTables_length select{

    border:1px solid #dfe5e9;

    border-radius:9px;

    padding:5px 8px;

    outline:none;

}


.dataTables_wrapper .dataTables_info{

    color:#8a94a6;

    font-size:13px;

}


.dataTables_wrapper .dataTables_paginate .paginate_button{

    border-radius:8px !important;

}


.dataTables_wrapper .dataTables_paginate .paginate_button.current{

    background:#16a34a !important;

    color:#fff !important;

    border:none !important;

}


.dataTables_wrapper .dataTables_paginate .paginate_button:hover{

    background:#dcfce7 !important;

    color:#15803d !important;

    border:none !important;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:768px){

    .user-page{

        padding:8px;

    }


    .user-header{

        padding:20px;

        flex-direction:column;

        align-items:stretch;

        text-align:left;

    }


    .btn-add-user{

        width:100%;

        text-align:center;

    }


    .user-body{

        padding:18px;

    }


    .table-intro{

        flex-direction:column;

        align-items:flex-start;

    }


    .user-count{

        align-self:flex-start;

    }


    .header-content{

        align-items:flex-start;

    }


    .user-header h3{

        font-size:20px;

    }

}


@media(max-width:480px){

    .header-icon{

        width:45px;

        height:45px;

        min-width:45px;

    }


    .user-header p{

        font-size:12px;

    }


    .modern-user-table{

        min-width:650px;

    }

}

</style>


<script>

$(document).ready(function(){

    /* =====================================================
       DATATABLE
    ===================================================== */

    $('#list').DataTable({

        responsive: false,

        pageLength: 10,

        lengthMenu: [
            [5,10,25,50,-1],
            [5,10,25,50,"All"]
        ],

        order: [],

        language: {

            search: "",

            searchPlaceholder: "Search users..."

        }

    });


    /* =====================================================
       VIEW USER
    ===================================================== */

    $('.view_user').click(function(){

        var id = $(this).attr('data-id');

        uni_modal(
            "<i class='fa fa-id-card mr-2'></i>User Details",
            "view_user.php?id=" + id
        );

    });


    /* =====================================================
       DELETE USER
    ===================================================== */

    $('.delete_user').click(function(){

        var id = $(this).attr('data-id');

        _conf(
            "Are you sure you want to delete this user?",
            "delete_user",
            [id]
        );

    });

});


/* =========================================================
   DELETE FUNCTION
========================================================= */

function delete_user($id){

    start_load();

    $.ajax({

        url:'ajax.php?action=delete_user',

        method:'POST',

        data:{
            id:$id
        },

        success:function(resp){

            if(resp == 1){

                alert_toast(
                    "User successfully deleted.",
                    "success"
                );

                setTimeout(function(){

                    location.reload();

                },1500);

            }else{

                alert_toast(
                    "Unable to delete user.",
                    "error"
                );

                end_load();

            }

        },

        error:function(){

            alert_toast(
                "An error occurred while deleting the user.",
                "error"
            );

            end_load();

        }

    });

}

</script>