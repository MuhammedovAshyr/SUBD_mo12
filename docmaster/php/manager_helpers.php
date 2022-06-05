<?php


require_once('../vendor/autoload.php');

require('../includes/governor_functions.php');

# As directed by helper_type :
#
# 'build_governing_body_update_table'   -   код возврата для отображения сводного представления руководящего органа
#
# 'build_governor_insert_screen'        -   код возврата для отображения кода для вставки нового регулятора
#
# 'insert_governor'                     -   вставляем новый регулятор
#
# 'get_governor_data'                   -   возвращаем данные для заданного governor_id                                                        '
#
# 'update_governor'                     -   обновляем регулятор для заданного идентификатора
#
# 'delete_governor'                      -  удаляем управляющего для данного идентификатора
#
# 'reorder_governors'                   -   изменяем порядок sections_configuration.txt файл с экрана Dom
#
# 'build_clerk_update_screen'           -   код возврата для отображения экрана обновления для сотрудника для данного
#                                           организация. Обратяем внимание, что система заботится о том, чтобы всегда был
#                                           запись сотрудника для каждой организации (при необходимости создаем пустую запись) и
#                                           не предоставляет возможности для создания второго!
#
# 'update_clerk'                        -   обновяем сотрудника для данной организации
#
# 'build_attendances_update_screen'     -   код возврата для отображения экрана обновления (обновление/вставка) для
#                                           посещаемость собраний для данной организации и данного собрания
#                                           диапазон дат (последние 4 встречи, если диапазон не указан)
#
# 'build_meeting_insert_table'          -   код возврата для создания новой встречи для данной организации и даты
#                                           для набора управляющих, вступивших в должность на эту дату
#
# 'insert_meeting'                     -   вставляем новое собрание для данной организации
#
# 'test_for_unique_meeting_date'        -   возвращаем "уникальный", если нет существующей записи собрания для данного
#                                           school_id и meeting_date
#
# 'update_meeting'                      -   обновяем информацию о посещаемости для данного собрания и данной организации
#
# 'delete_meeting'                      -   удалляем собрание для заданных school_id и meeting_date
#
# 'build_documents_update_table'        -   код возврата для отображения сводного представления таблицы документов
#                                           для данной организации в качестве базы для запуска обновлений
#
# 'build_document_insert_screen'         -   код возврата для создания нового документа
#
# 'insert_document'                      -   вставляем новый документ для данной организации и document_title
#
# 'build_version_insert_screen'          -   код возврата для создания новой версии документа
#
# 'insert_version'                       -   вставляем новую версию для данной организации и document_title
#
# 'reset_documents_update_table_row'     -   изменяем заданную строку текущей таблицы documents_update, чтобы ответляем на
#                                            выбор другого номера версии
#
# 'build_document_review_screen'         -   отображение экрана для обновления review_date в последней версии данного документа
#
# 'update_document'                      -   обновяем запись базы данных для данного документа
#
# 'delete_document'                      -   удалляем документ для данного документа
#
# 'create_doc_template_dismissal'        -   формируется WORD документ приказа об увольнениии

$page_title = 'manager_helpers';

# установяем заголовки ТАК, чтобы страница НЕ кэшировалась
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

date_default_timezone_set('Europe/London');

// check logged_in

session_start();

if (!isset($_SESSION['governors_user_logged_in_for_school_id'])) {
    echo "%timed_out%";
    exit(0);
} else {
    $school_id = $_SESSION['governors_user_logged_in_for_school_id'];
}

// подключение к базе данных губернаторов

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  build_governing_body_update_table ####################

