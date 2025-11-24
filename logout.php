<?php
    session_start();
    session_destroy();
    header('Location: display.php');
    exit();
?>