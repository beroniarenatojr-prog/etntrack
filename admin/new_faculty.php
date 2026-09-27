
<?php
include 'db_connect.php';

if(isset($_GET['id'])){
    $qry = $conn->query("
        SELECT *,
        CONCAT(firstname,' ',lastname) AS name
        FROM faculty_list
        WHERE id = ".$_GET['id']
    )->fetch_array();

    foreach($qry as $k => $v){
        $$k = $v;
    }
}

$is_edit = isset($id) && !empty($id);
?>

<div class="container-fluid faculty-page">

    <!-- =========================
         PAGE HEADER
    ========================== -->

    <div class="faculty-top-header">

        <div class="header-left">

            <div class="header-icon">
                <i class="fas fa-user-tie"></i>
            </div>

            <div>
                <h2>
                    <?php echo $is_edit ? 'Edit Faculty' : 'Add Extension Coordinator'; ?>
                </h2>

                <p>
                    <?php echo $is_edit
                        ? 'Update Research Extension account information'
                        : 'Create a new Research Extension evaluator account'; ?>
                </p>
            </div>

        </div>

        <div class="header-badge">

            <i class="fas fa-shield-alt"></i>

            Evaluator Account

        </div>

    </div>


    <!-- =========================
         MAIN FORM
    ========================== -->

    <div class="faculty-card">

        <form action="" id="manage_faculty" enctype="multipart/form-data">

            <input
                type="hidden"
                name="id"
                value="<?php echo isset($id) ? $id : ''; ?>"
            >


            <!-- =========================
                 PERSONAL INFORMATION
            ========================== -->

            <div class="section-title">

                <div class="section-icon">
                    <i class="fas fa-user"></i>
                </div>

                <div>
                    <h4>Personal Information</h4>
                    <p>Basic information of the Extension Coordinator</p>
                </div>

            </div>


            <div class="form-section">

                <div class="row">

                    <!-- SCHOOL ID -->

                    <div class="col-md-4">

                        <div class="form-group modern-group">

                            <label>
                                School ID
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <i class="fas fa-id-card"></i>

                                <input
                                    type="text"
                                    name="school_id"
                                    class="form-control modern-input"
                                    placeholder="Enter school ID"
                                    required
                                    value="<?php echo isset($school_id) ? htmlspecialchars($school_id) : ''; ?>"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- FIRST NAME -->

                    <div class="col-md-4">

                        <div class="form-group modern-group">

                            <label>
                                First Name
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <i class="fas fa-user"></i>

                                <input
                                    type="text"
                                    name="firstname"
                                    class="form-control modern-input"
                                    placeholder="Enter first name"
                                    required
                                    value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : ''; ?>"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- LAST NAME -->

                    <div class="col-md-4">

                        <div class="form-group modern-group">

                            <label>
                                Last Name
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <i class="fas fa-user"></i>

                                <input
                                    type="text"
                                    name="lastname"
                                    class="form-control modern-input"
                                    placeholder="Enter last name"
                                    required
                                    value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : ''; ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================
                 ACCOUNT SECTION
            ========================== -->

            <div class="section-title account-title">

                <div class="section-icon">
                    <i class="fas fa-lock"></i>
                </div>

                <div>
                    <h4>Account Information</h4>
                    <p>Login credentials and profile photo</p>
                </div>

            </div>


            <div class="form-section">

                <div class="row">


                    <!-- LEFT ACCOUNT -->

                    <div class="col-md-7">


                        <!-- EMAIL -->

                        <div class="form-group modern-group">

                            <label>
                                Email Address
                                <span class="required">*</span>
                            </label>

                            <div class="input-wrapper">

                                <i class="fas fa-envelope"></i>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control modern-input"
                                    placeholder="example@email.com"
                                    required
                                    value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                >

                            </div>

                            <small class="field-help">
                                <i class="fas fa-info-circle"></i>
                                This email will be used for the Extension Coordinator account.
                            </small>

                        </div>


                        <!-- PASSWORD -->

                        <div class="form-group modern-group">

                            <label>
                                Password

                                <?php if(!$is_edit): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>

                            </label>

                            <div class="password-wrapper">

                                <i class="fas fa-key password-icon"></i>

                                <input
                                    type="password"
                                    name="password"
                                    id="faculty_password"
                                    class="form-control modern-input password-input"
                                    placeholder="<?php echo $is_edit ? 'Leave blank to keep current password' : 'Enter password'; ?>"
                                    <?php echo !$is_edit ? 'required' : ''; ?>
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="faculty_password"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>

                            </div>


                            <div class="password-strength">

                                <div class="strength-bars">

                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>

                                </div>

                                <small id="strength_text">
                                    Password strength
                                </small>

                            </div>


                            <?php if($is_edit): ?>

                                <small class="field-help">
                                    <i class="fas fa-info-circle"></i>
                                    Leave this blank if you do not want to change the password.
                                </small>

                            <?php endif; ?>

                        </div>


                        <!-- CONFIRM PASSWORD -->

                        <div class="form-group modern-group">

                            <label>
                                Confirm Password

                                <?php if(!$is_edit): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>

                            </label>

                            <div class="password-wrapper">

                                <i class="fas fa-check-double password-icon"></i>

                                <input
                                    type="password"
                                    name="cpass"
                                    id="faculty_cpass"
                                    class="form-control modern-input password-input"
                                    placeholder="Confirm password"
                                    <?php echo !$is_edit ? 'required' : ''; ?>
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="faculty_cpass"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>

                            </div>

                            <small id="pass_match" data-status=""></small>

                        </div>


                        <div id="msg"></div>


                    </div>


                    <!-- RIGHT AVATAR -->

                    <div class="col-md-5">

                        <div class="avatar-upload-card">

                            <div class="avatar-title">

                                <i class="fas fa-camera"></i>

                                <span>Profile Photo</span>

                            </div>


                            <div class="avatar-preview">

                                <?php

                                $avatar_path = '';

                                if(
                                    isset($avatar) &&
                                    !empty($avatar) &&
                                    is_file('assets/uploads/'.$avatar)
                                ){
                                    $avatar_path = 'assets/uploads/'.$avatar;
                                }

                                ?>

                                <?php if($avatar_path): ?>

                                    <img
                                        src="<?php echo $avatar_path; ?>"
                                        id="cimg"
                                        alt="Faculty Avatar"
                                    >

                                <?php else: ?>

                                    <div
                                        class="avatar-placeholder"
                                        id="avatar_placeholder"
                                    >

                                        <i class="fas fa-user"></i>

                                    </div>

                                    <img
                                        src=""
                                        id="cimg"
                                        alt="Faculty Avatar"
                                        style="display:none;"
                                    >

                                <?php endif; ?>

                            </div>


                            <div class="avatar-info">

                                <strong>
                                    Upload Profile Photo
                                </strong>

                                <span>
                                    JPG, JPEG or PNG
                                </span>

                            </div>


                            <div class="custom-file modern-file">

                                <input
                                    type="file"
                                    class="custom-file-input"
                                    id="customFile"
                                    name="img"
                                    accept="image/png,image/jpeg,image/jpg"
                                >

                                <label
                                    class="custom-file-label"
                                    for="customFile"
                                >

                                    <i class="fas fa-upload"></i>
                                    Choose Photo

                                </label>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================
                 FOOTER
            ========================== -->

            <div class="faculty-footer">

                <div class="footer-note">

                    <i class="fas fa-info-circle"></i>

                    Fields marked with
                    <span class="required">*</span>
                    are required.

                </div>


                <div class="footer-buttons">

                    <button
                        type="button"
                        class="btn btn-cancel"
                        onclick="location.href='index.php?page=faculty_list'"
                    >

                        <i class="fas fa-times"></i>

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-save"
                    >

                        <i class="fas fa-save"></i>

                        <?php echo $is_edit ? 'Update ' : 'Save '; ?>

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- =========================
     CSS
