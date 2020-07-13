<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $message ?></title>
    <style type="text/css">
        body {
            font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace;
            margin: 20px;
        }
        h1, p {
            font-family: Consolas, "Liberation Mono", Courier, Verdana, "微软雅黑";
            font-size: 18px;
            font-weight: 500;
            color: #333;
            padding: 5px;
            margin: 0px;
        }
        a {
            color: #4183c4;
            text-decoration: underline;
        }
        a:hover {
            text-decoration: underline;
        }
        span, a {
            background-color: red;
            color: white;
            padding: 3px;
        }
    </style>
</head>
<body>

<h1><span><?= $message ?></span></h1>

<p><?= $type ?> code <?= $code ?></p>
<?php if (!empty($file)): ?>
    <p><span><?= $file ?></span> line <span><?= $line ?></span></p>
    <p><?= str_replace("\n", '<br>', $trace); ?></p>
<?php endif; ?>

<p style="margin-top: 20px;"><span><a href="http://mixphp.cn" target="_blank">MixPHP V<?= \Mix::$version ?></a> —— Single-threaded coroutine PHP microservice framework</span></p>

</body>
</html>
