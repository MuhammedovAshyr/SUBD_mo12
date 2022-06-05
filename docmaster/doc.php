<?php
require_once('./vendor/autoload.php');


$phpWord = new  \PhpOffice\PhpWord\PhpWord();

$phpWord->setDefaultFontName('Times New Roman');


$phpWord->setDefaultFontSize(14);

$properties = $phpWord->getDocInfo();

$document = new \PhpOffice\PhpWord\TemplateProcessor('./template/Приказ об увольнении (t8).docx');
$id = 56874;
$fname = 'Николаев';
$lname = 'Сергей Степанович';

$document->setValue('value_id', $id);
$document->setValue('value_num', '8956437');
$document->setValue('value_date_order', date('d.m.y'));
$document->setValue('value_date_order_d1', 1);
$document->setValue('value_date_order_m1', 10);
$document->setValue('value_date_order_y1', 22);
$document->setValue('value_date_order_d2', date('d'));
$document->setValue('value_date_order_m2', date('m'));
$document->setValue('value_date_order_y2', date('y'));
$document->setValue('value_date_order_d3', date('d'));
$document->setValue('value_date_order_m3', date('m'));
$document->setValue('value_date_order_y3', date('y'));
$document->setValue('value_org_name', 'ООО Техносила');
$document->setValue('value_fname', $fname);
$document->setValue('value_lname', $lname);
$document->setValue('value_org_type', 'Правительство');
$document->setValue('value_rez', 'По собственному желанию');

$document->setValue('weekday', date('d.m.y'));
$document->setValue('time', date('H:i'));

$document->saveAS('./template/template_out/t8_' . $id . '.docx');


?>