#!/usr/bin/php
<?php

echo "Cleaning database...\n";
shell_exec('php ' . __DIR__ . '/clean.php');

mysql_connect('localhost', 'root', 'crimson');
mysql_select_db('queue50');

echo "Inserting labels...\n";
mysql_query('insert into labels (suite_id, name) values (1, "pset1"), (1, "pset2")');

?>