========================== -->

<style>

:root{

    --faculty-green:#198754;
    --faculty-dark:#146c43;
    --faculty-light:#eaf8f0;

    --faculty-blue:#2563eb;
    --faculty-text:#1f2937;
    --faculty-muted:#6b7280;

    --faculty-border:#e5e7eb;

}


/* =========================
   PAGE
========================= */

.faculty-page{

    padding:10px 5px 35px;

}


/* =========================
   TOP HEADER
========================= */

.faculty-top-header{

    background:
        linear-gradient(
            135deg,
            #198754,
            #146c43
        );

    border-radius:20px;

    padding:24px 28px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    color:white;

    margin-bottom:22px;

    box-shadow:
        0 12px 30px rgba(25,135,84,.20);

}


.header-left{

    display:flex;

    align-items:center;

    gap:16px;

}


.header-icon{

    width:58px;

    height:58px;

    border-radius:16px;

    background:rgba(255,255,255,.15);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:25px;

}


.faculty-top-header h2{

    margin:0;

    font-size:25px;

    font-weight:700;

}


.faculty-top-header p{

    margin:4px 0 0;

    font-size:14px;

    opacity:.82;

}


.header-badge{

    background:rgba(255,255,255,.13);

    border:1px solid rgba(255,255,255,.22);

    padding:9px 15px;

    border-radius:30px;

    font-size:13px;

    font-weight:600;

}


