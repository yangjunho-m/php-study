<?php

    // 수정 폼 기능

    session_start();
    require_once 'conf/db_config.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: display.php");
        exit;
    }

    $travel_id = (int)$_GET['id'];
    $travel_item = null;
    $logged_in_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    try {
        // 해당 ID의 여행지 정보 조회
        $sql = "SELECT t_id, user_id, title, img_url, description FROM travel WHERE t_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $travel_id, PDO::PARAM_INT);
        $stmt->execute();
        $travel_item = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. 항목이 존재하지 않거나, 작성자 본인이 아닌 경우 권한 검사
        if (!$travel_item) {
            echo "<script>alert('존재하지 않는 여행지입니다.'); window.location.href='display.php';</script>";
            exit;
        }

        if ((int)$travel_item['user_id'] !== $logged_in_user_id) {
            echo "<script>alert('수정 권한이 없습니다.'); window.location.href='display.php';</script>";
            exit;
        }

    } 
    catch (PDOException $e) {
        echo "여행지 로딩 오류: " . $e->getMessage();
        exit;
    }
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>여행지 수정</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="display.php">홈</a>
            </nav>
    </header>

    <main id="content-area">
        <div id="modify-travel-section" class="section active">
            <form id="modify-travel-form" class="form-card" action="updateProcess.php" method="POST" enctype="multipart/form-data">
                <h2>여행지 수정</h2>
                <input type="hidden" id="modify-id" name="modify_id" value="<?php echo htmlspecialchars($travel_item['t_id']); ?>">
                
                <input type="hidden" name="original_image" value="<?php echo htmlspecialchars($travel_item['img_url']); ?>">

                <p>여행지 제목</p>
                <input type="text" id="modify-title" name="modify_title" 
                       placeholder="여행지 제목" required 
                       value="<?php echo htmlspecialchars($travel_item['title']); ?>">
                
                <p>현재 이미지: <?php echo htmlspecialchars($travel_item['img_url']); ?></p>
                
                <p>새 이미지 파일 (선택 사항)</p>
                <input type="file" id="modify-image" name="modify_image">
                
                <p>설명</p>
                <textarea id="modify-description" name="modify_description" 
                          placeholder="설명" rows="4" required><?php echo htmlspecialchars($travel_item['description']); ?></textarea>
                
                <button type="submit">수정 완료</button>
            </form>
        </div>
    </main>
</body>
</html>