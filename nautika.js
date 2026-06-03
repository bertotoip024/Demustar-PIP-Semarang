// ========== KONFIGURASI ==========
const API_URL = '/api/nautika.php';
let currentUser = null;
let currentQuiz = null;
let userAnswers = {};
let timerInterval = null;
let allMateri = [];
let allQuiz = [];

// ========== DOM ELEMENTS ==========
const userMenu = document.getElementById('userMenu');
const userBtn = document.getElementById('userBtn');
const userName = document.getElementById('userName');
const btnLogout = document.getElementById('btnLogout');

// ========== UTILITY FUNCTIONS ==========
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.borderColor = type === 'error' ? '#ff5a7a' : '#FFD700';
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

// ========== TAB NAVIGATION ==========
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById(`tab-${this.dataset.tab}`).classList.add('active');

        if (this.dataset.tab === 'materi') loadMateri();
        if (this.dataset.tab === 'quiz') loadQuiz();
        if (this.dataset.tab === 'hasil') loadHasilQuiz();
    });
});

// ========== LOAD MATERI ==========
async function loadMateri() {
    const container = document.getElementById('materiContainer');
    container.innerHTML = '<div class="loading">⏳ Memuat materi...</div>';

    try {
        const res = await fetch(`${API_URL}?action=materi`);
        const result = await res.json();

        if (result.ok && result.data.length > 0) {
            allMateri = result.data;
            renderMateri(allMateri);

            // Setup search and filter
            document.getElementById('searchMateri').addEventListener('input', filterMateri);
            document.getElementById('filterTipe').addEventListener('change', filterMateri);
        } else {
            container.innerHTML = '<div class="empty-data">📭 Belum ada materi</div>';
        }
    } catch (e) {
        console.error('Error loading materi:', e);
        container.innerHTML = '<div class="empty-data">❌ Gagal memuat materi. Periksa koneksi server.</div>';
    }
}

function renderMateri(materiList) {
    const container = document.getElementById('materiContainer');

    if (materiList.length === 0) {
        container.innerHTML = '<div class="empty-data">📭 Tidak ada materi yang sesuai</div>';
        return;
    }

    container.innerHTML = materiList.map(materi => {
        let tipeClass = '';
        let tipeIcon = '';
        switch (materi.tipe) {
            case 'video':
                tipeClass = 'tipe-video';
                tipeIcon = '🎬';
                break;
            case 'pdf':
                tipeClass = 'tipe-pdf';
                tipeIcon = '📄';
                break;
            case 'ppt':
                tipeClass = 'tipe-ppt';
                tipeIcon = '📊';
                break;
            default:
                tipeClass = 'tipe-dokumen';
                tipeIcon = '📁';
        }

        return `
            <div class="materi-card" onclick="openMateri(${materi.id})">
                <div class="materi-thumb">${tipeIcon}</div>
                <div class="materi-info">
                    <span class="materi-tipe ${tipeClass}">${materi.tipe.toUpperCase()}</span>
                    <div class="materi-judul">${escapeHtml(materi.judul)}</div>
                    <div class="materi-deskripsi">${escapeHtml(materi.deskripsi ? materi.deskripsi.substring(0, 100) + (materi.deskripsi.length > 100 ? '...' : '') : '-')}</div>
                    <div class="materi-footer">
                        <span>${materi.durasi ? '⏱️ ' + materi.durasi : '📖 Materi'}</span>
                        <span>${materi.tipe === 'video' ? '▶️ Tonton' : '📥 Buka'}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function filterMateri() {
    const searchTerm = document.getElementById('searchMateri').value.toLowerCase();
    const filterTipe = document.getElementById('filterTipe').value;

    let filtered = allMateri;

    if (searchTerm) {
        filtered = filtered.filter(m =>
            m.judul.toLowerCase().includes(searchTerm) ||
            (m.deskripsi && m.deskripsi.toLowerCase().includes(searchTerm))
        );
    }

    if (filterTipe !== 'all') {
        filtered = filtered.filter(m => m.tipe === filterTipe);
    }

    renderMateri(filtered);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function openMateri(materiId) {
    try {
        const res = await fetch(`${API_URL}?action=detail_materi&id=${materiId}`);
        const result = await res.json();

        if (result.ok && result.data) {
            const materi = result.data;
            const modal = document.getElementById('materiModal');
            const title = document.getElementById('materiModalTitle');
            const body = document.getElementById('materiModalBody');

            title.textContent = materi.judul;

            let content = '';
            if (materi.tipe === 'video') {
                let videoUrl = materi.url;
                if (videoUrl.includes('youtube.com/watch')) {
                    const videoId = new URL(videoUrl).searchParams.get('v');
                    if (videoId) {
                        videoUrl = `https://www.youtube.com/embed/${videoId}`;
                    }
                }
                content = `
                    <div class="video-container">
                        <iframe src="${videoUrl}" frameborder="0" allowfullscreen></iframe>
                    </div>
                    <div style="margin-top: 16px;">
                        <h4>Deskripsi</h4>
                        <p>${escapeHtml(materi.deskripsi || 'Tidak ada deskripsi')}</p>
                        ${materi.durasi ? `<p>⏱️ Durasi: ${materi.durasi}</p>` : ''}
                    </div>
                `;
            } else {
                content = `
                    <iframe src="${materi.url}" class="pdf-viewer" frameborder="0"></iframe>
                    <div style="margin-top: 16px;">
                        <h4>Deskripsi</h4>
                        <p>${escapeHtml(materi.deskripsi || 'Tidak ada deskripsi')}</p>
                        <a href="${materi.url}" download class="btn-primary" style="margin-top: 12px; display: inline-block;">📥 Unduh Materi</a>
                    </div>
                `;
            }

            body.innerHTML = content;
            modal.classList.add('active');
        }
    } catch (e) {
        console.error('Error opening materi:', e);
        showToast('Gagal membuka materi', 'error');
    }
}

