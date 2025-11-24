<?php
// 세션을 시작하여 로그인 상태를 확인합니다.
session_start();

// 데이터베이스 설정 파일 포함
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

        // 2. 파일 이름 정리 및 중복 방지
        // uniqid()를 사용해 고유한 이름 생성
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

        } else {
            echo "<script>alert('파일 저장에 실패했습니다. (디렉토리 권한을 확인하세요: uploads 폴더).');</script>";
        }
        //echo "<script>location.reload();</script>";  // 성공/실패와 상관없이 페이지 새로고침
        //exit();
    } 

    // === 여행지 수정 로직 ===
    else if (isset($_POST['modify_id']) && isset($_POST['action']) && $_POST['action'] === 'modify') {
        $t_id = $_POST['modify_id'];
        $title = trim($_POST['modify_title']);
        $img_url = trim($_POST['modify_image']);
        $description = trim($_POST['modify_description']);

        if (empty($title) || empty($img_url) || empty($description) || empty($t_id)) {
            echo "<script>alert('수정 정보가 부족합니다.');</script>";
        } else {
            try {
                // 1. 여행지 작성자 ID를 가져와 현재 사용자 ID와 비교하는 로직 (1회 실행)
                $check_sql = "SELECT user_id FROM travel WHERE t_id = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$t_id]);
                $travel = $check_stmt->fetch();

                if ($travel && $travel['user_id'] == $user_id) {
                    // 2. 권한이 있다면 UPDATE 실행
                    $sql = "UPDATE travel SET title = ?, img_url = ?, description = ? WHERE t_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$title, $img_url, $description, $t_id]);
                    echo "<script>alert('여행지가 성공적으로 수정되었습니다.');</script>";
                } else {
                    // 3. 권한이 없다면 오류 메시지 출력
                    echo "<script>alert('수정 권한이 없습니다.');</script>";
                }
            } catch (PDOException $e) {
                echo "<script>alert('여행지 수정 실패: " . $e->getMessage() . "');</script>";
            }
        }
    } 

    
    // === 여행지 삭제 로직 (GET으로 처리할 수도 있지만, POST로 통일) ===
    else if (isset($_POST['delete_id'])) {
        $t_id = $_POST['delete_id'];
        
        // 사용자가 해당 여행지의 작성자인지 확인하는 로직
        $check_sql = "SELECT user_id FROM travel WHERE t_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$t_id]);
        $travel = $check_stmt->fetch();

        if ($travel && $travel['user_id'] == $user_id) { // 권한 확인
            $sql = "DELETE FROM travel WHERE t_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$t_id]);
            echo "<script>alert('여행지가 성공적으로 삭제되었습니다.'); location.replace('display.php');</script>";
                exit();
        } else {
            echo "<script>alert('삭제 권한이 없습니다.');</script>";
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