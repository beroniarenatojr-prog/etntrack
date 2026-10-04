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

// Who is signed in: the same picture and name as in the top bar
$account_name = ucwords(trim((string)($_SESSION['login_firstname'] ?? '').' '.(string)($_SESSION['login_lastname'] ?? '')));
$account_email = (string)($_SESSION['login_email'] ?? '');
$account_avatar = basename((string)($_SESSION['login_avatar'] ?? ''));

if($account_avatar === '' || !is_file(__DIR__.'/assets/uploads/'.$account_avatar)){
    $account_avatar = 'no-image-available.png';
}
?>

<div class="sidebar-account">

    <ul class="nav nav-pills nav-sidebar flex-column nav-flat sidebar-menu">

        <li class="nav-item">
            <div class="nav-link sidebar-account-user" title="Signed in as <?php echo htmlspecialchars($account_name.($account_email !== '' ? ' ('.$account_email.')' : '')); ?>">
                <!-- In the icons' column, so the name lines up with the menu -->
                <i class="<?php echo $icon_class; ?> sidebar-account-avatar" aria-hidden="true">
                    <img src="assets/uploads/<?php echo rawurlencode($account_avatar); ?>" alt=""
                         onerror="this.onerror=null; this.src='assets/uploads/no-image-available.png';">
                </i>
                <p>
                    <b><?php echo htmlspecialchars($account_name); ?></b>
                    <?php if($account_email !== ''): ?>
                        <span><?php echo htmlspecialchars($account_email); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </li>

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

/* The signed-in person: shown, not clicked */
.main-sidebar .sidebar-account .nav-link.sidebar-account-user{
    cursor:default;
    padding-top:8px;
    padding-bottom:8px;
    margin-bottom:2px;
}

.main-sidebar .sidebar-account .nav-link.sidebar-account-user:hover{
    background:transparent;
    color:inherit;
    transform:none;
}

.main-sidebar .sidebar-account .sidebar-account-avatar{
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}

/* A little wider than the icon column, still centred on it */
.main-sidebar .sidebar-account .sidebar-account-avatar img{
    width:32px;
    height:32px;
    margin:0 -3px;
    border-radius:50%;
    object-fit:cover;
    background:#ffffff;
    border:2px solid #d1fae5;
}

/* A little space after the picture, which is wider than an icon */
.main-sidebar .sidebar-account .sidebar-account-user p{
    display:flex;
    flex-direction:column;
    min-width:0;
    margin:0;
    padding-left:6px;
    line-height:1.25;
}

.main-sidebar .sidebar-account .sidebar-account-user p b,
.main-sidebar .sidebar-account .sidebar-account-user p span{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.main-sidebar .sidebar-account .sidebar-account-user p b{
    font-size:14px;
    font-weight:700;
    color:#1f2937;
}

.main-sidebar .sidebar-account .sidebar-account-user p span{
    font-size:12px;
    font-weight:400;
    color:#9ca3af;
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
