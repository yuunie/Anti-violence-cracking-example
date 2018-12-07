<?php
    session_start();
    date_default_timezone_set('PRC');
    $msg = '';
    $host = 'localhost';
    $user = 'root';
    $pwd = '123456';
    $dbname = 'user';
    $dsn = "mysql:host={$host};dbname={$dbname}";
    
    if (!empty($_REQUEST['login']) && !empty($_REQUEST['u']) && !empty($_REQUEST['p'])) {
        $u = isset($_REQUEST['u']) ? $_REQUEST['u'] : '';
        $p = isset($_REQUEST['p']) ? $_REQUEST['p'] : '';

        try {
            $db = new PDO($dsn, $user, $pwd);

            // 判断用户是否存在
            $sql = "select * from user where username = :username";
            $sqlr = $db->prepare($sql);
            $res = $sqlr->execute([':username' => $u]);
            $data = $sqlr->fetch();

            if ($sqlr->rowCount() > 0) {
                // 用户存在 判断用户登录时间是否正常
                $nowtime = time();
                $lasttime = intval($data['lasttime']);
                $locktime = intval($data['locktime']);
                // 如果为负数 锁定解除
                $oktime = ($lasttime + $locktime) - $nowtime;

                // 取出的数据可能有问题的时候使用
                if ($oktime > 100000 || $oktime < -100000) {
                    $oktime == -1;
                }

                if ($oktime <= 0) {
                    // 已经可以登录了
                    if (md5($p) === $data['password']) {
                        // 密码正确
                        $_SESSION['user'] = $data['username'];
                        $msg = ':)成功登录[' . $_SESSION['user'] . '] ' . date('H:i:s');;
                    } else {
                        // 密码错误 更新用户登录时间 随机生成锁定时间
                        $lock = rand(10, 30);
                        $sql = "update user set `lasttime` = '{$nowtime}', `locktime` = '{$lock}' where username = :username";
                        $sqlr = $db->prepare($sql);
                        $sqlr->execute([':username' => $u]);
                        $msg = '!!!密码错误，请' . $lock . '秒后再登录' . date('H:i:s');;
                    }
                } else {
                    // 用户锁定中
                    $msg = '用户锁定! 请' . $oktime . '秒后在登录' . date('H:i:s');; 
                }
            } else {
                $msg = '!!!用户不存在' . date("H:i:s");
            }
        } catch (PDOException $e) {
            $msg = '数据库连接错误' . $e->getMessage();
        }
    } else {
        $msg = '输入用户名密码登录';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>登录</title>
</head>
<body>
    <div style="text-align: center">
        <form action="" method="post">
            <p>
                <h1>登录-防止暴力破解</h1>
            </p>
            <p><?php echo $msg; ?></p>
            <p><input type="text" name="u" placeholder="默认: admin"></p>
            <p><input type="password" name="p" placeholder="默认: password"></p>
            <p><input type="submit" value="登录" name="login"></p>
        </form>
    </div>

    <div>
        <a href="php.txt" target="__blank">PHP文件查看</a>
        <a href="sql.png" target="_blank">数据库查看</a>
    </div>

</body>
</html>
