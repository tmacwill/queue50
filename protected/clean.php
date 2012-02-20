#!/usr/bin/php
<?php

require_once __DIR__ . '/lib/core50/src/CS50/CS50.php';

$cs50 = new CS50('queue', array(
    'cache' => array(
        'host' => 'localhost',
        'port' => 11211
    ),
    'master' => array(
        'db_name' => 'auth',
        'host' => 'localhost',
        'password' => 'crimson',
        'user' => 'root'
    )
));

mysql_connect('localhost', 'root', 'crimson');
mysql_select_db('queue50');

echo "Emptying tables...\n";
mysql_query('truncate labels');
mysql_query('truncate questions');
mysql_query('trucate question_labels');

mysql_select_db('auth');
mysql_query('SET FOREIGN_KEY_CHECKS = 0');
mysql_query('truncate emails');
mysql_query('truncate groups');
mysql_query('truncate group_users');
mysql_query('truncate rules');
mysql_query('truncate suites');
mysql_query('truncate suite_groups');
mysql_query('truncate users');

$suite = $cs50->suites->createSuite(array('name' => 'CS50'));
$staff = $suite->createGroup(array('name' => 'Staff'));

$tommy = $cs50->users->createUser(array('email' => 'tmacwilliam@cs.harvard.edu', 'password' => 'testtest', 'name' => 'Tommy MacWilliam'));
$david = $cs50->users->createUser(array('email' => 'malan@harvard.edu', 'password' => 'testtest', 'name' => 'David Malan'));
$julia = $cs50->users->createUser(array('email' => 'jmitelman@college.harvard.edu', 'password' => 'testtest', 'name' => 'Julia Mitelman'));

$staff->addUsers(array($tommy, $david));
$suite->allow($staff, 'answer');

?>
