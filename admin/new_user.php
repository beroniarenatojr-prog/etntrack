<?php
?>
<div class="container-fluid user-management-page">

    <div class="user-card">

        <!-- =========================
             HEADER
        ========================== -->
        <div class="user-header">

            <div class="header-content">

                <div class="header-icon">
                    <i class="fas fa-user-cog"></i>
                </div>

                <div>
                    <h3>
                        <?php echo isset($id) ? 'Edit User' : 'Create New User'; ?>
                    </h3>

                    <p>
                        <?php echo isset($id)
                            ? 'Update the user account information below.'
                            : 'Create a new system user account.'; ?>
                    </p>
                </div>

            </div>

            <div class="header-badge">
                <i class="fas fa-shield-alt"></i>
                User Account
            </div>

        </div>


        <!-- =========================
             FORM
        ========================== -->
        <div class="user-body">

            <form action="" id="manage_user">

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo isset($id) ? $id : ''; ?>"
                >


                <div class="row">


                    <!-- =========================
                         LEFT SIDE
                    ========================== -->
                    <div class="col-md-6">

                        <div class="section-card">

                            <div class="section-title">

                                <div class="section-icon">
                                    <i class="fas fa-id-card"></i>
                                </div>

                                <div>
                                    <h5>Personal Information</h5>
                                    <span>Basic account details</span>
                                </div>

                            </div>


                            <div class="section-content">


                                <!-- FIRST NAME -->
                                <div class="form-group modern-group">

                                    <label>
                                        <i class="fas fa-user"></i>
                                        First Name
                                    </label>

                                    <div class="input-wrapper">

                                        <input
                                            type="text"
                                            name="firstname"
                                            class="form-control modern-input"
                                            placeholder="Enter first name"
                                            required
                                            value="<?php echo isset($firstname) ? $firstname : ''; ?>"
                                        >

                                    </div>

                                </div>



                                <!-- LAST NAME -->
                                <div class="form-group modern-group">

                                    <label>
                                        <i class="fas fa-user"></i>
                                        Last Name
                                    </label>

                                    <div class="input-wrapper">

                                        <input
                                            type="text"
                                            name="lastname"
                                            class="form-control modern-input"
                                            placeholder="Enter last name"
                                            required
                                            value="<?php echo isset($lastname) ? $lastname : ''; ?>"
                                        >

                                    </div>

                                </div>



                                <!-- AVATAR -->
                                <div class="form-group modern-group">

                                    <label>
                                        <i class="fas fa-camera"></i>
                                        Profile Picture
                                    </label>


                                    <div class="avatar-upload">

                                        <div class="avatar-preview">

                                            <?php
                                            $avatar_src = '';

                                            if (
                                                isset($avatar) &&
                                                !empty($avatar)
                                            ) {
                                                $avatar_src =
                                                    'assets/uploads/' . $avatar;
                                            }
                                            ?>

                                            <img
                                                src="<?php echo $avatar_src; ?>"
                                                id="cimg"
                                                alt="Profile Picture"
                                                <?php echo empty($avatar_src)
                                                    ? 'style="display:none;"'
                                                    : ''; ?>
                                            >

                                            <?php if (empty($avatar_src)): ?>

                                                <div
                                                    class="avatar-placeholder"
                                                    id="avatar-placeholder"
                                                >
                                                    <i class="fas fa-user"></i>
                                                </div>

                                            <?php else: ?>

                                                <div
                                                    class="avatar-placeholder"
                                                    id="avatar-placeholder"
                                                    style="display:none;"
                                                >
                                                    <i class="fas fa-user"></i>
                                                </div>

                                            <?php endif; ?>

                                        </div>


                                        <div class="avatar-info">

                                            <div class="custom-file modern-file">

                                                <input
                                                    type="file"
                                                    class="custom-file-input"
                                                    id="customFile"
                                                    name="img"
                                                    accept="image/*"
                                                    onchange="displayImg(this)"
                                                >

                                                <label
                                                    class="custom-file-label"
                                                    for="customFile"
                                                >
                                                    <i class="fas fa-upload mr-2"></i>
                                                    Choose Image
                                                </label>

                                            </div>

                                            <small>
                                                JPG, JPEG, PNG or GIF
                                            </small>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>



                    <!-- =========================
                         RIGHT SIDE
                    ========================== -->
                    <div class="col-md-6">

                        <div class="section-card">

                            <div class="section-title">

                                <div class="section-icon">
                                    <i class="fas fa-lock"></i>
                                </div>

                                <div>
                                    <h5>Account Security</h5>
                                    <span>Login credentials</span>
                                </div>

                            </div>


                            <div class="section-content">


                                <!-- EMAIL -->
                                <div class="form-group modern-group">

                                    <label>
                                        <i class="fas fa-envelope"></i>
                                        Email Address
                                    </label>

                                    <div class="input-icon-wrapper">

                                        <i class="fas fa-envelope input-icon"></i>

                                        <input
                                            type="email"
                                            name="email"
                                            class="form-control modern-input with-icon"
                                            placeholder="Enter email address"
                                            required
                                            value="<?php echo isset($email) ? $email : ''; ?>"
                                        >

                                    </div>

                                    <small
                                        id="msg"
                                        class="validation-message"
                                    ></small>

                                </div>



                                <!-- PASSWORD -->
                                <div class="form-group modern-group">

                                    <label>
                                        <i class="fas fa-key"></i>
                                        Password
                                    </label>

                                    <div class="password-wrapper">

                                        <input
                                            type="password"
                                            class="form-control modern-input"
                                            name="password"
                                            id="password"
                                            placeholder="<?php echo isset($id)
                                                ? 'Enter new password'
                                                : 'Create password'; ?>"
                                            <?php echo !isset($id)
                                                ? 'required'
                                                : ''; ?>
                                        >

                                        <button
                                            type="button"
                                            class="password-toggle"
                                            onclick="togglePassword('password', this)"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>

                                    </div>

                                    <?php if (isset($id)): ?>

                                        <small class="help-text">
                                            <i class="fas fa-info-circle"></i>
                                            Leave blank if you don't want to
                                            change the password.
                                        </small>

                                    <?php else: ?>

                                        <small class="help-text">
                                            <i class="fas fa-info-circle"></i>
                                            Use a strong password for better
                                            account security.
                                        </small>

                                    <?php endif; ?>

                                </div>



                                <!-- CONFIRM PASSWORD -->
                                <div class="form-group modern-group">

                                    <label>
                                        <i class="fas fa-check-circle"></i>
                                        Confirm Password
                                    </label>

                                    <div class="password-wrapper">

                                        <input
                                            type="password"
                                            class="form-control modern-input"
                                            name="cpass"
                                            id="cpass"
                                            placeholder="Confirm password"
                                            <?php echo !isset($id)
                                                ? 'required'
                                                : ''; ?>
                                        >

                                        <button
                                            type="button"
                                            class="password-toggle"
                                            onclick="togglePassword('cpass', this)"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>

                                    </div>

                                    <small
                                        id="pass_match"
                                        data-status=""
                                        class="password-status"
                                    ></small>

                                </div>



                                <!-- SECURITY NOTICE -->
                                <div class="security-notice">

                                    <div class="security-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            Account Security
                                        </strong>

                                        <p>
                                            Make sure the account credentials
                                            are kept private and secure.
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =========================
                     FORM FOOTER
                ========================== -->
                <div class="form-footer">

                    <button
                        type="button"
                        class="btn btn-cancel"
                        onclick="location.href='index.php?page=user_list'"
                    >

                        <i class="fas fa-arrow-left mr-2"></i>

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-save"
                    >

                        <i class="fas fa-save mr-2"></i>

                        <?php echo isset($id)
                            ? 'Update User'
                            : 'Save User'; ?>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- =========================================================
     CSS
