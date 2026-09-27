<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['login_id']) || empty($_SESSION['login_id'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

include 'db_connect.php';

/*
|--------------------------------------------------------------------------
| SYSTEM SETTINGS
|--------------------------------------------------------------------------
*/

ob_start();

if (!isset($_SESSION['system'])) {

    $system = $conn->query("SELECT * FROM system_settings")->fetch_array();

    if ($system) {
        foreach ($system as $k => $v) {
            $_SESSION['system'][$k] = $v;
        }
    }
}

ob_end_flush();

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include 'header.php';

?>

<!DOCTYPE html>
<html lang="en">

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">

    <?php include 'topbar.php'; ?>

    <?php include $_SESSION['login_view_folder'].'sidebar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">

        <div class="toast" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body text-white"></div>
        </div>

        <div id="toastsContainerTopRight" class="toasts-top-right fixed"></div>

        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $title ?></h1>
                    </div>
                </div>

                <hr class="border-primary">
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <?php

                $page = isset($_GET['page']) ? $_GET['page'] : 'home';

                if (!file_exists($_SESSION['login_view_folder'] . $page . ".php")) {

                    include '404.html';

                } else {

                    include $_SESSION['login_view_folder'] . $page . '.php';

                }

                ?>

            </div>
        </section>

        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirm_modal" role="dialog">
            <div class="modal-dialog modal-md" role="document">

                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Confirmation</h5>
                    </div>

                    <div class="modal-body">
                        <div id="delete_content"></div>
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-primary"
                            id="confirm"
                            onclick="">
                            Continue
                        </button>

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">
                            Close
                        </button>

                    </div>

                </div>

            </div>
        </div>

        <!-- Universal Modal -->
        <div class="modal fade" id="uni_modal" role="dialog">

            <div class="modal-dialog modal-md" role="document">

                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                    </div>

                    <div class="modal-body"></div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-primary"
                            id="submit"
                            onclick="$('#uni_modal form').submit()">
                            Save
                        </button>

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">
                            Cancel
                        </button>

                    </div>

                </div>

            </div>
        </div>

        <!-- Right Modal -->
        <div class="modal fade" id="uni_modal_right" role="dialog">

            <div class="modal-dialog modal-full-height modal-md" role="document">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title"></h5>

                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close">

                            <span class="fa fa-arrow-right"></span>

                        </button>

                    </div>

                    <div class="modal-body"></div>

                </div>

            </div>
        </div>

        <!-- Viewer Modal -->
        <div class="modal fade" id="viewer_modal" role="dialog">

            <div class="modal-dialog modal-md" role="document">

                <div class="modal-content">

                    <button
                        type="button"
                        class="btn-close"
                        data-dismiss="modal">

                        <span class="fa fa-times"></span>

                    </button>

                    <img src="" alt="">

                </div>

            </div>
        </div>

    </div>
    <!-- /.content-wrapper -->


    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
    </aside>


    <!-- Main Footer -->
    <footer class="main-footer">

        <div class="float-right d-none d-sm-inline-block"></div>

    </footer>

</div>
<!-- ./wrapper -->


<!-- REQUIRED SCRIPTS -->

<?php include 'footer.php'; ?>

</body>
</html>