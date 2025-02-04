<?php
session_start();

function checkAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.html');
        exit;
    }
}
?> 