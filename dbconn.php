
<?php
    /*php & mysqldb connection file*/ 
    $user ="root";
    $pass = "";
    $host = "localhost";
    $dbname = "itms";

    /*echo $user ."<br>";
    echo $pass ."<br>";
    echo $host ."<br>";
    echo $dbname ."<br>";*/
    $dbconn = mysqli_connect($host, $user, $pass, $dbname);
    if (!$dbconn) {
        die("Error:" .mysqli_connect_error());
    }
?>
