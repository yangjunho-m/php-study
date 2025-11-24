        // 모든 섹션을 숨기는 함수
        function hideAllSections() {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
        }

        // 특정 섹션을 보여주는 함수
        function showSection(sectionId) {
            hideAllSections();
            const section = document.getElementById(sectionId);
            if (section) {
                section.classList.add('active');
            }
        }

        // '회원가입' 링크 클릭 이벤트
        document.getElementById('signup-link').addEventListener('click', function(event) {
            event.preventDefault(); // 기본 링크 동작(페이지 이동) 방지
            showSection('signup-section');
        });

        // '홈' 링크 클릭 이벤트
        document.getElementById('home-link').addEventListener('click', function(event) {
            event.preventDefault(); // 기본 링크 동작 방지
            showSection('travel-list-section');
        });

        // '로그인' 링크 클릭 이벤트
        document.getElementById('login-link').addEventListener('click', function(event) {
            event.preventDefault(); // 기본 링크 동작 방지
            showSection('login-section');
        });
        
        // '여행지 추가' 링크 클릭 이벤트
        document.getElementById('add-travel-link').addEventListener('click', function(event) {
            event.preventDefault(); // 기본 링크 동작 방지
            showSection('add-travel-section');
        });