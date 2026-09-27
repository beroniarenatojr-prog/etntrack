<style>

/* ===== MODERN DESIGN (FROM YOUR THEME) ===== */

.modern-sidebar{
    background:#ffffff;
    border:none;
    height:100vh;
    box-shadow:8px 0 25px rgba(0,0,0,.08);
}


/* BRAND */
.brand-area{
    height:85px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#667eea,#764ba2);
}


.brand-link{
    display:flex!important;
    align-items:center;
    gap:10px;
    color:white!important;
    text-decoration:none;
}


.brand-title{
    font-size:18px;
    font-weight:700;
    line-height:18px;
}


.brand-title i{
    margin-right:6px;
}



/* MENU */
.sidebar-menu{
    margin-top:15px;
    padding:0 10px;
}


.sidebar-menu .nav-link{

    margin:7px 12px;
    border-radius:14px;
    padding:13px 15px;
    color:#555;
    display:flex;
    align-items:center;
    transition:.3s;

}


.sidebar-menu .nav-link i{
    font-size:18px;
    margin-right:12px;
}


.sidebar-menu .nav-link:hover{

    background:#f3f0ff;
    color:#764ba2;
    transform:translateX(5px);

}


.sidebar-menu .nav-link.active{

    background:linear-gradient(135deg,#667eea,#764ba2);
    color:white!important;
    box-shadow:0 8px 20px rgba(118,75,162,.35);

}


/* TREEVIEW (SAFE KEEP) */
.nav-treeview{
    padding-left:15px;
}

.nav-treeview .nav-link{
    margin-left:20px;
    font-size:14px;
    background:#fafafa;
    border-radius:10px;
}

</style>


<aside class="main-sidebar modern-sidebar elevation-4">

    <!-- BRAND -->
    <div class="brand-area">

        <a href="./" class="brand-link">

            <i class="fas fa-graduation-cap"></i>

            <div class="brand-title">
                Admin<br>
                <span style="font-size:12px;opacity:.85;">Panel</span>
            </div>

        </a>

    </div>


    <!-- SIDEBAR -->
    <div class="sidebar">

        <nav class="mt-3">

            <ul class="nav nav-pills nav-sidebar flex-column sidebar-menu"
                data-widget="treeview"
                data-accordion="false">

                <!-- DASHBOARD -->
                <li class="nav-item">
                    <a href="./" class="nav-link nav-home">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>


                <!-- EVALUATE -->
                <li class="nav-item">
                    <a href="./index.php?page=evaluate" class="nav-link nav-evaluate">
                        <i class="nav-icon fas fa-clipboard-check"></i>
                        <p>Evaluate</p>
                    </a>
                </li>

            </ul>

        </nav>

    </div>

</aside>


<script>

$(document).ready(function(){

    // ===== SYSTEM FLOW KEPT (NO CHANGE) =====
    var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
    var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : '' ?>';

    if(s!='')
        page = page+'_'+s;

    if($('.nav-link.nav-'+page).length > 0){

        $('.nav-link.nav-'+page).addClass('active');

        if($('.nav-link.nav-'+page).hasClass('tree-item')){

            $('.nav-link.nav-'+page)
            .closest('.nav-treeview')
            .siblings('a')
            .addClass('active');

            $('.nav-link.nav-'+page)
            .closest('.nav-treeview')
            .parent()
            .addClass('menu-open');

        }

    }

});

</script>