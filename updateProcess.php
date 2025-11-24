<?php
session_start();
require_once 'conf/db_config.php';

// 파일 업로드 디렉토리 설정 (travel_process.php와 동일하게 설정)
$upload_dir = 'uploads/'; 

// POST 요청인지 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('잘못된 접근입니다.'); window.location.href='display.php';</script>";
    exit;
}

// 1. 필요한 데이터 유효성 검사 및 추출
$travel_id = isset($_POST['modify_id']) ? (int)$_POST['modify_id'] : 0;
$title = trim($_POST['modify_title'] ?? '');
$description = trim($_POST['modify_description'] ?? '');

// 💡 추가된 부분: 기존 이미지 경로를 hidden 필드에서 가져옵니다.
$original_image = trim($_POST['original_image'] ?? ''); 

if ($travel_id <= 0 || empty($title) || empty($description)) {
    echo "<script>alert('필수 정보가 누락되었습니다.'); window.history.back();</script>";
    exit;

}

// 2. 로그인 사용자 ID 확인
$logged_in_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($logged_in_user_id === 0) {
    echo "<script>alert('로그인이 필요합니다.'); window.location.href='display.php';</script>";
    exit;
}

try {
    // 3. 수정 권한 확인: 해당 게시물의 작성자 ID를 가져옵니다.
    $check_sql = "SELECT user_id FROM travel WHERE t_id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->bindParam(':id', $travel_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $owner_user_id = $check_stmt->fetchColumn();

    if (!$owner_user_id || (int)$owner_user_id !== $logged_in_user_id) {
        echo "<script>alert('수정 권한이 없습니다.'); window.location.href='display.php';</script>";
        exit;
    }

    // 4. 이미지 처리 로직 (가장 중요한 수정 부분)
    $new_image_path = $original_image; // 기본적으로 기존 이미지 경로를 유지합니다.

    // 파일이 실제로 업로드되었는지 확인합니다. (폼에서 name="modify_image_file"로 보냄)
    if (isset($_FILES['modify_image_file']) && $_FILES['modify_image_file']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['modify_image_file'];

        // 파일 이름 정리 및 중복 방지
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid('img_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_file_name;
        // die($upload_path);

        // 파일 업로드 실행
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $new_image_path = $upload_path; // 새로운 이미지 경로로 업데이트
            
            // 💡 기존 파일이 업로드 디렉토리에 있다면 삭제 (clean-up)
            // uploads/ 디렉토리 내의 파일인지 확인하는 로직 필요
            if (!empty($original_image) && strpos($original_image, $upload_dir) === 0 && file_exists($original_image)) {
                unlink($original_image); 
            }

        } else {
            // 파일 업로드 실패 시 경고
            echo "<script>alert('새 파일 저장에 실패했습니다. (디렉토리 $upload_path 권한 확인)'); window.history.back();</script>";
            exit;
        }
    }

    // 5. 데이터베이스 업데이트 (SQL UPDATE)
    $update_sql = "UPDATE travel SET 
                   title = :title, 
                   img_url = :img_url, 
                   description = :description 
                   WHERE t_id = :id";
    $update_stmt = $pdo->prepare($update_sql);

    $update_stmt->bindParam(':title', $title);
    $update_stmt->bindParam(':img_url', $new_image_path); // 새로운 경로 또는 기존 경로 사용
    $update_stmt->bindParam(':description', $description);
    $update_stmt->bindParam(':id', $travel_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        echo "<script>alert('여행지가 성공적으로 수정되었습니다.'); window.location.href='display.php';</script>";
        exit;
    } else {
        echo "<script>alert('여행지 수정 실패: DB 오류');</script>";
    }

    echo "<script>window.location.href='display.php';</script>";
    exit;

} catch (PDOException $e) {
    echo "<script>alert('데이터베이스 오류가 발생했습니다: " . $e->getMessage() . "'); window.location.href='display.php';</script>";
    exit;
}
?>