========================================================= -->

<style>

:root{

    --user-green:#16a34a;
    --user-dark:#15803d;
    --user-deep:#166534;

    --user-light:#f0fdf4;
    --user-border:#dcfce7;

    --text-dark:#1f2937;
    --text-muted:#6b7280;

}


/* =========================================
   MAIN CARD
========================================= */

.user-management-page{

    padding:15px 10px 30px;

}


.user-card{

    background:#fff;

    border:none;

    border-radius:22px;

    overflow:hidden;

    box-shadow:
        0 12px 35px rgba(0,0,0,.08);

}


/* =========================================
   HEADER
========================================= */

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

}


.header-content{

    display:flex;

    align-items:center;

    gap:16px;

}


.header-icon{

    width:55px;

    height:55px;

    border-radius:16px;

    background:rgba(255,255,255,.18);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:24px;

    backdrop-filter:blur(5px);

}


.user-header h3{

    margin:0;

    font-size:23px;

    font-weight:700;

}


.user-header p{

    margin:5px 0 0;

    opacity:.85;

    font-size:14px;

}


.header-badge{

    background:rgba(255,255,255,.15);

    border:1px solid rgba(255,255,255,.2);

    padding:9px 15px;

    border-radius:30px;

    font-size:13px;

    font-weight:600;

}


