<?php

require('../includes/governor_functions.php');

# Вспомагательный раздел helper_type :
#
# 'build_governing_body_display'            -  код возврата для отображения сводного представления руководящего органа
#                                               для данного раздела в качестве страницы для использования на веб-сайте
#
# 'build_governing_body_interests_display'  -  код возврата для отображения сводного представления о  сотруднике
#
# 'build_goveror_attendance_display'        -  код возврата для отображения сводного представления посещаемости

$page_title = 'page_helpers';

# установите заголовки ТАК, чтобы страница НЕ кэшировалась
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Дата в прошлом

date_default_timezone_set('Europe/Moscow');

// подключение к базе данных губернаторов

connect_to_database();

// получаем helper-request

$helper_type = $_POST['helper_type'];
$school_id = $_POST['school_id'];

$sql = "SELECT
                school_name
            FROM
                schools
            WHERE
                school_id = '$school_id'";

$result = sql_result_for_location($sql, 1);

$row = mysqli_fetch_array($result);
$school_name = $row['school_name'];

#####################  build_governing_body_display ####################

if ($helper_type == "build_governing_body_display") {

    $return = "
        <h2 style='padding-top: 3vh; text-align: center;'>Сотрудники $school_name</h2>";

    // получаем ассоциативный массивы для "декодированных" типов управляющих и ролей управляющих

    $governor_types = get_governor_types();
    $governor_roles = get_governor_roles();

    //теперь создаем строку отображения для каждого определенного в данный момент 

    $sql = "SELECT *
            FROM
                governors
            WHERE
                school_id = '$school_id'
            ORDER BY
                display_sequence";

    $result = sql_result_for_location($sql, 2);

    $return .= "
        <style>

        th {
            padding: 1vw;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 1vw;
            vertical-align:top;
        }

        </style>

        <table style='margin-left: auto; margin-right: auto;'>
            <tr>
                <th>Роль</th>
                <th>Имя</th>
                <th>Адрес</th>
                <th>Тип раздела</th>
                <th>Обязанности</th>
                <th style='text-align: center;'>Срок полномочий<br>(заканчивается)</th>
            </tr>";

    // создаем строку таблицы для каждого управляющего

    while ($row = mysqli_fetch_array($result)) {

        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_postal_address = $row['governor_postal_address'];
        $governor_telephone_number = $row['governor_telephone_number'];
        $governor_email_address = $row['governor_email_address'];

        // приоритет для отображения контактного адреса - электронная почта -> почта -> телефон

        if ($governor_email_address != '') {
            $governor_contact_address = $governor_email_address;
        } else {
            if ($governor_postal_address != '') {
                $governor_contact_address = $governor_postal_address;
            } else {
                $governor_contact_address = $governor_telephone_number;
            }
        }

        $governor_name = "$governor_first_names $governor_surname";
        $governor_type = $governor_types[$row['governor_type_code']];
        $governor_role = $governor_roles[$row['governor_role_code']];

        if ($governor_role == "n/a")
            $governor_role = '';

        $governor_responsibilities = $row['governor_responsibilities'];
        $governor_appointment_date = $row['governor_appointment_date'];
        $governor_term_of_office = $row['governor_term_of_office'];
        $governor_retirement_date = date('Y-m-d', strtotime(" + $governor_term_of_office years", strtotime($governor_appointment_date)));

        $return .= "
            <tr>
                <td>$governor_role</td>
                <td>$governor_first_names $governor_surname</td>
                <td style='max-width: 25rem;'>$governor_contact_address</td>
                <td>$governor_type</td>
                <td style='min-width: 15rem;'>$governor_responsibilities</td>
                <td style='text-align: center;'>$governor_retirement_date</td>
            </tr>";
    }

    $return .= "
         </table>";

    //Теперь создайте строку для сотрудника

    $sql = "SELECT *
            FROM
                clerks
            WHERE
                school_id = '$school_id';";

    $result = sql_result_for_location($sql, 3);

    $clerk_first_names = '';
    $clerk_surname = '';
    $clerk_email_address = '';

    $row = mysqli_fetch_array($result);

    if (mysqli_num_rows($result) >= 1) {
        $clerk_first_names = $row['clerk_first_names'];
        $clerk_surname = $row['clerk_surname'];
        $clerk_email_address = $row['clerk_email_address'];
    }

    $return .= "
        <div style = 'text-align: center; margin: 2vh 0 2vh 0; font-weight: bold;'>
            <span>Сотрудник : </span>
            <span>$clerk_first_names $clerk_surname : </span>
            <span>$clerk_email_address</span>
        </div>";

    echo $return;
}

#####################  build_governing_body_interests_display ####################

