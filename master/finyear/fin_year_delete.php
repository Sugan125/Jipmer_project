<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);


$conn->prepare("DELETE FROM fin_year_master WHERE Id=?")
->execute([$_GET['id']]);


header('Location: fin_year_master.php');