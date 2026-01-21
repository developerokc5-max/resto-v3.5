<?php
$db = new PDO('sqlite:database/database.sqlite');
$result = $db->query('PRAGMA table_info(items)');
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['name'] . ' | ' . $row['type'] . PHP_EOL;
}
