<?php
    header('Content-Type: text/plain');
    chmod(dirname(__FILE__), 0644);
    echo "File permissions set!";