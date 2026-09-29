
<?php include 'db_connect.php' ?>

<div class="col-lg-12">

    <div class="card criteria-main-card">

        <!-- =========================
             MAIN HEADER
        ========================== -->
        <div class="criteria-main-header">

            <div class="header-left">

                <div class="header-icon">
                    <i class="fas fa-tasks"></i>
                </div>

                <div>
                    <h3>Criteria Management</h3>
                    <p>Manage and organize evaluation criteria</p>
                </div>

            </div>

            <div class="criteria-total">

                <?php
                    $total_criteria = $conn->query(
                        "SELECT COUNT(*) AS total FROM criteria_list"
                    )->fetch_assoc()['total'];
                ?>

                <span class="total-number">
                    <?php echo number_format($total_criteria); ?>
                </span>

                <span class="total-label">
                    Total Criteria
                </span>

            </div>

        </div>


        <!-- =========================
             CONTENT
        ========================== -->
        <div class="card-body criteria-content">

            <div class="row">

                <div class="col-12">

                    <div class="criteria-list-card">


                        <!-- LIST HEADER -->

                        <div class="list-card-header">

                            <div>

                                <div class="list-title">

                                    <div class="list-icon">
                                        <i class="fas fa-list"></i>
                                    </div>

                                    <div>

                                        <h5>Criteria List</h5>

                                        <p>
                                            Drag items to change their order
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="list-actions">

                                <button
                                    class="btn save-order"
                                    form="order-criteria"
                                    type="submit"
                                >

                                    <i class="fas fa-sort"></i>

                                    Save Order

                                </button>


                                <button
                                    class="btn add-criteria-btn"
                                    id="new_criteria"
                                    type="button"
                                >

                                    <i class="fas fa-plus"></i>

                                    Add Criteria

                                </button>

                            </div>

                        </div>


                        <!-- LIST BODY -->

                        <div class="list-card-body">


                            <?php

                            $qry = $conn->query(
                                "SELECT *
                                 FROM criteria_list
                                 ORDER BY ABS(order_by) ASC"
                            );


                            // Filled while listing; the edit modal reads it even when the list is empty

                            $criteria = array();


                            // Questions of the current academic year, grouped by criteria

                            $academic_id = isset($_SESSION['academic']['id'])
                                ? (int)$_SESSION['academic']['id']
                                : 0;

                            $questions = array();

                            $q_qry = $conn->query(
                                "SELECT criteria_id, question
                                 FROM question_list
                                 " . ($academic_id ? "WHERE academic_id = $academic_id" : "") . "
                                 ORDER BY ABS(order_by) ASC"
                            );

                            while($q = $q_qry->fetch_assoc()){

                                $questions[$q['criteria_id']][] = $q['question'];

                            }

                            ?>


                            <?php if($qry->num_rows > 0): ?>


                                <form id="order-criteria">

                                    <ul
                                        class="criteria-sortable"
                                        id="ui-sortable-list"
                                    >


                                        <?php

                                        $counter = 1;

                                        while($row = $qry->fetch_assoc()):

                                            $criteria[$row['id']] = $row;

                                        ?>


                                            <li
                                                class="criteria-item"
                                                data-id="<?php echo $row['id']; ?>"
                                            >


                                                <!-- DRAG HANDLE -->

                                                <div class="drag-handle">

                                                    <i class="fas fa-grip-vertical"></i>

                                                </div>


                                                <!-- NUMBER -->

                                                <div class="criteria-number">

                                                    <?php echo $counter++; ?>

                                                </div>


                                                <!-- CONTENT -->

                                                <?php
                                                $row_questions = isset($questions[$row['id']])
                                                    ? $questions[$row['id']]
                                                    : array();
                                                ?>

                                                <button
                                                    type="button"
                                                    class="criteria-details criteria-toggle"
                                                    aria-expanded="false"
                                                    aria-controls="criteria-questions-<?php echo $row['id']; ?>"
                                                >

                                                    <span class="criteria-name">

                                                        <?php
                                                        echo ucwords(
                                                            htmlspecialchars(
                                                                $row['criteria']
                                                            )
                                                        );
                                                        ?>

                                                    </span>

                                                    <small>
                                                        Evaluation Criterion
                                                        &middot;
                                                        <?php echo count($row_questions); ?>
                                                        <?php echo count($row_questions) == 1 ? 'Question' : 'Questions'; ?>
                                                    </small>

                                                </button>


                                                <!-- EXPAND ICON -->

                                                <span class="criteria-chevron">

                                                    <i class="fas fa-chevron-down"></i>

                                                </span>


                                                <!-- ACTION -->

                                                <div class="criteria-actions">

                                                    <div class="dropdown">

                                                        <button
                                                            type="button"
                                                            class="criteria-menu"
                                                            data-toggle="dropdown"
                                                            aria-haspopup="true"
                                                            aria-expanded="false"
                                                        >

                                                            <i class="fas fa-ellipsis-v"></i>

                                                        </button>


                                                        <div
                                                            class="dropdown-menu dropdown-menu-right"
                                                        >

                                                            <a
                                                                href="javascript:void(0)"
                                                                class="dropdown-item edit_criteria"
                                                                data-id="<?php echo $row['id']; ?>"
                                                            >

                                                                <span class="menu-icon edit-icon">
                                                                    <i class="fas fa-edit"></i>
                                                                </span>

                                                                Edit Criteria

                                                            </a>


                                                            <div class="dropdown-divider"></div>


                                                            <a
                                                                href="javascript:void(0)"
                                                                class="dropdown-item delete_criteria"
                                                                data-id="<?php echo $row['id']; ?>"
                                                            >

                                                                <span class="menu-icon delete-icon">
                                                                    <i class="fas fa-trash"></i>
                                                                </span>

                                                                Delete Criteria

                                                            </a>

                                                        </div>

                                                    </div>

                                                </div>


                                                <input
                                                    type="hidden"
                                                    name="criteria_id[]"
                                                    value="<?php echo $row['id']; ?>"
                                                >


                                                <!-- QUESTIONS (shown on click) -->

                                                <div
                                                    class="criteria-questions"
                                                    id="criteria-questions-<?php echo $row['id']; ?>"
                                                >

                                                    <?php if(count($row_questions) > 0): ?>

                                                        <ol class="question-items">

                                                            <?php foreach($row_questions as $question): ?>

                                                                <li>
                                                                    <?php echo htmlspecialchars($question); ?>
                                                                </li>

                                                            <?php endforeach; ?>

                                                        </ol>

                                                    <?php else: ?>

                                                        <p class="no-questions">

                                                            <i class="fas fa-info-circle"></i>

                                                            No questions under this criterion yet.

                                                        </p>

                                                    <?php endif; ?>

                                                </div>

                                            </li>


                                        <?php endwhile; ?>


                                    </ul>

                                </form>


                                <!-- DRAG INFO -->

                                <div class="drag-info">

                                    <i class="fas fa-arrows-alt"></i>

                                    <span>
                                        Drag and drop the criteria to arrange
                                        their evaluation order.
                                    </span>

                                </div>


                            <?php else: ?>


                                <!-- EMPTY STATE -->

                                <div class="empty-state">

                                    <div class="empty-icon">

                                        <i class="fas fa-clipboard-list"></i>

                                    </div>

                                    <h5>No Criteria Found</h5>

                                    <p>
                                        Start by adding your first evaluation
                                        criterion using the Add Criteria button.
                                    </p>

                                </div>


                            <?php endif; ?>


                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     CRITERIA FORM MODAL
