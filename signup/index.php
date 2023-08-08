<?php
//пароли и другие конфиденциальные значения
$db_user = "YOUR_DB_USERNAME";
$db_password = "YOUR_DB_PASSWORD";
$recaptcha_public = "RECAPTCHA_PUBLIC_KEY";
$recaptcha_secret = "RECAPTCHA_SECRET_KEY";

//подключение к БД
define("LINK", mysqli_connect("localhost", $db_user, $db_password));
if (LINK == false){
    echo "Error: can't connect with database";
    exit;
}
mysqli_select_db(LINK,"juicedev");

if (isset($_COOKIE["id"]) AND isset($_COOKIE["hash"])){
    //сверяем хэши и при необходимости переадресовываем на главную
    $user = mysqli_fetch_object(mysqli_query(LINK, "SELECT * FROM `users` WHERE id = '{$_COOKIE["id"]}'"));
    if ($user -> hash ?? false === $_COOKIE["hash"]){
        header("Location: /juicedev/");
        exit;
    }
}

//получаем данные формы и проверяем правильность капчи
$login = $_REQUEST['login'] ?? "";
$password = $_REQUEST['password'] ?? "";
$captcha = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response=" . ($_REQUEST["g-recaptcha-response"] ?? "")));
$error = false;

function generateHash(){
    $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    $hash = "";
    for ($i = 0; $i < 36; $i++){
        $hash .= $alphabet[rand(0,61)];
    }
    return (mysqli_fetch_object(mysqli_query(LINK, "SELECT * FROM `users` WHERE hash = '{$hash}'")) == false) ? $hash : generateHash();
}
function generateId(){
    $alphabet = "abcdefghijklmnopqrstuvwxyz0123456789";
    $id = "";
    for ($i = 0; $i < 10; $i++){
        $id .= $alphabet[rand(0,35)];
    }
    return (mysqli_fetch_object(mysqli_query(LINK, "SELECT * FROM `users` WHERE id = '{$id}'")) == false) ? $id : generateId();
}

//попытка регистрации
if ($login != "" and $password != ""){
    if (($captcha -> success ?? false) == true){
        if (preg_match("/^[a-zA-Zа-яА-Я0-9@._-]+$/", $login) AND mb_strlen($login) > 3){
            $user = mysqli_fetch_object(mysqli_query(LINK, "SELECT * FROM `users` WHERE login = '{$login}'"));
            if ($user == false) {
                $id = generateId();
                $hash = generateHash();
                mysqli_query(LINK, "INSERT INTO `users` (id, login, password, hash) VALUES ('{$id}', '{$login}', '".password_hash($password, PASSWORD_DEFAULT)."', '{$hash}')");
                setcookie("id", $id, time() + 3600 * 24 * 30 * 12, "/juicedev/", "prokal.tyt.su", true, true);
                setcookie("hash", $hash, time() + 3600 * 24 * 30 * 12, "/juicedev/", "prokal.tyt.su", true, true);
                header("Location: /juicedev/");
                exit;
            }
            else {
                $error = "Пользователь с таким логином уже зарегистрирован";
            }
        }
        else {
            $error = "Минимальная длина логина - 4 символа. В логине можно использовать только русский и английский алфавит, цифры и символы @._-";
        }
    }
    else {
        $error = "Нужно решить капчу";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="https://prokal.tyt.su/juicedev/favicon.ico" type="image/png">
    <title>Регистрация - JuiceDev</title>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
    <script type="text/javascript">
        var onloadCallback = function() {
            grecaptcha.render('recaptcha', {'sitekey' : '<?php echo $recaptcha_public; ?>'});
        };
    </script>
</head>
<body style="background: rgba(67,255,205,0.59); box-sizing: border-box; font-family: inherit;">
<style>
    @font-face {
        font-family: "VK Sans";
        src: url("/juicedev/VK Sans Medium.ttf") format("truetype");
    }
    .main {
        font-family: "VK Sans", serif;
        margin: 110px auto;
        text-align: center;
        border-radius: 25px;
        background: #FFFFFF;
        width: 95%;
        max-width: 600px;
    }
    @media (max-width: 767px) {
        .main {
            max-width: 95%;
        }
    }
    .form-control {
        display: block;
        width: calc(100% - 1.5rem);
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        border-radius: 5px;
    }
    .login-form {
        box-sizing: border-box;
        padding: 20px 30px;
        border-radius: 25px;
    }
    .title {
        font-size: 30px;
        text-align: center;
        padding-top: 20px;
    }
    .btn {
        border-color: #0d6efd;
        border-width: 3px;
        cursor: pointer;
        border-radius: 25px;
        font-size: 18px;
        font-family: "VK Sans", sans-serif;
        width: 100%!important;
        text-decoration: none;
    }
    .error {
        font-size: 18px;
        border-top-left-radius: 25px;
        border-top-right-radius: 25px;
        min-height: 25px;
        background: rgba(255,7,0,0.41);
        padding: 10px 10px;
    }
    a {
        text-decoration: none !important;
        border: none !important;
    }
    .form-group {
        padding: 5px 5px;
    }
</style>
<div class="main">
    <?php if ($error != false){ ?>
        <div class="error">
            <?php echo $error; ?>
        </div>
    <?php } ?>
    <h3 class="title">Регистрация</h3>
    <div class="login-form">
        <form method="POST">
            <div class="form-group">
                <input type="text" class="form-control" id="login" name="login" required
                       placeholder="Логин">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" id="password" name="password" required
                       placeholder="Пароль">
            </div>
            <div class="form-group" style="text-align: center;">
                <div style="display: inline-block;" id="recaptcha"></div>
            </div>
            <div class="form-group">
                <button class="form-control btn" style="background: #0d6efd; color: #FFFFFF">
                    Зарегистрироваться
                </button>
            </div>
        </form>
        <div class="form-group">
            <a href="/juicedev/login/">
                <button class="form-control btn" style="background: #FFFFFF; color: #0d6efd;">
                    Войти
                </button>
            </a>
        </div>
    </div>
</div>
</body>