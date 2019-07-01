<?php
require 'vendor/autoload.php';

use Roers\Demo\DemoForm;

function debug($msg) {
    echo $msg, PHP_EOL;
}

// 表单提交的数据
$data = [
    'name' => 'zhongdalong',
    'age' => '31',
    'sex' => '',
    'email' => 'demo@roers.cn',
    'others' => ['a', 'b', 'c']
];
// 演示默认所有字段为非必填项
$form = DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    debug('验证通过，业务处理结果：' . json_encode($result));
} else {
    debug('验证不通过，错误提示信息：' .  $form->getError());
}

debug(str_repeat('-------', 10));

// 演示默认所有字段为必填项
$form = DemoForm::instance($data, true);
if ($form->validate()) {
    $result = $form->handle();
    debug('验证通过，业务处理结果：' . json_encode($result));
} else {
    debug('验证不通过，错误提示信息：' .  $form->getError());
}

debug(str_repeat('-------', 10));


// 演示未成年注册场景
$data['age'] = 17;
$form = DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    debug('验证通过，业务处理结果：' . json_encode($result));
} else {
    debug('验证不通过，错误提示信息：' .  $form->getError());
}

debug(str_repeat('-------', 10));


// 演示自定义验证器
$data['age'] = 18;
$data['name'] = 'Roers.cn';
$form = DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    debug('验证通过，业务处理结果：' . json_encode($result));
} else {
    debug('验证不通过，错误提示信息：' .  $form->getError());
}

debug(str_repeat('-------', 10));
