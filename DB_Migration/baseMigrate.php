<?php
define(ERROR_SQL_FATAL, 2);
define(ERROR_SQL_INQUIRY, 3);
/**
 * Основной класс работы с миграциями баз данных. 
 */
class Migrate
{
    //Параметры для подключения к базе данных
    const DB_HOST = 'localhost';
    const DB_USER = 'root';
    const DB_PASS = 'root';
    const DB_NAME = 'Migrate_DB';
    const DB_TABLE_NAME = 'DB_Migrate';

    private $mysqli; // Класс подключения к базе данных

    private $history = []; // Массив с информацией о истории действий

/**
 * Метод для создания истории действий.
 * 
 * @return array $history
 */
    public function getHistory()
    {
        return $this->history;
    }

/**
 * Метод для создания массива с информацией о истории.
 * 
 * @param string $message - информация о произведенных операциях
 * @return boolean - удалось ли добавить сообщение в массив с историей
 */
    protected function setHistory($message)
    {
        if (!empty($message)){
            array_push($this->history, $message);
            return true;
        }
        return false;
    }

/**
 * Метод подключения к базе данных.
 * 
 * @return class $mysqli
 */
    private function dbConnect()
    {
        $mysqli = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASS, self::DB_NAME);

        if ($mysqli->connect_error) {
            die('Ошибка подключения (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
        }

        $this->setHistory('Соединение с БД успешно');

        $this->mysqli = $mysqli;
        return $mysqli;
        
    }
/**
 * Метод для создания необходимой таблицы с информацией о произведенных миграциях.
 * 
 * @return true успешно выполненный запрос
 */
    public function createTable()
    {
        // Запрос создания таблицы для хранения миграций 
        $sqlCreateInquery = "CREATE TABLE if not exists " .self::DB_TABLE_NAME. " (
                            `id` int(11) unsigned not null auto_increment,
                            `class_Name` varchar(255) not null,
                            `datetime` int(11) not null,
                            primary key(id)
                            )

                            engine = innodb
                            auto_increment = 1
                            character set utf8
                            collate utf8_general_ci;";

        $mysqli = $this->mysqli;

        $sqlShowTable = "SHOW TABLES FROM " . self::DB_NAME;

        $sing = false; //Таблица отсутствует в БД

        $r = $mysqli->query($sqlShowTable);
        // Цикл для поиска необходимой таблицы в базе данных
        while ($row = $r->fetch_row()) {
            foreach ($row as $key => $value) {
                if($row[$key] === self::DB_TABLE_NAME){
                    $sing = true; //Таблица существует в базе данных
                }   
            }
        } 
        
        $r->close();

        // Провека существования необходимой таблицы в базе данных
        if ($sing === false) {
            //Выполнение запроса на создание таблицы миграций 
            if($mysqli->query($sqlCreateInquery) === true) {
                $this->setHistory("Таблица " . self::DB_TABLE_NAME . " была успешно создана");
            } else { 
                throw new Exception("Ошибка: %s\n" . $mysqli->error, ERROR_SQL_FATAL);
            }
        } else {
            $this->setHistory("Таблица " . self::DB_TABLE_NAME . " уже существует");
        }
        return true;  
    }

    public function __construct()
    {
        $this->dbConnect();
    }

    public function __destruct()
    {
        //Закрываем соединение с базой данных
        $this->mysqli->close();
    }

