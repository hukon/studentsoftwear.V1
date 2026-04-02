<?php
session_start();
session_destroy(); // removes all login data
header("Location: login.html");
exit;
