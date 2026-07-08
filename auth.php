<?php
    session_start();
    if(!isset($_SESSION["user_id"])){
        $_SESSION["redirect_to"] = $_SERVER["REQUEST_URL"] ;
        header("Location: login.php");
        exit();
    }
    if(!isset($_SESSION['session_start_time'])){
        $_SESSION['session_start_time'] = time();
    }
    else if (time() - $SESSION['session_start_time'] > 1800){
        session_regenerate_id(true);
        $_SESSION['session_start_time'] = time() ;
    }
?>