if ($helper_type == "build_governing_body_interests_display") {

    $return = "
        <h2 style='padding-top: 3vh; text-align: center;'>Деловые интересы губернатора для $school_name</h2>";

    //получаем ассоциативный массив для "декодированных" типов управляющих и ролей управляющих

    $governor_types = get_governor_types();
    $governor_roles = get_governor_roles();

    // теперь создаем строку отображения для каждого определенного в данный момент 

    $sql = "SELECT *
            FROM
                governors
            WHERE
                school_id = '$school_id'
            ORDER BY
                display_sequence";

    $result = sql_result_for_location($sql, 2);

    $return .= "
        <style>

        th {
            padding: 1vw;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 1vw;
            vertical-align:top;
        }

        </style>

        <table style='margin-left: auto; margin-right: auto; margin-top: 3vh;'>
            <tr>
                <th>Имя губернатора</th>
                <th style='text-align: left;'>Тип регулятора</th>
                <th style='text-align: left;'>Интересы губернатора</th>
            </tr>";

    // создаем строку таблицы для каждого 

    while ($row = mysqli_fetch_array($result)) {

        $governor_first_names = $row['governor_first_names'];
        $governor_surname = $row['governor_surname'];
        $governor_type = $governor_types[$row['governor_type_code']];
        $governor_business_interests = $row['governor_business_interests'];

        $return .= "
            <tr>
                <td>$governor_first_names $governor_surname</td>
                <td>$governor_type</td>
                <td style='min-width: 15rem;'>$governor_business_interests</td>
            </tr>";
    }

    $return .= "
         </table>";

    echo $return;
}

#####################  build_goveror_attendance_display ####################

if ($helper_type == "build_goveror_attendance_display") {

    $first_meeting_date = $_POST['first_meeting_date'];
    $last_meeting_date = $_POST['last_meeting_date'];

    //получаем даты всех собраний для этой школы в виде массива

    $sql = "SELECT meeting_date FROM meetings
            WHERE
                school_id = '$school_id'
            ORDER BY meeting_date ASC";

    $result = sql_result_for_location($sql, 4);

    $meeting_dates = array();

    $i = 0;
    while ($row = mysqli_fetch_array($result)) {
        $meeting_dates[$i] = $row['meeting_date'];
        $i++;
    }

    // запишем имена всех губернаторов, они нам понадобятся позже

    $sql = "SELECT
                governor_id,
                governor_first_names,
                governor_surname
            FROM governors
            WHERE
                school_id = '$school_id'";

    $result = sql_result_for_location($sql, 5);

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

    // теперь собераем всех губернаторов, которые имели право посещать собрания этой организации во время
    // этотого диапазона meeting_date

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

    $result = sql_result_for_location($sql, 6);

    $governor_attendances = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_id = $row['governor_id'];
        $meeting_date = $row['meeting_date'];
        $governor_present = $row['governor_present'];
        $governor_attendances[$governor_id][$meeting_date] = $governor_present;
    }

    //  теперь используем $governor_attendances для построения центральной части дисплея - блока
    // из столбцов редактирования/ удаления для (исторических) собраний в диапазоне дат first_meeting_date до
    // last meeting_date (максимум 4 - не то, чтобы это имело значение, сколько именно из трех блоков будет
    // в конечном итоге будут отображаться рядом друг с другом). Итоговый дисплей будет содержать следующее
    // блоки:
    //
    // historic_meetings_left_sidebar
    // historic_meetings_update_block
    // historic_meetings_right_sidebar
    //

    $historic_meetings_update_block = "

    <div id='historicmeetingsblock' style = 'background: white; padding-top: 2vh;'>
        <form>
            <div>";


    // даты заголовка. Используем абзацы встроенных блоков как способ создания ячеек "таблицы" фиксированной ширины
    // зарезервируем столбец, чтобы оставить место для имени губернатора

    $historic_meetings_update_block .= "<p style= 'display: inline-block; width: 15rem; text-align: center;'></p>";

    for ($i = $first_meeting_date_index; $i <= $last_meeting_date_index; $i++) {

        $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center; font-weight: bold;'>$meeting_dates[$i]</p>";
    }
    $historic_meetings_update_block .= "
            </div>
            <div>";

    // ряды губернатора

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
                // Если ключ отсутствует, просто отобразите здесь пробел

                $historic_meetings_update_block .= "
                <p style= 'display: inline-block; width: 10rem; text-align: center;'>
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

    $historic_meetings_update_block .= "
            </div>
        </form>
    </div>";


    $historic_meetings_left_sidebar = "
    <p style= 'display: inline-block; width: 2rem; text-align: center;'>";

    // отображайте кнопку только в том случае, если для нее есть что делать!

    if ($first_meeting_date_index != 0) {
        $next_first_meeting_date_index = $first_meeting_date_index - 1;
        $next_last_meeting_date_index = $last_meeting_date_index - 1;

        $historic_meetings_left_sidebar .= "
        <button style = 'border: none;'
            title = 'Отображение предыдущих встреч'
            onclick='displayAttendances($school_id, \"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\");'>
            <span class = 'oi oi-caret-left'></span>
        </button>";
    } else {
        $historic_meetings_left_sidebar .= "";
    }
    $historic_meetings_left_sidebar .= "
    </p>";

    $historic_meetings_right_sidebar = "
    <p style= 'display: inline-block; width: 2rem; text-align: center;'>";

    if ($last_meeting_date_index != count($meeting_dates) - 1) {
        $next_first_meeting_date_index = $first_meeting_date_index + 1;
        $next_last_meeting_date_index = $last_meeting_date_index + 1;

        $historic_meetings_right_sidebar .= "
        <button style = 'border: none;'
            title = 'Отображение более поздних встреч'
            onclick='displayAttendances($school_id, \"$meeting_dates[$next_first_meeting_date_index]\", \"$meeting_dates[$next_last_meeting_date_index]\");'>
            <span class = 'oi oi-caret-right'></span>
        </button>";
    } else {
        $historic_meetings_right_sidebar .= "";
    }
    $historic_meetings_right_sidebar .= "
    </p>";

    $return = "
    <h2 style='text-align: center;'>Участие в собраниях для $school_name</h2>";

    // Блок historic_meeting_update_block теперь должен отображаться между его боковыми панелями
    // Какое-то расположение столов кажется лучшим способом продвижения вперед. Поскольку мы, по сути, только
    // есть одна строка для отображения, предпочтительнее flexbox

    $return .= "
    <div style='display:flex; justify-content: center;'>
        <div>$historic_meetings_left_sidebar</div>
        <div>$historic_meetings_update_block</div>
        <div>$historic_meetings_right_sidebar</div>
    </div>";

    echo $return;
}