========================== -->

<div
    class="modal fade criteria-modal"
    id="criteria-modal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="criteria-modal-title"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered" role="document">

        <div class="modal-content form-card">

            <div class="form-card-header">

                <div class="form-icon">
                    <i class="fas fa-plus"></i>
                </div>

                <div>
                    <h5 id="criteria-modal-title">Add Criteria</h5>
                    <p>Add or update evaluation criteria</p>
                </div>

                <button
                    type="button"
                    class="close criteria-modal-close"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>


            <div class="form-card-body">

                <form action="" id="manage-criteria">

                    <input type="hidden" name="id">


                    <div id="msg"></div>


                    <div class="form-group">

                        <label for="criteria">

                            <i class="fas fa-tag"></i>

                            Criteria Name

                        </label>


                        <input
                            type="text"
                            name="criteria"
                            id="criteria"
                            class="form-control criteria-input"
                            placeholder="Enter criteria name..."
                            autocomplete="off"
                            required
                        >


                        <small class="form-help">

                            Example: Program Relevance,
                            Service Quality, etc.

                        </small>

                    </div>


                    <!-- INFO BOX -->

                    <div class="criteria-info">

                        <div class="info-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>

                        <div>

                            <strong>Tip</strong>

                            <p>
                                Use short and clear criteria
                                names to make evaluation results
                                easier to understand.
                            </p>

                        </div>

                    </div>

                </form>

            </div>


            <div class="form-card-footer">

                <button
                    class="btn cancel-btn"
                    type="button"
                    data-dismiss="modal"
                >

                    Cancel

                </button>


                <button
                    class="btn save-btn"
                    form="manage-criteria"
                    type="submit"
                >

                    <i class="fas fa-save"></i>

                    Save Criteria

                </button>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     CSS
