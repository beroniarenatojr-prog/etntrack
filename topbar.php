<style>
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

/* Every dropdown menu in the system, including the account menu in the sidebar */
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

<!-- Navbar. The signed-in person and their account menu are at the bottom of the sidebar (sidebar_account.php). -->
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

</nav>
