<style>

/* ==============================
   SIDEBAR
============================== */

.main-sidebar{
    background:#ffffff;
    height:100vh;
    border:none;
    box-shadow:8px 0 25px rgba(0,0,0,.08);
}

/* ==============================
   HEADER
============================== */

.brand-link{
    background:linear-gradient(135deg,#198754,#2fb344);
    padding:20px 18px;
    border:none;
    text-decoration:none !important;
    display:flex;
    align-items:center;
}

.brand-content{
    display:flex;
    align-items:center;
    width:100%;
}

.brand-logo-custom{

    width:46px;
    height:46px;
    border-radius:50%;
    background:#fff;
    object-fit:contain;
    padding:4px;
    margin-right:12px;
    box-shadow:0 5px 12px rgba(0,0,0,.15);

}

.brand-text{
    display:flex;
    flex-direction:column;
}

.brand-text h6{

    margin:0;
    color:#fff;
    font-size:14px;
    font-weight:700;
    line-height:1.2;

}

.brand-text span{

    color:rgba(255,255,255,.85);
    font-size:11px;

}

/* ==============================
   MENU
============================== */

.nav-sidebar{

    margin-top:45px;
    padding:0 12px;

}

.nav-sidebar .nav-item{

    margin-bottom:8px;

}

.nav-sidebar .nav-link{

    display:flex;
    align-items:center;

    border-radius:14px;

    padding:14px 18px;

    color:#4b5563;

    font-weight:600;

    transition:.3s;

}

.nav-sidebar .nav-link i{

    width:26px;

    text-align:center;

    font-size:18px;

    margin-right:12px;

}

/* Hover */

.nav-sidebar .nav-link:hover{

    background:#eef8f1;

    color:#198754;

    transform:translateX(6px);

}

/* Active */

.nav-sidebar .nav-link.active{

    background:linear-gradient(135deg,#198754,#2fb344);

    color:#fff !important;

    box-shadow:0 10px 25px rgba(25,135,84,.25);

}

.nav-sidebar .nav-link.active i{

    color:#fff;

}

/* Remove AdminLTE default */

.sidebar-dark-primary{

    background:transparent !important;

}

/* Scrollbar */

.sidebar::-webkit-scrollbar{

    width:5px;

}

.sidebar::-webkit-scrollbar-thumb{

    background:#c8e6c9;

    border-radius:20px;

}

</style>



<aside class="main-sidebar elevation-4">

    <!-- HEADER -->

    <a href="./" class="brand-link">

        <div class="brand-content">

            <img src="assets/uploads/logo.webp"
                 class="brand-logo-custom">

            <div class="brand-text">

                <h6>Isabela State University</h6>

                <span>Extension Coordinator</span>

            </div>

        </div>

    </a>





    <div class="sidebar">

        <nav>

            <ul class="nav nav-pills nav-sidebar flex-column nav-flat sidebar-menu"
                data-widget="treeview"
                role="menu"
                data-accordion="false">




                <li class="nav-item">

                    <a href="./"
                       class="nav-link nav-home">

                        <i class="fas fa-home"></i>

                        <p>Dashboard</p>

                    </a>

                </li>





                <li class="nav-item">

                    <a href="./index.php?page=result"
                       class="nav-link nav-result">

                        <i class="fas fa-file-alt"></i>

                        <p>Reports</p>

                    </a>

                </li>





                <li class="nav-item">

                    <a href="./index.php?page=activities"
                       class="nav-link nav-activities">

                        <i class="fas fa-calendar-check"></i>

                        <p>Activities</p>

                    </a>

                </li>



            </ul>

        </nav>

    </div>

</aside>

<script>

$(document).ready(function(){

    var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
    var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : '' ?>';

    if(s != '')
        page = page + '_' + s;

    if($('.nav-link.nav-' + page).length > 0){

        $('.nav-link.nav-' + page).addClass('active');

        if($('.nav-link.nav-' + page).hasClass('tree-item')){

            $('.nav-link.nav-' + page)
                .closest('.nav-treeview')
                .siblings('a')
                .addClass('active');

            $('.nav-link.nav-' + page)
                .closest('.nav-treeview')
                .parent()
                .addClass('menu-open');

        }

    }

});

</script>a