.header-badge i{

    margin-right:6px;

}


/* =========================
   MAIN CARD
========================= */

.faculty-card{

    background:white;

    border-radius:20px;

    border:1px solid #edf0f2;

    box-shadow:
        0 10px 30px rgba(0,0,0,.07);

    overflow:hidden;

}


/* =========================
   SECTION TITLE
========================= */

.section-title{

    display:flex;

    align-items:center;

    gap:13px;

    padding:22px 28px 15px;

    border-bottom:1px solid #f0f2f4;

}


.section-icon{

    width:42px;

    height:42px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:var(--faculty-light);

    color:var(--faculty-green);

    font-size:17px;

}


.section-title h4{

    margin:0;

    font-size:17px;

    font-weight:700;

    color:var(--faculty-text);

}


.section-title p{

    margin:3px 0 0;

    font-size:12px;

    color:var(--faculty-muted);

}


.account-title{

    margin-top:8px;

}


/* =========================
   FORM SECTION
========================= */

.form-section{

    padding:24px 28px 12px;

}


/* =========================
   FORM GROUP
========================= */

.modern-group{

    margin-bottom:22px;

}


.modern-group label{

    display:block;

    font-size:13px;

    font-weight:700;

    color:#374151;

    margin-bottom:8px;

}


.required{

    color:#dc3545;

    font-weight:800;

}


/* =========================
   INPUT
========================= */

.input-wrapper{

    position:relative;

}


.input-wrapper > i{

    position:absolute;

    left:15px;

    top:50%;

    transform:translateY(-50%);

    color:#9ca3af;

    font-size:14px;

    z-index:2;

}


.modern-input{

    height:46px!important;

    border-radius:12px!important;

    border:1px solid var(--faculty-border)!important;

    background:#fbfcfd!important;

    padding-left:43px!important;

    padding-right:15px!important;

    color:#374151;

    font-size:14px;

    transition:.25s;

}


.modern-input:hover{

    border-color:#cbd5e1!important;

    background:white!important;

}


.modern-input:focus{

    border-color:var(--faculty-green)!important;

    background:white!important;

    box-shadow:
        0 0 0 4px rgba(25,135,84,.10)!important;

}


/* =========================
   PASSWORD
========================= */

.password-wrapper{

    position:relative;

}


.password-icon{

    position:absolute;

    left:15px;

    top:50%;

    transform:translateY(-50%);

    color:#9ca3af;

    z-index:3;

}


.password-input{

    padding-right:50px!important;

}