/* =========================================
   BODY
========================================= */

.user-body{

    padding:30px;

}


/* =========================================
   SECTION CARD
========================================= */

.section-card{

    background:#fff;

    border:1px solid #edf2f7;

    border-radius:18px;

    overflow:hidden;

    height:100%;

    box-shadow:
        0 5px 18px rgba(0,0,0,.04);

}


.section-title{

    background:#f8fafc;

    padding:18px 20px;

    display:flex;

    align-items:center;

    gap:12px;

    border-bottom:1px solid #edf2f7;

}


.section-icon{

    width:42px;

    height:42px;

    border-radius:12px;

    background:#dcfce7;

    color:#16a34a;

    display:flex;

    align-items:center;

    justify-content:center;

}


.section-title h5{

    margin:0;

    font-weight:700;

    color:#1f2937;

    font-size:16px;

}


.section-title span{

    display:block;

    margin-top:2px;

    font-size:12px;

    color:#9ca3af;

}


.section-content{

    padding:25px;

}


/* =========================================
   FORM
========================================= */

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


.modern-group label i{

    color:#16a34a;

    width:18px;

    margin-right:4px;

}


.modern-input{

    height:46px!important;

    border:1px solid #dfe5e9!important;

    border-radius:12px!important;

    padding:10px 14px!important;

    font-size:14px!important;

    color:#374151!important;

    transition:
        border-color .2s,
        box-shadow .2s,
        transform .2s;

}


.modern-input:focus{

    border-color:#16a34a!important;

    box-shadow:
        0 0 0 3px rgba(22,163,74,.12)!important;

    outline:none!important;

}


.modern-input::placeholder{

    color:#a1a1aa;

}


/* =========================================
   INPUT ICON
========================================= */

.input-icon-wrapper{

    position:relative;

}


.input-icon{

    position:absolute;

    left:15px;

    top:15px;

    color:#9ca3af;

    z-index:2;

}


.with-icon{

    padding-left:42px!important;

}


/* =========================================
   AVATAR
========================================= */

.avatar-upload{

    display:flex;

    align-items:center;

    gap:20px;

    background:#f8fafc;

    border:1px dashed #bbf7d0;

    border-radius:15px;

    padding:18px;

}


.avatar-preview{

    width:105px;

    height:105px;

    min-width:105px;

    border-radius:50%;

    position:relative;

    overflow:hidden;

    background:#ecfdf5;

    border:5px solid #fff;

    box-shadow:
        0 6px 18px rgba(0,0,0,.12);

}


#cimg{

    width:100%;

    height:100%;

    object-fit:cover;

    display:block;

}


.avatar-placeholder{

    width:100%;

    height:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    background:
        linear-gradient(
            135deg,
            #dcfce7,
            #bbf7d0
        );

    color:#16a34a;

    font-size:35px;

}


.avatar-info{

    flex:1;

}


.modern-file{

    width:100%;

}


.modern-file .custom-file-input{

    cursor:pointer;

}


.modern-file .custom-file-label{

    height:46px;

    line-height:30px;

    border-radius:12px;

    border:1px solid #dfe5e9;

    color:#4b5563;

    overflow:hidden;

}


