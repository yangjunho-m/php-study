<?php
session_start();
include('dbconnect.php'); // DB 연결파일 포함 (파일명 다를 수 있음)

if (!isset($_SESSION['userid'])) {
    echo "<script>alert('로그인이 필요합니다.'); history.back();</script>";
    exit;
}

if (!isset($_POST['id'])) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$item_id = intval($_POST['id']);
$user_id = $_SESSION['userid'];

// 먼저 본인 소유 아이템인지 확인
$check_sql = "SELECT * FROM travel WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('본인이 등록한 아이템만 삭제할 수 있습니다.'); history.back();</script>";
    exit;
}

// 아이템 삭제
$delete_sql = "DELETE FROM travel WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();

echo "<script>alert('삭제되었습니다.'); location.href='display.php';</script>";
?>