if ($helper_type == "build_governing_body_update_table") {

    // Создание таблиц для представления сводной информации о текущем членстве руководящего органа и
    // для использования в качестве стартовых площадок для обновления записей губернатора и секретаря. Стол губернатора
    // выглядит немного сложнее, чем можно было бы ожидать, поскольку мы используем sortableJS для
    // предоставления механизма "перетаскивания" для наложения последовательности отображения на записи '
    // SortableJS - это библиотека javascript, загруженная с Github. 
    // https://www.solodev.com/blog/web-design/how-to-create-sortable-lists-with-sortablejs.stml 

    $sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 1);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    $return = "
        <h2 style='text-align: center;'>Состав руководителей для '" . $school_name . "'</h2>
            <h5 style= 'text-align: center;'>(Нажмите строку для получения подробной информации)</h5>
        <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>
        <div style='text-align: center; margin-bottom:4vh;'>
            <button id = 'insertbutton'  type='button' class='btn-sm btn-success mb-2'
                title='Создаем запись для нового губернатора'
                onmousedown='displayGovernorInsertScreen();'>Добавить нового губернатора
            </button>
        </div>";

    // получяем ассоциативные массивы для "декодированных" типов управляющих и ролей управляющих

    $governor_types = get_governor_types();
    $governor_roles = get_governor_roles();

    // теперь создаем строку отображения для каждого определенного в данный момент регулятора

    $sql = "SELECT
                    governor_id,
                    governor_first_names,
                    governor_surname,
                    governor_type_code,
                    governor_role_code,
                    governor_appointment_date,
                    governor_term_of_office,
                    display_sequence
                FROM
                    governors
                WHERE
                    school_id = '$school_id'
                ORDER BY
                    display_sequence";

    $result = sql_result_for_location($sql, 2);

    $i = 0;

    $return .= "
        <div class='container'>
            <div class='row justify-content-center pt-0 pb-0 pt-1' style='background: white;'>
                <div class='list-group-item mb-0 pb-1'>
                    <p class= 'cell bold' style='width: 10rem;'>Имя</p>
                    <p class= 'cell bold' style='width: 10rem;'>Тип управляющего</p>
                    <div class= 'cell bold center' style='width: 10rem;'><span>Роль управляющего</span></div>
                    <div class= 'cell bold center' style='width: 10rem;'><span>Срок полномочий<br>(заканчивается)</span></div>
                    <p class= 'cell bold' style='width: 5rem;'></p>
                    <p class= 'cell bold' style='width: 5rem;'></p>
                    <p class= 'cell bold' style='width: 5rem;'></p>
                </div>
            </div>
            <div id='governorssortablelist' class='list-group'>";

    while ($row = mysqli_fetch_array($result)) {

        $governor_id = $row['governor_id'];
        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_name = "$governor_first_names $governor_surname";
        $governor_type_code = $row['governor_type_code'];
        $governor_role_code = $row['governor_role_code'];
        $governor_appointment_date = $row['governor_appointment_date'];
        $governor_term_of_office = $row['governor_term_of_office'];
        $governor_retirement_date = date('Y-m-d', strtotime(" + $governor_term_of_office years", strtotime($governor_appointment_date)));


        $i++;

        $governor_type = $governor_types[$governor_type_code];
        $governor_role = $governor_roles[$governor_role_code];


        if ($governor_type == 'n/a') {
            $governor_type = '';
        }
        if ($governor_role == 'n/a') {
            $governor_role = '';
        }

        $return .= "
                <div id = 'govrow$i' class='row justify-content-center pt-0 pb-0' style='background: white;'>
                    <div class='list-group-item mb-0 pb-0 pt-1'>

                        <span class = 'governorsentry' style = 'display: none;'>$governor_id</span>
                        <p class= 'cell' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'>$governor_name</p>
                        <p class= 'cell' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'>$governor_type</p>
                        <div class= 'cell center' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'><span>$governor_role</span></div>
                        <div class= 'cell center' style='width: 10rem;' onmousedown='displayGovernorUpdateScreen($governor_id);'><span>$governor_retirement_date</span></div>
                        
                        <p class= 'cell' style='width: 5rem;'>
                            <button id = 'editbutton$i'  type='button' class='ml-2 mr-2 btn-sm btn-info'
                                title='Отредактируем эту запись губернатора'
                                onmousedown='create_doc_template_dismissal($governor_id);'>Уволить
                            </button>
                        </p>

                        <p class= 'cell' style='width: 5rem;'>
                            <button id = 'editbutton$i'  type='button' class='ml-2 mr-2 btn-sm btn-info'
                                title='Отредактируем эту запись губернатора'
                                onmousedown='displayGovernorUpdateScreen($governor_id);'>Изменить
                            </button>
                        </p>
                        <p class= 'cell' style='width: 5rem;'>
                            <button id = 'deletebutton'  type='button' class='btn-sm btn-danger' style = 'margin-left: 2vw;'
                                title='Удалить'
                                onmousedown='deleteGovernor($governor_id, \"$governor_name\");'>Удалить
                            </button>
                        </p>
                </div>
                </div>";
    }

    // добавляем последнюю кнопку, чтобы разрешить переупорядочивание массива управляющих

    $return .= "
            </div>
            <div style = 'text-align: center;'>
                <span>Чтобы изменить порядок строк, щелкняем и перетащяем на левой панели, а затем щелкняем </span>
                <button id = 'reorderbutton'  type='button' class='mt-3 mr-2 btn-sm btn-secondary'
                    title='Re-order the Governors after \"drag and drop\"'
                    onclick='reorderGovernors();'>Переупорядочить
                </button>
                <span>  кнопку</span>
            </div>";

    // отображаем сведения о клерке этой школы. Если записи о клерке не существует, создаем ее

    $sql = "SELECT * FROM clerks
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 3);

    if (mysqli_num_rows($result) >= 1) {

        $row = mysqli_fetch_array($result);
        $clerk_first_names = $row['clerk_first_names'];
        $clerk_surname = $row['clerk_surname'];
        $clerk_email_address = $row['clerk_email_address'];
    } else {

        $clerk_first_names = '';
        $clerk_surname = '';
        $clerk_postal_address = '';
        $clerk_telephone_number = '';
        $clerk_email_address = '';

        $sql = "INSERT INTO clerks (
                school_id,
                clerk_first_names,
                clerk_surname,
                clerk_telephone_number,
                clerk_email_address,
                clerk_postal_address)
            VALUES (
                '$school_id',
                '$clerk_first_names',
                '$clerk_surname',
                '$clerk_telephone_number',
                '$clerk_email_address',
                '$clerk_postal_address');";

        $result = sql_result_for_location($sql, 4);
    }


    $return .= "
            <div class='row justify-content-center mt-4 pt-0 pb-0 pt-1' style='background: white;'>
                <div class='list-group-item mb-0 pb-1'>
                    <p class= 'cell bold' style='width: 10rem;' onmousedown='displayClerkUpdateScreen();'>Сотрудник : </p>
                    <p class= 'cell' style='width: 20rem;' onmousedown='displayClerkUpdateScreen();'>$clerk_first_names $clerk_surname : </p>
                    <p class= 'cell' style='width: 20em;' onmousedown='displayClerkUpdateScreen();'>$clerk_email_address</p>
                    <button id = 'editclerkbutton'  type='button' class='ml-2 mr-2 btn-sm btn-info'
                        title='Edit the Clerk record'
                        onmousedown='displayClerkUpdateScreen();'>Изменить
                    </button>
                </div>
            </div>
        </div>";

    // добавляем кнопки для отображения ссылок на полезные страницы веб-сайта

    $return .= "
        <div>
            <button  type='button' class='ml-2 mt-4 mr-2 btn-sm btn-primary'
                title='Отобразить ссылку на сводную информацию о Руководящем органе на веб-странице'
                onmousedown='grabClipboardLink(\"pages.html?id=$school_id&target=governors\");'>Сформировать документ 1 (html) 
            </button>
            <button  type='button' class='ml-4 mt-4 mr-2 btn-sm btn-primary'
                title='Отобразить ссылку на сводную информацию о бизнес-интересах губернатора на веб-странице'
                onmousedown='grabClipboardLink(\"pages.html?id=$school_id&target=busints\");'>Сформировать документ 2 (html) 
            </button>";

    echo $return;
}

#####################  get_governor_data ####################

if ($helper_type == "get_governor_data") {

    $governor_id = $_POST['governor_id'];

    $sql = "SELECT * FROM governors
    WHERE governor_id = '$governor_id';
    ";

    $result = sql_result_for_location($sql, 5);
    $row = mysqli_fetch_array($result);

    // вставляем данные в массив в качестве подготовки к возвращению их в виде json

    $returns = array();

    $returns['governor_first_names'] = prepareStringforXMLandJSONParse($row['governor_first_names']);
    $returns['governor_surname'] = prepareStringforXMLandJSONParse($row['governor_surname']);
    $returns['governor_type_code'] = prepareStringforXMLandJSONParse($row['governor_type_code']);
    $returns['governor_role_code'] = prepareStringforXMLandJSONParse($row['governor_role_code']);
    $returns['governor_responsibilities'] = prepareStringforXMLandJSONParse($row['governor_responsibilities']);
    $returns['governor_telephone_number'] = prepareStringforXMLandJSONParse($row['governor_telephone_number']);
    $returns['governor_email_address'] = prepareStringforXMLandJSONParse($row['governor_email_address']);
    $returns['governor_postal_address'] = prepareStringforXMLandJSONParse($row['governor_postal_address']);
    $returns['governor_appointment_date'] = prepareStringforXMLandJSONParse($row['governor_appointment_date']);
    $returns['governor_term_of_office'] = prepareStringforXMLandJSONParse($row['governor_term_of_office']);
    $returns['governor_business_interests'] = prepareStringforXMLandJSONParse($row['governor_business_interests']);

    $return = json_encode($returns);

    header("Content-type: text/xml");
    echo "<?xml version = '1.0' encoding = 'UTF-8'
    ?>";
    echo "<returns>$return</returns>";
}

#####################  build_governor_insert_screen ####################

