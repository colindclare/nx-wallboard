<?php

file_put_contents("drift_post.log", print_r($_POST,true) . "\n\n" . file_get_contents("php://input"),FILE_APPEND);