.password-toggle{

    position:absolute;

    right:10px;

    top:50%;

    transform:translateY(-50%);

    width:34px;

    height:34px;

    border:none;

    background:transparent;

    color:#9ca3af;

    border-radius:8px;

    cursor:pointer;

}


.password-toggle:hover{

    background:#f3f4f6;

    color:var(--faculty-green);

}


/* =========================
   PASSWORD STRENGTH
========================= */

.password-strength{

    margin-top:9px;

}


.strength-bars{

    display:flex;

    gap:4px;

    margin-bottom:4px;

}


.strength-bars span{

    height:4px;

    flex:1;

    background:#e5e7eb;

    border-radius:10px;

    transition:.25s;

}


.password-strength small{

    font-size:11px;

    color:#9ca3af;

}


/* =========================
   FIELD HELP
========================= */

.field-help{

    display:block;

    color:#9ca3af;

    margin-top:7px;

    font-size:11px;

}


.field-help i{

    margin-right:3px;

}


/* =========================
   AVATAR
========================= */

.avatar-upload-card{

    background:
        linear-gradient(
            180deg,
            #f8fffb,
            #ffffff
        );

    border:1px solid #dff3e7;

    border-radius:18px;

    padding:22px;

    text-align:center;

    height:100%;

    min-height:330px;

}


.avatar-title{

    display:flex;

    justify-content:center;

    align-items:center;

    gap:8px;

    color:var(--faculty-dark);

    font-weight:700;

    font-size:14px;

    margin-bottom:15px;

}


.avatar-title i{

    color:var(--faculty-green);

}


/* =========================
   AVATAR PREVIEW
========================= */

.avatar-preview{

    display:flex;

    justify-content:center;

    align-items:center;

    margin:8px 0 15px;

}


#cimg,
.avatar-placeholder{

    width:145px;

    height:145px;

    border-radius:50%;

    object-fit:cover;

}


#cimg{

    border:6px solid white;

    box-shadow:
        0 8px 25px rgba(0,0,0,.13);

}


.avatar-placeholder{

    background:
        linear-gradient(
            135deg,
            #dff7e9,
            #c7eed7
        );

    color:var(--faculty-green);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:52px;

    border:6px solid white;

    box-shadow:
        0 8px 25px rgba(0,0,0,.10);

}


.avatar-info{

    display:flex;

    flex-direction:column;

    gap:2px;

    margin-bottom:15px;

}


.avatar-info strong{

    font-size:13px;

    color:#374151;

}


.avatar-info span{

    color:#9ca3af;

    font-size:11px;

}


/* =========================
   FILE INPUT
========================= */

.modern-file{

    height:44px;

}


.modern-file .custom-file-input{

    cursor:pointer;

}


.modern-file .custom-file-label{

    height:44px;

    border-radius:11px;

    border:1px dashed #a7d9ba;

    background:#f6fcf8;

    color:var(--faculty-green);

    font-size:13px;

    font-weight:600;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:0 15px;

    transition:.25s;

}


.modern-file .custom-file-label::after{

    display:none;

}


.modern-file:hover .custom-file-label{

    background:#ecf9f1;

    border-color:var(--faculty-green);

}


.modern-file .custom-file-label i{

    margin-right:7px;

}


/* =========================
   MESSAGE
========================= */

#msg{

    margin-bottom:10px;

}


#msg .alert{

    border:none;

    border-radius:10px;

    font-size:13px;

}


/* =========================
   FOOTER
========================= */

.faculty-footer{

    border-top:1px solid #edf0f2;

    background:#fbfcfd;

    padding:18px 28px;

    display:flex;

    justify-content:space-between;

    align-items:center;

}


.footer-note{

    color:#9ca3af;

    font-size:12px;

}


.footer-note i{

    color:var(--faculty-green);

    margin-right:4px;

}


.footer-buttons{

    display:flex;

    gap:10px;

}


/* =========================
   BUTTONS
========================= */

.btn{

    border:none;

    font-weight:600;

    transition:.25s;

}


