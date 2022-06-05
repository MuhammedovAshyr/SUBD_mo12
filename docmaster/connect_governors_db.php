<?php

// подключаемся к бд

$con = mysqli_connect('localhost','asplace','Pass1234','docsystem_db'); 
if (!$con) {
    die('Не удалось подключиться: ' . mysqli_error($con));
}
