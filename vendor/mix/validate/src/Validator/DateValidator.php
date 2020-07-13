<?php

namespace Mix\Validate\Validator;

use Mix\Validate\Validate;

/**
 * DateValidator类
 * @author liu,jian <coder.keda@gmail.com>
 */
class DateValidator extends BaseValidator
{

    // 启用的选项
    protected $_enabledOptions = ['format'];

    // 格式验证
    protected function format($param)
    {
        $value = $this->attributeValue;
        if (!Validate::isDate($value, $param)) {
            // 设置错误消息
            $defaultMessage = "{$this->attribute}不符合日期格式.";
            $this->setError(__FUNCTION__, $defaultMessage);
            // 返回
            return false;
        }
        return true;
    }

}
