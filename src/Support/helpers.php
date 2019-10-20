<?php

/*
 * This file is part of the jiannei/http.
 *
 * (c) jiannei<longjian.huang@aliyun.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (!function_exists('tap')) {
    function tap($value, $callback)
    {
        $callback($value);

        return $value;
    }
}
