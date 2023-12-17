<?php

require_once __DIR__ .'/register.php';

$url = cm_reg_cart_count() ? 'cart.php' : 'edit.php';
header('Location: ' . $url);
