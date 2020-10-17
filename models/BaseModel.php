<?php

namespace app\models;

use app\components\Functions;
use app\components\QueryBuilder;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\Mysql\Select;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use PDO;

/**
 * Родительский класс модели для работы с базой данных
 * 
 * - использует для подключения к базе данных класс DB на основе PDO
 * - использует медоты с приминением позднего статического связывания, т.е. достаточно просто объявить дочерний класс и все методы родительского класса будут применяться с учётом имени дочернего класса.
 *  - действия совершаемые $this->__construct() вызывает метод $this->setColumns()  который в свою очередь формирует в свойстве $this->attributes ассоциативный массив где ключами являются имена столбцов таблицы с одноимённым модели названием
 * @package app\models
 */
abstract class BaseModel
{
    /**
     * Table properties (id, name, ...)
     * 
     *  - свойство в котором хранится ассоциативный массив в виде элементов где ключом является имя столбца таблицы базы данных соответствующей имени класса, а значением null (возможно что то другое пока не добрался)
     *  - Данный массив формируется с помощью метода $this->setColumns() вызываемый в $this->__construct().
     * 
     * @var array ассоциативный массив где ключ имя столбца таблцы по модели а занчени null
     */
    protected $attributes;
    /**
     * Table name
     * 
     * - содержит название таблицы, получается с помощью $this->getTable(), по умолчанию null
     * @var string
     */
    protected static $table;
    /**
     * You can use any connection defining in the config file by array key name
     * 
     * - Содержит имя ключа ассоциативного массива (/config/db.php) в котором хранятся данные для подключения к базе данных, т.е. можно подключиться под разными ключами. 
     *  - По умолчанию строка default но определяется в родительском классе и соответственно данное значение моно переопределять в дочерних классах
     * @var string
     */
    protected static $connectionName = 'default';

    /**
     *  формирует массив с ключами в виде названий столбцов таблицы соответствующей модели
     *
     *  - вызывает метод $this->setColumns()  который в свою очередь формирует в свойстве $this->attributes ассоциативный массив где ключами являются имена столбцов таблицы с одноимённым модели названием
     *   
     *  @return void
     */
    public function __construct()
    {
        $this->setColumns();
    }

