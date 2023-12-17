<?php
    header('Content-Type: text/plain');
    chmod(__DIR__, 0644);
    echo "File permissions set!";
