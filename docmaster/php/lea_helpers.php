<?php

require('../includes/governor_functions.php');

# As directed by helper_type :
#
# 'display_school_selector          '       -   код возврата для отображения элемента <select> для выбора
#                                               организации для отображения веб-страницы своих губернаторов
#
# 'build_schools_management_screen'         -   код возврата для отображения экрана обновления
#
# 'validate_lea_password'                   -   проверяем предоставленный пароль
#
# 'get_school_data'                         -   получием school_name для данной организации вместе с соответствующими
#                                               учетные данные пользователя
#
# 'ínsert_school'                           -   всталяем новую организации  для заданного school_id, а также создаем
#                                               запись пользователя для его сотрудника
#
# 'update_school'                           -   обновляем  запись организации для данной организации, а также обновляем пользователя
#
# 'delete_school'                           -   удаляем  запись  организации для данной организации, а также удаляем пользователя
#


$page_title = 'lea_helpers';

# установите заголовки ТАК, чтобы страница НЕ кэшировалась
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/Moscow');

// подключение к базе данных губернаторов

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  display_school_selector ####################

if ($helper_type == "display_school_selector") {

    $sql = "SELECT
                school_id,
                school_name
            FROM
                schools";

    $result = sql_result_for_location($sql, 1);

    $return = "
    <div style='text-align: center;'>
        Выберите организацию :&nbsp;
        <select id='schoolsselector' name='schoolsselector' onchange = 'launchSchoolPage();'>
            <option value = '0'>-- организация не выбрана --</option>";

    while ($row = mysqli_fetch_array($result)) {

        $school_id = $row['school_id'];
        $school_name = $row['school_name'];

        $return .= "
            <option value='$school_id'>$school_name</option>";
    }

    $return .= "
        </select>
    </div>";

    $return .= "
    <div style='text-align: center; margin-top: 6vh;'>
        <button id = 'lealoginbutton'  type='button' class='btn-sm btn-primary mb-2'
              title='Лоигн'
              onmousedown='displaySchoolsManagementScreen(\"\");'>Логин для Администратора организации
        </button>
    </div>";

    echo $return;
}

#####################  build_schools_management_screen ####################

