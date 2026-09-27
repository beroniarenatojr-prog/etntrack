<?php 
function ordinal_suffix($num){
    $num = $num % 100;
    if($num < 11 || $num > 13){
        switch($num % 10){
            case 1: return $num.'st';
            case 2: return $num.'nd';
            case 3: return $num.'rd';
        }
    }
    return $num.'th';
}

$rid='';
$faculty_id='';
$subject_id='';

if(isset($_GET['rid'])) $rid = $_GET['rid'];
if(isset($_GET['fid'])) $faculty_id = $_GET['fid'];
if(isset($_GET['sid'])) $subject_id = $_GET['sid'];

$restriction = $conn->query("
SELECT r.id,s.id as sid,f.id as fid,
concat(f.firstname,' ',f.lastname) as faculty,
s.code,s.subject
FROM restriction_list r
inner join faculty_list f on f.id = r.faculty_id
inner join subject_list s on s.id = r.subject_id
where academic_id ={$_SESSION['academic']['id']}
and class_id = {$_SESSION['login_class_id']}
and r.id not in (
    SELECT restriction_id 
    from evaluation_list 
    where academic_id ={$_SESSION['academic']['id']} 
    and student_id = {$_SESSION['login_id']}
)
");
?>

<style>

/* PAGE LAYOUT */
.eval-wrapper{
    padding:20px;
}

/* LEFT LIST */
.eval-list{
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 6px 18px rgba(0,0,0,.06);
}

.eval-list a{
    border:none;
    padding:14px 15px;
    transition:.3s;
}

.eval-list a:hover{
    background:#f3f0ff;
    transform:translateX(5px);
}

.eval-list a.active{
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:white;
    border:none;
}

/* MAIN CARD */
.eval-card{
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    border:none;
}

/* HEADER */
.eval-header{
    background:linear-gradient(135deg,#667eea,#764ba2);
    color:white;
    padding:18px;
}

/* LEGEND */
.legend-box{
    background:#f8f9ff;
    border-left:5px solid #667eea;
    padding:12px;
    border-radius:12px;
    margin-bottom:15px;
}

/* TABLE STYLE */
.eval-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 10px;
}

.eval-table th{
    background:#f1f3f9;
    padding:10px;
    font-size:14px;
}

.eval-table td{
    background:white;
    padding:12px;
    vertical-align:middle;
}

/* QUESTION CELL */
.question-cell{
    font-weight:500;
}

/* RADIO LOOK */
.icheck-success input{
    transform:scale(1.2);
    cursor:pointer;
}

/* SUBMIT BUTTON */
.eval-submit{
    background:linear-gradient(135deg,#667eea,#764ba2);
    border:none;
    color:white;
    padding:8px 16px;
    border-radius:10px;
}

</style>

<div class="col-lg-12 eval-wrapper">

<div class="row">

    <!-- LEFT SIDE -->
    <div class="col-md-3">

        <div class="list-group eval-list">

            <?php 
            while($row=$restriction->fetch_array()):
                if(empty($rid)){
                    $rid = $row['id'];
                    $faculty_id = $row['fid'];
                    $subject_id = $row['sid'];
                }
            ?>

            <a class="list-group-item list-group-item-action <?php echo $rid==$row['id']?'active':'' ?>"
            href="./index.php?page=evaluate&rid=<?php echo $row['id'] ?>&sid=<?php echo $row['sid'] ?>&fid=<?php echo $row['fid'] ?>">

                <?php echo ucwords($row['faculty']).' - ('.$row["code"].') '.$row['subject'] ?>

            </a>

            <?php endwhile; ?>

        </div>

    </div>


    <!-- RIGHT SIDE -->
    <div class="col-md-9">

        <div class="card eval-card">

            <div class="eval-header">

                <b>
                    Evaluation Questionnaire:
                    <?php echo $_SESSION['academic']['year'].' '.ordinal_suffix($_SESSION['academic']['semester']) ?>
                </b>

                <div class="float-right">
                    <button class="eval-submit" form="manage-evaluation">
                        Submit Evaluation
                    </button>
                </div>

            </div>


            <div class="card-body">


                <!-- LEGEND -->
                <div class="legend-box">

                    <b>Rating Guide:</b><br>
                    5 - Strongly Agree |
                    4 - Agree |
                    3 - Neutral |
                    2 - Disagree |
                    1 - Strongly Disagree

                </div>



                <form id="manage-evaluation">

                    <input type="hidden" name="class_id" value="<?php echo $_SESSION['login_class_id'] ?>">
                    <input type="hidden" name="faculty_id" value="<?php echo $faculty_id?>">
                    <input type="hidden" name="restriction_id" value="<?php echo $rid ?>">
                    <input type="hidden" name="subject_id" value="<?php echo $subject_id ?>">
                    <input type="hidden" name="academic_id" value="<?php echo $_SESSION['academic']['id'] ?>">


                    <?php 
                    $criteria = $conn->query("
                        SELECT * FROM criteria_list 
                        where id in (
                            SELECT criteria_id FROM question_list 
                            where academic_id = {$_SESSION['academic']['id']}
                        )
                        order by abs(order_by) asc
                    ");

                    while($crow = $criteria->fetch_assoc()):
                    ?>

                    <table class="eval-table">

                        <thead>
                            <tr>
                                <th colspan="6"><?php echo $crow['criteria'] ?></th>
                            </tr>
                            <tr>
                                <th style="width:45%">Question</th>
                                <th>1</th>
                                <th>2</th>
                                <th>3</th>
                                <th>4</th>
                                <th>5</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php 
                        $questions = $conn->query("
                            SELECT * FROM question_list 
                            where criteria_id = {$crow['id']} 
                            and academic_id = {$_SESSION['academic']['id']}
                            order by abs(order_by) asc
                        ");

                        while($row=$questions->fetch_assoc()):
                        ?>

                            <tr>

                                <td class="question-cell">
                                    <?php echo $row['question'] ?>
                                    <input type="hidden" name="qid[]" value="<?php echo $row['id'] ?>">
                                </td>

                                <?php for($c=1;$c<=5;$c++): ?>
                                <td class="text-center">

                                    <input type="radio"
                                    name="rate[<?php echo $row['id'] ?>]"
                                    value="<?php echo $c ?>"
                                    <?php echo $c==5?'checked':'' ?>>

                                </td>
                                <?php endfor; ?>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                    <?php endwhile; ?>

                </form>

            </div>

        </div>

    </div>

</div>

</div>