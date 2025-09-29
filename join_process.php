<?php
// 세션을 시작하여 로그인 상태를 관리합니다.
session_start();

// 데이터베이스 설정 파일 포함
require_once 'conf/db_config.php';

header('Content-Type: application/json'); // JSON 응답을 명시합니다.

// POST 요청인지 확인
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // === 회원가입 로직 ===
    if (isset($_POST['signup_name']) && isset($_POST['signup_id']) && isset($_POST['signup_pw'])) {
        
        $name = $_POST['signup_name'];
        $username = $_POST['signup_id'];
        $password = $_POST['signup_pw'];

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO users (name, username, password) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $username, $hashed_password]);

            echo json_encode(['status' => 'success', 'message' => '회원가입이 성공적으로 완료되었습니다.']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['status' => 'error', 'message' => '이미 존재하는 아이디입니다.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '회원가입 실패: ' . $e->getMessage()]);
            }
        }
    } 
    // === 로그인 로직 ===
    else if (isset($_POST['login_id']) && isset($_POST['login_pw'])) {
        
        $username = $_POST['login_id'];
        $password = $_POST['login_pw'];

        try {
            $sql = "SELECT user_id, username, password, name FROM users WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'];

                echo json_encode(['status' => 'success', 'message' => htmlspecialchars($user['name']) . '님, 로그인 성공!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '아이디 또는 비밀번호가 올바르지 않습니다.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '로그인 실패: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => '잘못된 접근입니다.']);
    }

} else {
    // POST 요청이 아닐 경우
    header('Location: index.php');
    exit();
}
?>