/**
 * Метод для исполнения и занесения информации о проведенных миграциях.
 * 
 * @param null|number $count количество миграций, которые необходимо применить
 */
    public function upCommand($count = null)
    {

        //Переменная для учета времени миграции
        $dt = time();

        //Переменная для запроса иформации таблицы базы данных
        $sql_inquiry = "SELECT class_Name FROM " . self::DB_TABLE_NAME;


        //Запрос на подключение к базе данных
        $mysqli = $this->mysqli;

        //Переменная для заненесения информации о содержащихся в базе даннх миграций в массив
        $class_Name_up = [];

        //После запроса перебираем массив имен файлов уже примененных миграций и заносим их в массив
        if ($result = $mysqli->query($sql_inquiry)) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $value) {
                    array_push($class_Name_up, $value);
                }
            }
            $result->free();
        }

        //Переменные для выбора файла из дирректории "up"
        $dir = 'up/';
        $file_up_arr = scandir($dir);


        //Массив с информацией имен файлов содержащихся в указанной выше директории
        $arr_file_up = [];

        //Переберам имена файлов в директории и заносим значения в массив
        foreach ($file_up_arr as $value) {
            if (stripos($value, '.') !== 0) {
                if (substr($value, -4) == '.sql'){
                    array_push($arr_file_up, $value); 
                }
            }
        }
        //Сравниваем два массива (массив с информацией о миграциях из базы данные и масив имен файлов из дирректории) и заносим не существующие элементы в массив
        $val_arr_up = array_diff($arr_file_up, $class_Name_up);

        //Подсчитываем количество элементов в массиве
        $count_arr = count($val_arr_up);

        if($count!=null && is_numeric($count)) {
            //Оставляем необходимое количество элементов в массиве
            $val_arr_up = array_slice($val_arr_up, 0, $count);
        }
            //Занесение данных о проделанных миграциях в таблицу
            foreach ($val_arr_up as $value) {
                $stmt = $mysqli->prepare("INSERT INTO " . self::DB_TABLE_NAME . " (class_Name, datetime) VALUES (?,?)");
                $stmt->bind_param("si", $value, $dt);
                $stmt->execute();
                $stmt->close();
                $this->setHistory('Добавлена информация о примененной миграции с именем: ' . $value . ' в таблицу');

                //Выполнение существующих миграций
                $file_contents = file_get_contents('up/' . $value);
                $this->setHistory('Выполнен файл с именем: ' . $value);
                if (strlen($file_contents) == 0){
                    $this->setHistory('Файл: ' . $value . ' - ПУСТОЙ!!!');
                } else {
                    if(!$mysqli->query($file_contents)){
                        $this->setHistory("Не удалось выполнить запрос: (" . $mysqli->errno . ") " . $mysqli->error);
                        throw new Exception("Не удалось выполнить запрос: (" . $mysqli->errno . ") " . $mysqli->error, ERROR_SQL_INQUIRY);
                    }
                }

            }
    }

   
 /**
 * Метод для отмены выполненых миграций и удаление их из таблицы.
 * 
 * @param null|number $count количество миграций, которые необходимо отменить
 */
    public function downCommand($count = null)
    {
        //Переменная для учета времени миграции
        $dt = time();

        //Переменная для запроса иформации таблицы базы данных
        $sql_inquiry = "SELECT class_Name FROM " . self::DB_TABLE_NAME;


        //Запрос на подключение к базе данных
        $mysqli = $this->mysqli;

        //Переменная для заненесения информации о содержащихся в базе данных миграций в массив
        $class_Name_down = [];

        //После запроса перебираем массив имен файлов уже примененных миграций и заносим их в массив
        if ($result = $mysqli->query($sql_inquiry)) {
            while ($row = $result->fetch_assoc()) {
                foreach ($row as $value) {
                    array_push($class_Name_down, $value);
                }
            }
            $result->free();
        }

        //Переменные для выбора файла из дирректории "down"
        $dir = 'down/';
        $file_down_arr = scandir($dir, 1);

        //Массив с информацией имен файлов содержащихся в указанной выше директории
        $arr_file_down = [];

        //Переберам имена файлов в директории и заносим значения в массив
        foreach ($file_down_arr as $value) {
            if (stripos($value, '.') !== 0) {
                array_push($arr_file_down, $value);
            }
        }

        //Сравниваем два массива (массив с информацией о миграциях из базы данных и массив имен файлов из дирректории) и заносим не существующие элементы в новый массив
        $val_arr_down = array_intersect($arr_file_down, $class_Name_down);

        //Подсчитываем количество элементов в массиве
        $count_arr = count($val_arr_down);

        if($count!=null && is_numeric($count)) {
            //Оставляем необходимое количество элементов в массиве
            $val_arr_down = array_slice($val_arr_down, 0, $count);
        }
            //Занесение данных о проделанных миграциях в таблицу
            foreach ($val_arr_down as $value) {
                $stmt = $mysqli->prepare("DELETE FROM " . self::DB_TABLE_NAME . " WHERE `class_Name` = '$value'");
                $stmt->bind_param("si", $value, $dt);
                $stmt->execute();
                $stmt->close();
                $this->setHistory('Удалена информация о примененной миграции с именем: ' . $value . ' из таблицы');

            //Выполнение существующих миграций
                $file_contents = file_get_contents('down/' . $value);
                $this->setHistory('Выполнен файл с именем: ' . $value);
                if (strlen($file_contents) == 0){
                    $this->setHistory('Файл: ' . $value . ' - ПУСТОЙ!!!');
                } else {
                    if(!$mysqli->query($file_contents)){
                        $this->setHistory("Не удалось выполнить запрос: (" . $mysqli->errno . ") " . $mysqli->error);
                        throw new Exception("Не удалось выполнить запрос: (" . $mysqli->errno . ") " . $mysqli->error, ERROR_SQL_INQUIRY);
                    }
                }
            }     
    }
    /**
     * Метод для подсказки пользователю о существующих командах
     */
    public function helpCommand()
    {
        echo 'Справка системы' . PHP_EOL;
        echo 'Существуют следующие команды:' . PHP_EOL;
        echo 'php console.php up - применение миграций' . PHP_EOL;
        echo 'php console.php down - отмена миграций' . PHP_EOL;
        echo 'php console.php up n - применение n миграций' . PHP_EOL;
        echo 'php console.php down n - отмена n миграций' . PHP_EOL;
    }
}