<?php

function connect_to_database()
{
    global $con, $url_root;

    if (($_SERVER['REMOTE_ADDR'] == 'localhost' or $_SERVER['REMOTE_ADDR'] == '::1')) {
        $url_root = '../../';
    } else {
        $current_directory_root = $_SERVER['DOCUMENT_ROOT']; // на один уровень выше текущего каталога

        $pieces = explode('public_html', $current_directory_root);
        $url_root = $pieces[0];
    }

    require($url_root . "/connect_governors_db.php");
}

function disconnect_from_database()
{
    global $con, $url_root;

    require($url_root . "/disconnect_governors_db.php");
}

function sql_result_for_location($sql, $location)
{
    global $con, $url_root, $page_title;

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Упс - доступ к базе данных %failed%. на $page_title расположенной по адресу $location. Далее следуют сведения об ошибке : " . mysqli_error($con);

        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);

        disconnect_from_database();
        exit(1);
    }

    return $result;
}

function get_governor_types()
{
    global $con;

    $sql = "SELECT
                    governor_type_code,
                    governor_type
                FROM
                    governor_types";

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Упс - доступ к базе данных %failed%. в функции get_governor_types. Далее следуют сведения об ошибке : " . mysqli_error($con);

        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);

        disconnect_from_database();
        exit(1);
    }

    $governor_types = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_type_code = $row['governor_type_code'];
        $governor_type = $row['governor_type'];

        if ($governor_type_code != '') {
            $governor_types[$governor_type_code] = $governor_type;
        }
    }
    return $governor_types;
}

function get_governor_roles()
{
    global $con;

    $sql = "SELECT
                    governor_role_code,
                    governor_role
                FROM
                    governor_roles";

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Упс - доступ к базе данных %failed%. в функции get_governor_roles. Далее следуют сведения об ошибке : " . mysqli_error($con);

        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);

        disconnect_from_database();
        exit(1);
    }

    $governor_roles = array();

    while ($row = mysqli_fetch_array($result)) {

        $governor_role_code = $row['governor_role_code'];
        $governor_role = $row['governor_role'];

        if ($governor_role_code != '') {
            $governor_roles[$governor_role_code] = $governor_role;
        }
    }
    return $governor_roles;
}

function prepareStringforXMLandJSONParse($input)
{

    # < , > и & должны быть преобразованы в &lt; , &gt; и &amp;, чтобы получить их через XML-возврат
    # " и переводы строк (\n) должны быть преобразованы в \\" и \\n, чтобы сделать их приемлемыми для JSON.Parse
    # &nbsp; должен быть включен в " "
    # &quot; должно быть превращено в "'"
    # 
    # возможно, следует рассмотреть encodeURIComponent см. https://stackoverflow.com/questions/20960582/html-string-nbsp-breaking-json
    # Синтаксис JSON см. в разделе https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON
    # Из этого не ясно, почему &nbsp; нарушает возврат JSON - в основном пустого - для 
    # дополнительную информацию о кодировке URL смотрите в разделе https://www.urlencoder.io/learn/
    # 
    # Возможно, вы подумали, что мы сделаем это с самого начала, до того, как эти персонажи дойдут до "помощников".
    # но проблема в том, что экранированные строки становятся неэкранированными, когда они хранятся в базе данных. То
    # С символами # "tag" < и >, вероятно, можно было бы разобраться с самого начала, но это кажется лучше
    # чтобы все было вместе

    $output = $input;

    $output = str_replace('&', '&amp;', $output);
    $output = str_replace('<', '&lt;', $output);
    $output = str_replace('>', '&gt;', $output);


    $output = str_replace('"', '\\"', $output);
    $output = str_replace('&nbsp;', ' ', $output);
    $output = str_replace('&quot;', "'", $output);

    return $output;
}

function build_documents_update_table_row(
    $row_number,
    $school_id,
    $document_title,
    $version_number,
    $max_version_number,
    $document_author,
    $document_issue_date,
    $version_last_review_date
) {
    // Создайте строку documents_update_table для $school_id, $document_title и $version_number в $row_number
    // $max_version_number - это самый высокий номер версии, определенный в настоящее время для $school_id и $document_title

    if ($version_number == $max_version_number) {
        $pdf_weblink = "school_$school_id/$document_title" . "_LATEST.pdf";
    } else {
        $pdf_weblink = "school_$school_id/$document_title" . "_VERSION_$version_number.pdf";
    }

    $src_weblink = '';

    // Получить адрес источника для этой версии документа. Поскольку мы не записываем его расширение
    // в базу данных, кажется, лучше просто посмотреть, что на самом деле находится на сервере - 
    // ie возвращает все, что появляется первым, с требуемым названием и версией, которые не являются pdf-файлом

    if ($files = scandir("../school_$school_id/")) {
        for ($i = 0; $i < count($files); $i++) {

            if ($files[$i] != "." && $files[$i] != "..") {
                $pieces = explode(".", $files[$i]);
                if (
                    $pieces[0] == "$document_title" . "_VERSION_$version_number" &&
                    $pieces[1] != "pdf"
                ) {
                    $src_weblink = "school_$school_id/$document_title" . "_VERSION_$version_number.$pieces[1]";
                    break;
                }
            }
        }
    } else {
        echo "Упс! скандир %%failed%% в функции build_documents_update_table_row.";
    }

    $return = "
        <td>
            <button type='button' class='btn-sm btn-outline-danger'
                title='Отобразить pdf-файл для этой версии документа'
                onmousedown='grabClipboardLink(\"$pdf_weblink\");'>Скачать PDF
            </button>
        </td>

        <td>
            <button type='button' class='btn-sm btn-outline-primary'
                title='Загрузите копию исходного файла для этой версии документа'
                onmousedown='downloadDocument(\"$src_weblink\");'>Скачать DOC
            </button>
        </td>                

        <td id = 'title$row_number'>$document_title</td>  
        <td style='text-align: center;'>$document_author</td>
        <td style='text-align: center;'>
            <select name='versions$row_number' id='versions$row_number'
                onchange = 'resetDocumentsUpdateTableRow($row_number, $max_version_number);'>";

    for ($i = $max_version_number; $i >= 1; $i--) {
        if ($i == $version_number) {
            $return .= "<option value='$i' selected>$i</option>";
        } else {
            $return .= "<option value='$i'>$i</option>";
        }
    }
    $return .= "
            </select>
        </td>

        <td style='text-align: center;'>$document_issue_date</td>  
        <td style='text-align: center;'>$version_last_review_date</td>
            
         <td>
            <button type='button' class='ml-2 btn-sm btn-light'
                title='Добавьте новую версию этого документа в бд'
                onmousedown='displayVersionInsertScreen(\"$document_title\");'>Добавить новую версию
            </button>
        </td>";;

    if ($version_number == $max_version_number) {
        $return .= "
        <td>
            <button type='button' class='ml-2 btn-sm btn-info'
                title='Измените поля Атор, Дата выпуска и Дата проверки для последней версии этого документа'
                onmousedown='displayDocumentReviewScreen($school_id, \"$document_title\", $max_version_number);'>Изменить
            </button>
        </td>";
    } else {
        $return .= "
        <td>
            <button type='button' class='btn-sm btn-info' style='opacity: 0.5;'>Изменить
            </button>
        </td>";
    }
    $return .= "
        <td>
            <button type='button' class='btn-sm btn-danger'
                title='Удалить документ'
                onmousedown='deleteDocument($school_id, \"$document_title\", $max_version_number);'>Удалить
            </button>
        </td>";

    return $return;
}