if ($helper_type == "build_governor_insert_screen") {

    $return = "

<h2 style='text-align: center;'>Форма ввода дданых грубернатора</h2>

<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";

    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-outline-dark' style='position: absolute; right: 0;'
            title='Return to the Governors screen'
            onmousedown='displayGovernorsScreen(\"\");'>Назад
        </button>
    </div>

    <form id='governordata' method = 'POST'>

        <label for='governorfirstnames'>Имя  Губернатора : </label>
        <input type='text' id ='governorfirstnames' name ='governorfirstnames' maxlength=20 size=20
               autocomplete='off' value=''
               title='Governor christian names'>
        <p id = 'governorfirstnameserror'></p>

        <label for='governorsurname'>Фамилия Губернатора : </label>
        <input type='text' id ='governorsurname' name ='governorsurname' maxlength=20 size=20
               autocomplete='off' value=''
               title='Governor surname'>
        <p id = 'governorsurnameerror'></p>

        <label for='governortypes'>Тип управления : </label>
        <input type = radio name = 'governortypes' value = 1> Управляющий персоналом&nbsp;
        <input type = radio name = 'governortypes' value = 2> Губернатор города&nbsp;
        <input type = radio name = 'governortypes' value = 3 checked> Главный управляющий&nbsp;
        <input type = radio name = 'governortypes' value = 4> Исполняемльный директор&nbsp;
        <input type = radio name = 'governortypes' value = 5> Заместяемль губернатора&nbsp;
        <input type = radio name = 'governortypes' value = 6> не определено
        <p id = 'governortypeserror'></p>

        <label for='governorroles'>Роль губернатора : </label>
        <input type = radio name = 'governorroles' value = 1> Председатель&nbsp;
        <input type = radio name = 'governorroles' value = 2> Заместяемль председателя&nbsp;
        <input type = radio name = 'governorroles' value = 3 checked> не определено
        <p id = 'governorroleserror'></p>

        <label for ='governorresponsibilities'>Обязанности губернатора : </label>
        <textarea id ='governorresponsibilities' name ='governorresponsibilities' rows = 3 cols = 60 maxlength = 200  wrap= 'virtual'
                  autocomplete='off' value=''
                  title='Подробная информация о сферах особой ответственности, которыми занимается этот губернатор'></textarea>
        <p id = 'governorresponsibilitieserror'></p>

        <label for='governorpostaladdress'>Почтовый адрес : </label>
        <input type='text' id ='governorpostaladdress' name ='governorpostaladdress' maxlength=60 size=60
               autocomplete='off' value=''
               title='Почтовый адрес этого губернатора в виде строки свободного формата'>
        <p id = 'governorpostaladdresserror'></p>

        <label for='governortelephonenumber'>Номер телефона : </label>
        <input type='text' id ='governortelephonenumber' name ='governortelephonenumber' maxlength=40 size=40
               autocomplete='off' value=''
               title='Номер телефона губернатора' maxlength=40 size=40>
        <p id = 'governortelephonenumbererror'></p>

        <label for='governoremailaddress'>Адрес электронной почты : </label>
        <input type='text' id ='governoremailaddress' name ='governoremailaddress' maxlength=40 size=40
               autocomplete='off' value=''
               title='Адрес электронной почты'>
        <p id = 'governoremailaddresserror'></p>

        <label for='governorappointmentdate'>Дата назначения : </label>
        <input type='text' id ='governorappointmentdate' name ='governorappointmentdate' size=10
               autocomplete='off' value=''
               title='Дата начала этой встречи'
               onmousedown='applyDatepicker(\"governorappointmentdate\");'>
        <p id = 'appointmentdateerror'></p>

        <label for='governortermofoffice'>Срок полномочий (годы): </label>
        <input type='text' id ='governortermofoffice' name ='governortermofoffice' maxlength=1 size=1
               autocomplete='off' value=''
               title='Количество лет пребывания в должности для этого назначения'>
        <p id = 'termofofficeerror'></p>

        <label for='governorbusinessinterests'>Деловые интересы : </label>
        <textarea id ='governorbusinessinterests' name ='governorbusinessinterests' rows = 3 cols = 60
                  autocomplete='off' value=''
                  title='Деловые интересы губернатора'></textarea>
        <p id = 'governorbusinessinterestserror'></p>

    </form>

    <div id = 'buildgovernorbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-info' style = 'margin-left: 2vw;'
                title='Вставляем запись для этого управляющего'
                onmousedown='insertGovernor();'>Добавить запись
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-secondary' style = 'margin-left: 2vw;'
                title='Отменить вставку'
                onmousedown='displayGovernorsScreen(\"Вставка отменена\");'>Отменить
        </button>
    </div>

</div>";

    echo $return;
}

#####################  insert_governor ####################

if ($helper_type == "insert_governor") {

    $governor_first_names = $_POST['governorfirstnames'];
    $governor_surname = $_POST['governorsurname'];
    $governor_type_code = $_POST['governortypes'];
    $governor_role_code = $_POST['governorroles'];
    $governor_responsibilities = $_POST['governorresponsibilities'];
    $governor_telephone_number = $_POST['governortelephonenumber'];
    $governor_email_address = $_POST['governoremailaddress'];
    $governor_postal_address = $_POST['governorpostaladdress'];
    $governor_appointment_date = $_POST['governorappointmentdate'];
    $governor_term_of_office = $_POST['governortermofoffice'];
    $governor_business_interests = $_POST['governorbusinessinterests'];

    // получяем текущее общее количество губернаторов для этой организации как display_sequence

    $sql = "SELECT COUNT(governor_id) as display_sequence
            FROM governors
            WHERE school_id = '$school_id';";

    $result = sql_result_for_location($sql, 6);
    $row = mysqli_fetch_array($result);
    $display_sequence = $row['display_sequence'];

    $sql = "INSERT INTO governors (
                school_id,
                governor_first_names,
                governor_surname,
                governor_type_code,
                governor_role_code,
                governor_responsibilities,
                governor_telephone_number,
                governor_email_address,
                governor_postal_address,
                governor_appointment_date,
                governor_term_of_office,
                governor_business_interests,
                display_sequence)
            VALUES (
                '$school_id',
                '$governor_first_names',
                '$governor_surname',
                '$governor_type_code',
                '$governor_role_code',
                '$governor_responsibilities',
                '$governor_telephone_number',
                '$governor_email_address',
                '$governor_postal_address',
                '$governor_appointment_date',
                '$governor_term_of_office',
                '$governor_business_interests',
                '$display_sequence');";

    $result = sql_result_for_location($sql, 7);

    echo "Insert succeeded";
}

#####################  update_governor ####################

if ($helper_type == "update_governor") {

    $governor_id = $_POST['governor_id'];
    $governor_first_names = $_POST['governorfirstnames'];
    $governor_surname = $_POST['governorsurname'];
    $governor_type_code = $_POST['governortypes'];
    $governor_role_code = $_POST['governorroles'];
    $governor_responsibilities = $_POST['governorresponsibilities'];
    $governor_telephone_number = $_POST['governortelephonenumber'];
    $governor_email_address = $_POST['governoremailaddress'];
    $governor_postal_address = $_POST['governorpostaladdress'];
    $governor_appointment_date = $_POST['governorappointmentdate'];
    $governor_term_of_office = $_POST['governortermofoffice'];
    $governor_business_interests = $_POST['governorbusinessinterests'];

    $sql = "UPDATE governors
            SET
                governor_first_names = '$governor_first_names',
                governor_surname = '$governor_surname',
                governor_type_code = '$governor_type_code',
                governor_role_code = '$governor_role_code',
                governor_responsibilities = '$governor_responsibilities',
                governor_telephone_number = '$governor_telephone_number',
                governor_email_address = '$governor_email_address',
                governor_postal_address = '$governor_postal_address',
                governor_appointment_date = '$governor_appointment_date',
                governor_term_of_office = '$governor_term_of_office',
                governor_business_interests = '$governor_business_interests'
            WHERE governor_id = '$governor_id';";

    $result = sql_result_for_location($sql, 9);

    echo "Update succeeded";
}

#####################  delete_governor ####################

if ($helper_type == "delete_governor") {

    $governor_id = $_POST['governor_id'];

    $result = sql_result_for_location('START TRANSACTION', 10); // сбой sql после этого момента инициирует откат

    $sql = "DELETE FROM governors
            WHERE
                governor_id = '$governor_id';";

    $result = sql_result_for_location($sql, 11);

    $sql = "DELETE FROM governor_meeting_attendances
            WHERE
                governor_id = '$governor_id';";

    $result = sql_result_for_location($sql, 12);

    $result = sql_result_for_location('COMMIT', 13);

    echo "Deletion succeeded";
}

#####################  reorder_governors ####################

if ($helper_type == "reorder_governors") {

    $sequenced_governor_ids_json = $_POST['sequenced_governor_ids_json'];

    // превратяем json обратно в массив governor_ids

    $governor_ids = json_decode($sequenced_governor_ids_json, true);

    for ($i = 0; $i < count($governor_ids); $i++) {

        $sql = "UPDATE governors
                SET
                    display_sequence = '$i'
                WHERE
                    governor_id = '$governor_ids[$i]'";

        $result = sql_result_for_location($sql, 14);
    }

    echo "Изменение порядка выполнено успешно";
}

