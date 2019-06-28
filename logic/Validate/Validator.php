<?php
declare(strict_types=1);

namespace Logic\Validate;

use ReflectionClass;

class Validator
{

    public static $validators;

    public static function validate($name, $value, $options = [])
    {
        // [['user_id', 'mileage'], 'required']
        $method = self::getValidateMethodName($name);
        if (!$method) {
            throw new InvalidRuleException("验证器（{$name}）不存在");
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
     * @param $options
     * @return bool|string
     */
    public static function validateRequired($value, $options)
    {
        if (is_null($value) || $value === '') {
            return $options['message'] ?? '不能为空';
        }

        return true;
    }

    public static function validateInteger($value, $options)
    {
        if (is_null($value) || $value === '') {
            return $options['message'] ?? '不能为空';
        }

        return true;
    }



}
