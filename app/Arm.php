<?php

class Arm
{
    private $db;
    private $userId;

    public function __construct()
    {
        $this->db = new PDO("pgsql:host=localhost;dbname=postgres", "postgres", "postgrespass",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function run()
    {
            echo 'Введите ваш логин и пароль'.PHP_EOL;
            while (($line = fgets(STDIN)) != "\n") {
                $authorizationData = explode(" ", trim($line));
                if (!empty($authorizationData[0]) && !empty($authorizationData[1]) ) {

                    $login = $authorizationData[0];
                    $password = $authorizationData[1];
                    $stmt = $this->db->prepare("SELECT id, password FROM managers WHERE login = :login");
                    $stmt->bindParam(':login', $login);
                    $stmt->execute();
                    $userData= $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $passwordDb ='';

                    foreach ($userData as $data) {
                        $this->userId  = $data['id'];
                        $passwordDb = $data['password'];
                    }

                    if ($password ===$passwordDb) {
                        echo 'Добро пожаловать!'.PHP_EOL;
                        $this->showProfile();
                    }
                }

                echo 'Вы ввели логин и пароль не верно!'.PHP_EOL;
                echo 'Попробуйте снова.'.PHP_EOL;

            }
    }


    public function showProfile()
    {
        echo 'Введите нужную команду - list: '.PHP_EOL;
        while (($line = fgets(STDIN)) != "\n")
        {
            $words = explode(" ", trim($line));
            if (!empty($words[0])) {
                switch ($words[0]) {
                    case 'add':
                        if (empty($words[1]) || empty($words[2]) || empty($words[3])) {
                            echo 'No such command'.PHP_EOL;
                        } else {
                            settype($words[2], 'integer');
                            settype($words[3], 'integer');
                            $this->addGoods($words[1], $words[2], $words[3]);
                        }
                        break;
                    case 'list';
                        echo $this->showCommand();
                        break;
                    case 'show':
                        $this->showCatalog();
                        break;
                    case 'sales':
                        echo $this->getCurrentSales();
                        break;
                    case 'plan':
                        echo $this->getPlanSales();
                        break;
                    case 'search':
                        if (empty($words[1])) {
                            echo 'No such command'.PHP_EOL;
                        }
                        else {
                            echo $this->searchNameGoods($words[1]);
                        }
                        break;
                    case 'sell':
                        if (empty($words[1]) || empty($words[2]) || empty($words[3])) {
                            echo 'No such command'.PHP_EOL;
                        } else {
                            settype($words[1], 'integer');
                            settype($words[2], 'integer');
                            settype($words[3], 'integer');
                            echo $this->sellGoods($words[1],$words[2],$words[3]);
                        }

                        break;
                }
            }
        }
    }


    public function addGoods( string $name, int $price, $count )
    {
        if ($price <= 0 || $count<=0) {
            echo 'Неправильное заполенение товара'.PHP_EOL;
        }
        $stmt = $this->db->prepare("INSERT INTO goods (title, price, count) VALUES (:title, :price, :count)");
        $stmt->bindParam(':title', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':count', $count);
        $stmt->execute();
        echo 'Товар был добавлен.'.PHP_EOL;
    }


    public function showCatalog()
    {
        $stmt = $this->db->query("SELECT title, price, count FROM goods");
        $rows =$stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                echo  $key.' '.$value.' ';
            }
            echo PHP_EOL;
        }
    }

    public function getCurrentSales():string
    {
        $stmt = $this->db->prepare("SELECT total FROM managers WHERE id = :id");
        $stmt->bindParam(':id', $this->userId);
        $stmt->execute();

        return 'Текушие продажи: '.$stmt->fetchColumn().PHP_EOL;
    }

    public function getPlanSales():string
    {
        $stmt = $this->db->prepare("SELECT plan FROM managers WHERE id = :id");
        $stmt->bindParam(':id', $this->userId);
        $stmt->execute();

        return 'План продаж товаров: '.$stmt->fetchColumn().PHP_EOL;
    }

    public function searchNameGoods(string $name):string
    {
        $stmt = $this->db->prepare("SELECT title FROM goods WHERE title LIKE :title");
        $stmt->bindParam(':title', $name);
        $rows = $stmt->execute();
        $rows =$stmt->fetchAll(PDO::FETCH_ASSOC);
        $result ='';
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                $result.= $value.' товар найден ';
            }
            $result.= PHP_EOL;
        }
        if (!$stmt->rowCount()) {
            $result.='товар не найден'.PHP_EOL;
        }
        return $result;
    }

    public function sellGoods(int $id, int $count, int $price): string
    {
        if ($price <= 0 || $count<=0 || !$this->searchIdGoods($id)) {
            echo 'Неправильное заполенение товара'.PHP_EOL;
        }

        $stmt = $this->db->prepare("SELECT count FROM goods WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $countGood =$stmt->fetchColumn();

        if ($countGood < $count) {
            return 'Товаров на складе меньше, чем вы запросили!'.PHP_EOL;
        }


        $stmt = $this->db->prepare("INSERT INTO sales (count, price, manager_id, good_id)
                                    VALUES (:count, :price, :manager_id, :good_id)");

        $stmt->bindValue(':count', $count);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':manager_id', $this->userId);
        $stmt->bindValue(':good_id', $id);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE goods SET count = 
                                      (SELECT goods.count FROM goods WHERE id =:id) - :count 
                                      WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':count', $count);
        $stmt->execute();

        $stmt = $this->db->prepare("UPDATE managers SET total = 
                                            (SELECT managers.total FROM managers WHERE id =:id) + :price
                                            WHERE id = :id");
        $stmt->bindValue(':id', $this->userId);
        $stmt->bindValue(':price', $price*$count);
        $stmt->execute();

        return 'Товар продан';
    }

    public function searchIdGoods(int $id):bool
    {
        if ($id >=0) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM goods WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            if ($stmt->fetchColumn()) {
                return true;
            }
        }

        return false;
    }
    public function showCommand()
    {
        return PHP_EOL.'Доступные команды: '.PHP_EOL.
            'show - показать все товары'.PHP_EOL.
            'add - добавить товар'.PHP_EOL.
            'sales - показать текущие продажи'.PHP_EOL.
            'plan - показать план продаж'.PHP_EOL.
            'search - найти товар по имени'.PHP_EOL.
            'sell - продать товары со склада'.PHP_EOL;
    }

}