if ($helper_type == "build_schools_management_screen") {

    // Создаем "переключающие" divs (отображаются как разрешенные / обязательные) для отображения :
    // - поле для входа в lea
    // - кнопка "Создать организацию", которая, в свою очередь, открывает экран для отображения названия организации и идентификатора / пароля организации администратора
    // - выпадающий  селектор организации, позволяющий редактировать вышеуказанные поля.

    $return = "
    <div id = 'lealogindiv' style='text-align: center;'>
        Пароль :
        <input type='text' id ='leapassword' name ='leapassword' maxlength=20 size=20
               value=''
               title=\"Пароль системного администратора \">

        <button id = 'loginbutton'  type='button' class='btn-sm btn-primary mb-2'
            title='Логин для ведения данных оргшаниации'
            onmousedown='validateLEAPassword();'>Вход
        </button>
        <p id = 'leapassworderror'></p>
    </div>";

    $return .= "
    <div id = 'leaschoolmanagementdiv' style='display: none; text-align: center;'>
        <p id = 'messagearea'></p>
        <div id = 'leaschoolinsertdiv'
            style = 'margin-left: auto; margin-right: auto;
                     text-align: center; border: 1px solid black;'>
            <h4 style= 'margin-top: 2vh;'>Добавлена нвоая организация</h4>

            <div style='text-align: left; margin-top: 4vh;'>
                <label for='schoolname'>Название организации : </label>
                <input type='text' id ='schoolname' name ='schoolname' maxlength=40 size=30
                    autocomplete='off' value=''
                    title='Название организации - должно быть уникальным'
                    onkeyup = 'clearAllErrors();'>
                <p id = 'schoolnameerror' class = 'formerrormessage'></p>

                <label for='clerkid'>Логин сотрудника : </label>
                <input type='text' id ='clerkid' name ='clerkid' maxlength=40 size=20
                    autocomplete='off' value=''
                    title='Идентификатор пользователя для идентификации сотрудника оргинизации (обычно адрес электронной почты)'
                    onkeyup = 'clearAllErrors();'>
                <p id = 'clerkiderror' class = 'formerrormessage'></p>

                <label for='clerkpassword'>Пароль сотрудника: </label>
                <input type='text' id ='clerkpassword' name ='clerkpassword' maxlength=40 size=20
                    autocomplete='off' value=''
                    title='Системный пароль администратора для сотрудника организации'
                    onkeyup = 'clearAllErrors();'>
                <p id = 'clerkpassworderror' class = 'formerrormessage'></p>
             </div>

            <button id = 'insertbutton'  type='button' class='btn-sm btn-primary mb-2'
                title='Добавить организацию'
                onmousedown='insertSchool();'>Добавить организацию
            </button>
        </div>
        <div id = 'leaschoolupdatetdiv'
            style = 'margin-top: 4vh; margin-left: auto; margin-right: auto;
                     text-align: center; border: 1px solid black;'>
            <h4 style= 'margin-top: 2vh; margin-bottom: 2vh;'>Обновить существующую организацию</h4>


            <label for='schoolsselector'>Update School Record for : </label>
            <select id='schoolsselector' name='schoolsselector' onchange = 'displayOldSchoolParameters();'>
                <option value = '0'>--- выберите организацию ---</option>";

    // Нам нужно получить список названий организаций  для выпадающего списка.  создаем строку для возврата этого

    $sql = "SELECT
              school_id,
              school_name
          FROM
              schools";

    $result = sql_result_for_location($sql, 2);

    $school_names = '';
    while ($row = mysqli_fetch_array($result)) {

        $school_id = $row['school_id'];
        $school_name = $row['school_name'];
        $school_names .= $school_name . ",";

        $return .= "
             <option value='$school_id'>$school_name</option>";
    }

    $sql = "SELECT
                user_id
            FROM
                users";

    $result = sql_result_for_location($sql, '2a');

    $user_ids = '';
    while ($row = mysqli_fetch_array($result)) {

        $user_id = $row['user_id'];
        $user_ids .= $user_id . ",";
    }

    $return .= "
            </select>

            <div id='oldschoolparameters' style='display: none; margin-top: 4vh;'>

                <div style='text-align: left;'>
                    <label for='oldschoolname'>Название организации : </label>
                    <input type='text' id ='oldschoolname' name ='oldschoolname' maxlength=40 size=30
                        autocomplete='off' value=''
                        title='Название организации'
                        onkeyup = 'clearAllErrors();'>
                    <p id = 'oldschoolnameerror' class = 'formerrormessage'></p>

                    <label for='olduserid'>Логин сотрудника : </label>
                    <input type='text' id ='olduserid' name ='olduserid' maxlength=40 size=20
                        autocomplete='off' value=''
                        title='Идентификатор пользователя для идентификации организации (обычно адрес электронной почты)'
                        onkeyup = 'clearAllErrors();'>
                    <p id = 'olduseriderror' class = 'formerrormessage'></p>

                    <label for='oldpassword'>Пароль сотрудника : </label>
                    <input type='text' id ='oldpassword' name ='oldpassword' maxlength=40 size=20
                        autocomplete='off' value=''
                        title='Системный пароль администратора для сотрудника организации'
                        onkeyup = 'clearAllErrors();'>
                    <p id = 'oldpassworderror' class = 'formerrormessage'></p>
                </div>

                <button id = 'updatebutton'  type='button' class='btn-sm btn-primary mb-2'
                    title='Обновите запись для этой организации'
                    onmousedown='updateSchool();'>Обновить
                </button>

                <button id = 'deletebutton'  type='button' class='btn-sm btn-primary mb-2'
                    title='Удалить запись для этой организации'
                    onmousedown='deleteSchool();'>Удалить
                </button>
            </div>
        </div>
        <button id = 'logoutbutton'  type='button' class='btn-sm btn-primary mt-3 mb-2'
            title='Выход из системы Возврат на главный экран LEA'
            onmousedown='displaySchoolSelector();'>Выход из системы
        </button>
    </div>";
    $returns = array();

    $returns['return'] = prepareStringforXMLandJSONParse($return);
    $returns['school_names'] = prepareStringforXMLandJSONParse($school_names);
    $returns['user_ids'] = prepareStringforXMLandJSONParse($user_ids);

    $return = json_encode($returns);

    header("Content-type: text/xml");
    echo "<?xml version = '1.0' encoding = 'UTF-8'?>";
    echo "<returns>$return</returns>";
}

