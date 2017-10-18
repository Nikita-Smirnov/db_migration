<?php 

define(ERROR_CODE_EXIT, 1);
require_once 'baseMigrate.php';

$action = null;
if($argc == 2){
    $action = $argv[1];
} elseif ($argc == 3){
    $action = $argv[1];
    $count = $argv[2];
} else {
    echo 'Заданы неверные параметры'.PHP_EOL;
    exit(ERROR_CODE_EXIT); 
}

try {
$app = new Migrate();

switch ($action)
{
    case 'up': 
        $app->createTable();
        $app->upCommand($count);
        break;

    case 'down': 
        $app->downCommand($count);
    	break;
    default:
        echo 'Не существует такой команды'.PHP_EOL;
        $app->helpCommand();
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

if (!empty($app->getHistory())){
    foreach ($app->getHistory() as $value){
         echo $value . PHP_EOL;
    }
}


