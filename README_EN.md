# Logic package component for Swoole framework

## Document

- **[中文](README.md)**
- **[English](README_EN.md)**

## Description

Encapsulate business code into Logic layer with Form and Validate

Hot loading, which takes effect immediately after modifying the business code without restarting the service

Reduce the amount of controller code, the structure is clear and easy to maintain

Encapsulates efficient form data validation rules

## Principle of hot loading

As we all know, most of our development time is spent debugging business logic code,So I encapsulate the business Logic in the Logic layer (separate directory, custom directory name).

This Logic layer PHP file will not be loaded when the framework service (swoft, imi, easyswoole, hyperf, etc.) starts, but will be loaded when the Work process starts.

Then, write an interface for call swoole's $server->reload() to reload the Work process's code, instead of restarting the service. 

Every time you change the business code, request this interface to make the code reload Work.


## API of form class

- Instantiate the form by requesting data
```
$form = DemoForm::instance($data); 
```

- Instantiate the form by requesting data and setting all fields mandatory by default.
```
$form = DemoForm::instance($data, true);
```

- Close all fields to Spaces (default is on)
```
$form->setIsAutoTrim(false);
```

- Set all field data for the form
```
$form->setAttributes($data);
```

- Verify that the form data is valid
```
$form->validate();
```
- Get a validation error message
```
$form->getError();
```


## Validation rule

- integer 
- string 
- number 
- url  
- email 
- required 
- boolean  
- in  
- regex 
- array 
- custom  hint:You can be customized in the form subclass



## Use this component example in conjunction with an existing framework

- Hyperf    https://github.com/qiao520/hyperf-skeleton

- IMI       https://github.com/qiao520/imi-logic


## Demo 

I like Yii and have some understanding of it. This component mainly refers to Yii's form validation.
If you have Yii development experience, it will be easy to use.

- Form subclass DemoForm (The file directory: /demo/DemoForm.php)
```
use Roers\SwLogic\BaseForm;

class DemoForm extends BaseForm
{
    // Here are the form properties
    public $name;
    public $email;
    public $age;
    public $sex;
    public $others;
    public $default = 0;

    // Here are the default Settings to override the parent class
    protected $isAutoTrim = true;   // Enable automatic de-whitespace (default)
    protected $defaultRequired = true;   // Enable all properties as required (not enabled by default)
    protected $defaultErrorMessage = '{attribute}格式错误';  // Overrides the custom error message

    /**
     * Define validation rules
     * @return array
     */
    public function rules()
    {
        return [
            // 验证6到30个字符的字符串
            ['name', 'string', 'min' => 6, 'max' => 30, 'maxMinMessage' => 'The name must be within {min}~{Max} characters'],
            // 验证年龄必须是整数
            ['age', 'integer', 'min' => 18, 'max' => 100],
            // 集合验证器，验证性别必须是1或2
            ['sex', 'in', 'in' => [1, 2],],
            // 使用自定义验证器，验证名字不能重复
            ['name', 'validateName'],
            // 还可以这样用，对多个字段用同一个验证器规则
            [['age', 'sex'], 'integer'],
            // 验证邮箱格式，并且必填required对所有校验器都有效
            [['email'], 'email', 'required' => true],
            // 验证是否是数组，并对数组元素进行格式校验
            [['others'], 'array', 'validator' => 'string'],
        ];
    }

    /**
     * Field name mapping
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'name' => '名字',
            'age' => '年龄',
        ];
    }

    /**
     * business process
     * @return array
     */
    public function handle()
    {
        // do something here

        // 返回业务处理结果
        return ['name' => $this->name, 'age' => $this->age];
    }

    /**
     * Custom validator
     * @param $attribute
     * @param $options
     * @return bool
     */
    public function validateName($attribute, $options)
    {
        $value = $this->{$attribute};

        if ($value == 'Roers.cn') {
            $this->addError($attribute, "名字{$value}已存在");
            return false;
        }

        return true;
    }
}
```
- demo.php (The file directory: /demo.php)

```
require 'vendor/autoload.php';

// This is the demo form class
use Roers\Demo\DemoForm;

// Debug print function
function debug($msg) {
    echo $msg, PHP_EOL;
}

// Data submitted by the form
$data = [
    'name' => 'zhongdalong',
    'age' => '31',
    'sex' => '',
];
// Demonstrates that all fields are non-mandatory by default
$form = DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    debug('Validation passes, business processing results:' . json_encode($result));
} else {
    debug('Validation failed, error message:' .  $form->getError());
}

debug(str_repeat('-------', 10));

// Demonstrates that all fields are required by default
$form = DemoForm::instance($data, true);
if ($form->validate()) {
    $result = $form->handle();
    debug('Validation passes, business processing results:' . json_encode($result));
} else {
    debug('Validation failed, error message:' .  $form->getError());
}

debug(str_repeat('-------', 10));


// Demonstrate the minor registration scenario
$data['age'] = 17;
$form = DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    debug('Validation passes, business processing results:' . json_encode($result));
} else {
    debug('Validation failed, error message:' .  $form->getError());
}

debug(str_repeat('-------', 10));


// Demonstrates a custom validator
$data['age'] = 18;
$data['name'] = 'Roers.cn';
$form = DemoForm::instance($data);
if ($form->validate()) {
    $result = $form->handle();
    debug('Validation passes, business processing results:' . json_encode($result));
} else {
    debug('Validation failed, error message:' .  $form->getError());
}

debug(str_repeat('-------', 10));
```

- Result of enforcement
```
Validation passes, business processing results:{"name":"zhongdalong","age":"31"}
----------------------------------------------------------------------
Validation failed, error message:Sex是必填项
----------------------------------------------------------------------
Validation failed, error message:年龄必须在18 ~ 100范围内
----------------------------------------------------------------------
Validation failed, error message:名字Roers.cn已存在
----------------------------------------------------------------------
```


## The step of join the component 

- First, I create a new logic directory as the business logic layer in the project root directory, which is not loaded by the framework at startup

- Develop an interface to use when the business code changes, call swoole's $server->reload() to reload the Work process so that the changed code reloads along with it

## Environmental requirement 

1. PHP 7.0 +

## idea

I have seen several Swoole frameworks (swoft, imi, easyswoole, hyperf, in no particular order).

All have the same problem: hot loading (code changes that require you to restart the service), and although some frameworks have added caches to optimize the startup time, they are still slow.

Because swoole is a command-line mode of operation, PHP code does not reload when it is loaded.

A robust system requires request parameter data validation and maintainability, and this component is designed to help you do that easily.

## Install

composer require qiao520/swoole-logic

## Contact me

email:380552499@qq.com

Like the friend point like, thanks for the support!