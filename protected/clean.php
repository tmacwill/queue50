#!/usr/bin/php
<?php

// connect to database
mysql_connect('localhost', 'root', 'crimson');
mysql_select_db('queue50');

echo "Emptying tables...\n";
mysql_query('truncate labels');
mysql_query('truncate questions');
mysql_query('trucate question_labels');

?>