#####################  create_doc_template_dismissal ####################

if ($helper_type == "create_doc_template_dismissal") {


    $phpWord = new  \PhpOffice\PhpWord\PhpWord();

    $phpWord->setDefaultFontName('Times New Roman');

    $phpWord->setDefaultFontSize(14);

    $properties = $phpWord->getDocInfo();

    $document = new \PhpOffice\PhpWord\TemplateProcessor('./template/Приказ об увольнении (t8).docx');

    // получаем данны из бд

    $input_id = $_POST['input_id'];

    $sql = "SELECT 
        g.governor_id as d_id,
        g.governor_first_names as fname,
        g.governor_first_names as lname,
        gr.governor_role as grole,
        DATE_FORMAT(g.governor_appointment_date, '%d') as g_start_work_d,
        DATE_FORMAT(g.governor_appointment_date, '%m')  as g_start_work_m,
        DATE_FORMAT(g.governor_appointment_date, '%y')  as g_start_work_y,
        DATE_FORMAT(now(), '%d') as g_end_work_d,
        DATE_FORMAT(now(), '%m')  as g_end_work_m,
        DATE_FORMAT(now(), '%y')  as g_end_work_y,
        sch.school_name as g_organisation,
        gt.governor_type as g_type    
    FROM 
        governors g
        inner join governor_roles gr on g.governor_role_code = gr.governor_role_code
        inner join schools sch on g.school_id = sch.school_id
        inner join governor_types gt on g.governor_type_code = gt.governor_type_code
    where 
        g.governor_id = '$input_id'";

    $result = sql_result_for_location($sql, 4);

    $meeting_dates = array();

    $i = 0;
    while ($row = mysqli_fetch_array($result)) {

        $id = $row['d_id'];
        $fname = $row['fname'];
        $lname = $row['lname'];
        $g_start_work_d = $row['g_start_work_d'];
        $g_start_work_m = $row['g_start_work_m'];
        $g_start_work_y = $row['g_start_work_y'];
        $g_end_work_d = $row['g_end_work_d'];
        $g_end_work_m = $row['g_end_work_m'];
        $g_end_work_y = $row['g_end_work_y'];
        $g_organisation = $row['g_organisation'];
        $g_type = $row['g_type'];

        // заполняем места шаблона
        $document->setValue('value_id', $id);
        $document->setValue('value_num', '8956437');
        $document->setValue('value_date_order', date('d.m.y'));
        $document->setValue('value_date_order_d1', $g_start_work_d);
        $document->setValue('value_date_order_m1', $g_start_work_m);
        $document->setValue('value_date_order_y1', $g_start_work_y);
        $document->setValue('value_date_order_d2', $g_end_work_d);
        $document->setValue('value_date_order_m2', $g_end_work_m);
        $document->setValue('value_date_order_y2', $g_end_work_y);
        $document->setValue('value_date_order_d3', date('d'));
        $document->setValue('value_date_order_m3', date('m'));
        $document->setValue('value_date_order_y3', date('y'));
        $document->setValue('value_org_name', $g_organisation);
        $document->setValue('value_fname', $fname);
        $document->setValue('value_lname', $lname);
        $document->setValue('value_org_type', $g_type);
        $document->setValue('value_rez', 'По собственному желанию');

        $document->setValue('weekday', date('d.m.y'));
        $document->setValue('time', date('H:i'));

        // сохранем шаблон
        $document->saveAS('./template/template_out/t8_' . $id . '.docx');
    }
}


disconnect_from_database();
