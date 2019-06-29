<?php
require 'vendor/autoload.php';

use Roers\Demo\DemoForm;
$data = [
    'name' => '1',
    'age' => '32',
    'sex' => '',
];
$form = DemoForm::instance($data, true);
if ($form->validate()) {
    $result = $form->handle();
    var_dump($result);
} else {
    var_dump($form->getErrors());
}

$data = [
    'name' => 'aaaaaa',
    'age' => '32',
    'sex' => '',
];
$form = DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    var_dump($result);
} else {
    var_dump($form->getErrors());
}