.modern-file .custom-file-label::after{

    height:44px;

    line-height:30px;

    background:#f0fdf4;

    color:#15803d;

    border-left:1px solid #dcfce7;

}


.avatar-info > small{

    display:block;

    color:#9ca3af;

    margin-top:8px;

    font-size:11px;

}


/* =========================================
   PASSWORD
========================================= */

.password-wrapper{

    position:relative;

}


.password-wrapper .modern-input{

    padding-right:50px!important;

}


.password-toggle{

    position:absolute;

    right:7px;

    top:6px;

    width:34px;

    height:34px;

    border:none;

    background:transparent;

    color:#9ca3af;

    border-radius:8px;

    cursor:pointer;

    transition:.2s;

}


.password-toggle:hover{

    background:#f0fdf4;

    color:#16a34a;

}


.help-text{

    display:block;

    margin-top:7px;

    color:#9ca3af;

    font-size:11px;

}


.help-text i{

    color:#16a34a;

}


.password-status{

    display:block;

    margin-top:7px;

    font-size:12px;

    font-weight:600;

}


/* =========================================
   SECURITY NOTICE
========================================= */

.security-notice{

    display:flex;

    align-items:center;

    gap:13px;

    padding:15px;

    background:#f0fdf4;

    border:1px solid #dcfce7;

    border-radius:14px;

    margin-top:8px;

}


.security-icon{

    width:40px;

    height:40px;

    min-width:40px;

    border-radius:10px;

    background:#dcfce7;

    color:#16a34a;

    display:flex;

    align-items:center;

    justify-content:center;

}


.security-notice strong{

    color:#166534;

    font-size:13px;

}


.security-notice p{

    margin:3px 0 0;

    font-size:11px;

    color:#6b7280;

}


/* =========================================
   VALIDATION
========================================= */

.border-danger{

    border-color:#ef4444!important;

    box-shadow:
        0 0 0 3px rgba(239,68,68,.08)!important;

}


.validation-message{

    display:block;

    margin-top:8px;

}


/* =========================================
   FOOTER
========================================= */

.form-footer{

    border-top:1px solid #edf2f7;

    margin-top:30px;

    padding-top:25px;

    display:flex;

    justify-content:flex-end;

    gap:10px;

}


.btn-save{

    background:
        linear-gradient(
            135deg,
            #16a34a,
            #15803d
        );

    color:#fff!important;

    border:none;

    border-radius:12px;

    padding:11px 25px;

    font-weight:600;

    box-shadow:
        0 5px 15px rgba(22,163,74,.22);

    transition:.25s;

}


.btn-save:hover{

    transform:translateY(-2px);

    box-shadow:
        0 8px 20px rgba(22,163,74,.28);

}


.btn-cancel{

    background:#f3f4f6;

    color:#4b5563;

    border:none;

    border-radius:12px;

    padding:11px 25px;

    font-weight:600;

    transition:.25s;

}


.btn-cancel:hover{

    background:#e5e7eb;

    color:#374151;

    transform:translateY(-2px);

}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:768px){

    .user-header{

        padding:20px;

        flex-direction:column;

        align-items:flex-start;

        gap:15px;

    }


    .header-badge{

        display:none;

    }


    .user-body{

        padding:18px;

    }


    .section-card{

        margin-bottom:20px;

    }


    .section-content{

        padding:20px;

    }


    .avatar-upload{

        flex-direction:column;

        text-align:center;

    }


    .avatar-info{

        width:100%;

    }


    .form-footer{

        flex-direction:column-reverse;

    }


    .form-footer button{

        width:100%;

    }

}

</style>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* =========================================
   PASSWORD MATCH
========================================= */

$('[name="password"],[name="cpass"]').on('keyup', function(){

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


    if(cpass === pass){

        $('#pass_match')
            .attr('data-status','1')
            .html(
                '<i class="fas fa-check-circle text-success"></i> ' +
                '<span class="text-success">Password matched.</span>'
            );

        $('[name="password"],[name="cpass"]')
            .removeClass('border-danger');

    }else{

        $('#pass_match')
            .attr('data-status','2')
            .html(
                '<i class="fas fa-times-circle text-danger"></i> ' +
                '<span class="text-danger">Password does not match.</span>'
            );

        $('[name="password"],[name="cpass"]')
            .addClass('border-danger');

    }

});