#####################  build_clerk_update_screen ####################

if ($helper_type == "build_clerk_update_screen") {

    //  Система создаст пустую запись сотрудника в none exists, так что получяем все, что угодно
    // данные доступны и отображают их для обновления

    $sql = "SELECT * FROM clerks
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 15);

    $row = mysqli_fetch_array($result);
    $clerk_first_names = $row['clerk_first_names'];
    $clerk_surname = $row['clerk_surname'];
    $clerk_postal_address = $row['clerk_postal_address'];
    $clerk_telephone_number = $row['clerk_telephone_number'];
    $clerk_email_address = $row['clerk_email_address'];

    $return = "

<h2 style='text-align: center;'>Форма сотрудника</h2>
<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>

   <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-outline-dark' style='position: absolute; right: 0;'
            title='Верняемсь к экрану управления'
            onmousedown='displayGovernorsScreen(\"\");'>Назад
        </button>
    </div>

    <form id='clerkdata' method = 'POST'>

        <label for='clerkfirstnames'>Имя сотрудника : </label>
        <input type='text' id ='clerkfirstnames' name ='clerkfirstnames' maxlength=20 size=20
               autocomplete='off' value=$clerk_first_names
               title='Имя сотрудника '>
        <p id = 'clerkfirstnameserror'></p>

        <label for='clerksurname'>Фамилия сотрудника : </label>
        <input type='text' id ='clerksurname' name ='clerksurname' maxlength=20 size=20
               autocomplete='off' value='$clerk_surname'
               title='Фамилия сотрудника'>
        <p id = 'clerksurnameerror'></p>

        <label for='clerkpostaladdress'>Почтовый адрес : </label>
        <input type='text' id ='clerkpostaladdress' name ='clerkpostaladdress' maxlength=60 size=60
               autocomplete='off' value='$clerk_postal_address'
               title='Почтовый адрес для клерка в виде строки свободного формата'>
        <p id = 'clerkpostaladdresserror'></p>

        <label for='clerktelephonenumber'>Номер телефона : </label>
        <input type='text' id ='clerktelephonenumber' name ='clerktelephonenumber' maxlength=40 size=40
               autocomplete='off' value='$clerk_telephone_number '
               title='Номер телефона' maxlength=40 size=40>
        <p id = 'clerktelephonenumbererror'></p>

        <label for='clerkemailaddress'>Email : </label>
        <input type='text' id ='clerkemailaddress' name ='clerkemailaddress' maxlength=40 size=40
               autocomplete='off' value='$clerk_email_address'
               title='Email'>
        <p id = 'clerkemailaddresserror'></p>

    </form>

    <div id = 'clerkupdatebuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'clerkupdatebutton'  type='button' class='btn-sm btn-info' style = 'margin-left: 2vw;'
                title='Обновяем запись для сотрудника в этой организации'
                onmousedown='updateClerk();'>Обновить
        </button>
        <button id = 'clerkcancelbutton'  type='button' class='btn-sm btn-secondary' style = 'margin-left: 2vw;'
                title='Отменить обновление'
                onmousedown='displayGovernorsScreen(\"Обновление отменено\");'>Отменить
        </button>
    </div>";

    echo $return;
}

#####################  update_clerk ####################

if ($helper_type == "update_clerk") {

    $clerk_first_names = $_POST['clerkfirstnames'];
    $clerk_surname = $_POST['clerksurname'];
    $clerk_postal_address = $_POST['clerkpostaladdress'];
    $clerk_telephone_number = $_POST['clerktelephonenumber'];
    $clerk_email_address = $_POST['clerkemailaddress'];

    $sql = "UPDATE clerks
            SET
                clerk_first_names = '$clerk_first_names',
                clerk_surname = '$clerk_surname',
                clerk_postal_address = '$clerk_postal_address',
                clerk_telephone_number = '$clerk_telephone_number',
                clerk_email_address = '$clerk_email_address'
            WHERE school_id = '$school_id';";

    $result = sql_result_for_location($sql, 16);

    echo "Update succeeded";
}


#####################  build_attendances_update_screen ####################

