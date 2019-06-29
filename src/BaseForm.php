<?php
declare(strict_types=1);

namespace Roers\SwLogic;

use ReflectionClass;

abstract class BaseForm
{
    /**
     * 是否必填的默认设置
     * @var bool
     */
    public $defaultRequired;

    /**
     * 验证不合法的错误提示信息
     * @var string
     */
    protected $errorMessage;

    /**
     * 是否自动对值进行trim
     * @var bool
     */
    protected $isAutoTrim = true;

    /**
     * 子类名称
     * @var
     */
    private $_name;

    /**
     * 缓存（子类名称 => 属性）集合的映射
     * @var array
     */
    private static $_attributesMap = [];

    /**
     * 缓存（子类名称 => 属性验证规则）集合的映射
     * @var array
     */
    private static $_validateRulesMap = [];

    /**
     * 缓存（子类名称 => 属性名称）集合的映射
     * @var array
     */
    private static $_attributeLabelsMap = [];

    /**
     * 缓存子类自定义的验证器
     * @var
     */
    private static $_validators;
    

    /**
     * 定义验证规则
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * 逻辑业务处理
     * @return mixed  返回处理结果
     */
    public abstract function handle();

    /**
     * 根据参数数据实例化Form
     * @param $data
     * @param $defaultRequired
     * @return BaseForm
     */
    public static function instance($data, $defaultRequired = false) {

        $form = new static();
        $form->setAttributes($data);
        $form->defaultRequired = $defaultRequired;

        return $form;
    }

    /**
     * 根据参数数据设置Form的属性值
     * @param $values
     */
    public function setAttributes($values)
    {
        if (is_array($values)) {
            $attributes = $this->attributes();
            foreach ($values as $name => $value) {
                if (isset($attributes[$name])) {
                    $this->{$name} = is_string($value) && $this->isAutoTrim ? trim($value) : $value;
                }
            }
        }
    }

    /**
     * 返回字段与名称的映射数组（子类需重写该方法）
     * @return array
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * 获取当前子类的字段名称数据
     * @return array|mixed
     */
    public function getAttributeLabels()
    {
        $name = $this->getName();
        if (isset(self::$_attributeLabelsMap[$name])) {
            return self::$_attributeLabelsMap[$name];
        }

        return self::$_attributeLabelsMap[$name] = $this->attributeLabels();
    }

    /**
     * 获取属性名称
     * @param $attribute
     * @return string
     */
    public function getAttributeName($attribute) 
    {
        return $this->getAttributeLabels()[$attribute] ?? ucfirst($attribute);
    }

    /**
     * 验证是否合法
     * @return bool
     */
    public function validate()
    {
        $validateRules = $this->getValidateRules();

        foreach ($validateRules as $validateRule) {

            // 判断是否为空，并且允许为空，直接返回，验证通过
            if (Validator::checkIsCanEmpty($validateRule->value, $validateRule->options)) {
                continue;
            }

            // 优先找自定义验证器
            $customValidator = $this->getCustomValidator($validateRule->validate);
            if ($customValidator) {
                $isValid = $this->{$customValidator}(
                    $validateRule->attribute,
                    $validateRule->options
                );
                if (!$isValid) {
                    if (!$this->errorMessage) {
                        $this->errorMessage = $validateRule->message;
                    }
                    return false;
                }
            } else {
                $isValid = Validator::validate(
                    $validateRule->validate,
                    $validateRule->value,
                    $validateRule->options
                );
                if (!$isValid) {
                    $this->errorMessage = $validateRule->message;
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrors() {
        return $this->errorMessage;
    }

    /**
     * 获取Form类自定义验证器
     * @param $validatorName
     * @return array|string
     */
    public function getCustomValidator($validatorName)
    {
        $name = $this->getName();

        // 缓存在类属性，不用每次实例化后执行一次
        if (isset(self::$_validators[$name])) {
            return self::$_validators[$name][$validatorName] ?? '';
        }

        try {
            $class = new ReflectionClass(static::class);
        } catch (\Exception $e) {
            return '';
        }

        $validators = [];
        $methods = $class->getMethods(\ReflectionProperty::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            $method->getParameters();
            if (substr($methodName, 0, 8) === 'validate' && $methodName !== 'validate') {
                $validators[$methodName] = $methodName;
            }
        }

        self::$_validators[$name] = $validators;

        return $this->getCustomValidator($validatorName);
    }

    /**
     * 根据验证规则生成验证器的参数
     * @return array|mixed
     * @throws InvalidLogicException
     */
    public function getValidateRules()
    {
        $name = $this->getName();

        // 缓存在类属性，不用每次实例化后执行一次
        if (isset(self::$_validateRulesMap[$name])) {
            return self::$_validateRulesMap[$name];
        }

        $rules = $this->rules();
        $attributes = $this->attributes();

        $validateRules = [];
        foreach ($rules as $rule)
        {
            if (!is_array($rule) || count($rule) < 2) {
                throw new InvalidLogicException('验证规则格式错误');
            }

            $properties   = (array) array_shift($rule);
            $validateName = (string) array_shift($rule);
            if (!isset($rule['isRequired'])) {
                $rule['isRequired'] = $this->defaultRequired;
            }

            foreach ($properties as $attribute)
            {
                if (!isset($attributes[$attribute])) {
                    throw new InvalidLogicException(sprintf('属性%s不存在', $attribute));
                }

                $message = $rule['message'] ?? ($this->getAttributeName($attribute) . '格式错误');

                $validateRule = new \stdClass();
                $validateRule->attribute = $attribute;
                $validateRule->validate = $validateName;
                $validateRule->value    = $this->{$attribute};
                $validateRule->message  = $message;
                $validateRule->options  = $rule;

                $validateRules[] = $validateRule;
            }
        }

        return self::$_validateRulesMap[$name] = $validateRules;
    }



    /**
     * 获取验证类公共属性
     * @return array|mixed
     */
    public function attributes()
    {
        $name = $this->getName();
        if (isset(self::$_attributesMap[$name])) {
            return self::$_attributesMap[$name];
        }

        try {
            $class = new ReflectionClass($this);
        } catch (\Exception $e) {
            return [];
        }

        $attributeNames = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $attribute) {
            if (!$attribute->isStatic()) {
                $attributeName = $attribute->getName();
                $attributeNames[$attributeName] = $attributeName;
            }
        }

        return self::$_attributesMap[$name] = $attributeNames;
    }

    /**
     * 获取Form类名
     * @return string
     */
    public function getName() {

        if (is_null($this->_name)) {
            $this->_name = static::class;
        }

        return $this->_name;
    }
}
