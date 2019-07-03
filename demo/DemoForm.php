<?php
declare(strict_types=1);

/**
 * 表单演示类
 * @author Roers
 * @email 380552499@qq.com
 */
namespace Roers\Demo;

use Roers\SwLogic\BaseForm;

class DemoForm extends BaseForm
{
    // 以下是表单属性
    // Here are the form properties
    public $name;
    public $email;
    public $age;
    public $sex;
    public $others;
    public $default = 0;  // 设置默认值
    public $avatar;
    public $agree;

    // 以下是覆盖父类的默认设置
    // Here are the default Settings to override the parent class
    /**
     * 开启自动去空格（默认开启）
     * Enable automatic de-whitespace (default)
     * @var bool
     */
    protected $isAutoTrim = true;

    /**
     * 开启所有属性为必填（默认未开启）
     * Enable all properties as required (not enabled by default)
     * @var bool
     */
    protected $defaultRequired = true;

    /**
     * 覆盖自定义错误提示信息
     * Overrides the custom error message
     * @var string
     */
    protected $defaultErrorMessage = '{attribute}格式错误';

    /**
     * 定义验证规则
     * Define validation rules
     * @return array
     */
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
            [['age', 'sex'], 'integer'],
            // 验证邮箱格式，并且必填required对所有校验器都有效
            [['email'], 'email', 'required' => true],
            // 验证是否是数组，并对数组元素进行格式校验
            [['others'], 'array', 'validator' => 'string'],
            // 验证是否是超链接
            [['avatar'], 'url'],
            // 验证超链接是否是jpg图片格式后缀
            [['avatar'], 'regex', 'pattern' => '/.jpg$/'],
            [['agree'], 'boolean'],
        ];
    }

    /**
     * 字段名称映射关系
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
