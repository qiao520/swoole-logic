<?php
require 'vendor/autoload.php';

$data = [
    'name' => 'Roers',
    'age' => 'a32',
    'sex' => 1,
];
$form = \Roers\Demo\DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    var_dump($result);
} else {
    echo $form->getErrors();
}