====================================================== -->

<style>

/* =========================================
   VARIABLES
========================================= */

:root{

    --criteria-green:#10b981;
    --criteria-dark:#047857;
    --criteria-darker:#065f46;

    --criteria-light:#ecfdf5;

    --criteria-border:#d1fae5;

    --criteria-text:#1f2937;
    --criteria-muted:#6b7280;

}


/* =========================================
   MAIN CARD
========================================= */

.criteria-main-card{

    border:none!important;

    border-radius:22px!important;

    overflow:hidden;

    background:#fff;

    box-shadow:
        0 12px 30px rgba(0,0,0,.08);

}


/* =========================================
   MAIN HEADER
========================================= */

.criteria-main-header{

    background:
        linear-gradient(
            135deg,
            #10b981,
            #047857
        );

    padding:25px 28px;

    color:white;

    display:flex;

    align-items:center;

    justify-content:space-between;

}


.header-left{

    display:flex;

    align-items:center;

    gap:15px;

}


.header-icon{

    width:52px;

    height:52px;

    border-radius:15px;

    background:rgba(255,255,255,.16);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:22px;

    border:1px solid rgba(255,255,255,.2);

}


.criteria-main-header h3{

    margin:0;

    font-size:23px;

    font-weight:700;

}


.criteria-main-header p{

    margin:4px 0 0;

    font-size:13px;

    opacity:.85;

}


/* =========================================
   TOTAL CRITERIA
========================================= */

.criteria-total{

    background:rgba(255,255,255,.13);

    border:1px solid rgba(255,255,255,.18);

    padding:10px 18px;

    border-radius:14px;

    display:flex;

    flex-direction:column;

    align-items:center;

    min-width:110px;

}


.total-number{

    font-size:24px;

    font-weight:800;

    line-height:1;

}


.total-label{

    font-size:11px;

    margin-top:5px;

    opacity:.85;

}


/* =========================================
   CONTENT
========================================= */

.criteria-content{

    padding:28px;

    background:#f9fafb;

}


/* =========================================
   FORM CARD
========================================= */

.form-card{

    background:white;

    border-radius:18px;

    border:1px solid #eef2f7;

    overflow:hidden;

    box-shadow:
        0 8px 20px rgba(0,0,0,.05);

    margin-bottom:20px;

}


.form-card-header{

    display:flex;

    align-items:center;

    gap:12px;

    padding:20px;

    background:#f0fdf4;

    border-bottom:1px solid #dcfce7;

}


.form-icon{

    width:42px;

    height:42px;

    border-radius:12px;

    background:#10b981;

    color:white;

    display:flex;

    align-items:center;

    justify-content:center;

}


.form-card-header h5{

    margin:0;

    color:#065f46;

    font-size:17px;

    font-weight:700;

}


.form-card-header p{

    margin:3px 0 0;

    color:#6b7280;

    font-size:12px;

}


.form-card-body{

    padding:23px;

}


.form-group label{

    display:block;

    font-size:14px;

    font-weight:700;

    color:#374151;

    margin-bottom:8px;

}


.form-group label i{

    color:#10b981;

    margin-right:6px;

}


.criteria-input{

    height:45px!important;

    border-radius:11px!important;

    border:1px solid #d1d5db!important;

    padding:10px 13px!important;

    font-size:14px!important;

    transition:.2s;

}


.criteria-input:focus{

    border-color:#10b981!important;

    box-shadow:
        0 0 0 3px rgba(16,185,129,.12)!important;

}


