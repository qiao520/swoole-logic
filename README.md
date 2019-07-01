#### 概述

swoole-logic是一个专门为swoole框架设计的高性能表单验证、业务逻辑封装组件。

#### 什么是swoole-logic组件

主要是解决如下2个问题：
  - Logic层热加载，修改业务代码后不用重启服务能立即生效
  - 高性能、方便使用的表单验证

#### 热加载的一个解决方案

我看过几个Swoole框架（swoft、imi、easyswoole、hyperf，排名不分先后），都有一个相同的问题：热加载（代码修改后需要重新启动服务），虽然有些框架专门加了缓存优化了启动速度，不过还是慢。
因为swoole是命令行的运行模式，PHP代码加载后就不会重复加载。

我的解决方案是，我们平时开发调试时大部分都是在调试业务逻辑代码，所以我将业务逻辑封装在Logic层，
这个Logic层不会在框架服务启动时加载，是在Work进程启动后加载的。
为什么要这样呢，因为我要利用swoole的$server->reload();接口来重载这个目录下的代码，而不是重启服务。

主要的实现思路
- 首先，我在项目根目录下新建一个logic目录作为业务逻辑层（Logic），这个目录不受框架启动时加载
- 开发一个接口，用于业务代码修改后，调用swoole的$server->reload()重载Work进程，让修改代码也跟着一起重新加载

更多具体的实现步骤
- Hyperf框架上的具体做法可以到这里查看：https://github.com/qiao520/hyperf-skeleton
- IMI框架上的具体做法可以到这里查看：https://github.com/qiao520/imi-logic

#### 现有的验证器有如下几种

- integer 整型
- string 字符串
- number 数字
- url  链接地址
- email 邮箱
- required 必填项
- boolean  布尔（0或1）
- in  集合
- regex 正则
- array 数组（对子项进行类型校验）
- 自定义校验器 可在form子类进行自定义

#### 表单验证使用示例

本人对Yii比较喜欢，也对它有一定的了解，这个组件主要是参考了Yii的表单验证。
如果你有Yii的开发经验，用起来会很顺手，希望你会喜欢。

- Form类示例代码（/demo/DemoForm.php）
```
namespace Roers\Demo;

use Roers\SwLogic\BaseForm;

class DemoForm extends BaseForm
{
    public $name;
    public $age;
    public $sex;

    public function rules()
    {
        return [
            // 验证6到30个字符的字符串
            ['name', 'string', 'min' => 6, 'max' => 30, 'maxMinMessage' => '名字必须在{min}~{max}个字符范围内'],
            // 验证年龄必须是整数
            ['age', 'integer', 'min' => 18, 'max' => 100],
            // 集合验证器，验证性别必须是1或2
            ['sex', 'in', 'in' => [1, 2],],
            // 使用自定义验证器，验证名字不能重复
            ['name', 'validateName'],
            // 还可以这样用，对多个字段用同一个验证器规则
            [['age', 'sex'], 'integer']
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => '名字',
            'age' => '年龄',
        ];
    }

    /**
     * 业务处理
     * @return array
     */
    public function handle()
    {
        // do something here

        // 返回业务处理结果
        return ['name' => $this->name, 'age' => $this->age];
    }

    /**
     * 自定义验证器
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
- 示例代码demo.php

```
require 'vendor/autoload.php';

// 这个是演示表单类
use Roers\Demo\DemoForm;

// 调试打印函数
function debug($msg) {
    echo $msg, PHP_EOL;
}

// 表单提交的数据
$data = [
    'name' => 'zhongdalong',
    'age' => '31',
    'sex' => '',
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
```

- 执行结果
```
验证通过，业务处理结果：{"name":"zhongdalong","age":"31"}
----------------------------------------------------------------------
验证不通过，错误提示信息：Sex是必填项
----------------------------------------------------------------------
验证不通过，错误提示信息：年龄必须在18 ~ 100范围内
----------------------------------------------------------------------
验证不通过，错误提示信息：名字Roers.cn已存在
----------------------------------------------------------------------
```


#### 安装
composer require qiao520/swoole-logic:~1.0.0

#### 联系我

QQ：380552499
