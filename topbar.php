<style>
.user-img{
    width:42px;
    height:42px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #e5e7eb;
}

.main-header{
    background:#fff !important;
    border:none;
    height:72px;
    box-shadow:0 8px 20px rgba(0,0,0,.05);
    padding:0 20px;
}

.main-header .nav-link{
    color:#6b7280 !important;
    transition:.3s;
}

.main-header .nav-link:hover{
    color:#198754 !important;
}

.navbar-nav .nav-item{
    display:flex;
    align-items:center;
}

.icon-btn{
    width:42px;
    height:42px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#f8fafc;
    transition:.3s;
}

.icon-btn:hover{
    background:#198754;
    color:#fff !important;
}

.profile-card{
    display:flex;
    align-items:center;
    padding:6px 10px;
    border-radius:50px;
    transition:.3s;
    cursor:pointer;
}

.profile-card:hover{
    background:#f5f5f5;
}

.profile-info{
    margin-left:12px;
    line-height:1.2;
}

.profile-info b{
    display:block;
    color:#222;
    font-size:14px;
    font-weight:600;
}

.profile-info small{
    color:#8b8b8b;
    font-size:11px;
}

.dropdown-menu{
    border:none;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,.12);
    padding:8px 0;
}

.dropdown-item{
    padding:10px 18px;
    transition:.3s;
}

.dropdown-item:hover{
    background:#198754;
    color:#fff;
}

.dropdown-item i{
    width:20px;
}
</style>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-light">

    <!-- Left -->
    <ul class="navbar-nav">
        <?php if(isset($_SESSION['login_id'])): ?>
        <li class="nav-item">
            <a class="nav-link icon-btn"
               data-widget="pushmenu"
               href="javascript:void(0)"
               role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Right -->
    <ul class="navbar-nav ml-auto">

        <li class="nav-item dropdown">

            <a class="nav-link p-0"
               data-toggle="dropdown"
               href="javascript:void(0)">

                <div class="profile-card">

                    <img src="assets/uploads/<?php echo $_SESSION['login_avatar'] ?>"
                         class="user-img">

                    <div class="profile-info">

                        <b><?php echo ucwords($_SESSION['login_firstname']) ?></b>

                       

                    </div>

                </div>

            </a>

            <div class="dropdown-menu dropdown-menu-right">

                <a class="dropdown-item"
                   href="javascript:void(0)"
                   id="manage_account">

                    <i class="fa fa-user-cog"></i>

                    Manage Account

                </a>

                <div class="dropdown-divider"></div>

                <a class="dropdown-item"
                   href="ajax.php?action=logout">

                    <i class="fa fa-sign-out-alt"></i>

                    Logout

                </a>

            </div>

        </li>

    </ul>

</nav>

<script>
$('#manage_account').click(function(){
    uni_modal(
        'Manage Account',
        'manage_user.php?id=<?php echo $_SESSION['login_id'] ?>'
    );
});
</script>