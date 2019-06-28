<?php
declare(strict_types=1);

namespace Logic\Validate;

use ReflectionClass;

class Validator
{
    public static $validators;

    const INTEGER_PATTERN = '/^[+-]?\d+$/';

    public static function validate($name, $value, $options = [])
    {
        // [['user_id', 'mileage'], 'required']
        $method = self::getValidateMethodName($name);
        if (!$method) {
            throw new InvalidRuleException("Validator which name of {$name} does not exist!");
        }

        return self::{$method}($value, $options);
    }

    /**
     * 获取验证方法
     * @param $name
     * @return string
     */
    public static function getValidateMethodName($name)
    {
        if (!is_null(self::$validators)) {
            if ($name && isset(self::$validators[$name])) {
                return self::$validators[$name];
            }
        }

        try {
            $class = new ReflectionClass(self::class);
        } catch (\Exception $e) {
            return '';
        }

        $validators = [];
        $methods = $class->getMethods(\ReflectionProperty::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->isStatic()) {
                $methodName = $method->getName();
                $method->getParameters();
                if (substr($methodName, 0, 8) === 'validate' && $methodName !== 'validate') {
                    $validator = lcfirst(substr($methodName, 8));
                    $validators[$validator] = $methodName;
                }
            }
        }

        self::$validators = $validators;

        return self::getValidateMethodName($name);
    }

    /**
     * 验证是否必填
     * @param $value
     * @return bool|string
     */
    public static function validateRequired($value)
    {
        if (is_null($value) || $value === '') {
            return false;
        }

        return true;
    }

    /**
     * 验证是否是整数
     * @param $value
     * @param $options
     * @return bool
     */
    public static function validateInteger($value, $options)
    {
        // 默认是必填的
        if (self::checkIsCanEmpty($value, $options)) {
            return true;
        }

        if (is_integer($value) || (is_string($value) && preg_match(self::INTEGER_PATTERN, $value)))
        {
            return true;
        }

        return false;
    }

    public static function checkIsCanEmpty($value, $options)
    {
        // 默认是非必填的
        $isRequired = $options['isRequired'] ?? true;

        if ((is_null($value) || $value === '') && !$isRequired) {
            return true;
        }

        return false;
    }


    public static function validateNumber($value, $options)
    {
        // 默认是必填的
        if (self::checkIsCanEmpty($value, $options)) {
            return true;
        }

        if (is_integer($value) || (is_string($value) && preg_match(self::INTEGER_PATTERN, $value)))
        {
            return true;
        }

        if (is_null($value) || $value === '') {
            return false;
        }

        return true;
    }


}
