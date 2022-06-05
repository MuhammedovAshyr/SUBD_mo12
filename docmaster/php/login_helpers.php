<?php

require('../includes/governor_functions.php');

# As directed by helper_type :
# 
# 'login            '       -   проверет предоставленные идентификатор пользователя и пароль на соответствие 
#                               таблица member_passwords и возвращает связанный 
#                               идентификатор организации
#                                                         
# 'change_password'         -   смена пароля 
#                                       
# 'keep_alive'              -   придаем сеансу  параметры                                                        
# 

$page_title = 'login_helpers';

header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/Moscow');

session_start();

// подключение к базе данных
connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  login ####################

if ($helper_type == "login") {

    $user_id = $_POST['userid'];
    $password = $_POST['password'];

    $return = "invalid";

    $sql = "SELECT 
                school_id
            FROM users
            WHERE 
                user_id = '$user_id'
            AND
                password = '$password';";

    $result = sql_result_for_location($sql, 1);

    if (mysqli_num_rows($result) >= 1) {

        $row = mysqli_fetch_array($result);
        $school_id = $row['school_id'];

        // ОК - действительный логин. Запускаем сеанс и набор $_SESSION['governors_user_logged_in_for_school_id'] для $school_id 

        session_start();

        $_SESSION['governors_user_logged_in_for_school_id'] = $school_id;
        $return = "valid";
    }

    echo $return;
}

#####################  keep_alive ####################

if ($helper_type == "keep_alive") {

    # фиктивный помощник - уже вызвал session_start(), поэтому сеанс теперь должен быть обновлен
    # error_log("вызов "сохранить в живых");
}