/* =========================================
   PASSWORD VISIBILITY
========================================= */

function togglePassword(fieldId, button){

    var field =
        document.getElementById(fieldId);

    var icon =
        $(button).find('i');


    if(field.type === 'password'){

        field.type = 'text';

        icon
            .removeClass('fa-eye')
            .addClass('fa-eye-slash');

    }else{

        field.type = 'password';

        icon
            .removeClass('fa-eye-slash')
            .addClass('fa-eye');

    }

}



/* =========================================
   IMAGE PREVIEW
========================================= */

function displayImg(input){

    if(input.files && input.files[0]){

        var file =
            input.files[0];


        /* Validate image */

        if(!file.type.match('image.*')){

            alert_toast(
                'Please select a valid image file.',
                'error'
            );

            input.value = '';

            return;

        }


        var reader =
            new FileReader();


        reader.onload = function(e){

            $('#cimg')
                .attr('src',e.target.result)
                .show();


            $('#avatar-placeholder')
                .hide();

        };


        reader.readAsDataURL(file);


        /* Update filename */

        var fileName =
            file.name;


        $(input)
            .next('.custom-file-label')
            .html(
                '<i class="fas fa-image mr-2"></i>' +
                fileName
            );

    }

}



/* =========================================
   FORM SUBMIT
========================================= */

$('#manage_user').submit(function(e){

    e.preventDefault();


    $('input')
        .removeClass('border-danger');


    $('#msg').html('');


    var password =
        $('[name="password"]').val();

    var confirmPassword =
        $('[name="cpass"]').val();


    /*
    -----------------------------------------
    PASSWORD VALIDATION
    -----------------------------------------
    */

    if(password !== '' || confirmPassword !== ''){

        if(password === '' || confirmPassword === ''){

            $('#pass_match')
                .html(
                    '<i class="fas fa-exclamation-circle text-danger"></i> ' +
                    '<span class="text-danger">' +
                    'Please complete both password fields.' +
                    '</span>'
                )
                .attr('data-status','2');


            $('[name="password"],[name="cpass"]')
                .addClass('border-danger');


            return false;

        }


        if(password !== confirmPassword){

            $('#pass_match')
                .html(
                    '<i class="fas fa-times-circle text-danger"></i> ' +
                    '<span class="text-danger">' +
                    'Password does not match.' +
                    '</span>'
                )
                .attr('data-status','2');


            $('[name="password"],[name="cpass"]')
                .addClass('border-danger');


            return false;

        }

    }



    start_load();


    $.ajax({

        url:'ajax.php?action=save_user',

        data:
            new FormData(
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
                    'User account successfully saved.',
                    'success'
                );


                setTimeout(function(){

                    location.replace(
                        'index.php?page=user_list'
                    );

                },900);


            }

            else if(resp == 2){

                $('#msg').html(
                    "<div class='alert alert-danger'>" +
                    "<i class='fas fa-exclamation-circle mr-2'></i>" +
                    "Email already exists." +
                    "</div>"
                );


                $('[name="email"]')
                    .addClass('border-danger');


                end_load();

            }

            else{

                $('#msg').html(
                    "<div class='alert alert-danger'>" +
                    "<i class='fas fa-exclamation-circle mr-2'></i>" +
                    "Unable to save user. Please try again." +
                    "</div>"
                );


                end_load();

            }

        },


        error:function(){

            $('#msg').html(
                "<div class='alert alert-danger'>" +
                "<i class='fas fa-exclamation-circle mr-2'></i>" +
                "An unexpected error occurred." +
                "</div>"
            );


            end_load();

        }

    });

});



/* =========================================
   EMAIL CLEANUP
========================================= */

$('[name="email"]').on('input',function(){

    $(this).removeClass('border-danger');

    $('#msg').html('');

});


</script>