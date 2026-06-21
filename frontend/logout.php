<?php
session_start();
session_destroy();
require_once __DIR__ . '/includes/auth.php';
redirect_to('/login.php');
