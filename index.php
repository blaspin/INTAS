<?php
require $_SERVER["DOCUMENT_ROOT"] . '\INTAS\config.env.php';

Class Root {
    function get_data(){
        foreach($_REQUEST as $key => $value){
            $this->$key = $value;
        }
    }

    function to_json(){
		return json_encode($this,JSON_UNESCAPED_UNICODE);
	}

    function make_responce($message = '', $status = '200'){
        $this->message = $message;
        $this->status = $status;
        echo $this->to_json();
    }

    function make_wrong_responce($message = '', $status = '400'){
        $this->message = $message;
        $this->status = $status;
        echo $this->to_json();
    }
}

Class Request extends Root {
    
}
$in = new Request();

class Responce extends Root {
    
}
$out = new Responce();

require $_SERVER["DOCUMENT_ROOT"] . '\INTAS\seed.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, DB_SSL_FLAG === MYSQLI_CLIENT_SSL ? [
        PDO::MYSQL_ATTR_SSL_CA => DB_SSL_CA,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ] : [PDO::MYSQL_ATTR_MULTI_STATEMENTS => false]);
} catch (PDOException $exception) {
    $out->make_wrong_responce('Нет соединения с базой данных');
}

$stmt = $pdo->prepare("SELECT * FROM `region`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий (1)");
    $stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи заданий (1)");
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); unset($stmt);
$stmt = $pdo->prepare("SELECT * FROM `courier`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий (1)");
    $stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи заданий (1)");
    $couriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); unset($stmt);
