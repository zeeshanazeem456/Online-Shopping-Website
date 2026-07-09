<?php

require __DIR__ . '/includes/auth.php';

$auth->logout();

redirect_to('login.php');
