<?php

$out = new Responce();

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, DB_SSL_FLAG === MYSQLI_CLIENT_SSL ? [
        PDO::MYSQL_ATTR_SSL_CA => DB_SSL_CA,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ] : [PDO::MYSQL_ATTR_MULTI_STATEMENTS => false]);
} catch (PDOException $exception) {
    $out->make_wrong_responce('Нет соединения с базой данных');
}
$stmt = $pdo->prepare("SELECT `id`, `name`, `travel_time` FROM `region`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей региона");
$stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи региона");
$regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); unset($stmt);

$stmt = $pdo->prepare("SELECT `id`, `name`, `surname` FROM `courier`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей курьеров");
$stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи курьеров");
$couriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); unset($stmt);

$stmt = $pdo->prepare("SELECT * FROM `task`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей курьеров");
$stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи курьеров");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); unset($stmt);

if(count($couriers) == 0){
    $names = ['Григорий', 'Аркадий', 'Михаил', 'Евгений'];
    $surnames = ['Афанасьев', 'Герасимов', 'Романов', 'Журавлев'];
    $middleNames = ['Степанович', 'Андреевич', 'Матвеевич', 'Дмитриевич'];
    for($i = 0; $i < 20; $i++){
        $stmt = $pdo->prepare("INSERT INTO `courier` (`id`, `name`, `surname`, `middle_name`) 
            VALUES (NULL, :name, :surname, :middle_name)") or $out->make_wrong_responce("Не удалось подготовить создание записи курьера");
        $stmt->execute([
            'name' => $names[rand(0, 3)],
            'surname' => $surnames[rand(0, 3)],
            'middle_name' => $middleNames[rand(0, 3)],
        ]) or $out->make_wrong_responce("Не удалось создать запись курьера");
    }
    $stmt->closeCursor(); unset($stmt);
}

$stmt = $pdo->prepare("SELECT `id`, `name`, `surname` FROM `courier`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей курьеров");
$stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи курьеров");
$couriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); unset($stmt);

if(count($regions) == 0) {
    $regions = [
        'Санкт-Петербург',
        'Уфа',
        'Нижний Новгород',
        'Владимир',
        'Кострома',
        'Екатеринбург',
        'Ковров',
        'Воронеж',
        'Самара',
        'Астрахань'
    ];
    
    for($i = 0; $i < 10; $i++){
        $stmt = $pdo->prepare("INSERT INTO `region` (`id`, `name`, `travel_time`) 
            VALUES (NULL, :region, :time)") or $out->make_wrong_responce("Не удалось подготовить создание записи региона");
        $stmt->execute([
            'region' => $regions[$i],
            'time' => rand(2, 30),
        ]) or $out->make_wrong_responce("Не удалось создать запись региона");
    }
    $stmt->closeCursor(); unset($stmt);
}

$stmt = $pdo->prepare("SELECT `id`, `name`, `travel_time` FROM `region`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей региона");
$stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи региона");
$regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); unset($stmt);

if(count($tasks) == 0){
    foreach($regions as $key=>$region){
        $taskStart = 0;
        $taskEnd = $region['travel_time'];
        $stmt = $pdo->prepare("INSERT INTO `task` (`id`, `courier_id`, `region_id`, `description`, `ship_date`, `arrival_date`) 
            VALUES (NULL, 
            :courier_id, 
            :region_id, 
            NULL, 
            DATE_ADD(CURRENT_DATE, INTERVAL :ship_date DAY), 
            DATE_ADD(CURRENT_DATE, INTERVAL :arrival_date DAY))") or $out->make_wrong_responce("Не удалось подготовить создание записи задания");
            $stmt->execute([
                'courier_id' => $couriers[$key]['id'],
                'region_id' => $region['id'],
                'ship_date' => $taskStart,
                'arrival_date' => $taskEnd,
            ]) or $out->make_wrong_responce("Не удалось создать запись задания");
    }
    for($day = 1; $day < 90; $day++){
        $taskStart = $day;
        foreach($regions as $region){
            $taskEnd = $region['travel_time'] + $day;

            $stmt = $pdo->prepare("SELECT * FROM `task` WHERE `region_id` = :region_id AND `arrival_date` < DATE_ADD(CURRENT_DATE, INTERVAL :current_day DAY) AND `status` = '0'") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий");
            $stmt->execute([
                'region_id' => $region['id'],
                'current_day' => $day,
            ]) or $out->make_wrong_responce("Не удалось прочитать записи заданий");
            $taskRegions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); unset($stmt);
            
            if(count($taskRegions) != 0){
                foreach($couriers as $courier){
    
                    $stmt = $pdo->prepare("SELECT * FROM `task` 
                        WHERE `courier_id` = :courier_id 
                        AND `arrival_date` < DATE_ADD(CURRENT_DATE, INTERVAL :current_day DAY) 
                        AND DATE_ADD(`ship_date`, INTERVAL :current_day DAY) > `arrival_date` 
                        AND `status` = '0'") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий (1)");
                    $stmt->execute([
                        'current_day' => $day,
                        'region_id' => $region['id'],
                        'courier_id' => $courier['id'],
                    ]) or $out->make_wrong_responce("Не удалось прочитать записи заданий (1)");
                    $taskCouriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt->closeCursor(); unset($stmt);

                    if(count($taskCouriers) != 0){
                        $stmt = $pdo->prepare("INSERT INTO `task` (`id`, `courier_id`, `region_id`, `description`, `ship_date`, `arrival_date`) 
                        VALUES (NULL, 
                        :courier_id, 
                        :region_id, 
                        NULL, 
                        DATE_ADD(CURRENT_DATE, INTERVAL :ship_date DAY), 
                        DATE_ADD(CURRENT_DATE, INTERVAL :arrival_date DAY))") or $out->make_wrong_responce("Не удалось подготовить создание записи задания");
                        $stmt->execute([
                            'courier_id' => $courier['id'],
                            'region_id' => $region['id'],
                            'ship_date' => $taskStart,
                            'arrival_date' => $taskEnd,
                        ]) or $out->make_wrong_responce("Не удалось создать запись задания");
                        break;
                    } 
                }
            }
        }
        $stmt = $pdo->prepare("UPDATE `task` SET `status` = '1' WHERE `task`.`arrival_date` < DATE_ADD(CURRENT_DATE, INTERVAL :current_day DAY)") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий");
            $stmt->execute([
                'current_day' => $day,
            ]) or $out->make_wrong_responce("Не удалось прочитать записи заданий");
            $taskRegions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); unset($stmt);
        
    }
}


