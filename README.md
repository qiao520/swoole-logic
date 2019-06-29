#### 概述

专门为swoole框架设计的一个高性能表单验证组件。名字叫：SwooleLogic

#### 设计初衷

最近在网上了解到一款swoole框架（Hyperf），这款框架号称是旨在搭建高性能分布式系统。
我是对性能比较看重的，所以对这个框架有比较的喜欢看好。
由于该框架项目刚开源，很多功能有待完善，其中就缺少验证器，我利用周末时间整了这么一个Logic组件。
当然，我也是遵循高性能为原则，开发了这个组件。

开发这个Logic组件是为了解决2个事情，一个是表单验证，另外一个是热加载。

#### 热加载的一个解决方案

我看过几个Swoole框架，如：swoft、imi、easyswoole，都有一个相同的短板：热加载（代码修改后需要重新启动服务）。
因为swoole是命令行的运行模式，PHP代码加载后就不会重复加载。
我的解决方案是，我们大部分都是在调试业务逻辑代码，所以我将业务逻辑封装在Logic层，
这个Logic层是在服务启动后加载的。
为什么要这样呢，因为我要利用swoole的$server->reload();接口来重载这个目录下的代码，而不是重启服务。

- 首先，我在项目根目录下新建一个logic目录作为业务逻辑层（Logic）

- 新建一个控制器ReloadController，用于访问时重载work进程。代码如下：
```

```

#### 表单验证使用示例
- 示例代码
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


