<?php

    class SimpleNoSql
    {

        public $file;
        public $data;

        /**
         * Создание обьекта SimpleNoSql
         * 
         * @param filename - название файла
         * 
         * 1. Проверяеться на существование файла
         * 
         * 2. Декодирование json
         * 
         */
        function __construct(string $filename)
        {
            // Проверка на существование файла
            if(!file_exists($filename)) file_put_contents($filename, "{}");

            // Назначение свойства file
            $this->file = $filename;
            // Назначение свойства data
            $this->data = json_decode(file_get_contents($filename), true);

            // Если файл пустой изменяем на массив
            if($this->data == null)
            {
                $this->data = [];
            }

            // Сохранение
            $this->save();

        }
        /**
         * Сохранение изменений данных
         * 
         */
        protected function save() 
        {
            // Вставка данных в файл и сохранение
            file_put_contents($this->file, json_encode($this->data));
        }
        /**
         * Создание таблицы
         * 
         * @param table_name - название таблицы
         * 
         * 1. Проверяеться на наличие уже существующей таблицы
         * 
         * 2. Создание таблицы 
         * 
         * 3. Сохранение изменений
         * 
         */
        public function create_table($table_name)
        {
            try
            {
                if(array_key_exists($table_name, $this->data))
                {
                    throw new \Exception('Table already exists!');
                }
                else
                {
                    $this->data[$table_name] = [];
                    $this->save();
                    return true;
                }
            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
        /**
         * 
         */
        public function check_table($table_name)
        {
            try
            {
                if(array_key_exists($table_name, $this->data))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
        /**
         * Очистка от данных в таблице
         * 
         * 1. Проверяеться на наличие уже существующей таблицы
         * 
         * 2. Очистка
         * 
         * 3. Сохранение изменений
         * 
         */
        public function clear_table($table_name)
        {
            try
            {
                if(!array_key_exists($table_name, $this->data))
                {
                    throw new \Exception('Table not exists!');
                }
                else
                {
                    $this->data[$table_name] = [];
                    $this->save();
                    return true;
                }
            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
        /**
         * Удаление таблицы из файла
         * 
         * 1. Проверяеться на наличие уже существующей таблицы
         * 
         * 2. Сохранение изменений
         * 
         */
        public function drop_table($table_name)
        {
            try
            {
                if(!array_key_exists($table_name, $this->data))
                {
                    throw new \Exception('Table not exists!');
                }
                else
                {
                    unset($this->data[$table_name]);
                    $this->save();
                    return true;
                }
            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
        /**
         * Выборка данных из документа
         * 
         * @param table_name - Название таблицы
         * 
         * @param selection - Вывод определленных данных с документов 
         * default = 'all'
         * -------
         * Example
         * -------
         * $selection = ['uid', 'first_name', 'email'];
         * 
         * @param where $args - Фильтрация
         * -------
         * Example
         * -------
         * $where = [
         *      [
         *          "key" => "uid",
         *          "format" => "? > 10 and ? < 34"
         *      ]
         * ];
         * 
         * @param sorted
         * 
         */
        public function select_document(string $table_name, mixed $selection = 'all', mixed $where = null)
        {
            try
            {

                $data = [];

                if(!array_key_exists($table_name, $this->data)) throw new \Exception("Table not exists!");

                foreach($this->data[$table_name] as $db_row => $value)
                {
                    $prepare_row = [];

                    if($where != null)
                    {
                        $is_continue = true;
                        
                        foreach($where as $target => $exp)
                        {
                            $prepare_exp = 'if(' . sprintf($exp['format'], $value[$exp["key"]]) . ') $is_continue = false;';
                            eval($prepare_exp);
                        }

                        if($is_continue == true) continue;

                    }

                    if($selection != 'all')
                    {
                        for($i = 0; $i < count($selection); $i++)
                        {
                            if(array_key_exists($selection[$i], $value))
                            {
                                $prepare_row[$selection[$i]] = $value[$selection[$i]];
                            }
                        }
                    }
                    else
                    {
                        $prepare_row = $value;
                    }

                    array_push($data, $prepare_row);

                }

                return $data;
                
            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
        /**
         * Вставка данных в документ
         * 
         * @param table_name - Название таблицы
         * 
         * @param unique - вставка с определенным uid
         * 
         * @param to_many - вставка итерацией
         * 
         * @param data - данные
         * 
         */
        public function insert_document(string $table_name, array $data, mixed $unique = true, bool $to_many = false)
        {
            try
            {

                if(!array_key_exists($table_name, $this->data)) throw new \Exception("Table not exists!");

                if(empty($data)) throw new \Exception("Param `data` is empty!");

                if(isset($to_many) and $to_many == true)
                {
                    foreach ($data as $row) {

                        if(isset($unique) and $unique == true)
                        {
                            $unique = rand(100000, 999999);
                            $row['uid'] = $unique;
                        }
                        else
                        {
                            $row['uid'] = $unique;
                        }

                        $this->data[$table_name][$unique] = $row;

                    }
                }
                else
                {
                    if(isset($unique) and $unique == true)
                    {
                        $unique = rand(100000, 999999);
                        $data['uid'] = $unique;
                    }
                    else
                    {
                        $data['uid'] = $unique;
                    }

                    $this->data[$table_name][$unique] = $data;

                }

                $this->save();
                
            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
        /**
         * Обновление данныхв в документе
         * 
         * @param table_name
         * 
         * @param unique
         * 
         * @param data
         * 
         */
        public function update_document(string $table_name, int $unique, array $data)
        {
            try
            {

                if(!array_key_exists($table_name, $this->data)) throw new \Exception("Table not exists");

                if(!array_key_exists($unique, $this->data[$table_name])) throw new \Exception("Document not exists");

                $this->data[$table_name][$unique] = $data;

                $this->save();

                return true;

            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
        /**
         * Удаление данных в документе
         * 
         * @param table_name
         * 
         * @param unique
         * 
         */
        public function delete_document(string $table_name, int $unique)
        {
            try
            {

                if(!array_key_exists($table_name, $this->data)) throw new \Exception("Table not exists!");

                if(!array_key_exists($unique, $this->data[$table_name])) throw new \Exception("Document not exists!");

                unset($this->data[$table_name][$unique]);

                $this->save();

                return true;

            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }

    }