.btn-save{

    background:
        linear-gradient(
            135deg,
            #198754,
            #157347
        );

    color:white;

    border-radius:11px;

    padding:11px 23px;

    box-shadow:
        0 6px 15px rgba(25,135,84,.20);

}


.btn-save:hover{

    color:white;

    transform:translateY(-2px);

    box-shadow:
        0 9px 20px rgba(25,135,84,.28);

}


.btn-save i{

    margin-right:6px;

}


.btn-cancel{

    background:#f1f3f5;

    color:#6b7280;

    border-radius:11px;

    padding:11px 21px;

}


.btn-cancel:hover{

    background:#e5e7eb;

    color:#374151;

}


.btn-cancel i{

    margin-right:5px;

}


/* =========================
   INVALID INPUT
========================= */

.border-danger{

    border-color:#dc3545!important;

    box-shadow:
        0 0 0 3px rgba(220,53,69,.08)!important;

}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:768px){

    .faculty-page{

        padding:5px;

    }


    .faculty-top-header{

        padding:20px;

        border-radius:16px;

        flex-direction:column;

        align-items:flex-start;

        gap:15px;

    }


    .faculty-top-header h2{

        font-size:21px;

    }


    .header-badge{

        font-size:11px;

    }


    .section-title{

        padding:18px;

    }


    .form-section{

        padding:20px 18px 5px;

    }


    .faculty-footer{

        padding:18px;

        flex-direction:column;

        align-items:flex-start;

        gap:15px;

    }


    .footer-buttons{

        width:100%;

    }


    .footer-buttons .btn{

        flex:1;

    }


    .avatar-upload-card{

        margin-top:5px;

        min-height:300px;

    }

}


/* =========================
   SMALL MOBILE
========================= */

@media(max-width:480px){

    .header-left{

        gap:10px;

    }


    .header-icon{

        width:48px;

        height:48px;

    }


    .faculty-top-header h2{

        font-size:19px;

    }


    .faculty-top-header p{

        font-size:12px;

    }


    .section-title h4{

        font-size:15px;

    }


    .footer-note{

        line-height:1.5;

    }

}

</style>


<!-- =========================
     JAVASCRIPT
========================== -->

<script>