if ($helper_type == "build_attendances_update_screen") {

    $first_meeting_date = $_POST['first_meeting_date'];
    $last_meeting_date = $_POST['last_meeting_date'];

    // получить даты всех собраний для этой школы в виде массива

    $sql = "SELECT meeting_date FROM meetings
            WHERE
                school_id = '$school_id'
            ORDER BY meeting_date ASC";

    $result = sql_result_for_location($sql, 17);

    $meeting_dates = array();

    $i = 0;
    while ($row = mysqli_fetch_array($result)) {
        $meeting_dates[$i] = $row['meeting_date'];
        $i++;
    }

    //запиши имена всех губернаторов, они нам понадобятся позже

    $sql = "SELECT
                governor_id,
                governor_first_names,
                governor_surname
            FROM governors
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 18);

    $governors = array();

    while ($row = mysqli_fetch_array($result)) {
        $governor_id = $row['governor_id'];
        $governors[$governor_id]['governor_first_names'] = $row['governor_first_names'];
        $governors[$governor_id]['governor_surname'] = $row['governor_surname'];
        $governor_name = $governors[$governor_id]['governor_first_names'] . " " . $governors[$governor_id]['governor_surname'];
    }

    // если диапазон дат собрания не указан, выбераем последние 4 собрания

    if ($first_meeting_date == '') {
        $first_meeting_date_index = max(0, count($meeting_dates) - 4);
        $last_meeting_date_index = count($meeting_dates) - 1;
        $first_meeting_date = $meeting_dates[$first_meeting_date_index];
        $last_meeting_date = $meeting_dates[$last_meeting_date_index];
    } else {
        $first_meeting_date_index = array_search($first_meeting_date, $meeting_dates, true);
        $last_meeting_date_index = array_search($last_meeting_date, $meeting_dates, true);
    }

    $sql = "SELECT
                    governor_id,
                    meeting_date,
                    governor_present
            FROM governor_meeting_attendances
            WHERE
                school_id = '$school_id' AND
                meeting_date >= '$first_meeting_date' AND
                meeting_date <= '$last_meeting_date'
            ORDER BY governor_id,meeting_date ASC";

    $result = sql_result_for_location($sql, 19);

    $governor_attendances = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_id = $row['governor_id'];
        $meeting_date = $row['meeting_date'];
        $governor_present = $row['governor_present'];
        $governor_attendances[$governor_id][$meeting_date] = $governor_present;
    }

    $historic_meetings_update_block = "

    <div id='historicmeetingsblock' style = 'background: white; padding-top: 2vh;'>
        <form id = 'historic_meetings' method = 'POST'>
            <div>";


    $historic_meetings_update_block .= "<p style= 'display: inline-block; width: 15rem; text-align: center;'></p>";

    for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {

        $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center; font-weight: bold;'>$meeting_dates[$i]</p>";
    }
    $historic_meetings_update_block .= "
            </div>
            <div>";

    foreach ($governor_attendances as $key => $list) {

        $governor_id = $key;
        $governor_name = $governors[$governor_id]['governor_first_names'] . " " . $governors[$governor_id]['governor_surname'];
        $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 15rem; text-align: left; padding-left:2rem;'>
                    $governor_name
                </p>";


        for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {
            $meeting_date = $meeting_dates[$i];
            if (array_key_exists($meeting_date, $list)) {

                // Если ключ присутствует, мы можем быть уверены, что этот губернатор должен был присутствовать
                // на этом собрании и, таким образом, может предложить флажок обновления с соответствующей настройкой.
                // Если ключ отсутствует, просто отобразяем здесь пробел

                $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <span class = 'governor$meeting_date' style ='display: none;'>$governor_id</span>
                    <input class = 'attendancecheckbox$meeting_date' type='checkbox' id='historicmeetingcheckbox%$governor_id%$meeting_date' name='historicmeetingcheckbox%$governor_id%$meeting_date'";

                $governor_present = $governor_attendances[$governor_id][$meeting_date];
                if ($governor_present == "Y") {
                    $historic_meetings_update_block .= "
                        value = 'Y' checked>";
                } else {
                    $historic_meetings_update_block .= "
                    value = 'N'>";
                }
                $historic_meetings_update_block .= "
                    </p>";
            } else {
                $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center;'></p>";
            }
        }
        $historic_meetings_update_block .= "
            </div>
            <div>";
    }

    // добавить строки кнопок обновления/удаления в нижней части блока historicmeetingsblock

    $historic_meetings_update_block .= "<p style= 'display: inline-block; width: 15rem; text-align: center;'></p>";

    for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {
        $historic_meetings_update_block .= "
                <p = 'historicmeetingupdatebuttons' style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <button id = 'historicmeetingupdatebutton%$i'  type='button' class='btn-sm btn-info'
                        title='Обновить запись этого собрания'
                        onmousedown='updateMeeting(\"$meeting_dates[$i]\");'>Обновить
                    </button>
                </p>";
    }
    $historic_meetings_update_block .= "
            </div>
            <div>";

    $historic_meetings_update_block .= "<p style= 'display: inline-block; width: 15rem; text-align: center;'></p>";

    for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {
        $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <button id = 'historicmeetingdeletebutton%$i'  type='button' class='btn-sm btn-danger'
                        title='Удалить эту запись собрания'
                        onmousedown='deleteMeeting(\"$meeting_dates[$i]\");'>Удалить
                    </button>
                </p>";
    }
    $historic_meetings_update_block .= "
            </div>";

    $historic_meetings_update_block .= "
        </form>
    </div>";


    // теперь создаем боковые панели для блока historic_meetings_update_block, чтобы переместить окно отображения вверх
    // и вниз по исторической последовательности

    $historic_meetings_left_sidebar = "
    <p style= 'display: inline-block; width: 2rem; text-align: center;'>";

    // отображать кнопку только в том случае, если для нее есть что делать!

    if ($first_meeting_date_index != 0) {
        $next_first_meeting_date_index = $first_meeting_date_index - 1;
        $next_last_meeting_date_index = $last_meeting_date_index - 1;

        $historic_meetings_left_sidebar .= "
        <button style = 'border: none;'
            onclick='displayMeetingsScreen(\"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\",\"\",\"\")'>
            <span class = 'oi oi-caret-left'></span>
        </button>";
    } else {
        $historic_meetings_left_sidebar .= "";
    }
    $historic_meetings_left_sidebar .= "
    </p>";

    $historic_meetings_right_sidebar = "
    <p style= 'display: inline-block; width: 2rem; text-align: center;'>";

    // отображать кнопку только в том случае, если для нее есть что делать!

    if ($last_meeting_date_index != count($meeting_dates) - 1) {
        $next_first_meeting_date_index = $first_meeting_date_index + 1;
        $next_last_meeting_date_index = $last_meeting_date_index + 1;

        $historic_meetings_right_sidebar .= "
        <button style = 'border: none;'
            onclick = 'displayMeetingsScreen(\"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\",\"\",\"\")'>
            <span class = 'oi oi-caret-right'></span>
        </button>";
    } else {
        $historic_meetings_right_sidebar .= "";
    }
    $historic_meetings_right_sidebar .= "
    </p>";

    $sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 20);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    $return = "
    <h2 style='text-align: center;'>Участие в собрании для '" . $school_name . "'</h2>
    <div style='text-align: center; margin-bottom:4vh;'>
        <button id = 'insertmeetingbutton'  type='button' class='btn-sm btn-primary mt-4 mb-4'
            title='Создаем новое собрание и связанные с ним записи о посещаемости'>Добавить новое собрание для :&nbsp;
                    <input type='text' id ='insertmeetingdate' name ='insertmeetingdate' size=10rem
                           style=' text-align: center; font-weight: bold;'
                           autocomplete='off'
                           value = ''
                           title='Данные, записанные в настоящее время в отношении этой встречи'
                           onmousedown='applyDatepicker(\"insertmeetingdate\");'
                           onchange='displayMeetingInsertScreen();'>
        </button>
        <span id='messagearea'></span
    </div>";

    // Блок historic_meeting_update_block теперь должен отображаться между его боковыми панелями
    // Какое-то расположение столов кажется лучшим способом продвижения вперед. Поскольку мы, по сути, только
    // есть одна строка для отображения, предпочтяемльнее flexbox

    $return .= "
    <div style='display:flex; justify-content: center;'>
        <div>$historic_meetings_left_sidebar</div>
        <div>$historic_meetings_update_block</div>
        <div>$historic_meetings_right_sidebar</div>
    </div>";
    // добавить последнюю кнопку для отображения просмотра посещаемости веб-сайта губернатора

    $return .= "
        <div style='text-align: left;'>
            <button  type='button' class='ml-2 mt-4 mr-2 btn-sm btn-primary'
                title='Отобразить ссылку на просмотр веб-страницы посещаемости губернатора'
                onmousedown='grabClipboardLink(\"pages.html?id=$school_id&target=attendances\");'>Получить Ссылку на Посещение
            </button>
        </div>";


    echo $return;
}

#####################  build_meeting_insert_table ####################

if ($helper_type == "build_meeting_insert_table") {

    $meeting_date = $_POST['meeting_date'];

    // назначляем губернаторов на должность в $meeting_date

    $sql = "SELECT
                governor_id,
                governor_first_names,
                governor_surname
            FROM
                governors
            WHERE
                school_id = '$school_id' AND
                governor_appointment_date <= 'meeting_date' AND
                DATE_ADD(governor_appointment_date, INTERVAL governor_term_of_office YEAR) >='$meeting_date'
                ORDER BY governor_id ASC;";

    $result = sql_result_for_location($sql, 21);

    $governors = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_id = $row['governor_id'];
        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_name = "$governor_first_names $governor_surname";
        $governors[] = ['governor_id' => $governor_id, 'governor_name' => $governor_name];
    }

    //Создаем таблицу обновлений в виде пар имен управляющих и соответствующих флажков посещаемости


    $return = "

<h2 style='text-align: center;'>Meeting and Attendance Insert Form</h2>

<div style = 'width: 50%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>
    <form id='meetinginsertdata' method = 'POST'>
        <div style = 'margin-top: 2vh; text-align: center;'>
                <p style= 'display: inline-block; width: 15rem; text-align: center;'></p>
                <p style= 'display: inline-block; width: 10rem; text-align: center; font-weight: bold;'>$meeting_date</p>
        </div>";

    for ($i = 0; $i < count($governors); $i++) {

        $governor_id = $governors[$i]['governor_id'];
        $governor_name = $governors[$i]['governor_name'];

        $return .= "
            <div style= 'text-align: center;'>
                <p style= 'display: inline-block; width: 15rem; text-align: center;'>$governor_name</p>
                <span class = 'governor' style ='display: none;'>$governor_id</span>
                <p style= 'display: inline-block; width: 10rem; text-align: center;'>
                    <input class = 'attendancecheckbox' type='checkbox' id='newmeetingcheckbox$governor_id' name='newmeetingcheckbox%$governor_id%$meeting_date'
                        title = 'Check this box to signify attendance'>
                </p>
            </div>";
    }

    // добавить кнопку вставки

    $return .= "
    </form>
    <div style= 'text-align: center;'>
        <p style= 'display: inline-block; width: 15rem; text-align: center;'></p>
        <p style= 'display: inline-block; width: 10rem; text-align: center;'>
            <button type='button' class='btn-sm btn-info'
                title='Вставить этот отчет о собрании'
                onmousedown='insertMeeting(\"$meeting_date\");'>Добавить
            </button>
            <button type='button' class='btn-sm btn-secondary ml-4'
                title='Отменить вставку'
                onmousedown='displayMeetingsScreen(\"\", \"\", \"\", \"\");'>Отменить
            </button>
        </p>
    </div>
