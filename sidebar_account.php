<?php
/*
| The bottom of the sidebar: Manage Account and Logout, the same two actions
| as the account menu in the top bar (topbar.php), so they are always one
| click away. Included at the end of admin/sidebar.php and faculty/sidebar.php,
| inside <aside class="main-sidebar">.
|
| The links use the menu's own classes, so they look, line up and fold to
| icons exactly like the menu above them. Each sidebar marks its icons its own
| way, so it says which class to use in $sidebar_icon_class.
*/

$icon_class = isset($sidebar_icon_class) ? $sidebar_icon_class : 'nav-icon';
?>

<div class="sidebar-account">

    <ul class="nav nav-pills nav-sidebar flex-column nav-flat sidebar-menu">

        <li class="nav-item">
            <a href="javascript:void(0)" class="nav-link js-sidebar-manage-account">
                <i class="<?php echo $icon_class; ?> fas fa-user-cog"></i>
                <p>Manage Account</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="ajax.php?action=logout" class="nav-link sidebar-account-logout">
                <i class="<?php echo $icon_class; ?> fas fa-sign-out-alt"></i>
                <p>Logout</p>
            </a>
        </li>

    </ul>

</div>

<style>

/* The sidebar is a column: header, the menu (scrolls), then these two links */
.main-sidebar{
    display:flex;
    flex-direction:column;
}

.main-sidebar > .brand-link{
    flex-shrink:0;
}

.main-sidebar > .sidebar{
    flex:1 1 auto;
    min-height:0;
    height:auto !important;
    overflow-y:auto;
}

/* Same side padding as the menu's area, so the links line up with the menu's */
.main-sidebar .sidebar-account{
    flex-shrink:0;
    padding:6px .5rem 10px;
    border-top:1px solid #eef2f7;
    background:#ffffff;
}

.main-sidebar .sidebar-account .nav-sidebar{
    margin-top:0 !important;
}

.main-sidebar .sidebar-account .nav-item{
    margin-bottom:0;
}

.main-sidebar .sidebar-account .nav-link{
    white-space:nowrap;
}

/* Logout turns red, not green, when pointed at */
.main-sidebar .sidebar-account .nav-link.sidebar-account-logout:hover{
    background:#fef2f2;
    color:#dc2626 !important;
}

.main-sidebar .sidebar-account .nav-link.sidebar-account-logout:hover i{
    color:#dc2626 !important;
}

</style>

<script>
$(function(){
    // The same dialog as Manage Account in the top bar
    $('.js-sidebar-manage-account').on('click', function(){
        uni_modal('Manage Account', 'manage_user.php?id=<?php echo (int)($_SESSION['login_id'] ?? 0); ?>');
    });
});
</script>
