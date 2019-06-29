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
            ['name', 'string'],
            ['age', 'integer'],
            ['sex', 'in', 'in' => [1, 2]],
        ];
    }

    public function handle()
    {
        return ['name' => $this->name, 'age' => $this->age];
    }
}
