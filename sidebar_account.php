<?php
/*
| The bottom of the sidebar: who is signed in (picture, name, email). Clicking
| it opens a small menu above it with Manage Account and Logout. This took the
| place of the account menu that used to be in the top bar.
| Included at the end of admin/sidebar.php and faculty/sidebar.php, inside
| <aside class="main-sidebar">.
|
| The row uses the menu's own classes, so it lines up and folds to a picture
| exactly like the menu's icons. Each sidebar marks its icons its own way, so
| it says which class to use in $sidebar_icon_class.
*/

$icon_class = isset($sidebar_icon_class) ? $sidebar_icon_class : 'nav-icon';

$account_name = ucwords(trim((string)($_SESSION['login_firstname'] ?? '').' '.(string)($_SESSION['login_lastname'] ?? '')));
$account_email = (string)($_SESSION['login_email'] ?? '');
$account_avatar = basename((string)($_SESSION['login_avatar'] ?? ''));

if($account_avatar === '' || !is_file(__DIR__.'/assets/uploads/'.$account_avatar)){
    $account_avatar = 'no-image-available.png';
}
?>

<div class="sidebar-account">

    <ul class="nav nav-pills nav-sidebar flex-column nav-flat sidebar-menu">

        <li class="nav-item dropup">

            <a href="javascript:void(0)" class="nav-link sidebar-account-user"
               data-toggle="dropdown" data-display="static" role="button"
               aria-haspopup="true" aria-expanded="false"
               title="Signed in as <?php echo htmlspecialchars($account_name.($account_email !== '' ? ' ('.$account_email.')' : '')); ?>">

                <!-- In the icons' column, so the name lines up with the menu -->
                <i class="<?php echo $icon_class; ?> sidebar-account-avatar" aria-hidden="true">
                    <img src="assets/uploads/<?php echo rawurlencode($account_avatar); ?>" alt=""
                         onerror="this.onerror=null; this.src='assets/uploads/no-image-available.png';">
                </i>

                <p>
                    <span class="sidebar-account-who">
                        <b><?php echo htmlspecialchars($account_name); ?></b>
                        <?php if($account_email !== ''): ?>
                            <span><?php echo htmlspecialchars($account_email); ?></span>
                        <?php endif; ?>
                    </span>
                    <i class="right fas fa-angle-up" aria-hidden="true"></i>
                </p>

            </a>

            <div class="dropdown-menu sidebar-account-menu">

                <a class="dropdown-item js-sidebar-manage-account" href="javascript:void(0)">
                    <i class="fas fa-user-cog"></i>
                    Manage Account
                </a>

                <div class="dropdown-divider"></div>

                <a class="dropdown-item sidebar-account-logout" href="ajax.php?action=logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>

            </div>

        </li>

    </ul>

</div>

<style>

/* The sidebar is a column: header, the menu (scrolls), then this */
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

/* Same side padding as the menu's area, so the row lines up with the menu */
.main-sidebar .sidebar-account{
    flex-shrink:0;
    padding:8px .5rem 10px;
    border-top:1px solid #eef2f7;
    background:#ffffff;
}

/* The menu list normally cuts off whatever sticks out; the account menu must */
.main-sidebar .sidebar-account .nav-sidebar{
    margin-top:0 !important;
    overflow:visible;
}

.main-sidebar .sidebar-account .nav-item{
    margin-bottom:0;
}

/* The signed-in person: click to open the account menu */
.main-sidebar .sidebar-account .nav-link.sidebar-account-user{
    cursor:pointer;
    padding-top:8px;
    padding-bottom:8px;
    white-space:nowrap;
}

.main-sidebar .sidebar-account .nav-link.sidebar-account-user:hover,
.main-sidebar .sidebar-account .dropup.show > .nav-link.sidebar-account-user{
    background:#ecfdf5;
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

/* A little space after the picture, which is wider than an icon; room for the arrow */
.main-sidebar .sidebar-account .sidebar-account-user p{
    display:block;
    min-width:0;
    margin:0;
    padding-left:6px;
    padding-right:22px;
}

.main-sidebar .sidebar-account .sidebar-account-who{
    display:flex;
    flex-direction:column;
    min-width:0;
    line-height:1.25;
}

.main-sidebar .sidebar-account .sidebar-account-who b,
.main-sidebar .sidebar-account .sidebar-account-who span{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.main-sidebar .sidebar-account .sidebar-account-who b{
    font-size:14px;
    font-weight:700;
    color:#1f2937;
}

.main-sidebar .sidebar-account .sidebar-account-who span{
    font-size:12px;
    font-weight:400;
    color:#9ca3af;
}

/* The arrow, like the menu's: up while closed, turning down when open */
.main-sidebar .sidebar-account .sidebar-account-user p > .right{
    top:50%;
    margin-top:-9px;
    width:auto;
    margin-right:0;
    font-size:16px;
    transition:transform .25s ease;
}

.main-sidebar .sidebar-account .dropup.show .sidebar-account-user p > .right{
    transform:rotate(180deg);
}

/* The menu opens above the row, as wide as the sidebar allows */
.main-sidebar .sidebar-account .sidebar-account-menu{
    left:0;
    right:0;
    min-width:0;
    margin-bottom:6px;
}

.main-sidebar .sidebar-account .sidebar-account-menu .dropdown-item{
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:500;
}

/* Logout turns red, not green, when pointed at */
.main-sidebar .sidebar-account .sidebar-account-menu .sidebar-account-logout:hover,
.main-sidebar .sidebar-account .sidebar-account-menu .sidebar-account-logout:focus{
    background:#dc2626;
    color:#ffffff;
}

</style>

<script>
$(function(){
    // Manage Account: the account dialog, as it was in the top bar
    $('.js-sidebar-manage-account').on('click', function(e){
        e.preventDefault();
        uni_modal('Manage Account', 'manage_user.php?id=<?php echo (int)($_SESSION['login_id'] ?? 0); ?>');
    });
});
</script>
