<?php
declare(strict_types=1);

namespace Logic\Form;

use Logic\Validate\Validator;
use ReflectionClass;

abstract class BaseForm
{
    // 默认是都不必填的
    public $defaultRequired;

    protected $errorMessage;
    protected $isAutoTrim = true;

    private $_name;

    private static $_attributes = [];

    private static $_validateRules = [];

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
     * 业务处理
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
                    $this->{$name} = $this->isAutoTrim ? trim($value) : $value;
                }
            }
        }
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
     */
    public function getValidateRules()
    {
        $name = $this->getName();

        // 缓存在类属性，不用每次实例化后执行一次
        if (isset(self::$_validateRules[$name])) {
            return self::$_validateRules[$name];
        }

        $rules = $this->rules();
        $attributes = $this->attributes();

        $validateRules = [];
        foreach ($rules as $rule) {
            if (!is_array($rule) || count_chars($rule) < 2) {
                throw new InvalidRuleException('验证规则格式错误');
            }

            $properties   = (array) array_shift($rule);
            $validateName = (string) array_shift($rule);
            $message      = $rule['message'] ?? '格式错误';
            if (!isset($rule['isRequired'])) {
                $rule['isRequired'] = $this->defaultRequired;
            }

            foreach ($properties as $property) {
                if (!isset($attributes[$property])) {
                    throw new InvalidRuleException(sprintf('属性%s不存在', $property));
                }

                $validateRule = new \stdClass();
                $validateRule->property = $property;
                $validateRule->validate = $validateName;
                $validateRule->value    = $this->{$property};
                $validateRule->message  = $message;
                $validateRule->options  = $rule;

                $validateRules[] = $validateRule;
            }
        }

        return self::$_validateRules[$name] = $validateRules;
    }



    /**
     * 获取验证类公共属性
     * @return array|mixed
     */
    public function attributes()
    {
        $name = $this->getName();
        if (isset(self::$_attributes[$name])) {
            return self::$_attributes[$name];
        }

        try {
            $class = new ReflectionClass($this);
        } catch (\Exception $e) {
            return [];
        }

        $propertyNames = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $propertyName = $property->getName();
                $propertyNames[$propertyName] = $propertyName;
            }
        }

        return self::$_attributes[$name] = $propertyNames;
    }

    /**
     * 获取Form类名
     * @return string
     */
    public  function getName() {

        if (is_null($this->_name)) {
            $this->_name = static::class;
        }

        return $this->_name;
    }
}
