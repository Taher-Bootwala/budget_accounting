<?php

require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    redirectByRole();
} else {
    redirect('/Furniture/login.php');
}
