<?php
// 세션을 시작하여 로그인 상태를 유지합니다.
session_start();

// 데이터베이스 설정 파일 포함
require_once 'conf/db_config.php';

// 페이지네이션 설정
$items_per_page = 8; // 페이지당 항목 수
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// 여행지 데이터베이스에서 가져오기
$travel_destinations = [];
$total_items = 0;

try {
    // 1. 총 항목 수 계산
    $count_sql = "SELECT COUNT(*) FROM travel";
    $total_items = $pdo->query($count_sql)->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    // 2. 현재 페이지의 여행지 데이터와 작성자 이름 가져오기
    $sql = "SELECT t.t_id AS id, t.user_id, t.title, t.img_url AS img, t.description, t.created_at, u.username AS author_username 
            FROM travel t
            JOIN users u ON t.user_id = u.user_id
            ORDER BY t.created_at ASC 
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $travel_destinations = $stmt->fetchAll();

} catch (PDOException $e) {
    // 데이터베이스 오류 처리 (선택 사항)
     echo "여행지 로딩 오류: " . $e->getMessage();
}

// PHP 데이터를 JSON 문자열로 변환하여 JS에 전달
$travel_data_json = json_encode($travel_destinations);
$total_pages_js = $total_pages;
$current_page_js = $current_page;
$logged_in_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>국내 여행지 가이드</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="#" id="home-link">홈</a>
            <a href="#" id="add-travel-link">여행지 추가</a>
            <span id="user-info-area" class="<?php echo isset($_SESSION['user_id']) ? 'hidden' : ''; ?>">
                <a href="#" id="login-link">로그인</a>
                <a href="#" id="signup-link">회원가입</a>
            </span>
            <span id="logged-in-area" class="<?php echo isset($_SESSION['user_id']) ? '' : 'hidden'; ?>">
                <span id="welcome-message"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) . '님 환영합니다!' : ''; ?></span>
                <a href="logout.php" id="logout-link">로그아웃</a>
            </span>
        </nav>
    </header>
    
    <main id="content-area">
        <div id="travel-list-section" class="section active">
            <h2>국내 인기 여행지</h2>
            <div class="card-grid" id="travel-cards-container"></div>
            <div id="pagination-controls" class="pagination-container"></div>
        </div>

        <div id="login-section" class="section">
            <form id="login-form" class="form-card" action="join_process.php" method="POST">
                <h2>로그인</h2>
                <input type="text" id="login-id" name="login_id" placeholder="아이디" required>
                <input type="password" id="login-pw" name="login_pw" placeholder="비밀번호" required>
                <button type="submit">로그인</button>
            </form>
        </div>

        <div id="signup-section" class="section">
            <form id="signup-form" class="form-card" action="join_process.php" method="POST">
                <h2>회원가입</h2>
                <input type="text" id="signup-name" name="signup_name" placeholder="이름 (예: 김민지)" required>
                <input type="text" id="signup-id" name="signup_id" placeholder="아이디 (예: minji123)" required>
                <input type="password" id="signup-pw" name="signup_pw" placeholder="비밀번호 (4자 이상)" required>
                <button type="submit">회원가입</button>
            </form>
        </div>

        <div id="add-travel-section" class="section">
            <form id="add-travel-form" class="form-card" enctype="multipart/form-data">
                <h2>여행지 추가</h2>
                <p>여행지</p>
                <input type="text" id="add-title" name="add_title" placeholder="여행지 제목" required>
                <p>새 이미지 파일</p>
                <input type="file" id="add-image" name="add_image" placeholder="이미지 파일" required>
                <p>설명</p>
                <textarea id="add-description" name="add_description" placeholder="설명" rows="4" required></textarea>
                <button type="submit">추가하기</button>
            </form>
        </div>

        <div id="modify-travel-section" class="section active">
            <form id="modify-travel-form" class="form-card" action="updateProcess.php" method="POST" enctype="multipart/form-data"> 
                <h2>여행지 수정</h2>
                <input type="hidden" id="modify-id" name="modify_id" value="">
                
                <input type="hidden" name="original_image" value="">
                
                <p>여행지 제목</p>
                <input type="text" id="modify-title" name="modify_title" placeholder="여행지 제목" required>
                
                <p>이미지 첨부 (새 파일)</p>
                <input type="file" id="modify-image-file" name="modify_image_file" accept="image/*">
                <small>(새 파일을 첨부하지 않으면 기존 이미지가 유지됩니다.)</small>
                
                <p>설명</p>
                <textarea id="modify-description" name="modify_description" 
                        placeholder="설명" rows="4" required></textarea>
                
                <button type="submit">수정 완료</button>
                <button type="button" onclick="window.location.href='display.php'">취소</button>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // DOM 요소 선택
            const sections = document.querySelectorAll('.section');
            const navLinks = document.querySelectorAll('nav a');
            const travelCardsContainer = document.getElementById('travel-cards-container');
            const paginationControls = document.getElementById('pagination-controls');
            const addTravelForm = document.getElementById('add-travel-form');
            const modifyTravelForm = document.getElementById('modify-travel-form');
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const travelDestinations = JSON.parse(JSON.stringify(<?php echo $travel_data_json; ?>));
            let currentPage = <?php echo $current_page_js; ?>; 
            const itemsPerPage = 8; 
            const totalPages = <?php echo $total_pages_js; ?>;

            
            const loggedInUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; ?>;
            const loggedInUserName = "<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>";
            showSection('travel-list-section');
            
            function showSection(sectionId) {
                sections.forEach(section => {
                    section.classList.remove('active');
                });
                const targetSection = document.getElementById(sectionId);
                if (targetSection) {
                    targetSection.classList.add('active');
                }
            }

            // index.php의 renderCards 함수 전체를 이 코드로 교체하세요.
            function renderCards() {
                travelCardsContainer.innerHTML = ''; 
                travelDestinations.forEach(item => { 
                    const trimmedDescription = item.description.length > 40
                            ? item.description.substring(0, 40) + '...'
                            : item.description;
                        
                    const newCard = document.createElement('div');
                    newCard.classList.add('card');
                    newCard.dataset.id = item.id; // item.id 사용 (t_id가 id로 alias됨)

                    const isAuthor = (parseInt(item.user_id) === loggedInUserId);
                    const buttonHtml = isAuthor ? `
                        <div class="card-buttons">
                            <button class="edit-btn">수정</button>
                            <button class="delete-btn">삭제</button>
                        </div>
                    ` : '';

                    // 3. HTML 전체를 한 번에 할당
                    newCard.innerHTML = `
                        <img src="${item.img}" alt="${item.title}"> 
                        <div class="card-content">
                            <h3>${item.title}</h3>
                            <p class="card-author">작성자: ${item.author_username}</p>
                            <p>${trimmedDescription}</p>
                            ${buttonHtml} 
                        </div>
                    `;
                    travelCardsContainer.appendChild(newCard);
                });
            }
            // 페이지네이션 렌더링 함수
            function renderPagination() {
                paginationControls.innerHTML = '';

                if (totalPages > 1) {
                    for (let i = 1; i <= totalPages; i++) {
                        const btn = document.createElement('a');
                        btn.href = `display.php?page=${i}`;
                        btn.textContent = i;
                        
                        btn.addEventListener('click', (e) => {});
                        paginationControls.appendChild(btn);
                    }
                }
            }

            // 초기 화면 로드
            renderCards(); 
            renderPagination();

            // 내비게이션 링크 클릭 이벤트
            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (e.target.id === 'logout-link') {
                        return;
                    }
                    let sectionId = e.target.id.replace('-link', '-section');
                    if (sectionId === 'home-section') {
                        sectionId = 'travel-list-section';
                    }
                    showSection(sectionId);
                });
            });
            
            // 여행지 추가 기능
            addTravelForm.addEventListener('submit', (e) => {
                e.preventDefault();
                /*
                const title = document.getElementById('add-title').value;
                const image = document.getElementById('add-image').value;
                const description = document.getElementById('add-description').value;
                const newId = Date.now();
                */
                const formData = new FormData(addTravelForm);
                 
                fetch('travel_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    return response.text();
                })
                .then(text => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = text;
                    const script = tempDiv.querySelector('script');
                    if (script) {
                        eval(script.textContent);
                    } else {
                        console.log(text); 
                        location.reload(); 
                    }
                })
            });

            // 여행지 수정 기능

            function openModifyModal(item) {
                document.getElementById('modify-id').value = item.id;
                document.getElementById('modify-title').value = item.title;
                
                // 💡 수정된 부분: 기존 이미지 경로를 hidden 필드에 저장
                document.getElementById('original-image').value = item.img; 
                document.getElementById('current-image-name').textContent = item.img.split('/').pop(); // 파일명만 표시
                
                // 파일 첨부 필드는 .value를 설정할 수 없으므로 건드리지 않습니다.
                document.getElementById('modify-description').value = item.description;
                
                showSection('modify-travel-section');
            }   
            modifyTravelForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(modifyTravelForm);
                //formData.append('action', 'modify');

                fetch('updateProcess.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = text;
                    const script = tempDiv.querySelector('script');
                    if (script) {
                        eval(script.textContent); 
                    } else {
                        console.log(text); 
                        location.reload(); 
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('여행지 수정 처리 중 오류가 발생했습니다.');
                    location.reload();
                });
                /*
                const itemToUpdate = travelDestinations.find(item => item.id === id); // 🚨 'id' is not defined
                if (itemToUpdate) {
                    itemToUpdate.title = title;
                    itemToUpdate.img = image; 
                    itemToUpdate.description = description; 
                }

                renderCards(currentPage); // 클라이언트 배열로 카드 다시 그리기
                renderPagination(); 
                modifyTravelForm.reset();
                showSection('travel-list-section');
                */
            });

            // 수정 및 삭제 기능 (이벤트 위임)
            travelCardsContainer.addEventListener('click', (e) => {
                const card = e.target.closest('.card');
                if (!card) return;
                const cardId = parseInt(card.dataset.id);
                
                // 삭제 기능
                if (e.target.classList.contains('delete-btn')) {
                    if (confirm('정말로 이 여행지를 삭제하시겠습니까?')) {
                        const formData = new FormData();
                        formData.append('delete_id', cardId); // 삭제할 ID 전달
                        
                        fetch('travel_process.php', {
                            method: 'POST',
                            body: formData
                        })
                        // 서버 응답 처리 (서버에서 보낸 alert과 페이지 새로고침 스크립트 실행)
                        .then(response => response.text())
                        .then(text => {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = text;
                            const script = tempDiv.querySelector('script');
                            if (script) {
                                eval(script.textContent);
                            } else {
                                console.log(text);
                                location.reload(); 
                            }
                        });
                    }
                }

                // 수정 기능
                if (e.target.classList.contains('edit-btn')) {
                    const itemToEdit = travelDestinations.find(item => parseInt(item.id) === cardId);

                    // 권한 재확인 (혹시 모를 상황 대비)
                    if (itemToEdit && parseInt(itemToEdit.user_id) === loggedInUserId) {
                        document.getElementById('modify-id').value = itemToEdit.id;
                        document.getElementById('modify-title').value = itemToEdit.title;
                        //document.getElementById('modify-image').value = itemToEdit.img;
                        document.getElementById('modify-description').value = itemToEdit.description;
                        showSection('modify-travel-section');
                    } else {
                        alert('수정 권한이 없습니다.');
                    }
                }
            });

            // 로그인 기능 (AJAX)
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault(); // 폼 기본 제출 동작 막기

                const formData = new FormData(loginForm);

                fetch('join_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        location.reload(); 
                    }
                });
            });

            // 회원가입 기능 (AJAX)
            signupForm.addEventListener('submit', (e) => {
                e.preventDefault(); // 폼 기본 제출 동작 막기
                
                const formData = new FormData(signupForm);
                fetch('join_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        showSection('login-section'); // 회원가입 성공 시 로그인 페이지로 이동
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('회원가입 처리 중 오류가 발생했습니다.');
                });
            });

            // 로그인 상태에 따라 UI 업데이트
            const userInfoArea = document.getElementById('user-info-area');
            const loggedInArea = document.getElementById('logged-in-area');
            const welcomeMessage = document.getElementById('welcome-message');
            
            if (loggedInUserId) {
                userInfoArea.classList.add('hidden');
                loggedInArea.classList.remove('hidden');
                welcomeMessage.textContent = `${loggedInUserName}님 환영합니다!`;
            } else {
                userInfoArea.classList.remove('hidden');
                loggedInArea.classList.add('hidden');
            }
            
        });
    </script>
</body>
</html>