.form-help{

    display:block;

    margin-top:7px;

    font-size:11px;

    color:#9ca3af;

}


/* =========================================
   INFO BOX
========================================= */

.criteria-info{

    margin-top:22px;

    padding:13px;

    background:#f0fdf4;

    border:1px solid #dcfce7;

    border-radius:12px;

    display:flex;

    gap:10px;

}


.info-icon{

    color:#10b981;

    font-size:18px;

    padding-top:2px;

}


.criteria-info strong{

    color:#065f46;

    font-size:13px;

}


.criteria-info p{

    margin:3px 0 0;

    font-size:11px;

    line-height:1.5;

    color:#6b7280;

}


/* =========================================
   FORM FOOTER
========================================= */

.form-card-footer{

    padding:17px 20px;

    border-top:1px solid #f1f5f9;

    background:#fafafa;

    display:flex;

    justify-content:flex-end;

    gap:8px;

}


.save-btn{

    background:#10b981!important;

    color:white!important;

    border:none!important;

    border-radius:10px!important;

    padding:9px 16px!important;

    font-weight:600!important;

    transition:.2s;

}


.save-btn:hover{

    background:#047857!important;

    transform:translateY(-1px);

}


.cancel-btn{

    background:#f3f4f6!important;

    color:#6b7280!important;

    border:none!important;

    border-radius:10px!important;

    padding:9px 16px!important;

}


.cancel-btn:hover{

    background:#e5e7eb!important;

}


/* =========================================
   LIST CARD
========================================= */

.criteria-list-card{

    background:white;

    border-radius:18px;

    border:1px solid #eef2f7;

    box-shadow:
        0 8px 20px rgba(0,0,0,.05);

    overflow:visible;

}


.list-card-header{

    padding:20px 22px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    border-bottom:1px solid #eef2f7;

}


.list-title{

    display:flex;

    align-items:center;

    gap:12px;

}


.list-icon{

    width:42px;

    height:42px;

    border-radius:12px;

    background:#ecfdf5;

    color:#10b981;

    display:flex;

    align-items:center;

    justify-content:center;

}


.list-title h5{

    margin:0;

    color:#1f2937;

    font-size:17px;

    font-weight:700;

}


.list-title p{

    margin:3px 0 0;

    color:#9ca3af;

    font-size:11px;

}


.save-order{

    background:#047857!important;

    color:white!important;

    border:none!important;

    border-radius:10px!important;

    padding:9px 15px!important;

    font-weight:600!important;

}


.save-order:hover{

    background:#065f46!important;

}


.list-actions{

    display:flex;

    gap:8px;

}


.add-criteria-btn{

    background:#10b981!important;

    color:white!important;

    border:none!important;

    border-radius:10px!important;

    padding:9px 15px!important;

    font-weight:600!important;

    transition:.2s;

}


.add-criteria-btn:hover{

    background:#047857!important;

    transform:translateY(-1px);

}


/* =========================================
   CRITERIA FORM MODAL
========================================= */

.criteria-modal .modal-content{

    border:none;

    margin-bottom:0;

    box-shadow:
        0 20px 45px rgba(0,0,0,.18);

}


.criteria-modal-close{

    margin-left:auto;

    align-self:flex-start;

    color:#6b7280;

    opacity:.8;

}


.criteria-modal-close:hover{

    color:#065f46;

}


/* =========================================
   LIST BODY
========================================= */

.list-card-body{

    padding:20px;

}


/* =========================================
   SORTABLE LIST
========================================= */

.criteria-sortable{

    list-style:none;

    padding:0;

    margin:0;

}


.criteria-item{

    position:relative;

    display:flex;

    flex-wrap:wrap;

    align-items:center;

    min-height:68px;

    padding:10px 12px;

    margin-bottom:10px;

    background:white;

    border:1px solid #e5e7eb;

    border-radius:13px;

    cursor:default;

    transition:

        transform .2s,

        box-shadow .2s,

        border-color .2s,

        background .2s;

}


.criteria-item:hover{

    border-color:#a7f3d0;

    background:#fafffc;

    transform:translateX(3px);

    box-shadow:
        0 6px 16px rgba(16,185,129,.08);

}