</div>";

    echo $return;
}

#####################  insert_meeting ####################

if ($helper_type == "insert_meeting") {

    $meeting_date = $_POST['meeting_date'];
    $attendances_count = $_POST['attendances_count'];

    $attendances = array();
    $governors = array();
    $checkbox_valuees = array();

    for ($i = 0; $i < $attendances_count; $i++) {
        $attendances[$i] = $_POST["attendance_$i"];
        $pieces = explode("%", $attendances[$i]);
        $governors[$i] = $pieces[0];
        $checkbox_values[$i] = $pieces[1]; // флажок имеет значение "Y", если присутствует регулятор, "N" в противном случае
    }

    // сначала создаем запись собрания, а затем создаем governor_attendances

    $result = sql_result_for_location('START TRANSACTION', 22); // сбой sql после этого момента инициирует откат

    $sql = "INSERT INTO meetings (
                school_id,
                meeting_date,
                meeting_type)
            VALUES (
                '$school_id',
                '$meeting_date',
                '');";

    $result = sql_result_for_location($sql, 23);

    // а теперь губернатор_подчинения

    for ($i = 0; $i < $attendances_count; $i++) {

        $sql = "INSERT INTO governor_meeting_attendances (
                school_id,
                meeting_date,
                governor_id,
                governor_present)
            VALUES (
                '$school_id',
                '$meeting_date',
                '$governors[$i]',
                '$checkbox_values[$i]');";

        $result = sql_result_for_location($sql, 24);
    }

    $result = sql_result_for_location('COMMIT', 25);

    echo "Встреча добавлена успешно!";
}

#####################  test_for_unique_meeting_date ####################

if ($helper_type == "test_for_unique_meeting_date") {

    $meeting_date = $_POST['meeting_date'];

    $sql = "SELECT * FROM meetings
            WHERE
                school_id = '$school_id' AND
                meeting_date = '$meeting_date';";

    $result = sql_result_for_location($sql, 26);

    $row = mysqli_fetch_assoc($result);
    if (mysqli_num_rows($result) >= 1) {
        echo "дубликат";
    } else {
        echo "уникальный";
    }
}

#####################  update_meeting ####################

if ($helper_type == "update_meeting") {

    $meeting_date = $_POST['meeting_date'];
    $attendances_count = $_POST['attendances_count'];

    $attendances = array();
    $governors = array();
    $checkbox_valuees = array();

    for ($i = 0; $i < $attendances_count; $i++) {
        $attendances[$i] = $_POST["attendance_$i"];
        $pieces = explode("%", $attendances[$i]);
        $governors[$i] = $pieces[0];
        $checkbox_values[$i] = $pieces[1]; // флажок имеет значение "Y", если присутствует регулятор, "N" в противном случае
    }

    // изменить governor_attendances

    $result = sql_result_for_location('START TRANSACTION', 27); // сбой sql после этого момента инициирует откат

    for ($i = 0; $i < $attendances_count; $i++) {

        $sql = "UPDATE governor_meeting_attendances
                SET
                    governor_present = '$checkbox_values[$i]'
                WHERE
                    school_id = '$school_id' AND
                    meeting_date = '$meeting_date'AND
                    governor_id = '$governors[$i]';";

        $result = sql_result_for_location($sql, 28);
    }

    $result = sql_result_for_location('COMMIT', 29);

    echo "Собрание успешно обновлено";
}

#####################  delete_meeting ####################

if ($helper_type == "delete_meeting") {

    $meeting_date = $_POST['meeting_date'];

    $result = sql_result_for_location('START TRANSACTION', 30); //сбой sql после этого момента инициирует откат

    $sql = "DELETE FROM meetings
            WHERE
                school_id = '$school_id' AND
                meeting_date = '$meeting_date';";

    $result = sql_result_for_location($sql, 31);


    $sql = "DELETE FROM governor_meeting_attendances
            WHERE
                meeting_date = '$meeting_date';";

    $result = sql_result_for_location($sql, 32);

    $result = sql_result_for_location('COMMIT', 33);

    echo "Собание успешно удалено!";
}

#####################  build_documents_update_table ####################

if ($helper_type == "build_documents_update_table") {

    $sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 34);

    $row = mysqli_fetch_array($result);
    $school_name = $row['school_name'];

    $return = "
    <style>

        tr.striped:nth-child(even) { /* used to stripe and space membership table rows */
            background-color: gainsboro;
        }

        th, td { /* th and td cells */
            padding: .5rem;
        }

    </style>

    <h2 style='text-align: center;'>Документы для $school_name</h2>
    <p id = 'messagearea' style = 'text-align: center; padding-top: .5vh; padding-bottom: .5vh; margin-top: 0; margin-bottom: 0;'></p>
    <div style='text-align: center; margin-bottom:4vh;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-primary mb-2'
            title='Создаем совершенно новый документ'
            onmousedown='displayDocumentInsertScreen();'>Загрузить новый документ
        </button>
    </div>";


    $return .= "
    <div style = 'width: 98%; padding: 1vh; margin-left:auto; margin-right: auto; background: white;'>
        <table style='width: 100%; padding: .5vh; margin-left:auto; margin-right: auto; background: white;'>
            <tr>
                <th style = 'width: 4%;'></th>
                <th style = 'width: 4%;'></th>
                <th style = 'width: 10%;'>Название документа</th>
                <th style='width: 5%; text-align: center; max-width: 5rem;'>Автор</th>
                <th style='width: 3%; text-align: center;'>Номер версиси</th>
                <th style='width: 5%; text-align: center;'>Дата <br>выпуска</th>
                <th style='width: 5%; text-align: center;'>Последний просмотр<br>(дата)</th>
                <th style='width: 7%;'></th>
                <th style='width: 3%;'></th>
                <th style='width: 3%;'></th>
            </tr>";

    $sql = "SELECT
                a.school_id,
                a.document_title,
                a.version_number,
                a.document_author,
                a.document_issue_date,
                a.version_last_review_date
            FROM documents a
            INNER JOIN (
            SELECT
		school_id,
                document_title,
                MAX(version_number) as version_number
            FROM documents
            WHERE school_id = '$school_id'
            GROUP BY school_id, document_title) b ON
                a.school_id = b.school_id AND
                a.document_title = b.document_title AND
                a.version_number = b.version_number
            ORDER BY a.document_title ASC";

    $result = sql_result_for_location($sql, 35);

    $i = 0;

    while ($row = mysqli_fetch_array($result)) {

        $i++;

        $document_title = $row['document_title'];
        $document_author = $row['document_author'];
        $max_version_number = $row['version_number'];
        $document_issue_date = $row['document_issue_date'];
        $version_last_review_date = $row['version_last_review_date'];

        $return .= "
                <tr id = 'row$i' class = 'striped'>";

        $return .= build_documents_update_table_row(
            $i,
            $school_id,
            $document_title,
            $max_version_number,
            $max_version_number,
            $document_author,
            $document_issue_date,
            $version_last_review_date
        );

        $return .= "
                </tr>";
    }

    $return .= "
        </table>
    </div>";

    echo $return;
}

#####################  build_document_insert_screen ####################

