<?php
declare(strict_types=1);

/**
 * Logic层业务逻辑表单基类
 * @desc 对业务逻辑进行抽离，以简化控制器的代码简洁
 * @author Roers
 * @email 380552499@qq.com
 */
namespace Roers\SwLogic;

use ReflectionClass;

abstract class BaseForm
{
    /**
     * 是否必填的默认设置
     * @var bool
     */
    protected $defaultRequired;

    /**
     * 默认的错误提示信息
     * @var string
     */
    protected $defaultErrorMessage = '{attribute}格式错误';

    /**
     * 默认的必填错误提示信息
     * @var string
     */
    protected $defaultRequiredMessage = '{attribute}是必填项';

    /**
     * 默认的范围错误提示信息
     * @var string
     */
    protected $defaultMaxMinMessage = '{attribute}必须在{min} ~ {max}范围内';

    /**
     * 是否自动对值进行trim
     * @var bool
     */
    protected $isAutoTrim = true;

    /**
     * 验证不合法的错误提示信息
     * @var array
     */
    private $errorMessages = [];

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
    public function rules(){
        return [];
    }

    /**
     * 逻辑业务处理（子类需自行实现）
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
     * 获取所有属性值
     * @return array
     */
    public function getAttributes()
    {
        $attributes = $this->attributes();

        $values = [];
        foreach ($attributes as $name => $value) {
            if (isset($attributes[$name])) {
                $values[$name] = $this->{$name};
            }
        }

        return $values;
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

        /**
         * @var $validateRule FormValidateRule
         */
        foreach ($validateRules as $validateRule)
        {
            $value       =  $this->{$validateRule->attribute};
            $isEmpty     =  Validator::checkIsEmpty($value);
            $isRequired  =  $validateRule->isRequired || $this->defaultRequired;
            $validator   =  $validateRule->validate;
            
            // 校验必填项，如果是非必填项，且值为空，验证通过
            if (!$isRequired && $isEmpty) {
                continue;
            }

            // 校验必填项，如果是必填项，且值为空，验证不通过
            if ($isRequired && $isEmpty) {
                $this->addError($validateRule->attribute, $validateRule->requiredMessage);
                return false;
            }

            // 优先找自定义验证器进行校验
            $customValidator = $this->getCustomValidator($validator);
            if ($customValidator) {
                $isValid = call_user_func(
                    [$this, $customValidator], 
                    $validateRule->attribute,
                    $validateRule->options
                );
                if (!$isValid) {
                    // 自定义验证器，自行添加错误信息，直接返回校验失败
                    return false;
                }
            } else {
                $isValid = Validator::validate($validator, $value, $validateRule->options);
                if (!$isValid) {
                    $this->addError($validateRule->attribute, $validateRule->message);
                    return false;
                }

                // 校验大小
                if ($validateRule->isMaxMin) {
                    $isValid = Validator::checkMaxMin($validator, $value, $validateRule->options);
                    if (!$isValid) {
                        $this->addError($validateRule->attribute, $validateRule->maxMinMessage);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * 添加一条错误信息
     * @param $attribute
     * @param $message
     */
    protected function addError($attribute, $message) {
        $this->errorMessages[$attribute] = $message;
    }
    /**
     * 获取第一条错误信息
     * @return string
     */
    public function getError() {
        return reset($this->errorMessages);
    }

    /**
     * 获取所有错误信息
     * @return array
     */
    public function getErrors() {
        return $this->errorMessages;
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
     * @return array
     * @throws LogicException
     */
    public function getValidateRules()
    {
        $name = $this->getName();

        // 缓存在类属性，不用每次实例化后执行一次
        if (isset(self::$_validateRulesMap[$name])) {
            return self::$_validateRulesMap[$name];
        }

        $validateRules = $this->initValidateRules();

        return self::$_validateRulesMap[$name] = $validateRules;
    }

    /**
     * 初始化类的验证规则配置（每个类只需初始化一次）
     * @return array
     * @throws LogicException
     */
    private function initValidateRules ()
    {
        $rules = $this->rules();
        $attributes = $this->attributes();

        $validateRules = [];
        foreach ($rules as $rule)
        {
            if (!is_array($rule) || count($rule) < 2) {
                throw new LogicException('验证规则格式错误');
            }

            $properties   = (array) array_shift($rule);
            $validateName = (string) array_shift($rule);

            // 是否必填
            $isRequired = $rule['isRequired'] ?? false;

            // 错误提示信息
            $errorMessage   = $rule['message'] ?? $this->defaultErrorMessage;
            $maxMinMessage  = $rule['maxMinMessage'] ?? $this->defaultMaxMinMessage;
            $requireMessage = $rule['requireMessage'] ?? $this->defaultRequiredMessage;

            // 是否有最大最小规则
            $isMaxMin = isset($rule['min']) || isset($rule['max']);

            foreach ($properties as $attribute)
            {
                if (!isset($attributes[$attribute])) {
                    throw new LogicException(sprintf('属性%s不存在', $attribute));
                }

                $validateRule = new FormValidateRule();
                $validateRule->attribute  = $attribute;
                $validateRule->validate   = $validateName;
                $validateRule->isRequired = $isRequired;
                $validateRule->isMaxMin   = $isMaxMin;
                $validateRule->options    = $rule;
                $validateRule->message    = $this->formatMessage($attribute, $errorMessage, $rule);
                $validateRule->requiredMessage = $this->formatMessage($attribute, $requireMessage, $rule);

                if ($isMaxMin) {
                    $validateRule->maxMinMessage   = $this->formatMessage($attribute, $maxMinMessage, $rule);
                }

                $validateRules[] = $validateRule;
            }
        }

        return $validateRules;
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

    /**
     * 格式化错误提示消息
     * @param $attribute
     * @param $message
     * @param array $options
     * @return mixed
     */
    protected function formatMessage($attribute, $message, $options = [])
    {
        $attributeName = $this->getAttributeName($attribute);
        $message = str_replace('{attribute}', $attributeName, $message);

        foreach ($options as $option => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            } else if (is_numeric($value)) {
                $value = (string) $value;
            }

            if (is_string($option) && is_string($value)) {
                $message = str_replace(sprintf('{%s}', $option), $value, $message);
            }
        }

        return $message;
    }

    /**
     * 设置是否自动对每个自动数据去掉前后空格
     * @param $status
     */
    public function setIsAutoTrim($status) {
        $this->isAutoTrim = $status;
    }

}
