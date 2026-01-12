<?php
session_start();
session_destroy();
header("Location: index.php"); // or redirect to your homepage
exit();
?>
