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


/* Dropdown arrow: points right when the menu is closed and turns down when it
   opens. The template rotates it the other way, so this overrides that. */

.nav-sidebar .nav-link i.right{

    transition:transform .25s ease;

}

.nav-sidebar .menu-open > .nav-link i.right{

    transform:rotate(90deg) !important;

}


/* Items inside a dropdown: indented a little and in a lighter weight, so the
   group heading above them stays the thing you read first. */

.nav-sidebar .nav-treeview .nav-link{

    padding-left:36px;

    font-size:14px;

}

.nav-sidebar .nav-treeview .nav-icon{

    font-size:13px;

    opacity:.85;

    width:18px;

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

                <span>Administrator</span>

            </div>

        </div>

    </a>

   <div class="sidebar">

        <nav>

            <ul class="nav nav-pills nav-sidebar flex-column nav-flat sidebar-menu"
                data-widget="treeview"
                role="menu"
                data-accordion="false">


<!-- DASHBOARD -->

                <li class="nav-item">

                    <a href="./"
                       class="nav-link nav-home">

                        <i class="fas fa-home"></i>

                        <p>Dashboard</p>

                    </a>

                </li>



<!-- ACADEMIC -->

<li class="nav-item">


</li>



<!-- QUESTIONNAIRES (the evaluation criteria live on that page too) -->

<li class="nav-item">

<a href="./index.php?page=questionnaire"
class="nav-link nav-questionnaire nav-criteria_list nav-manage_questionnaire">

<i class="nav-icon fas fa-clipboard-list"></i>

<p>Questionnaires</p>

</a>

</li>



<!-- FACULTY -->

<li class="nav-item has-treeview">

<a href="#"
class="nav-link">

<i class="nav-icon fas fa-chalkboard-teacher"></i>

<p>
Coordinators
<i class="right fas fa-angle-right"></i>
</p>

</a>


<ul class="nav nav-treeview">


<li>

<a href="./index.php?page=new_faculty"
class="nav-link tree-item nav-new_faculty">

<i class="fas fa-user-plus nav-icon"></i>
<p>Add New</p>

</a>

</li>


<li>

<a href="./index.php?page=faculty_list"
class="nav-link tree-item nav-faculty_list">

<i class="fas fa-address-book nav-icon"></i>
<p>List</p>

</a>

</li>


</ul>

</li>








<!-- PROJECTS (with the activities and reports that belong to them) -->
<li class="nav-item has-treeview">

    <a href="#" class="nav-link">

        <i class="nav-icon fas fa-project-diagram"></i>

        <p>
        Projects
        <i class="right fas fa-angle-right"></i>
        </p>

    </a>


    <ul class="nav nav-treeview">


    <li>

        <a href="./index.php?page=projects"
           class="nav-link tree-item nav-projects nav-project_detail">

            <i class="fas fa-folder-open nav-icon"></i>
            <p>All Projects</p>

        </a>

    </li>


    <li>

        <a href="./index.php?page=activities"
           class="nav-link tree-item nav-activities">

            <i class="fas fa-tasks nav-icon"></i>
            <p>Activities</p>

        </a>

    </li>


    <li>

        <a href="./index.php?page=report"
           class="nav-link tree-item nav-report">

            <i class="fas fa-file-contract nav-icon"></i>
            <p>Reports</p>

        </a>

    </li>


    </ul>

</li>



<!-- CALENDAR -->
<li class="nav-item">

    <a href="./index.php?page=calendar"
       class="nav-link nav-calendar">

        <i class="nav-icon fas fa-calendar-alt"></i>

        <p>Calendar</p>

    </a>

</li>



<!-- Reports now sit under Projects, above -->


<li class="nav-item">

<a href="./index.php?page=evaluation_results"
class="nav-link nav-evaluation_results">

<i class="nav-icon fas fa-chart-line"></i>

<p>Evaluation Results</p>

</a>

</li>







<!-- USERS -->

<li class="nav-item has-treeview">


<a href="#"
class="nav-link">


<i class="nav-icon fas fa-user-cog"></i>


<p>
Users
<i class="right fas fa-angle-right"></i>
</p>


</a>



<ul class="nav nav-treeview">


<li>

<a href="./index.php?page=new_user"
class="nav-link tree-item nav-new_user">

<i class="fas fa-user-plus nav-icon"></i>

<p>Add User</p>

</a>

</li>



<li>

<a href="./index.php?page=user_list"
class="nav-link tree-item nav-user_list">

<i class="fas fa-users nav-icon"></i>

<p>User List</p>

</a>

</li>



<li>

<a href="./index.php?page=system_update"
class="nav-link tree-item nav-system_update">

<i class="fas fa-database nav-icon"></i>

<p>System Update</p>

</a>

</li>


</ul>

</li>


</ul>

</nav>

</div>


</aside>





<style>

/* SIDEBAR */

/* SIDEBAR */
.modern-sidebar{
    background:#ffffff;
    border:none;
    height:100vh;
    display:flex;
    flex-direction:column;
    position:fixed;
    overflow:hidden;
}

/* Sidebar Content */
.sidebar{
    flex:1;
    overflow-y:auto;
    overflow-x:hidden;
    padding-bottom:20px;
}




/* BRAND HEADER */

.brand-area{

height:85px;

display:flex;
align-items:center;
justify-content:center;


background:
linear-gradient(135deg,#10b981,#047857);


}





.brand-link{


display:flex!important;

align-items:center;

gap:12px;

color:white!important;


}





.brand-image{


height:45px;


filter:
drop-shadow(0 4px 5px rgba(0,0,0,.2));


}





.brand-text{


font-size:18px;

font-weight:700;

line-height:18px;


}



.brand-text span{


font-size:13px;

opacity:.85;


}









/* MENU */


.nav-sidebar .nav-link{


margin:7px 12px;


border-radius:14px;


padding:13px;


color:#4b5563;


transition:.3s;


}





.nav-sidebar .nav-link:hover{


background:#ecfdf5;


color:#10b981;


transform:translateX(5px);


}








/* ACTIVE MENU */


.nav-sidebar .nav-link.active{


background:
linear-gradient(135deg,#10b981,#047857);


color:white;


box-shadow:

0 8px 20px rgba(16,185,129,.35);


}








.nav-icon{


font-size:18px!important;


}









/* DROPDOWN */


.nav-treeview{


padding-left:15px;


}






.nav-treeview .nav-link{


margin-left:20px;


font-size:14px;


background:#f9fafb;


}







.nav-treeview .nav-link:hover{


background:#ecfdf5;


color:#047857;


}









/* SIDEBAR SHADOW */


.main-sidebar{


box-shadow:

8px 0 25px rgba(0,0,0,.08)!important;


}







/* SCROLLBAR */


/* SCROLLBAR */
.sidebar::-webkit-scrollbar{
    width:6px;
}

.sidebar::-webkit-scrollbar-track{
    background:#f3f4f6;
}

.sidebar::-webkit-scrollbar-thumb{
    background:#10b981;
    border-radius:20px;
}

.sidebar::-webkit-scrollbar-thumb:hover{
    background:#047857;
}





.sidebar::-webkit-scrollbar-thumb{


border-radius:20px;


background:#d1fae5;


}





/* TEXT COLOR */

.nav-sidebar p{

font-weight:500;

}






/* RESPONSIVE */


@media(max-width:768px){


.modern-sidebar{

box-shadow:none!important;

}


}




</style>


<script>

$(document).ready(function(){


var page='<?php echo isset($_GET["page"])?$_GET["page"]:"home"; ?>';


var s='<?php echo isset($_GET["s"])?$_GET["s"]:""; ?>';


if(s!=''){

page=page+"_"+s;

}



var menu=$('.nav-link.nav-'+page);



if(menu.length){


menu.addClass('active');


menu.closest('.nav-treeview')
.parent()
.addClass('menu-open');


}


});

</script>