$(document).ready(function(){


    /* =========================
       PASSWORD SHOW / HIDE
    ========================== */

    $('.password-toggle').click(function(){

        var target = $(this).data('target');

        var input = $('#' + target);

        var icon = $(this).find('i');


        if(input.attr('type') === 'password'){

            input.attr('type','text');

            icon.removeClass('fa-eye')
                .addClass('fa-eye-slash');

        }else{

            input.attr('type','password');

            icon.removeClass('fa-eye-slash')
                .addClass('fa-eye');

        }

    });


    /* =========================
       IMAGE PREVIEW
    ========================== */

    $('#customFile').change(function(){

        var input = this;

        var fileName = input.files.length
            ? input.files[0].name
            : 'Choose Photo';


        $(this)
            .next('.custom-file-label')
            .html(
                '<i class="fas fa-image"></i> ' +
                fileName
            );


        if(input.files && input.files[0]){

            var reader = new FileReader();


            reader.onload = function(e){

                $('#avatar_placeholder').hide();

                $('#cimg')
                    .attr('src',e.target.result)
                    .show();

            };


            reader.readAsDataURL(input.files[0]);

        }

    });


    /* =========================
       PASSWORD STRENGTH
    ========================== */

    $('#faculty_password').on('keyup',function(){

        var password = $(this).val();

        var bars = $('.strength-bars span');

        var text = $('#strength_text');


        bars.css('background','#e5e7eb');


        if(password.length === 0){

            text.text('Password strength');

            return;

        }


        var strength = 0;


        if(password.length >= 6)
            strength++;


        if(password.length >= 10)
            strength++;


        if(/[A-Z]/.test(password))
            strength++;


        if(/[0-9]/.test(password))
            strength++;


        if(/[^A-Za-z0-9]/.test(password))
            strength++;


        if(strength <= 1){

            bars.eq(0).css('background','#dc3545');

            text.text('Weak password')
                .css('color','#dc3545');

        }
        else if(strength <= 3){

            bars.slice(0,2)
                .css('background','#f59e0b');

            text.text('Medium password')
                .css('color','#f59e0b');

        }
        else{

            bars.css('background','#198754');

            text.text('Strong password')
                .css('color','#198754');

        }

    });


    /* =========================
       PASSWORD MATCH
    ========================== */

    $('[name="password"],[name="cpass"]').keyup(function(){

        var pass =
            $('[name="password"]').val();

        var cpass =
            $('[name="cpass"]').val();


        if(cpass === '' || pass === ''){

            $('#pass_match')
                .attr('data-status','')
                .html('');

            $('[name="password"],[name="cpass"]')
                .removeClass('border-danger');

            return;

        }


        if(pass === cpass){

            $('#pass_match')
                .attr('data-status','1')
                .html(
                    '<span class="text-success">' +
                    '<i class="fas fa-check-circle"></i> ' +
                    'Password matched.' +
                    '</span>'
                );

            $('[name="password"],[name="cpass"]')
                .removeClass('border-danger');

        }else{

            $('#pass_match')
                .attr('data-status','2')
                .html(
                    '<span class="text-danger">' +
                    '<i class="fas fa-times-circle"></i> ' +
                    'Passwords do not match.' +
                    '</span>'
                );

        }

    });


    /* =========================
       FORM SUBMIT
    ========================== */

    $('#manage_faculty').submit(function(e){

        e.preventDefault();


        $('input')
            .removeClass('border-danger');


        $('#msg').html('');


        var password =
            $('[name="password"]').val();

        var cpass =
            $('[name="cpass"]').val();


        /* PASSWORD CHECK */

        if(password !== '' || cpass !== ''){

            if(password !== cpass){

                $('[name="password"],[name="cpass"]')
                    .addClass('border-danger');

                $('#pass_match')
                    .attr('data-status','2')
                    .html(
                        '<span class="text-danger">' +
                        '<i class="fas fa-times-circle"></i> ' +
                        'Passwords do not match.' +
                        '</span>'
                    );

                $('html,body').animate({

                    scrollTop:
                        $('[name="password"]')
                        .offset().top - 100

                },300);

                return false;

            }

        }


        start_load();


        $.ajax({

            url:'ajax.php?action=save_faculty',

            data:new FormData(
                $(this)[0]
            ),

            cache:false,

            contentType:false,

            processData:false,

            method:'POST',

            type:'POST',

            success:function(resp){

                if(resp == 1){

                    alert_toast(
                        'Coordinator data successfully saved.',
                        'success'
                    );


                    setTimeout(function(){

                        location.replace(
                            'index.php?page=faculty_list'
                        );

                    },1000);


                }
                else if(resp == 2){

                    $('#msg').html(
                        "<div class='alert alert-danger'>" +
                        "<i class='fas fa-exclamation-circle'></i> " +
                        "Email already exists." +
                        "</div>"
                    );


                    $('[name="email"]')
                        .addClass('border-danger');


                    end_load();

                }
                else if(resp == 3){

                    $('#msg').html(
                        "<div class='alert alert-danger'>" +
                        "<i class='fas fa-exclamation-circle'></i> " +
                        "School ID already exists." +
                        "</div>"
                    );


                    $('[name="school_id"]')
                        .addClass('border-danger');


                    end_load();

                }
                else{

                    $('#msg').html(
                        "<div class='alert alert-danger'>" +
                        "<i class='fas fa-exclamation-circle'></i> " +
                        "Unable to save Coordinator data. Please try again." +
                        "</div>"
                    );


                    end_load();

                }

            },

            error:function(){

                $('#msg').html(
                    "<div class='alert alert-danger'>" +
                    "<i class='fas fa-exclamation-circle'></i> " +
                    "An unexpected error occurred." +
                    "</div>"
                );


                end_load();

            }

        });

    });

});

</script>