if ($helper_type == "build_document_insert_screen") {

    $return = "

<h2 style='text-align: center;'>Новая Форма Ввода Документа</h2>

<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";

    // position a "Back" button at top right

    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-outline-dark' style='position: absolute; right: 0;'
            title='Верняемсь к экрану документов'
            onmousedown='displayDocumentsScreen(\"\");'>Назад
        </button>
    </div>

    <form id='documentdata' method = 'POST'>

        <label for='documenttitle'>Название документа : </label>
        <input type='text' id ='documenttitle' name ='documenttitle' maxlength=40 size=40
               autocomplete='off' value=''
               title='Введяем название документа'>
        <p id = 'documenttitleerror'></p>

        <label for='documentauthor'>Автор : </label>
        <input type='text' id ='documentauthor' name ='documentauthor' maxlength=20 size=20
               autocomplete='off' value=''
               title='Введяем имя автора документа'>
        <p id = 'documentauthorerror'></p>

        <label for='documentissuedate'>Дата создания документа : </label>
        <input type='text' id ='documentissuedate' name ='documentissuedate' size=10
               autocomplete='off' value=''
               title='Введяем дату создания документа'
               onmousedown='applyDatepicker(\"documentissuedate\");'>
        <p id = 'documentissuedateeerror'></p>

        <div>
            <label for='documentsourcefilename'>Загрузить файл : </label>
            <input type='file' id='documentsourcefilename' name='documentsourcefilename'
                    accept='.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    title = 'Выберяем исходное имя файла текстового процессора'>
            <p id = 'documentsourcefilenameerror'></p>
        </divp>

        <div>
            <label for='documentpdffilename'>Прилагаемое имя файла pdf : </label>
            <input type='file' id='documentpdffilename' name='documentpdffilename'
                    accept='.pdf'
                    title = 'Выберяем pdf-копию исходного имени файла'>
            <p id = 'documentpdffilenameerror'></p>
        </div>

        </form>

    <div id = 'builddocumentbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-info' style = 'margin-left: 2vw;'
                title='Вставляем запись для этого документа'
                onmousedown='insertDocument();'>Загрузить
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-secondary' style = 'margin-left: 2vw;'
                title='Отменить'
                onmousedown='displayDocumentsScreen(\"Вставка отменена\");'>Отменить
        </button>
    </div>

</div>";

    echo $return;
}

#####################  insert_document ####################

if ($helper_type == "insert_document") {

    // если вы обнаружяем, что у вас уже есть документ с заданным названием, просто создаем новый
    // версия для него - обратяем внимание, что есть специальная кнопка для создания новой версии

    $document_title = $_POST['documenttitle'];
    $document_author = $_POST['documentauthor'];
    $document_issue_date = $_POST['documentissuedate'];
    $version_creation_date = date("Y-m-d");
    $version_last_review_date = $version_creation_date;

    //получяем текущий самый высокий номер версии для school_id и document_title

    $sql = "SELECT
                max(version_number)
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title';";

    $result = sql_result_for_location($sql, 36);
    $row = mysqli_fetch_assoc($result);
    if (mysqli_num_rows($result) >= 1) {
        $version_number = $row['max(version_number)'] + 1;
    } else {
        $version_number = 1;
    }

    $result = sql_result_for_location('START TRANSACTION', 37); // сбой sql после этого момента инициирует откат

    $sql = "INSERT INTO documents (
                school_id,
                document_title,
                version_number,
                document_author,
                document_issue_date,
                version_creation_date,
                version_last_review_date)
            VALUES (
                '$school_id',
                '$document_title',
                '$version_number',
                '$document_author',
                '$document_issue_date',
                '$version_creation_date',
                '$version_last_review_date');";

    $result = sql_result_for_location($sql, 38);

    // получить имена файлов

    $document_source_name = $_FILES["documentsourcefilename"]["name"];
    $source_pieces = explode(".", $document_source_name);
    $document_pdf_filename = $_FILES["documentpdffilename"]["name"];
    $pdf_pieces = explode(".", $document_pdf_filename);

    $upload_source_target = "../school_$school_id/$document_title" . "_VERSION_$version_number.$source_pieces[1]";
    $upload_pdf_target_1 = "../school_$school_id/$document_title" . "_VERSION_$version_number.pdf";
    $upload_pdf_target_2 = "../school_$school_id/$document_title" . "_LATEST.pdf";

    if (
        !move_uploaded_file($_FILES['documentsourcefilename']['tmp_name'], $upload_source_target) ||
        !move_uploaded_file($_FILES['documentpdffilename']['tmp_name'], $upload_pdf_target_1)
    ) {
        echo "Oops - Document upload %failed% in 'insert_document' of manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 39);
        exit(1);
    }

    // копировать до последней версии (нельзя снова использовать move_uploaded - вы "использовали tmp_name up")

    if (!copy($upload_pdf_target_1, $upload_pdf_target_2)) {
        echo "Упс - Ошибка копирования документа %failed% в 'insert_document' из manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 40);
        exit(1);
    }

    $result = sql_result_for_location('COMMIT', 41);
}

#####################  build_version_insert_screen ####################

if ($helper_type == "build_version_insert_screen") {

    $document_title = $_POST['document_title'];

    $return = "

<h2 style='text-align: center;'>Новая Версия <br>документа : $document_title </h2>
<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";

    // position a "Back" button at top right

    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-outline-dark' style='position: absolute; right: 0;'
            title='Верняемсь к экрану документов'
            onmousedown='displayDocumentsScreen(\"\");'>Назад
        </button>
    </div>

    <form id='versiondata' method = 'POST'>

        <label for='documentauthor'>Автор : </label>
        <input type='text' id ='documentauthor' name ='documentauthor' maxlength=20 size=20
               autocomplete='off' value=''
               title='Автор'>
        <p id = 'documentauthorerror'></p>

        <label for='documentissuedate'>Дата создания документа : </label>
        <input type='text' id ='documentissuedate' name ='documentissuedate' size=10
               autocomplete='off' value=''
               title='Дата создания документа'
               onmousedown='applyDatepicker(\"documentissuedate\");'>
        <p id = 'documentissuedateeerror'></p>

        <div>
            <label for='documentsourcefilename'>Загрузить файл : </label>
            <input type='file' id='documentsourcefilename' name='documentsourcefilename'
                    accept='.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    title = 'Загрузить файл'>
            <p id = 'documentsourcefilenameerror'></p>
        </divp>

        <div>
            <label for='documentpdffilename'>Прилагаемое имя файла pdf : </label>
            <input type='file' id='documentpdffilename' name='documentpdffilename'
                    accept='.pdf'
                    title = 'Прилагаемое имя файла pdf'>
            <p id = 'documentpdffilenameerror'></p>
        </div>

        </form>

    <div id = 'builddocumentbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'insertbutton'  type='button' class='btn-sm btn-info' style = 'margin-left: 2vw;'
                title='Вставляем запись для этого документа'
                onmousedown='insertVersion(\"$document_title\");'>Вставить
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-secondary' style = 'margin-left: 2vw;'
                title='Отменить'
                onmousedown='displayDocumentsScreen(\"Insert cancelled\");'>Отменить
        </button>
    </div>

</div>";

    echo $return;
}

#####################  insert_version ####################

