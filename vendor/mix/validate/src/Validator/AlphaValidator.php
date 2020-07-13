<?php

namespace Mix\Validate\Validator;

use Mix\Validate\Validate;

/**
 * AlphaValidator类
 * @author liu,jian <coder.keda@gmail.com>
 */
class AlphaValidator extends BaseValidator
{

    // 初始化选项
    protected $_initOptions = ['alpha'];

    // 启用的选项
    protected $_enabledOptions = ['length', 'minLength', 'maxLength'];

    // 类型验证
    protected function alpha()
    {
        $value = $this->attributeValue;
        if (!Validate::isAlpha($value)) {
            // 设置错误消息
            $defaultMessage = "{$this->attribute}只能为字母.";
            $this->setError(__FUNCTION__, $defaultMessage);
            // 返回
            return false;
        }
        return true;
    }

}
