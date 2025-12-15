<?php
session_start();
include '../../config/db.php';

if(!isset($_SESSION['role']) || $_SESSION['role']!=1){
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if($action=='get'){
    $id = intval($_POST['MenuId']);
    $stmt = $conn->prepare("SELECT * FROM menu_master WHERE MenuId=?");
    $stmt->execute([$id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    if($menu) echo json_encode(['status'=>'success','data'=>$menu]);
    else echo json_encode(['status'=>'error','message'=>'Menu not found']);
    exit;
}

if($action=='delete'){
    $id = intval($_POST['MenuId']);
    $stmt = $conn->prepare("DELETE FROM menu_master WHERE MenuId=?");
    $stmt->execute([$id]);
    echo json_encode(['status'=>'success','message'=>'Menu deleted']);
    exit;
}

// Add/Edit
$MenuId = $_POST['MenuId'] ?? '';
$MenuName = $_POST['MenuName'] ?? '';
$PageUrl = $_POST['PageUrl'] ?? '';
$IconClass = $_POST['IconClass'] ?? '';
$SortOrder = $_POST['SortOrder'] ?? 1;
$Status = $_POST['Status'] ?? 1;

if($MenuId){
    // Update
    $stmt = $conn->prepare("UPDATE menu_master SET MenuName=?, PageUrl=?, IconClass=?, SortOrder=?, Status=? WHERE MenuId=?");
    $stmt->execute([$MenuName,$PageUrl,$IconClass,$SortOrder,$Status,$MenuId]);
    echo json_encode(['status'=>'success','message'=>'Menu updated']);
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO menu_master(MenuName,PageUrl,IconClass,SortOrder,Status) VALUES(?,?,?,?,?)");
    $stmt->execute([$MenuName,$PageUrl,$IconClass,$SortOrder,$Status]);
    echo json_encode(['status'=>'success','message'=>'Menu added']);
}
