<?php
declare(strict_types=1);

/**
 * Logic层业务逻辑表单的验证规则配置类
 * @desc 对业务逻辑进行抽离，以简化控制器的代码简洁
 * @author Roers
 * @email 380552499@qq.com
 */
namespace Roers\SwLogic;

class FormValidateRule
{
    /**
     * 属性
     * @var string
     */
    public $attribute;

    /**
     * 校验器名
     * @var string
     */
    public $validate;

    /**
     * 校验错误提示信息
     * @var string
     */
    public $message;

    /**
     * 必填校验错误提示信息
     * @var string
     */
    public $requiredMessage;

    /**
     * 最大最小值校验错误提示信息
     * @var string
     */
    public $maxMinMessage;

    /**
     * 是否必填
     * @var string
     */
    public $isRequired;

    /**
     * 是否有最大最小限制
     * @var string
     */
    public $isMaxMin;

    /**
     * 选项配置
     * @var string
     */
    public $options;
}
