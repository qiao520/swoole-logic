<?php
declare(strict_types=1);

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
            ['name', 'string', 'message' => '名字必须在6~30个字符范围内'],
            ['age', 'integer'],
            ['sex', 'in', 'in' => [1, 2],],
        ];
    }

    public function attributeLabels()
    {
        return [
            'sex' => '性别',
        ];
    }

    public function handle()
    {

        // 返回业务处理结果
        return ['name' => $this->name, 'age' => $this->age];
    }
}