/* The hover transform makes the row its own stacking context,
   so raise it above the next rows or its action menu is hidden under them. */

.criteria-item:hover,

.criteria-item:focus-within{

    z-index:2;

}


/* =========================================
   DRAG HANDLE
========================================= */

.drag-handle{

    width:32px;

    color:#9ca3af;

    text-align:center;

    cursor:grab;

    font-size:15px;

}


.drag-handle:hover{

    color:#10b981;

}


.criteria-item.ui-sortable-helper{

    box-shadow:
        0 15px 30px rgba(0,0,0,.12);

    transform:rotate(1deg);

    background:#f0fdf4;

}


.criteria-item.ui-sortable-placeholder{

    visibility:visible!important;

    background:#ecfdf5;

    border:2px dashed #10b981;

    height:68px;

}


/* =========================================
   NUMBER
========================================= */

.criteria-number{

    width:35px;

    height:35px;

    border-radius:10px;

    background:#ecfdf5;

    color:#047857;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:13px;

    font-weight:800;

    margin-right:13px;

}


/* =========================================
   CRITERIA DETAILS
========================================= */

.criteria-details{

    flex:1;

    min-width:0;

}


.criteria-toggle{

    padding:0;

    border:none;

    background:none;

    text-align:left;

    cursor:pointer;

}


.criteria-toggle:focus{

    outline:none;

}


.criteria-toggle:focus-visible{

    outline:2px solid #10b981;

    outline-offset:4px;

    border-radius:6px;

}


.criteria-toggle:hover .criteria-name{

    color:#047857;

}


/* =========================================
   EXPAND / QUESTIONS
========================================= */

.criteria-chevron{

    margin-left:8px;

    color:#9ca3af;

    font-size:12px;

    cursor:pointer;

    transition:transform .2s, color .2s;

}


.criteria-item.open .criteria-chevron{

    transform:rotate(180deg);

    color:#10b981;

}


.criteria-item.open{

    border-color:#a7f3d0;

}


.criteria-questions{

    display:none;

    flex-basis:100%;

    margin-top:12px;

    padding-top:12px;

    border-top:1px dashed #d1fae5;

}


.question-items{

    margin:0;

    padding-left:0;

    list-style:none;

    counter-reset:question;

}


.question-items li{

    position:relative;

    padding:8px 10px 8px 40px;

    margin-bottom:6px;

    background:#f9fafb;

    border-radius:9px;

    font-size:13px;

    line-height:1.5;

    color:#4b5563;

    counter-increment:question;

}


.question-items li::before{

    content:counter(question);

    position:absolute;

    left:10px;

    top:8px;

    width:20px;

    height:20px;

    border-radius:6px;

    background:#ecfdf5;

    color:#047857;

    font-size:11px;

    font-weight:800;

    display:flex;

    align-items:center;

    justify-content:center;

}


.no-questions{

    margin:0;

    font-size:13px;

    color:#9ca3af;

}


.criteria-name{

    display:block;

    font-size:14px;

    font-weight:700;

    color:#374151;

    word-break:break-word;

}


.criteria-details small{

    display:block;

    margin-top:3px;

    font-size:10px;

    color:#9ca3af;

}


/* =========================================
   ACTION MENU
========================================= */

.criteria-actions{

    margin-left:10px;

}


.criteria-menu{

    width:36px;

    height:36px;

    border:none;

    border-radius:10px;

    background:#f3f4f6;

    color:#6b7280;

    display:flex;

    align-items:center;

    justify-content:center;

    cursor:pointer;

    transition:.2s;

}


.criteria-menu:hover{

    background:#ecfdf5;

    color:#047857;

}


.dropdown-menu{

    border:none!important;

    border-radius:12px!important;

    padding:6px!important;

    min-width:175px;

    box-shadow:
        0 12px 30px rgba(0,0,0,.14)!important;

}


.dropdown-item{

    border-radius:8px;

    padding:9px 11px;

    font-size:13px;

    display:flex;

    align-items:center;

    gap:9px;

    color:#374151;

}


.dropdown-item:hover{

    background:#f0fdf4;

    color:#047857;

}


.menu-icon{

    width:27px;

    height:27px;

    border-radius:7px;

    display:flex;

    align-items:center;

    justify-content:center;

}


.edit-icon{

    background:#eff6ff;

    color:#3b82f6;

}