    /**
     * Method receives table columns and adds them to the attribute's array
     * 
     * - получает из таблицы  соответствующей имени модели (с помощью $this->getTable()) имена её столбцов, формирует из них в цикле ассоциативный массив, где каждый элемент состоит из ключа в виде названия столбца таблицы а значением  null.
     *  - Массив формируется в protected свойстве экземпляра класса  $this->attributes
     *  - Вызывается в $this->__construct() т.е. при создании экземпляра любой модели 
     * 
     *  @return void
     */
    protected function setColumns() : void
    {
        $result = DB::getPDO(static::$connectionName)->query('SHOW COLUMNS FROM ' . $this->getTable());

        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $this->attributes[$value['Field']] = null;
        }
    }

    /**
     *  Обращение к несуществующему или защищённому свойству
     * 
     *  - Проверяет является ли строка переданная в параметре метода именем ключа в ассоциативном массиве хранящимся в $this->attributes, если является то возвращает содержимое по данному ключу, если нет то возвращает null
     *  @param String $name имя несуществующего или защищённого свойства
     *  @return mix|null
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     *  Попытка изменить закрытое или несуществующее свойство
     * 
     *  - Если имя свойства ($name) есть в качестве ключа ассоциативном массиве $this->attributes и это имя не является ‘id’ то элементу с указанным ключом присваивается новое значение хранящееся в ($value)
     *  @param string $name имя ключа в ассоциативном массиве $this->attributes
     * @param mixed $value новое значение добавляемого в ассоциативный массив $this->attributes
     *  @return void
     */
    public function __set(string $name, $value)
    {
        if (array_key_exists($name, $this->attributes) && $name != 'id') { // id изменять нельзя
            $this->attributes[$name] = $value;
        }
    }

    /**
     *  добавляет передаваемый в метод параметр в качестве значения в $this->attributes['id']
     *  
     *  @param mixed $id
     *  @return void
     */
    protected function setId($id)
    {
        $this->attributes['id'] = $id;
    }

    /**
     *  возвращает ассоциативный массив хранящейся в $this->attributes
     *  
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Добавление в $this->attributes (который хранит ассоциативный массив с элементами у которых ключами являются имена столбцов таблицы ) новых данных из передваемого массива 
     * 
     * - Передаваемый в качестве аргумента массив должен быть ассоциативным где ключи должны соотвествовать ключам из $this->attributes
     * - Данные добавляются циклически  только по тем ключам которые есть в $this->attributes
     * - Если передаваемые в метод данные являются новыми для этой таблицы (новые это у которых в $this->attributes['id'] хранится null), то автоматически будут добавлены значения по умолчанию используемые для данной таблице, если иных значений нет в передаваемом в качестве аргумента массиве 
     * @param array $values
     * @return void
     */
    public function setAttributes(array $values)
    {
        foreach ($values as $key => $value) {
            if ($key == 'id') {
                $this->setId($value);
            } else {
                $this->$key = $value;
            }
        }
        
        if ($this->isNewRecord()) {
            $values = $this->setDefaultAttributes($values);
            foreach ($values as $key => $value) {
                if ($key == 'id') {
                    $this->setId($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     *  Если запись для передачи в БД новая, то этот метод записывает в передаваемый массив, значения по умолчанию которые указываются в $this->defaultAttributes() для конкретной таблицы 
     *  
     *  @param array $requestData
     *  @return array $requestData видоизминённый массив со значениями по умолчанию
     */
    protected function setDefaultAttributes(array $requestData)
    {
        foreach ($this->defaultAttributes() as $key => $value) {
            
            if (is_null($this->$key) && !isset($requestData[$key])) {
                $requestData[$key] = $value;
            }
        }
        return $requestData;
    }

    /**
     *  Создаёт и возвращает массив в котором элементы состоят из ключей в виде названия столбцов (запрашиваемой моделью таблицы) в которых используются значения по умолчанию а значениями данных элементов массива указываются значения по умолчанию для данных столбцов используемых в таблице
     *   
     *  - В BaseModel пустой массив, так как для каждой таблицы могут быть свои столбцы со значениями по умолчанию
     *  - в дочерних моделях этот массив может быть переопределён конкретными столбцами для запрашиваемой таблицы
     *  - надо помнить что значения по умолчанию указываются для таблицы с названием идентичным названию модели которую вы сейчас смотрите
     *  @return array 
     */
    protected function defaultAttributes()
    {
        return [];
    }

    /**
     *  Возвращаем копию массива хранящимся в $this->attributes но без элемент с ключом id) 
     * 
     *  - перед возвратом заполняются значения по умолчанию если они пустые
     *  
     *  @return array 
     */
    public function getAttributesWithoutId()
    {
        $withoutId = $this->attributes;
        unset($withoutId['id']);
        foreach ($withoutId as $key => $value) {
            if (is_null($value)) {
                $default = $this->defaultAttributes();
                if (isset($default[$key])) {
                    $withoutId[$key] = $default[$key];
                }
            }
        }

        return $withoutId;
    }

    /**
     *  Возвращает копию массива хранящегося в $this->attributes в возвращаемом массиве все элементы не имеют значение null то есть являются заполненными
     * 
     *  - Если аргумент при вызове метода установлен в истину то в возвращаемом массиве удаляется $this->attributes['id']
     *  - При возврате не проверяются значения по умолчанию, а элементы массива проверяется только на заполненность
     *  @param bool $withOutId
     *  @return array $withoutId
     */
    public function getFilledAttributes($withOutId = true)
    {
        $withoutId = $this->attributes;
        if ($withOutId) {
            unset($withoutId['id']);
        }
        foreach ($withoutId as $key => $value) {
            if (is_null($value)) {
                unset($withoutId[$key]);
            }
        }

        return $withoutId;
    }

    /**
     *  Возвращает имя таблицы для запроса
     * 
     *  - из строки подключения класса модели (static::class) получает имя таблицы, так как имя таблицы ровно имени модели но в нижнем регистре, добавляет это имя в свойство static::$table и затем возвращает данное свойство.
     *  
     *  @return string свойство static::$table содержащая имя таблицы для запроса
     */
    public static function getTable() : string
    {
        $tableName = explode("\\", static::class);
        $model = end($tableName);
        $name = Functions::splitByCapital(lcfirst($model));
        static::$table = strtolower($name);

        return static::$table;
    }

    /**
     *  Имя таблицы для запроса перезаписывается строкой переданной методу в качестве аргумента
     *
     *  - Аргумент метода перезаписывает static::$table
     *  @param string $name
     *  @return void
     */
    public static function setTable(string $name) : void
    {
        if (!empty($name)) {
            static::$table = $name;
        }
    }

    /**
     *  Формирует и возвращает интерфейс запроса на выборку данных из столбцов таблицы БД, на основе построителя запросов Aura.SqlQuery  
     * 
     * - Таблица для запроса берётся из static::getTable()
     * - Столбцы таблицы передаются в метод через первый параметр ($cols) в виде индексного массива 
     * - Если второй параметр $withId истина (по умолчанию) в массив запрашиваемых столбцов принудительно добавляется элемент 'id'
     * - Иные выражения типа WHERE и тд в данном методе не используются 
     * - Данный метод должен использоваться в качестве аргумента в объекте подключения к БД 
     * - Возвращает интерфейс запроса это значит что к данному методу можно добавить цепочкой иные уточняющее методы из Aura.SqlQuery (например ->where('bar > :bar')) или методы модели созданные на основе Aura.SqlQuery         
     * 
     * @param array $cols массив с именами запрашиваемых столбцов
     * @param bool $withId если истина значит запрашиваетя и id
     * @return SelectInterface
     * @see https://github.com/auraphp/Aura.SqlQuery
     */
    public static function select(array $cols, bool $withId = true)
    {
        if ($withId) {
            $cols[] = 'id';
        }
        return QueryBuilder::getInstance()->newSelect()->from(static::getTable())->cols($cols);
    }

    /**
     *  Создаёт и возвращает интерфейс на выборку из БД, на основе построителя запросов Aura.SqlQuery. Создаётся только интерфейс без указания таблицы и столбцов
     *
     *  - Данный метод должен использоваться в качестве аргумента в объекте подключения к БД
     *  - Возвращает интерфейс запроса это значит что к данному методу можно добавить цепочкой иные уточняющее методы из Aura.SqlQuery (например ->where('bar > :bar')) или методы модели созданные на основе Aura.SqlQuery
     * @return SelectInterface
     * @see https://github.com/auraphp/Aura.SqlQuery
     */
    public static function customSelect()
    {
        return QueryBuilder::getInstance()->newSelect();
    }

    /**
     *  Создаёт и возвращает интерфейс добавления данных в БД, на основе построителя запросов Aura.SqlQuery. учитывается таблица и столбцы в ней
     * 
     *  - Таблица для запроса берётся из static::getTable()
     *  - Столбцы таблицы передаются в метод через параметр ($values) в виде индексного массива
     *  - Данный метод должен использоваться в качестве аргумента в объекте подключения к БД 
     *  - Возвращает интерфейс запроса это значит что к данному методу можно добавить цепочкой иные уточняющее методы из Aura.SqlQuery (например ->set('ts')) или методы модели созданные на основе Aura.SqlQuery
     * 
     *  @param array $values индексный массив с именами используемых  столбцов таблицы
     *  @return InsertInterface Insert Object
     *  @see https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/insert.md
     * 
     */
    public static function insert(array $values)
    {
        return QueryBuilder::getInstance()->newInsert()->into(static::getTable())->cols($values);
    }

    /**
     *  Создаёт и возвращает интерфейс обновления данных в БД, на основе построителя запросов Aura.SqlQuery. учитывается таблица и столбцы в ней
     * 
     *  - Таблица для запроса берётся из static::getTable()
     *  - Столбцы таблицы передаются в метод через параметр ($values) в виде индексного массива
     *  - Данный метод должен использоваться в качестве аргумента в объекте подключения к БД 
     *  - Возвращает интерфейс запроса это значит что к данному методу можно добавить цепочкой иные уточняющее методы из Aura.SqlQuery (например ->set('ts')) или методы модели созданные на основе Aura.SqlQuery
     * 
     *  @param array $values индексный массив с именами используемых  столбцов таблицы
     *  @return UpdateInterface Update  Object
     *  @see https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/update.md
     * 
     */
    public static function update(array $values)
    {
        return QueryBuilder::getInstance()->newUpdate()->table(static::getTable())->cols($values);
    }

    //    public static function delete()
    //    {
    //        return QueryBuilder::getInstance()->newDelete()->from(static::getTable());
    //    }



    /**
     *  Базовый метод запроса в БД на основе PDO в первом параметре указывается интерфейс запроса Aura.SqlQuery
     * 
     *  - в первом параметре метода указывается цепочка методов из Aura.SqlQuery - это и является QueryInterface - в качестве первого элемента данной цепочки указывается (::select, ::insert, ::update)  с необходимыми столбцами, дальше к этому методу при необходимости цепочкой навешиваются другие методы из Aura.SqlQuery с помощью которых достигается нужная логика запроса
     * 
     *  - ОПИСАНИЕ ВОЗВРАЩАЕМЫХ ЗНАЧЕНИЙ:
     *  - Данные полученные из базы данных обрабатываются только если используется выборка (SelectInterface) и возвращается объект модели или массив с объектами модели в которых в $this->attributes находится  массив со строками из БД,
     *  -- Если при SelectInterface из базы возвращена одна строка и она пуста возвращается null
     *  -- Если при SelectInterface второй аргумент метода истина то возвращается один объект из результатирующего массива
     *  -- Если при SelectInterface второй аргумент метода ложь (по умолчанию) возвращается ассоциативный массив со всеми строками запроса
     * 
     *  - Если используется InsertInterface т.е. добавление в базу, то полученный ответ из базы не обрабатывается и возвращается объект $statement т.е. первый аргумент метода 
     *  - Если запрос был на обновление или удаление, осуществляется запрос в базу а метод возвращает истину или ложь говорящий о результате запроса в базу
     *  
     * @param QueryInterface $statement цепочка из методов Aura.SqlQuery с помощью которых формируется запрос в базу
     * @param bool  $selectOne определяет количество строк в возвращаемом массиве
     * @return mixed смотри  расшифровку в описании
     */
    protected static function execute(QueryInterface $statement, bool $selectOne = false)
    {
        $query = DB::getPDO(static::$connectionName)->prepare($statement->getStatement());
        $result = $query->execute($statement->getBindValues());

        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        if ($statement instanceof SelectInterface) {
            // Если возвращена одна строка, то проверяем пустая она или нет, если пустая то возвращаем null
            if ($query->rowCount() == 1) {
                $flag = false;
                if (count($rows)) {
                    foreach ($rows[0] as $key => $value) {
                        if (!is_null($value)) {
                            $flag = true;
                        }
                    }
                }
                if (!$flag) {
                    return null;
                }
            }
            if ($selectOne) {
                return static::getInstance(
                    $rows
                );
            } else {
                return static::getInstances(
                    $rows
                );
            }
        } elseif ($statement instanceof InsertInterface) {
            return $statement;
        }

        return $result;
    }

    /**
     *  Запрос в БД и возвращает одну строку (1й элемент результирующего массива)  
     * 
     *  - Запрос осуществляется с помощью DB::getPDO()
     *  - в параметре метода указывается цепочка методов из Aura.SqlQuery - это и является QueryInterface - в качестве первого элемента данной цепочки указывается один из вариантов запроса ::select (SelectInterface), ::insert (InsertInterface), ::update (UpdateInterface)  с необходимыми столбцами , дальше к этому методу при необходимости цепочкой навешиваются другие методы из Aura.SqlQuery с помощью которых достигается нужная логика запроса
     * 
     *  - ОПИСАНИЕ ВОЗВРАЩАЕМЫХ ЗНАЧЕНИЙ:
     *  - Данные полученные из базы данных обрабатываются и возвращаются только если используется выборка (SelectInterface) 
     *  -- возвращается объект экземпляра BaseModel в $this->attributes находится  массив со строками из БД, с объектом  надо работать как с обычным объектом модели
     *  -- Если при SelectInterface из базы возвращена одна строка и она пуста возвращается null
     *  
     *  - Если при запросе используется InsertInterface т.е. добавление в базу, то полученный ответ из базы не обрабатывается и возвращается объект $statement т.е. первый аргумент метода 
     *  - Если запрос был на обновление (UpdateInterface) или удаление, осуществляется запрос в базу а метод возвращает истину или ложь говорящий о результате запроса в базу
     *  
     * @param QueryInterface $statement цепочка из методов Aura.SqlQuery с помощью которых формируется запрос в базу
     * @return mixed смотри  расшифровку в описании
     */
    public static function executeOne(QueryInterface $statement)
    {
        return static::execute($statement, true);
    }

    /**
     *  Запрос в БД и возвращает массив всех строк удовлетворяющих запросу 
     * 
     *  - Запрос осуществляется с помощью DB::getPDO()
     *  - в параметре метода указывается цепочка методов из Aura.SqlQuery - это и является QueryInterface - в качестве первого элемента данной цепочки указывается один из вариантов запроса ::select (SelectInterface), ::insert (InsertInterface), ::update (UpdateInterface)  с необходимыми столбцами , дальше к этому методу при необходимости цепочкой навешиваются другие методы из Aura.SqlQuery с помощью которых достигается нужная логика запроса
     * 
     *  - ОПИСАНИЕ ВОЗВРАЩАЕМЫХ ЗНАЧЕНИЙ:
     *  - Данные полученные из базы данных обрабатываются и возвращаются только если используется выборка (SelectInterface) 
     *  -- возвращается индексный массив из объектов экземпляра BaseModel в $this->attributes которых находится  массив со строками из БД, с объектом  надо работать как с обычным объектом модели
     *  -- Если при SelectInterface из базы возвращена одна строка и она пуста возвращается null
     * 
     *  - Если используется InsertInterface т.е. добавление в базу, то полученный ответ из базы не обрабатывается и возвращается объект $statement т.е. первый аргумент метода 
     *  - Если запрос был на обновление или удаление, осуществляется запрос в базу а метод возвращает истину или ложь говорящий о результате запроса в базу
     *  
     * @param QueryInterface $statement цепочка из методов Aura.SqlQuery с помощью которых формируется запрос в базу
     * @return mixed смотри  расшифровку в описании
     */
    public static function executeMany(QueryInterface $statement)
    {
        return static::execute($statement);
    }

    /**
     *  Запрос в базу на основании произвольно построенных методов из Aura.SqlQuery
     * 
     * - Запрос осуществляется с помощью DB::getPDO()
     * - В параметре метода указывается цепочка методов из Aura.SqlQuery - это и является QueryInterface - в качестве первого элемента данной цепочки указывается либо  ::select (SelectInterface), ::insert (InsertInterface), ::update (UpdateInterface)  с необходимыми столбцами , дальше к этому методу при необходимости цепочкой навешиваются другие методы из Aura.SqlQuery с помощью которых достигается нужная логика запроса
     * - ОПИСАНИЕ ВОЗВРАЩАЕМЫХ ЗНАЧЕНИЙ:
     * - SelectInterface возвращает:
     * -- Если вернулась одна строка и она пустая возвращается null
     * -- В ином случае индексный массив, если есть записи то элементами являются ассоциативные массивы по каждой записи из базы
     * - InsertInterface полученный ответ из базы не обрабатывается и возвращается объект $statement т.е. аргумент метода 
     *  - UpdateInterface возвращает истину или ложь говорящий о результате запроса в базу
     * - Отличие от executeMany, в том что последний вовзращает индексный массив с объектами модели и записи из базы хранятся в в $this->attributes 
     * @param QueryInterface $statement цепочка из методов Aura.SqlQuery с помощью которых формируется запрос в базу
     * @return array|QueryInterface|null
     */
    public static function auraCustomExecute(QueryInterface $statement)
    {
        $query = DB::getPDO(static::$connectionName)->prepare($statement->getStatement());
        $result = $query->execute($statement->getBindValues());

        if ($statement instanceof SelectInterface) {
            $rows = $query->fetchAll(PDO::FETCH_ASSOC);
            if ($query->rowCount() == 1) {
                $flag = false;
                if (count($rows)) {
                    foreach ($rows[0] as $key => $value) {
                        if (!is_null($value)) {
                            $flag = true;
                        }
                    }
                }
                if (!$flag) {
                    return null;
                }
            }

            return $rows;
        } elseif ($statement instanceof InsertInterface) {
            return $statement;
        }

        return $result;
    }

    /**
     *  Запрос в БД, на основе произвольного SQL-запроса в соотвествии с PDO
     *
     *  @param string $statement  строка SQL-запроса
     *  @param array $binds  массив подстановочных в запрос значений
     *  @return object объект PDO
     */
    public static function customExecute($statement, $binds)
    {
        $query = DB::getPDO(static::$connectionName)->prepare($statement);
        $query->execute($binds);

        return $query;
    }

    /**
     *  Используется в методе execute для создания объекта класса производного от BaseModel и добавления в его свойство attributes  первой записи из результатирующего массива из БД (параметр метода)
     *  - 
     *  @param array $values массив с записями полученный из запроса в базу данных
     *  @return object|null объект экземпляра класса производного от BaseModel у которого в свойстве attributes находится ассоциативных массив с названиями столбца и значением по столбцу
     */
    protected static function getInstance(array $values)
    {
        if (count($values)) {
            $instance = new static;
            $instance->setAttributes($values[0]);

            return $instance;
        }

        return null;
    }

    /**
     *  Используется в методе execute для создания массива с объектами класса производного от BaseModel и добавления в свойства attributes этих объектов данных из передаваемого аргументом массива - каждый объект одна запись из БД
     *  - 
     *  @param array $values массив с записями полученный из запроса в базу данных
     *  @return array массив с объектами экземпляра класса производного от BaseModel у которых в свойстве attributes находится ассоциативных массив с названиями столбца и значением по столбцу
     */
    protected static function getInstances(array $values)
    {
        if (count($values)) {
            $instancesArray = [];
            foreach ($values as $data) {
                $instance = new static;
                $instance->setAttributes($data);
                $instancesArray[] = $instance;
            }

            return $instancesArray;
        }

        return null;
    }

    /**
     *  Осущесвтляет запрос на выборку со связыванием двух таблиц
     *  
     *  @param string $className имя класса с пространством имён из которого будет получена таблица для связывания
     *  @param string $foreignKey имя сравниваемого столбца
     *  @param string $localKey имя сравниваемого столбца
     *  @return object по факту выполняется executeOne с сформированным запросом
     */
    public function hasOne(string $className, string $foreignKey, string $localKey)
    {
        return $this->has($className, $foreignKey, $localKey, true);
    }

    /**
     *  Осущесвтляет запрос на выборку со связыванием двух таблиц
     *  
     *  @param string $className имя класса с пространством имён из которого будет получена таблица для связывания
     *  @param string $foreignKey имя сравниваемого столбца
     *  @param string $localKey имя сравниваемого столбца
     *  @return array по факту выполняется executeMany
     */
    public function hasMany(string $className, string $foreignKey, string $localKey)
    {
        return $this->has($className, $foreignKey, $localKey);
    }

    /**
     *  Осущесвтляет запрос на выборку со связыванием двух таблиц
     *  
     *  @param string $className имя класса с пространством имён из которого будет получена таблица для связывания
     *  @param string $foreignKey имя сравниваемого столбца
     *  @param string $localKey имя сравниваемого столбца
     *  @param bool $limit применять или нет лимит при выборке
     *  @return array|object варианты зависит от $limit так как по факту выполняется либо executeOne либо executeMany
     */
    protected function has(string $className, string $foreignKey, string $localKey, bool $limit = false) {
        $model = Functions::getTableName($className);
        $className = "\\$className";
        $foreignKey = "$model.$foreignKey";
        $localKey = $this->getTable() . '.' . $localKey;

        $query = $this->select([$model . '.*'], false)->join('left', $model, "$foreignKey = $localKey")->where("{$this->getTable()}.id = " . $this->id);
        if ($limit) {
            return $className::executeOne($query);
        }

        return $className::executeMany($query);
    }

    /**
     *  Проверяет является ли значение свойства $this->id равным NULL
     * 
     *  - если $this->id равно null возвращается true
     *  - иначе false
     *  
     *  @return bool
     */
    public function isNewRecord()
    {
        if (is_null($this->id)) {
            return true;
        }

        return false;
    }

    /**
     * Добавляет или обновляет данные в таблицу БД
     * 
     * - отправляемые данные находятся в $this->attributes 
     * - таблица находится в $this->table 
     */
    public function save()
    {
        if ($this->isNewRecord()) {
            $insert = static::execute(
                self::insert($this->getAttributesWithoutId())
            );

            $name = $insert->getLastInsertIdName('id');
            $id = DB::getPDO(static::$connectionName)->lastInsertId($name);
            $this->setId($id);

            return true;
        } else {
            return static::execute(
                self::update($this->getFilledAttributes(true))->where('id = :id')->bindValue('id', $this->id)
            );
        }
    }
}