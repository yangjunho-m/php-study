<?php

// 추가 기능

session_start();
require_once 'conf/db_config.php';

// 비로그인 상태면 접근 차단
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인 후 이용해주세요.');</script>";
    echo "<script>location.replace('display.php');</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_dir = 'uploads/';

// POST 요청인지 확인
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // === 여행지 추가 로직 ===
    if (isset($_POST['add_title']) && isset($_FILES['add_image'])) {

        $title = trim($_POST['add_title']);
        $description = trim($_POST['add_description']);
        $file = $_FILES['add_image'];

        if (empty($title) || empty($description) || $file['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('제목, 설명 입력 및 파일 첨부 오류가 발생했습니다.');</script>";
            echo "<script>location.replace('display.php');</script>"; 
            exit();
        }

        // 2. 파일 이름 정리 및 중복 방지 ( uniqid()를 사용 )
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid('img_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_file_name;
        
        // 3. 파일 업로드 실행
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            
            $img_url_to_save = $upload_path; // DB에 저장될 웹 접근 경로

            try {
                // DB에 데이터 삽입
                $sql = "INSERT INTO travel (user_id, title, img_url, description) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $title, $img_url_to_save, $description]); // 파일 경로 저장

                echo "<script>alert('여행지가 성공적으로 추가되었습니다.'); location.replace('display.php');</script>";
                exit();
            } catch (PDOException $e) {
                // DB 삽입 실패 시 업로드된 파일도 삭제 (clean-up)
                unlink($upload_path); 
                echo "<script>alert('여행지 추가 실패 (DB 오류): " . $e->getMessage() . "');</script>";
            }

        } 
        else {
            echo "<script>alert('파일 저장에 실패했습니다. (디렉토리 권한을 확인하세요: uploads 폴더).');</script>";
        }

    } 
    // 처리 후 메인 페이지로 리디렉션. 페이지네이션을 위해 기본 페이지로 이동
    echo "<script>location.replace('display.php');</script>";
    exit();
} else {
    header('Location: display.php');
    exit();
}
?>