.delete-icon{

    background:#fef2f2;

    color:#ef4444;

}


.dropdown-divider{

    margin:5px 0;

}


/* =========================================
   DRAG INFO
========================================= */

.drag-info{

    margin-top:15px;

    padding:11px 13px;

    border-radius:10px;

    background:#f9fafb;

    color:#9ca3af;

    font-size:11px;

    display:flex;

    align-items:center;

    gap:8px;

}


.drag-info i{

    color:#10b981;

}


/* =========================================
   EMPTY STATE
========================================= */

.empty-state{

    padding:55px 20px;

    text-align:center;

}


.empty-icon{

    width:75px;

    height:75px;

    margin:0 auto 15px;

    border-radius:50%;

    background:#ecfdf5;

    color:#10b981;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:30px;

}


.empty-state h5{

    margin:0;

    color:#374151;

    font-weight:700;

}


.empty-state p{

    margin:7px auto 0;

    max-width:350px;

    font-size:12px;

    color:#9ca3af;

    line-height:1.6;

}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:991px){

    .criteria-content{

        padding:20px;

    }

    .form-card{

        margin-bottom:20px;

    }

}


@media(max-width:767px){

    .criteria-main-header{

        padding:20px;

        align-items:flex-start;

        gap:15px;

    }


    .criteria-main-header h3{

        font-size:18px;

    }


    .criteria-total{

        min-width:80px;

        padding:8px 10px;

    }


    .total-number{

        font-size:20px;

    }


    .total-label{

        font-size:9px;

    }


    .list-card-header{

        align-items:flex-start;

        flex-direction:column;

        gap:15px;

    }


    .list-actions{

        width:100%;

    }


    .save-order,

    .add-criteria-btn{

        flex:1;

    }


    .criteria-item{

        min-height:62px;

    }


    .criteria-number{

        width:30px;

        height:30px;

        margin-right:8px;

    }


    .drag-handle{

        width:25px;

    }

}


@media(max-width:480px){

    .criteria-main-header{

        flex-direction:column;

    }


    .criteria-total{

        align-self:flex-end;

    }


    .criteria-content{

        padding:12px;

    }


    .form-card-body,

    .list-card-body{

        padding:15px;

    }


    .criteria-details small{

        display:none;

    }

}

</style>


<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>

