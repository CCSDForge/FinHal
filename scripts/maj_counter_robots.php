<?php

$CounterRobotsUrl = 'https://github.com/atmire/COUNTER-Robots/raw/master/COUNTER_Robots_list.json';
$CcsdCounterRobotsClassFile = __DIR__ . '/../vendor/library/Ccsd/Counter/Robots.php';


$robotsJson = file_get_contents($CounterRobotsUrl);
$robots = json_decode($robotsJson);

$content = "<?php\n";
$content .= "class Ccsd_Counter_Robots {\n";
$content .= "// Last script update: " . date('Y-m-d') . "\n";
$content .= "\tpublic static \$_regexCounterRobotList = array(\n";
$lastdate = '0000-00-00';
foreach ($robots as $robot) {
    $content .= "\t\t'" . $robot->pattern . "',\n";
    if ($robot->last_changed > $lastdate) {
        $lastdate = $robot->last_changed;
    }
}
$content .= "\t);\n";
$content .= "\tpublic static \$last_changed = '$lastdate';\n";
$content .= "}\n";

$dir = dirname($CcsdCounterRobotsClassFile);
$parent = dirname($dir);
file_exists($parent) || mkdir($parent,0755);
file_exists($dir)    || mkdir($dir,   0755);
file_put_contents($CcsdCounterRobotsClassFile, $content);