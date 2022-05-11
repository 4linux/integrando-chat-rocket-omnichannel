#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

use FourLinux\ChatBackend\ChatServer;

$chatServer = new ChatServer();

$chatServer->start();