$(document).ready(function(){


    /* =========================================
       SORTABLE
    ========================================= */

    $('#ui-sortable-list').sortable({

        handle: '.drag-handle',

        placeholder: 'criteria-item ui-sortable-placeholder',

        tolerance: 'pointer'

    });


    /* =========================================
       SHOW QUESTIONS UNDER CRITERIA
    ========================================= */

    $('.criteria-toggle, .criteria-chevron').click(function(){

        var item = $(this).closest('.criteria-item');

        var open = !item.hasClass('open');


        item.toggleClass('open', open);

        item.find('.criteria-toggle').attr('aria-expanded', open);

        item.find('.criteria-questions').stop(true, true).slideToggle(200);

    });


    /* =========================================
       CRITERIA FORM MODAL
    ========================================= */

    // Move the modal to <body> so the page's cards can't stack it under the backdrop

    $('#criteria-modal').appendTo('body');


    function open_criteria_modal(data){

        var form = $('#manage-criteria');


        form[0].reset();

        $('#msg').html('');


        form
            .find("[name='id']")
            .val(data ? data.id : '');


        form
            .find("[name='criteria']")
            .val(data ? data.criteria : '');


        $('#criteria-modal-title')
            .text(data ? 'Edit Criteria' : 'Add Criteria');


        $('#criteria-modal .form-icon')
            .html(
                data
                    ? '<i class="fas fa-edit"></i>'
                    : '<i class="fas fa-plus"></i>'
            );


        $('#criteria-modal .save-btn')
            .html(
                '<i class="fas fa-save"></i> ' +
                (data ? 'Update Criteria' : 'Save Criteria')
            );


        $('#criteria-modal').modal('show');

    }


    $('#criteria-modal').on('shown.bs.modal',function(){

        $('#criteria').trigger('focus');

    });


    /* =========================================
       ADD CRITERIA
    ========================================= */

    $('#new_criteria').click(function(){

        open_criteria_modal(null);

    });


    /* =========================================
       EDIT CRITERIA
    ========================================= */

    $('.edit_criteria').click(function(){

        var id = $(this).attr('data-id');

        var criteria =
            <?php echo json_encode($criteria); ?>;


        if(criteria[id]){

            open_criteria_modal(criteria[id]);

        }

    });


    /* =========================================
       DELETE
    ========================================= */

    $('.delete_criteria').click(function(){

        var id =
            $(this).attr('data-id');


        _conf(
            "Are you sure you want to delete this criteria?",
            "delete_criteria",
            [id]
        );

    });


    /* =========================================
       OLD MAKE DEFAULT FUNCTION
       KEPT FOR COMPATIBILITY
    ========================================= */

    $('.make_default').click(function(){

        _conf(
            "Are you sure to make this criteria year as the system default?",
            "make_default",
            [$(this).attr('data-id')]
        );

    });


    /* =========================================
       SAVE CRITERIA
    ========================================= */

    $('#manage-criteria').submit(function(e){

        e.preventDefault();

        start_load();

        $('#msg').html('');


        var criteriaValue =
            $.trim(
                $('#criteria').val()
            );


        if(criteriaValue === ''){

            $('#msg').html(
                '<div class="alert alert-danger">' +
                '<i class="fa fa-exclamation-triangle"></i> ' +
                'Please enter a criteria name.' +
                '</div>'
            );

            end_load();

            $('#criteria').focus();

            return false;

        }


        $.ajax({

            url:'ajax.php?action=save_criteria',

            method:'POST',

            data:$(this).serialize(),

            success:function(resp){

                if(resp == 1){

                    $('#criteria-modal').modal('hide');

                    alert_toast(
                        "Criteria successfully saved.",
                        "success"
                    );


                    setTimeout(function(){

                        location.reload();

                    },1750);


                }else if(resp == 2){

                    $('#msg').html(

                        '<div class="alert alert-danger">' +

                        '<i class="fa fa-exclamation-triangle"></i> ' +

                        'Criteria already exist.' +

                        '</div>'

                    );

                    end_load();


                }else{

                    alert_toast(
                        "Unable to save criteria.",
                        "error"
                    );

                    end_load();

                }

            },

            error:function(){

                alert_toast(
                    "An error occurred while saving.",
                    "error"
                );

                end_load();

            }

        });

    });


    /* =========================================
       SAVE ORDER
    ========================================= */

    $('#order-criteria').submit(function(e){

        e.preventDefault();

        start_load();


        $.ajax({

            url:'ajax.php?action=save_criteria_order',

            method:'POST',

            data:$(this).serialize(),

            success:function(resp){

                if(resp == 1){

                    alert_toast(
                        "Criteria order successfully saved.",
                        "success"
                    );


                    setTimeout(function(){

                        location.reload();

                    },1200);


                }else{

                    alert_toast(
                        "Unable to save criteria order.",
                        "error"
                    );

                    end_load();

                }

            },

            error:function(){

                alert_toast(
                    "An error occurred while saving the order.",
                    "error"
                );

                end_load();

            }

        });

    });


});


/* =========================================
   DELETE FUNCTION
========================================= */

function delete_criteria($id){

    start_load();


    $.ajax({

        url:'ajax.php?action=delete_criteria',

        method:'POST',

        data:{
            id:$id
        },

        success:function(resp){

            if(resp == 1){

                alert_toast(
                    "Criteria successfully deleted.",
                    "success"
                );


                setTimeout(function(){

                    location.reload();

                },1500);


            }else{

                alert_toast(
                    "Unable to delete criteria.",
                    "error"
                );

                end_load();

            }

        },

        error:function(){

            alert_toast(
                "An error occurred while deleting.",
                "error"
            );

            end_load();

        }

    });

}


/* =========================================
   MAKE DEFAULT
   KEPT FOR COMPATIBILITY
========================================= */

function make_default($id){

    start_load();


    $.ajax({

        url:'ajax.php?action=make_default',

        method:'POST',

        data:{
            id:$id
        },

        success:function(resp){

            if(resp == 1){

                alert_toast(
                    "Default academic year updated.",
                    "success"
                );


                setTimeout(function(){

                    location.reload();

                },1500);

            }else{

                end_load();

            }

        }

    });

}

</script>
