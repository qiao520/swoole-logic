<?php
declare(strict_types=1);

namespace Roers\SwLogic;

use ReflectionClass;

class Validator
{
    /**
     * 验证错误提示信息
     * @var array
     */
    public static $validatorMessages = [
        'string' => '必须是字符串格式',
        'integer' => '必须是整数格式',
        'number' => '必须是数字格式',
        'boolean' => '必须是1或0',
        'array' => '必须是数组',
        'in' => '不在可选值范围值',
        'email' => '不是合法的邮箱地址',
        'url' => '不是合法的链接地址',
        'required' => '是必填的',
        'regex' => '格式不正确',
    ];

    /**
     * 验证器
     * @var array
     */
    public static $validators;

    // 整型正则
    const PATTERN_INTEGER = '/^[+-]?\d+$/';

    // 浮点数正则
    const PATTERN_FLOAT = '/^[+-]?\d+\.?\d+$/';

    // URL正则
    const PATTERN_URL = '/^https?:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i';

    // Email正则
    const PATTERN_EMAIL = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';

    /**
     * 验证是否符合某规则
     * @param $name
     * @param $value
     * @param array $options
     * @return mixed
     * @throws InvalidLogicException
     */
    public static function validate($name, $value, $options = [])
    {
        $method = self::getValidateMethodName($name);
        if (!$method) {
            throw new InvalidLogicException("Validator which name of {$name} does not exist!");
        }

        return self::{$method}($value, $options);
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
     * 字符串验证
     * @param $value
     * @return bool
     */
    public static function validateString($value)
    {
        if (is_string($value))
        {
            return true;
        }

        return false;
    }

    /**
     * 整数验证
     * @param $value
     * @return bool
     */
    public static function validateInteger($value)
    {
        if (is_integer($value) || (is_string($value) && preg_match(self::PATTERN_INTEGER, $value)))
        {
            return true;
        }

        return false;
    }

    /**
     * 数字验证（包括整数、浮点数）
     * @param $value
     * @return bool
     */
    public static function validateNumber($value)
    {
        if (is_numeric($value)) {
            return true;
        }

        if (is_string($value) && (preg_match(self::PATTERN_INTEGER, $value) ||
                preg_match(self::PATTERN_INTEGER, $value)))
        {
            return true;
        }

        return false;
    }

    /**
     * 布尔验证（1：true 0：false）
     * @param $value
     * @return bool
     */
    public static function validateBoolean($value)
    {
        if (is_integer($value)) {
            return $value === 1 || $value === 0;
        }

        if (is_string($value)) {
            return $value === '1' || $value === '0';
        }

        return false;
    }

    /**
     * 是否在范围集合内验证
     * @param $value
     * @param $options
     * @return bool|string
     */
    public static function validateIn($value, $options)
    {
        $in = $options['in'] ?? [];

        return in_array($value, $in);
    }

    /**
     * 正则验证
     * @param $value
     * @param $options
     * @return bool
     */
    public static function validateRegex($value, $options)
    {
        $pattern = $options['pattern'] ?? '';

        if (!$pattern) {
            return false;
        }

        if (!is_string($value)) {
            $value = (string) $value;
        }

        return preg_match($pattern, $value) > 0;
    }

    /**
     * URL地址验证
     * @param $value
     * @return bool
     */
    public static function validateUrl($value)
    {
        if (is_string($value) && preg_match(self::PATTERN_URL, $value))
        {
            return true;
        }

        return false;
    }

    /**
     * 邮箱验证
     * @param $value
     * @return bool|int
     */
    public static function validateEmail($value)
    {
        if (!is_string($value)) {
            return false;
        }

        if (!preg_match('/^(?P<name>(?:"?([^"]*)"?\s)?)(?:\s+)?(?:(?P<open><?)((?P<local>.+)@(?P<domain>[^>]+))(?P<close>>?))$/i', $value, $matches)) {
            return false;
        } else {
            if (strlen($matches['local']) > 64) {
                $valid = false;
            } elseif (strlen($matches['local'] . '@' . $matches['domain']) > 254) {
                $valid = false;
            } else {
                $valid = preg_match(self::PATTERN_EMAIL, $value);
            }
            return $valid;
        }
    }

    /**
     * 数组验证
     * @param $value
     * @param $options
     * @return bool
     */
    public static function validateArray($value, $options)
    {
        if (!is_array($value)) return false;

        $validator = $options['validator'] ?? 'string';
        foreach ($value as $item) {
            if (!self::validate($validator, $item, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断非必填是否合法
     * @param $value
     * @param $options
     * @return bool
     */
    public static function checkIsCanEmpty($value, $options)
    {
        // 默认是非必填的
        $isRequired = $options['isRequired'] ?? false;

        if ((is_null($value) || $value === '') && !$isRequired) {
            return true;
        }

        return false;
    }

    /**
     * 判断长度是否合法
     * @param $value
     * @param $options
     * @return bool
     */
    public static function checkStringLengthValid($value, $options)
    {
        if (isset($options['min']) && strlen($value) < $options['min']) {
            return false;
        }

        if (isset($options['max']) && strlen($value) > $options['max']) {
            return false;
        }

        return true;
    }

    /**
     * 判断数字大小是否合法
     * @param $value
     * @param $options
     * @return bool
     */
    public static function checkNumberSizeValid($value, $options)
    {
        if (isset($options['min']) && $value < $options['min']) {
            return false;
        }

        if (isset($options['max']) && $value > $options['max']) {
            return false;
        }

        return true;
    }

    /**
     * 获取验证方法
     * @param $name
     * @return string
     */
    public static function getValidateMethodName($name)
    {
        if (!$name) {
            return '';
        }

        // 从缓存读取
        if (!is_null(self::$validators)) {
            if (isset(self::$validators[$name])) {
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
                if (substr($methodName, 0, 8) === 'validate' && $methodName !== 'validate') {
                    $validator = lcfirst(substr($methodName, 8));
                    $validators[$validator] = $methodName;
                }
            }
        }

        self::$validators = $validators;

        return self::getValidateMethodName($name);
    }


    public static function getValidateMessage($validator)
    {
        return self::$validatorMessages[$validator] ?? '格式错误';
    }
}
