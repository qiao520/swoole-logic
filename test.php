<?php
require 'vendor/autoload.php';

$data = [
    'name' => '',
    'age' => '32',
    'sex' => '',
];
$form = \Roers\Demo\DemoForm::instance($data, true);
if ($form->validate()) {
    $result = $form->handle();
    var_dump($result);
} else {
    var_dump($form->getErrors());
}