if ($helper_type == "insert_version") {

    $document_title = $_POST['document_title'];
    $document_author = $_POST['documentauthor'];
    $document_issue_date = $_POST['documentissuedate'];
    $version_creation_date = date("Y-m-d");
    $version_last_review_date = $version_creation_date;

    // получяем текущий самый высокий номер версии для school_id и document_title

    $sql = "SELECT
                max(version_number)
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title'
            GROUP BY document_title;";

    $result = sql_result_for_location($sql, 42);
    $row = mysqli_fetch_assoc($result);
    $version_number = $row['max(version_number)'] + 1;


    $result = sql_result_for_location('START TRANSACTION', 43); // сбой sql после этого момента инициирует откат

    $sql = "INSERT INTO documents (
                school_id,
                document_title,
                version_number,
                document_author,
                document_issue_date,
                version_creation_date,
                version_last_review_date)
            VALUES (
                '$school_id',
                '$document_title',
                '$version_number',
                '$document_author',
                '$document_issue_date',
                '$version_creation_date',
                '$version_last_review_date');";

    $result = sql_result_for_location($sql, 44);

    // получить имена файлов

    $document_source_name = $_FILES["documentsourcefilename"]["name"];
    $source_pieces = explode(".", $document_source_name);
    $document_pdf_filename = $_FILES["documentpdffilename"]["name"];
    $pdf_pieces = explode(".", $document_pdf_filename);

    $upload_source_target = "../school_$school_id/$document_title" . "_VERSION_$version_number.$source_pieces[1]";
    $upload_pdf_target_1 = "../school_$school_id/$document_title" . "_VERSION_$version_number.pdf";
    $upload_pdf_target_2 = "../school_$school_id/$document_title" . "_LATEST.pdf";

    if (
        !move_uploaded_file($_FILES['documentsourcefilename']['tmp_name'], $upload_source_target) ||
        !move_uploaded_file($_FILES['documentpdffilename']['tmp_name'], $upload_pdf_target_1)
    ) {
        echo "Упс - Загрузка документа %failed% в 'insert_version' из manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 45);
        exit(1);
    }

    if (!copy($upload_pdf_target_1, $upload_pdf_target_2)) {
        echo "Упс - Копирование документа %failed% в 'insert_version' из manager_helpers";
        $result = sql_result_for_location('ROLLBACK', 46);
        exit(1);
    }

    $result = sql_result_for_location('COMMIT', 47);

    echo "upload succeedeed";
}

#####################  reset_documents_update_table_row ####################

if ($helper_type == "reset_documents_update_table_row") {

    $row_number = $_POST['row_number'];
    $document_title = $_POST['document_title'];
    $version_number = $_POST['version_number'];
    $max_version_number = $_POST['max_version_number'];

    $sql = "SELECT
                document_author,
                document_issue_date,
                version_last_review_date
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title' AND
                version_number = '$version_number';";

    $result = sql_result_for_location($sql, 48);
    $row = mysqli_fetch_assoc($result);

    $document_author = $row['document_author'];
    $document_issue_date = $row['document_issue_date'];
    $version_last_review_date = $row['version_last_review_date'];

    $return = build_documents_update_table_row(
        $row_number,
        $school_id,
        $document_title,
        $version_number,
        $max_version_number,
        $document_author,
        $document_issue_date,
        $version_last_review_date
    );

    echo $return;
}

#####################  build_document_review_screen ####################

if ($helper_type == "build_document_review_screen") {

    $document_title = $_POST['document_title'];
    $max_version_number = $_POST['max_version_number'];
    // получяем название и последнюю версию документа и отобразяем экран, позволяющий вам изменить
    // автора, дату выпуска и дату проверки

    $sql = "SELECT
                document_author,
                document_issue_date,
                version_last_review_date
            FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title' AND
                version_number = '$max_version_number';";

    $result = sql_result_for_location($sql, 49);

    $row = mysqli_fetch_assoc($result);
    $document_author = $row['document_author'];
    $document_issue_date = $row['document_issue_date'];
    $version_last_review_date = $row['version_last_review_date'];


    $return = "

<h2 style='text-align: center;'>Обновить версию<br>документа : $document_title</h2>

<div style = 'width: 90%; padding: 5vh; margin: 5vh auto 0 auto; background: white;'>";


    $return .= "

    <div style='display: flex; justify-content: center; position: relative;'>
            <button id = 'backbutton'  type='button' class='btn-sm btn-outline-dark' style='position: absolute; right: 0;'
            title='Назад'
            onmousedown='displayDocumentsScreen(\"\");'>Назад
        </button>
    </div>

    <form id='documentdata' method = 'POST'>

        <label for='documentauthor'>Автор : </label>
        <input type='text' id ='documentauthor' name ='documentauthor' maxlength=20 size=20
               placeholder = '$document_author'
               value = '$document_author'
               autocomplete='off' value=''
               title='Автор'>
        <p id = 'documentauthorerror'></p>

        <label for='documentissuedate'>Дата создания документа : </label>
        <input type='text' id ='documentissuedate' name ='documentissuedate' size=10
               placeholder = '$document_issue_date'
               value = '$document_issue_date'
               autocomplete='off' value=''
               title='Дата создания документа'
               onmousedown='applyDatepicker(\"documentissuedate\");'>
        <p id = 'documentissuedateeerror'></p>

        <label for='versionlastreviewdate'>Дата последнего просмотра : </label>
        <input type='text' id ='versionlastreviewdate' name ='versionlastreviewdate' size=10
               placeholder = '$version_last_review_date'
               value = '$version_last_review_date'
               autocomplete='off' value=''
               title='Дата последнего просмотра'
               onmousedown='applyDatepicker(\"versionlastreviewdate\");'>
        <p id = 'versionlastreviewdateerror'></p>

    </form>

    <div id = 'builddocumentbuttons' style = 'width: 90%; margin: 4vh auto 2vh auto; text-align: center;'>
        <button id = 'updatebutton'  type='button' class='btn-sm btn-info' style = 'margin-left: 2vw;'
                title='Обновить'
                onmousedown='updateDocument($school_id, \"$document_title\", $max_version_number);'>Обновить
        </button>
        <button id = 'cancelbutton'  type='button' class='btn-sm btn-secondary' style = 'margin-left: 2vw;'
                title='Отменить вставку'
                onmousedown='displayDocumentsScreen(\"Insert cancelled\");'>Отменить
        </button>
    </div>

</div>";

    echo $return;
}

#####################  update_document ####################

if ($helper_type == "update_document") {

    $document_title = $_POST['document_title'];
    $document_author = $_POST['document_author'];
    $document_issue_date = $_POST['document_issue_date'];
    $max_version_number = $_POST['max_version_number'];

    $version_last_review_date = $_POST['version_last_review_date'];

    $sql = "UPDATE documents
            SET
                document_author = '$document_author',
                document_issue_date = '$document_issue_date',
                version_last_review_date = '$version_last_review_date'
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title' AND
                version_number = '$max_version_number';";

    $result = sql_result_for_location($sql, 50);

    echo 'Документ обновлен';
}

#####################  delete_document ####################

if ($helper_type == "delete_document") {

    $document_title = $_POST['document_title'];
    $max_version_number = $_POST['max_version_number'];

    $result = sql_result_for_location('START TRANSACTION', 61);

    $sql = "DELETE FROM documents
            WHERE
                school_id = '$school_id' AND
                document_title = '$document_title';";

    $result = sql_result_for_location($sql, 52);


    for ($i = 1; $i <= $max_version_number; $i++) { // grr - не может  delete ".*"
        $files = glob("../documents/school_$i/" . $document_title . "_VERSION_$i.*"); // то же, что и scandir, но не получает "." и..."
        foreach ($files as $file) {
            if (!unlink($file)) {
                echo "Упс! Ошибка удаления  %%failed%% в delete_document.";
                $result = sql_result_for_location('ROLLBACK', 53);
                exit(1);
            }
        }
    }

    $result = sql_result_for_location('COMMIT', 54);

    echo "Удаление прошло успешно";
}



disconnect_from_database();
