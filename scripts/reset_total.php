<?php
if (isset($_POST["reset_total"])) {
    require __DIR__ . '/destory_session.php';
    header("Location: ../index.php");
    exit;
}
