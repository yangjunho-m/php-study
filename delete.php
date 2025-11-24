<?php
    session_start();
    require_once 'conf/db_config.php';

    // 로그인 확인 (프로젝트의 세션 변수 사용)
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('로그인이 필요합니다.'); history.back();</script>";
        exit;
    }

    // 삭제할 ID 확인
    if (!isset($_POST['delete_id'])) {
        echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
        exit;
    }

    $item_id = (int)$_POST['delete_id']; // POST로 받은 ID
    $user_id = (int)$_SESSION['user_id']; // 로그인 사용자 ID

    try {
        // 1. 이미지 파일 경로를 가져오기 위해 먼저 조회
        $get_img_sql = "SELECT user_id, img_url FROM travel WHERE t_id = ?";
        $get_img_stmt = $pdo->prepare($get_img_sql);
        $get_img_stmt->execute([$item_id]);
        $item = $get_img_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            echo "<script>alert('삭제할 여행지를 찾을 수 없습니다.'); location.href='display.php';</script>";
            exit;
        }

        // 2. 본인 소유 아이템인지 확인 (권한 검사)
        if ((int)$item['user_id'] !== $user_id) {
            echo "<script>alert('본인이 등록한 아이템만 삭제할 수 있습니다.'); history.back();</script>";
            exit;
        }

        // 3. 아이템 삭제
        $delete_sql = "DELETE FROM travel WHERE t_id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$item_id]);

        // 4. 서버에서 파일도 삭제 (clean-up)
        $upload_dir = 'uploads/'; 
        if (strpos($item['img_url'], $upload_dir) === 0 && file_exists($item['img_url'])) {
            unlink($item['img_url']);
        }

        echo "<script>alert('삭제되었습니다.'); location.href='display.php';</script>";
        exit;

    } 
    catch (PDOException $e) {
        echo "<script>alert('삭제 처리 중 오류가 발생했습니다: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
?>