#####################  validate_lea_password ####################

if ($helper_type == "validate_lea_password") {

    $lea_password = $_POST['lea_password'];

    // получите авторизованный пароль из личного хранилища в корне веб-сайта

    if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
        require '../lea_credentials.php';
    } else {
        $current_directory_root = $_SERVER['DOCUMENT_ROOT']; // на один уровень выше текущего каталога

        $pieces = explode('/public_html', $current_directory_root);
        $root = $pieces[0];

        require "$root/lea_credentials.php";
    }

    if ($lea_password == $stored_password_from_lea_credentials) {
        echo "valid";
    } else {
        echo "invalid";
    }
}

#####################  get_school_data ####################

if ($helper_type == "get_school_data") {

    $school_id = $_POST['school_id'];

    $sql = "SELECT
              school_name
            FROM
                schools
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 3);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    $sql = "SELECT
              user_id,
              password
            FROM
                users
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 4);

    $row = mysqli_fetch_array($result);
    $user_id = $row['user_id'];
    $password = $row['password'];

    // вставляем данные в массив в качестве подготовки к возвращению их в виде json

    $returns = array();

    $returns['school_name'] = prepareStringforXMLandJSONParse($school_name);
    $returns['user_id'] = prepareStringforXMLandJSONParse($user_id);
    $returns['password'] = prepareStringforXMLandJSONParse($password);

    $return = json_encode($returns);

    header("Content-type: text/xml");
    echo "<?xml version = '1.0' encoding = 'UTF-8'?>";
    echo "<returns>$return</returns>";
}


#####################  ínsert_school ####################

if ($helper_type == "insert_school") {

    $school_name = $_POST['school_name'];
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    $result = sql_result_for_location('START TRANSACTION', 5);

    $sql = "INSERT INTO schools (
                school_name)
            VALUES (
                '$school_name')";

    $result = sql_result_for_location($sql, 6);

    // получаем только что выданный ориганзаци id 

    $school_id = mysqli_insert_id($con);

    $sql = "INSERT INTO users (
                user_id,
                password,
                school_id)
            VALUES (
                '$user_id',
                '$password',
                '$school_id')";

    $result = sql_result_for_location($sql, 7);

    $result = sql_result_for_location('COMMIT', 8);
}

#####################  update_school ####################

if ($helper_type == "update_school") {

    $school_id = $_POST['school_id'];
    $school_name = $_POST['school_name'];
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    $result = sql_result_for_location('START TRANSACTION', 9);

    $sql = "UPDATE schools
            SET
                school_name = '$school_name'
            WHERE school_id = '$school_id';";

    $result = sql_result_for_location($sql, 10);

    $sql = "UPDATE users
            SET
                user_id = '$user_id',
                password = '$password'
            WHERE school_id= '$school_id';";


    $result = sql_result_for_location($sql, 11);

    $result = sql_result_for_location('COMMIT', 12);
}

#####################  delete_school ####################

if ($helper_type == "delete_school") {

    $school_id = $_POST['school_id'];

    $result = sql_result_for_location('START TRANSACTION', 13);

    $sql = "DELETE FROM schools
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 14);

    $sql = "DELETE FROM users
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 15);

    $result = sql_result_for_location('COMMIT', 16);
}

disconnect_from_database();