function closeMateriModal() {
    document.getElementById('materiModal').classList.remove('active');
}

// ========== LOAD QUIZ ==========
async function loadQuiz() {
    const container = document.getElementById('quizContainer');
    container.innerHTML = '<div class="loading">⏳ Memuat quiz...</div>';

    try {
        const res = await fetch(`${API_URL}?action=quiz`);
        const result = await res.json();

        if (result.ok && result.data.length > 0) {
            allQuiz = result.data;

            // Get user's completed quizzes
            let completedQuizzes = [];
            if (currentUser) {
                const hasilRes = await fetch(`${API_URL}?action=hasil_saya`);
                const hasilResult = await hasilRes.json();
                if (hasilResult.ok) {
                    completedQuizzes = hasilResult.data.map(h => h.quiz_id);
                }
            }

            container.innerHTML = allQuiz.map(quiz => {
                const isCompleted = completedQuizzes.includes(quiz.id);
                return `
                    <div class="quiz-card">
                        <div class="quiz-judul">📝 ${escapeHtml(quiz.judul)}</div>
                        <div class="quiz-deskripsi">${escapeHtml(quiz.deskripsi || 'Uji pemahaman Anda tentang materi ini.')}</div>
                        <div class="quiz-meta">
                            <span>⏱️ ${quiz.waktu} menit</span>
                            <span>📋 ${quiz.jumlah_soal || 0} soal</span>
                            <span>🎯 Minimal ${quiz.passing_score}%</span>
                            ${isCompleted ? '<span class="quiz-status status-done">✓ Sudah dikerjakan</span>' : '<span class="quiz-status status-not-done">⚡ Belum dikerjakan</span>'}
                        </div>
                        <button class="btn-quiz ${isCompleted ? 'btn-retry' : ''}" onclick="startQuiz(${quiz.id}, '${escapeHtml(quiz.judul)}', ${quiz.waktu}, ${quiz.passing_score})">
                            ${isCompleted ? '🔄 Kerjakan Ulang' : '🚀 Mulai Quiz'}
                        </button>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<div class="empty-data">📭 Belum ada quiz</div>';
        }
    } catch (e) {
        console.error('Error loading quiz:', e);
        container.innerHTML = '<div class="empty-data">❌ Gagal memuat quiz</div>';
    }
}

// ========== START QUIZ ==========
async function startQuiz(quizId, judul, waktu, passingScore) {
    if (!currentUser) {
        showToast('Silakan login terlebih dahulu', 'error');
        setTimeout(() => {
            window.location.href = './login.html';
        }, 1500);
        return;
    }

    currentQuiz = { id: quizId, judul: judul, waktu: waktu, passingScore: passingScore };
    userAnswers = {};

    const modalBody = document.getElementById('modalBody');
    modalBody.innerHTML = '<div class="loading">⏳ Memuat soal...</div>';
    document.getElementById('modalTitle').textContent = judul;
    document.getElementById('quizModal').classList.add('active');

    try {
        const res = await fetch(`${API_URL}?action=soal&quiz_id=${quizId}`);
        const result = await res.json();

        if (result.ok && result.data.length > 0) {
            let html = `
                <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>📋 <strong>${result.data.length}</strong> soal</div>
                    <div class="quiz-timer" id="timerDisplay">⏱️ ${waktu}:00</div>
                </div>
                <div class="quiz-progress" id="quizProgress"></div>
            `;

            result.data.forEach((soal, index) => {
                html += `
                    <div class="soal-item" data-soal-id="${soal.id}">
                        <div class="soal-pertanyaan">${index + 1}. ${escapeHtml(soal.pertanyaan)}</div>
                        <div class="soal-opsi" onclick="selectAnswer(${soal.id}, 'A')">
                            <input type="radio" name="q${soal.id}" value="A" id="q${soal.id}_a">
                            <label for="q${soal.id}_a">A. ${escapeHtml(soal.opsi_a)}</label>
                        </div>
                        <div class="soal-opsi" onclick="selectAnswer(${soal.id}, 'B')">
                            <input type="radio" name="q${soal.id}" value="B" id="q${soal.id}_b">
                            <label for="q${soal.id}_b">B. ${escapeHtml(soal.opsi_b)}</label>
                        </div>
                        <div class="soal-opsi" onclick="selectAnswer(${soal.id}, 'C')">
                            <input type="radio" name="q${soal.id}" value="C" id="q${soal.id}_c">
                            <label for="q${soal.id}_c">C. ${escapeHtml(soal.opsi_c)}</label>
                        </div>
                        <div class="soal-opsi" onclick="selectAnswer(${soal.id}, 'D')">
                            <input type="radio" name="q${soal.id}" value="D" id="q${soal.id}_d">
                            <label for="q${soal.id}_d">D. ${escapeHtml(soal.opsi_d)}</label>
                        </div>
                    </div>
                `;
            });

            html += `<button class="btn-submit-quiz" onclick="submitQuiz()">✅ Selesai & Kirim</button>`;
            modalBody.innerHTML = html;
            updateQuizProgress();
            startTimer(waktu * 60);
        } else {
            modalBody.innerHTML = '<div class="empty-data">❌ Gagal memuat soal</div>';
        }
    } catch (e) {
        console.error('Error loading soal:', e);
        modalBody.innerHTML = '<div class="empty-data">❌ Error: ' + e.message + '</div>';
    }
}

function updateQuizProgress() {
    const totalSoal = document.querySelectorAll('.soal-item').length;
    const answeredSoal = Object.keys(userAnswers).length;
    const progress = Math.round((answeredSoal / totalSoal) * 100);
    const progressDiv = document.getElementById('quizProgress');
    if (progressDiv) {
        progressDiv.innerHTML = `
            <div style="background: rgba(255,255,255,0.1); border-radius: 20px; height: 6px; margin-bottom: 16px;">
                <div style="width: ${progress}%; background: var(--gold); height: 6px; border-radius: 20px; transition: width 0.3s;"></div>
            </div>
            <div style="text-align: right; font-size: 11px; margin-bottom: 16px;">${answeredSoal}/${totalSoal} soal terjawab</div>
        `;
    }
}

function selectAnswer(soalId, jawaban) {
    userAnswers[soalId] = jawaban;
    const radio = document.getElementById(`q${soalId}_${jawaban.toLowerCase()}`);
    if (radio) radio.checked = true;
    updateQuizProgress();
}

function startTimer(detik) {
    if (timerInterval) clearInterval(timerInterval);
    const timerDisplay = document.getElementById('timerDisplay');

    timerInterval = setInterval(() => {
        if (detik <= 0) {
            clearInterval(timerInterval);
            showToast('Waktu habis! Quiz akan dikirim otomatis.', 'error');
            submitQuiz();
            return;
        }
        const menit = Math.floor(detik / 60);
        const detikLeft = detik % 60;
        timerDisplay.textContent = `⏱️ ${menit}:${detikLeft < 10 ? '0' + detikLeft : detikLeft}`;
        detik--;
    }, 1000);
}

async function submitQuiz() {
    if (timerInterval) clearInterval(timerInterval);

    // Collect answers from checked radios
    document.querySelectorAll('.soal-item').forEach(item => {
        const radio = item.querySelector('input[type="radio"]:checked');
        if (radio) {
            const soalId = radio.name.substring(1);
            userAnswers[soalId] = radio.value;
        }
    });

    try {
        const res = await fetch(`${API_URL}?action=soal&quiz_id=${currentQuiz.id}`);
        const result = await res.json();

        let skor = 0;
        let maxSkor = 0;

        if (result.ok && result.data) {
            result.data.forEach(soal => {
                maxSkor += soal.poin || 10;
                if (userAnswers[soal.id] === soal.jawaban) {
                    skor += soal.poin || 10;
                }
            });
        }

        const nilai = Math.round((skor / maxSkor) * 100);
        const status = nilai >= currentQuiz.passingScore ? 'lulus' : 'tidak_lulus';

        const saveRes = await fetch(`${API_URL}?action=simpan_hasil`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                quiz_id: currentQuiz.id,
                skor: skor,
                nilai: nilai,
                status: status,
                jawaban: userAnswers
            })
        });

        const saveResult = await saveRes.json();

        if (saveResult.ok) {
            showToast(`Quiz selesai! Nilai: ${nilai}% - ${status === 'lulus' ? 'LULUS 🎉' : 'TIDAK LULUS'}`);
            closeQuizModal();
            loadHasilQuiz();
            // Switch to hasil tab
            document.querySelector('[data-tab="hasil"]').click();
        } else {
            showToast('Gagal menyimpan hasil quiz: ' + (saveResult.error || 'Unknown error'), 'error');
        }
    } catch (e) {
        console.error('Error submitting quiz:', e);
        showToast('Terjadi kesalahan: ' + e.message, 'error');
    }
}

function closeQuizModal() {
    if (timerInterval) clearInterval(timerInterval);
    document.getElementById('quizModal').classList.remove('active');
    currentQuiz = null;
    userAnswers = {};
}

// ========== LOAD HASIL QUIZ ==========
async function loadHasilQuiz() {
    const container = document.getElementById('hasilContainer');
    const statsContainer = document.getElementById('hasilStats');
    container.innerHTML = '<div class="loading">⏳ Memuat hasil quiz...</div>';

    try {
        const res = await fetch(`${API_URL}?action=hasil_saya`);
        const result = await res.json();

        if (result.ok && result.data.length > 0) {
            // Calculate stats
            const totalQuiz = result.data.length;
            const lulusCount = result.data.filter(h => h.status === 'lulus').length;
            const avgNilai = Math.round(result.data.reduce((sum, h) => sum + h.nilai, 0) / totalQuiz);
            const bestNilai = Math.max(...result.data.map(h => h.nilai));

            statsContainer.innerHTML = `
                <div class="stat-card">
                    <div class="stat-value">${totalQuiz}</div>
                    <div class="stat-label">Quiz Dikerjakan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${lulusCount}</div>
                    <div class="stat-label">Quiz Lulus</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${avgNilai}%</div>
                    <div class="stat-label">Rata-rata Nilai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${bestNilai}%</div>
                    <div class="stat-label">Nilai Tertinggi</div>
                </div>
            `;

            container.innerHTML = result.data.map(hasil => `
                <div class="hasil-card" onclick="showHasilDetail(${hasil.id})">
                    <div class="hasil-info">
                        <div class="hasil-judul">📝 ${escapeHtml(hasil.quiz_judul)}</div>
                        <div class="hasil-tanggal">📅 ${formatDate(hasil.created_at)}</div>
                    </div>
                    <div class="hasil-nilai ${hasil.status === 'lulus' ? 'nilai-lulus' : 'nilai-tidak'}">
                        ${hasil.nilai}% - ${hasil.status === 'lulus' ? '✅ Lulus' : '❌ Tidak Lulus'}
                    </div>
                </div>
            `).join('');
        } else {
            statsContainer.innerHTML = '';
            container.innerHTML = '<div class="empty-data">📭 Belum ada hasil quiz. Mulai kerjakan quiz sekarang!</div>';
        }
    } catch (e) {
        console.error('Error loading hasil:', e);
        container.innerHTML = '<div class="empty-data">❌ Gagal memuat hasil quiz</div>';
    }
}

function showHasilDetail(hasilId) {
    // Implement detail hasil jika diperlukan
    showToast('Fitur detail hasil akan segera hadir');
}

function closeHasilModal() {
    document.getElementById('hasilModal').classList.remove('active');
}

// ========== USER FUNCTIONS ==========
async function updateUserUI() {
    const welcomeData = sessionStorage.getItem('demustar_welcome');
    if (welcomeData) {
        try {
            const data = JSON.parse(welcomeData);
            if (data && data.name) {
                userName.textContent = data.name;
                currentUser = { nama: data.name, nit: data.nit };
                return;
            }
        } catch (e) { }
    }

    const userStr = localStorage.getItem('user');
    if (userStr) {
        try {
            const user = JSON.parse(userStr);
            if (user && user.nama) {
                userName.textContent = user.nama;
                currentUser = user;
                return;
            }
        } catch (e) { }
    }

    try {
        const res = await fetch('/api/auth/me.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (res.ok && data.ok && data.user) {
            userName.textContent = data.user.nama || 'Guest';
            currentUser = data.user;
            return;
        }
    } catch (e) { }

    // For demo purposes - allow guest access
    userName.textContent = 'Guest Mode';
    currentUser = { nama: 'Guest Mode', nit: 'guest' };
}

async function logout() {
    try {
        await fetch('/api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
    } catch (e) { }
    sessionStorage.clear();
    localStorage.clear();
    window.location.href = './index.html';
}

// ========== EVENT LISTENERS ==========
if (userBtn) {
    userBtn.onclick = (e) => {
        e.stopPropagation();
        userMenu.classList.toggle('open');
    };
}

document.addEventListener('click', (e) => {
    if (userMenu && !userMenu.contains(e.target)) {
        userMenu.classList.remove('open');
    }
});

if (btnLogout) {
    btnLogout.onclick = logout;
}

// ========== INITIALIZE ==========
updateUserUI();
loadMateri();