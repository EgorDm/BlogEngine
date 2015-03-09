<?php
/**
 * @author Egor Dmitriev
 * @package BlogEngine
 */

include_once 'pageinit.php';

$registerstatus = '';
$loginstatus = '';
$logoutstatus = '';
$poststatus = '';
$pwstatus = '';

if (!empty($_POST['Register'])) {
    $registerstatus = 'OK';
    if(isset($_POST['fullname'], $_POST['username'], $_POST['email'], $_POST['password'])) {
        $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $email = 	filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $email = 	filter_var($email, FILTER_VALIDATE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $reg_result = $bea->user->register($fullname, $email, $username, $password);

            if($reg_result == 2) {
                $registerstatus = 'Registered sucesfully';
            } else {
                $registerstatus = 'Something is wrong!';
            }

        } else {
            $registerstatus = 'Please enter a valid email!';
        }
    }
}

if (!empty($_POST['Login'])) {
    $loginstatus = 'OK';
    if(isset($_POST['username'], $_POST['password'])) {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

        $login_result = $bea->user->login($username, $password);

        if($login_result == 2) {
            $loginstatus = 'Logged in sucesfully';
            $bea->user->set_group($_SESSION['user_id'], 3);
        } else {
            $loginstatus = 'Something is wrong!';
        }
    }
}

if (!empty($_POST['Logout'])) {
    $logoutstatus = 'OK';

    $bea->user->logout();

    $logoutstatus = 'Logged out succesfully!';
}

if (!empty($_POST['AddPost'])) {
    $poststatus = 'OK';

    if(isset($_POST['title'], $_POST['description'], $_POST['postcontent'])) {
        if($bea->user->is_logged_in() == true) {
            $bea->add_post($_POST['title'], $_POST['description'], $_POST['postcontent'], $bea->user->get_userid($_SESSION['username']));
            $logoutstatus = 'posted succesfully!';
        }
    }
}

if (!empty($_POST['changepw'])) {
    $pwstatus = 'OK';

    $bea->user->set_password($_SESSION['user_id'], $_POST['password']);

    $pwstatus = 'Password changed succesfully!';
}

if (isset($_GET['deletepost'])) {
    if ($bea->user->is_logged_in() && $bea->user->has_permission($_SESSION['user_id'], 'perm_deletepost')) {
        $bea->delete_post($_GET['deletepost']);
    }
}

?>


<html>
<head>
    <script src="//cdn.ckeditor.com/4.4.7/basic/ckeditor.js"></script>
</head>
<body>
<?php if($bea->user->is_logged_in() == true) { ?>
<h1>Hello, your mail is <?php echo $bea->user->get_email();?></h1>
<?php }?>

<form method="post">
    <H1>Register Form</H1>
    <p><?php echo $registerstatus ?></p>
    <fieldset>
        <input type='text' name='fullname' id='fullname' placeholder="fullname" maxlength="50" />
        <input type='text' name='username' id='username' placeholder="username" maxlength="50" />
        <input type='text' name='email' id='email' placeholder="email" maxlength="50" />
        <input type='password' name='password' id='password' placeholder="password" maxlength="50" />
        <input type='submit' name='Register' value='Register' />
    </fieldset>
</form>

<form method="post">
    <H1>Login Form</H1>
    <p><?php echo $loginstatus ?></p>
    <fieldset>
        <input type='text' name='username' id='username' placeholder="username" maxlength="50" />
        <input type='password' name='password' id='password' placeholder="password" maxlength="50" />
        <input type='submit' name='Login' value='Login' />
    </fieldset>
</form>

<form method="post">
    <H1>Logout</H1>
    <p><?php echo $logoutstatus ?></p>
    <fieldset>
        <input type='submit' name='Logout' value='logout' />
    </fieldset>
</form>

<?php if($bea->user->is_logged_in() == true) { ?>
    <form method="post">
        <H1>ChangePW</H1>
        <p><?php echo $pwstatus ?></p>
        <fieldset>
            <input type='password' name='password' id='password' placeholder="password" maxlength="50" />
            <input type='submit' name='changepw' value='changepw' />
        </fieldset>
    </form>


<form method="post">
    <H1>Make a post</H1>
    <p><?php echo $poststatus ?></p>
    <fieldset>
        <input type='text' name='title' id='title' placeholder="title" maxlength="50" />
        <input type='text' name='description' id='description' placeholder="description" maxlength="50" />
        <textarea name="postcontent" id='postcontent'></textarea>
        <script>
            CKEDITOR.replace('postcontent');
        </script>
        <input type='submit' name='AddPost' value='AddPost' />
    </fieldset>
</form>
<?php }?>

<?php
$post_list = $bea->get_post_row(5);
foreach ($post_list as $row) {
?>

<div style="margin-top: 50px; padding: 50px; border: solid 1px black;">
    <table style="width:100%">
        <tr>
            <td>Title:</td>
            <td>Description:</td>
            <td>Author:</td>
        </tr>
        <tr>
            <td><?php echo $row['post_title']; ?></td>
            <td><?php echo $row['post_desc']; ?></td>
            <td><?php echo $bea->user->get_username($row['post_owner']); ?></td>
            </tr>
    </table>

    <div style="margin-top: 50px;">
            <?php echo $row['post_cont']; ?>
    </div>
    <?php if($bea->user->is_logged_in() && $bea->user->has_permission($_SESSION['user_id'], 'perm_deletepost')) { ?>
        <a href="?deletepost=<?php echo $row['post_id']; ?>">Delete Post</a>
    <?php } ?>
</div>

<?php } ?>

</body>
</html>