$stmt = $pdo->prepare("SELECT `task`.`id`, `task`.`courier_id`, `task`.`region_id`, `task`.`ship_date`, `task`.`arrival_date`, `task`.`status`, 
    `region`.`name` AS `region`, 
    `courier`.`name`, `courier`.`surname`, `courier`.`middle_name` 
    FROM `task` 
    JOIN `region` ON `region`.`id` = `task`.`region_id` 
    JOIN `courier`ON `courier`.`id` = `task`.`courier_id`") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий (1)");
    $stmt->execute([]) or $out->make_wrong_responce("Не удалось прочитать записи заданий (1)");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); unset($stmt);
    //var_dump($_POST);
    if(count($_POST) > 0){
        $stmt = $pdo->prepare("UPDATE `task` SET `status` = '1' 
            WHERE `task`.`arrival_date` < CURRENT_TIMESTAMP") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий");
        $stmt->execute([
            'current_day' => $_POST['trip-start'],
        ]) or $out->make_wrong_responce("Не удалось прочитать записи заданий");
        $stmt->closeCursor(); unset($stmt);

        $stmt = $pdo->prepare("SELECT * FROM `task` 
            WHERE `courier_id` = :courier_id 
            AND `arrival_date` > :current_day
            AND `status` = '0'") or $out->make_wrong_responce("Не удалось подготовить прочтение записей заданий (1)");
        $stmt->execute([
            'current_day' => $_POST['trip-start'],
            'courier_id' => $_POST['courier-id'],
        ]) or $out->make_wrong_responce("Не удалось прочитать записи заданий (1)");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); unset($stmt);
        if(count($result) == 0){
            $stmt = $pdo->prepare("INSERT INTO `task` (`id`, `courier_id`, `region_id`, `description`, `ship_date`, `arrival_date`) 
                VALUES (NULL, 
                :courier_id, 
                :region_id, 
                NULL, 
                :ship_date, 
                :arrival_date)") or $out->make_wrong_responce("Не удалось подготовить создание записи задания");
            $stmt->execute([
                'courier_id' => $_POST['courier-id'],
                'region_id' => $_POST['region-id'],
                'ship_date' => $_POST['trip-start'],
                'arrival_date' => $_POST['trip-end'],
                ]) or $out->make_wrong_responce("Не удалось создать запись задания");
        }
        else{
            //var_dump($result, count($result));
            $out->make_wrong_responce("Курьер или регион уже заняты");
        }
        
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script>
        function setTime(form) {
            var regionSelect = form.querySelector('#region-select');
            console.log(regionSelect);

            var regionId = form.querySelector('#region-id');
            console.log(regionId);

            var courierSelect = form.querySelector('#courier-select');
            console.log(regionSelect);

            var courierId = form.querySelector('#courier-id');
            console.log(courierId);
            
            var startInput = form.querySelector('#start');
            console.log(startInput);

            var endInput = form.querySelector('#end');
            console.log(endInput);

            startInput.min = new Date(Date.now()).toISOString().split('T')[0];

            var date = new Date(startInput.value);
            date.setDate(date.getDate() + Number(regionSelect.options[regionSelect.selectedIndex].id));

            endInput.value = date.toISOString().split('T')[0];

            regionId.value = regionSelect.value;
            courierId.value = courierSelect.value;
        }
        function table() {
            var myTable = document.getElementById('table');
            var rowLength = myTable.rows.length;
            var maxDate = new Date("9999-12-30");
            var minDate = new Date("2000-01-01");
            
            if(!isNaN(new Date(document.getElementById('table-start').value)))
                minDate = new Date(document.getElementById('table-start').value);
            if(!isNaN(new Date(document.getElementById('table-end').value)))
                maxDate = new Date(document.getElementById('table-end').value);
            console.log(minDate,maxDate);

            for (i = 0; i < rowLength; i++){
                var oRow = myTable.rows.item(i);
                var oCells = myTable.rows.item(i).cells;
                var date = new Date(oCells.item(2).innerHTML);
                console.log(oCells.item(2).innerHTML, date, minDate, maxDate);

                if(date < minDate || date > maxDate){
                    oRow.style.display = 'none';
                }
                else{
                    oRow.style.display = 'table-row';
                }
            }
        }
    </script>
</head>
<body>
<br><br>
    <form method="POST" onchange="setTime(this)">
        <label for="region-select">Choose a region:</label>
        <select name="regions" id="region-select">
            <?php foreach($regions as $region){
                echo "<option value=".$region['id']." id=".$region['travel_time'].">".$region['name']."</option>";
            } ?>
        </select>
        <label for="courier-select">Choose a courier:</label>
        <select name="couriers" id="courier-select">
            <?php foreach($couriers as $courier){
                echo "<option value=".$courier['id'].">".$courier['surname']." ".$courier['name']." ".$courier['middle_name']."</option>";
            } ?>
        </select>
        <label for="start">Ship date:</label>
        <input type="date" id="start" name="trip-start" min="2024-07-20"/>
        <label for="end">Arrival date:</label>
        <input hidden name="region-id" type="text" id="region-id" value="" />
        <input hidden name="courier-id" type="text" id="courier-id" value="" />
        <input readonly type="date" id="end" name="trip-end" min="2024-07-21" value="2024-07-21" />
        <input type="submit" value="Save">
    </form>
    <br><br>
    <label for="start">От:</label>
    <input type="date" id="table-start" name="table-start" min="2024-07-20" onchange="table()"/>
    
    <label for="start">До:</label>
    <input type="date" id="table-end" name="table-end" min="2024-07-20" onchange="table()"/>

    <table id="table">
        <tr>
            <td>ФИО</td>
            <td>Регион</td>
            <td>Дата отправки</td>
            <td>Статус(0 - В процессе, 1 - завершён)</td>
        </tr>  
        <?php
            foreach($tasks as $task){
                echo "<tr>";
                echo "<td>".$task['surname']." ".$task['name']." ".$task['middle_name']."</td>";
                echo "<td>".$task['region']."</td>";
                echo "<td>".$task['ship_date']."</td>";
                echo "<td>".$task['status']."</td>";
                echo "</tr>";
            }
        ?>
    </table>
</body>
</html>
