<?php

require_once 'baseMigrate.php';

$action = strtolower($_GET['action']);
$count = $_GET['count'];

try {
$app = new Migrate();

switch ($action) {
    case 'up':
        $app->createTable();
        $app->upCommand($count);
        break;
    case 'down':
        $app->downCommand($count);
        break;
}
} catch (Exception $exception) {
    
    $expMessage = 'Произошло исключение ' 
    . 'Сообщение: ' . $exception->getMessage() . ';'
    . 'Файл: ' . $exception->getFile() . ';' 
    . 'Код ошибки: ' . $exception->getCode() . ';' 
    . 'Строка, где произошла ошибка: ' . $exception->getLine() . ';'
    . 'Стек: ' . $exception->getTraceAsString() . '.';
    $app->setHistory($expMessage);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Браузерный интерфейс миграций</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
<div class="form_conteiner">
    <h1>Выполнение миграций</h1>
    <form method="get">
        <p><label>Выберите действие:
                <select name="action">
                    <option value="up">UP</option>
                    <option value="down">DOWN</option>
                </select>
            </label>
        </p>
        <p><label for="count">Укажите количество миграций, которые бы Вы хотели откатить/добавить:</label> <input
                type="text" id="count" name="count" size="10">
        </p>
        <p><input type="submit" class="button" value="Осуществить"></p>
    </form>
</div>
<div class="conteiner">
    <?php if (!empty($app->getHistory())): ?>
        <?php foreach ($app->getHistory() as $value): ?>
            <p><?= $value; ?><hr></p>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>