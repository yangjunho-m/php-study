document.addEventListener('DOMContentLoaded', () => {
    // DOM 요소 선택
    const sections = document.querySelectorAll('.section');
    const navLinks = document.querySelectorAll('nav a');
    const travelCardsContainer = document.getElementById('travel-cards-container');
    const paginationControls = document.getElementById('pagination-controls');
    const userInfoArea = document.getElementById('user-info-area');
    const loggedInArea = document.getElementById('logged-in-area');
    const welcomeMessage = document.getElementById('welcome-message');
    const logoutLink = document.getElementById('logout-link');

    // 여행지 데이터 배열 (초기 데이터)
    let travelDestinations = [
        { id: 1, title: '부산', img: 'https://picsum.photos/300/200?random=1', description: '해운대, 광안리, 태종대 등 볼거리 가득한 바다 도시! 낭만적인 야경을 즐기기에도 완벽한 곳입니다.' },
        { id: 2, title: '제주도', img: 'https://picsum.photos/300/200?random=2', description: '에메랄드빛 바다와 한라산의 절경이 어우러진 꿈의 섬. 드라이브 코스도 환상적입니다.' },
        { id: 3, title: '강릉', img: 'https://picsum.photos/300/200?random=3', description: '정동진, 경포대 등 아름다운 동해안의 숨결을 느껴보세요. 감성적인 카페거리도 유명합니다.' },
        { id: 4, title: '전주', img: 'https://picsum.photos/300/200?random=4', description: '한옥마을, 비빔밥, 전통문화가 살아 숨 쉬는 도시! 한복을 입고 거리를 거닐어보세요.' },
        { id: 5, title: '경주', img: 'https://picsum.photos/300/200?random=5', description: '신라 천년의 고도, 불국사와 첨성대가 기다리는 역사 도시! 벚꽃이 필 때 특히 아름답습니다.' },
        { id: 6, title: '여수', img: 'https://picsum.photos/300/200?random=6', description: '밤바다, 케이블카, 오동도까지! 낭만이 흐르는 남해 도시! 싱싱한 해산물도 꼭 맛보세요.' },
        { id: 7, title: '속초', img: 'https://picsum.photos/300/200?random=7', description: '설악산과 속초해수욕장, 그리고 맛있는 해산물까지! 자연과 미식을 모두 즐길 수 있습니다.' },
        { id: 8, title: '안동', img: 'https://picsum.photos/300/200?random=8', description: '하회마을과 유교문화, 고즈넉한 전통이 살아있는 곳! 신선한 공기와 함께 산책하기 좋습니다.' },
        { id: 9, title: '남해', img: 'https://picsum.photos/300/200?random=9', description: '바다 위 독일 마을, 보물섬이라 불리는 환상의 섬! 이국적인 풍경을 느낄 수 있습니다.' },
        { id: 10, title: '단양', img: 'https://picsum.photos/300/200?random=10', description: '패러글라이딩, 만천하스카이워크 등 액티비티 천국! 스릴 넘치는 경험을 해보세요.' },
    ];
    let currentPage = 1;
    const itemsPerPage = 10;
    
    // 회원정보를 저장할 배열
    let registeredUsers = [];
    let loggedInUser = null;

    // 섹션 전환 함수
    function showSection(sectionId) {
        sections.forEach(section => {
            section.classList.remove('active');
        });
        document.getElementById(sectionId).classList.add('active');
    }

    // 카드 렌더링 함수
    function renderCards(page) {
        travelCardsContainer.innerHTML = '';
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const pageItems = travelDestinations.slice(start, end);

        pageItems.forEach(item => {
            const trimmedDescription = item.description.length > 40
                ? item.description.substring(0, 40) + '...'
                : item.description;
            
            const newCard = document.createElement('div');
            newCard.classList.add('card');
            newCard.dataset.id = item.id;
            newCard.innerHTML = `
                <img src="${item.img}" alt="${item.title}">
                <div class="card-content">
                    <h3>${item.title}</h3>
                    <p>${trimmedDescription}</p>
                    <div class="card-buttons">
                        <button class="edit-btn">수정</button>
                        <button class="delete-btn">삭제</button>
                    </div>
                </div>
            `;
            travelCardsContainer.appendChild(newCard);
        });
    }

    // 페이지네이션 렌더링 함수
    function renderPagination() {
        paginationControls.innerHTML = '';
        const totalPages = Math.ceil(travelDestinations.length / itemsPerPage);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            if (i === currentPage) {
                btn.classList.add('active');
            }
            btn.addEventListener('click', () => {
                currentPage = i;
                renderCards(currentPage);
                renderPagination();
            });
            paginationControls.appendChild(btn);
        }
    }

    // 사용자 로그인 상태 UI 업데이트
    function updateHeaderUI() {
        if (loggedInUser) {
            userInfoArea.classList.add('hidden');
            loggedInArea.classList.remove('hidden');
            welcomeMessage.textContent = `${loggedInUser.name}님 환영합니다!`;
        } else {
            userInfoArea.classList.remove('hidden');
            loggedInArea.classList.add('hidden');
            welcomeMessage.textContent = '';
        }
    }

    // 초기 화면 로드 시 실행
    renderCards(currentPage);
    renderPagination();
    updateHeaderUI();

    // 내비게이션 링크 클릭 이벤트
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = e.target.id.replace('-link', '-section');
            if (sectionId === 'home-section') {
                showSection('travel-list-section');
            } else {
                showSection(sectionId);
            }
        });
    });
    
    // 로그아웃 이벤트
    logoutLink.addEventListener('click', (e) => {
        e.preventDefault();
        loggedInUser = null;
        updateHeaderUI();
        alert('로그아웃 되었습니다.');
        showSection('travel-list-section');
    });

    // 회원가입 기능 (AJAX 코드 제거)
    // 폼 제출은 이제 PHP 파일로 직접 전송됩니다.
    
    // 로그인 기능 (AJAX 코드 제거)
    // 폼 제출은 이제 PHP 파일로 직접 전송됩니다.

    // 여행지 추가 기능
    const addTravelForm = document.getElementById('add-travel-form');
    addTravelForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const title = document.getElementById('add-title').value;
        const image = document.getElementById('add-image').value;
        const description = document.getElementById('add-description').value;
        const newId = Date.now();

        travelDestinations.push({ id: newId, title, img: image, description });
        
        currentPage = Math.ceil(travelDestinations.length / itemsPerPage);
        renderCards(currentPage);
        renderPagination();

        addTravelForm.reset();
        showSection('travel-list-section');
    });

    // 수정 및 삭제 기능 (이벤트 위임)
    travelCardsContainer.addEventListener('click', (e) => {
        const card = e.target.closest('.card');
        if (!card) return;
        const cardId = parseInt(card.dataset.id);
        
        // 삭제 기능
        if (e.target.classList.contains('delete-btn')) {
            if (confirm('정말로 이 여행지를 삭제하시겠습니까?')) {
                travelDestinations = travelDestinations.filter(item => item.id !== cardId);
                if ((currentPage - 1) * itemsPerPage >= travelDestinations.length) {
                    currentPage = Math.max(1, currentPage - 1);
                }
                renderCards(currentPage);
                renderPagination();
            }
        }

        // 수정 기능
        if (e.target.classList.contains('edit-btn')) {
            const itemToEdit = travelDestinations.find(item => item.id === cardId);
            if (!itemToEdit) return;

            document.getElementById('modify-id').value = itemToEdit.id;
            document.getElementById('modify-title').value = itemToEdit.title;
            document.getElementById('modify-image').value = itemToEdit.img;
            document.getElementById('modify-description').value = itemToEdit.description;

            showSection('modify-travel-section');
        }
    });

    // 수정 폼 제출 시
    const modifyTravelForm = document.getElementById('modify-travel-form');
    modifyTravelForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const id = parseInt(document.getElementById('modify-id').value);
        const newTitle = document.getElementById('modify-title').value;
        const newImage = document.getElementById('modify-image').value;
        const newDescription = document.getElementById('modify-description').value;

        const indexToUpdate = travelDestinations.findIndex(item => item.id === id);
        if (indexToUpdate !== -1) {
            travelDestinations[indexToUpdate] = {
                id: id,
                title: newTitle,
                img: newImage,
                description: newDescription
            };
        }

        renderCards(currentPage);
        renderPagination();
        modifyTravelForm.reset();
        showSection('travel-list-section');
    });
});