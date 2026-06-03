// Pemilihan Top 4 — DEMUSTAR (rev)

const candidates = [
  { id: "K01", nama: "Kandidat 1", foto: "./assets/kandidat1.jpg",
    visi: "Mewujudkan lingkungan taruna yang lebih kolaboratif, transparan, dan progresif.",
    misi: "• Tingkatkan komunikasi dan keterbukaan informasi\n• Program kerja terukur\n• Respons cepat aspirasi" },
  { id: "K02", nama: "Kandidat 2", foto: "./assets/kandidat2.jpg",
    visi: "Membangun budaya organisasi yang sehat, produktif, dan berprestasi.",
    misi: "• Dukungan kesejahteraan taruna\n• Pengembangan skill\n• Apresiasi kinerja" },
  { id: "K03", nama: "Kandidat 3", foto: "./assets/kandidat3.jpg",
    visi: "Menjadi penggerak inovasi dan perbaikan berkelanjutan.",
    misi: "• Digitalisasi proses\n• Simplifikasi birokrasi\n• Keputusan berbasis data" },
  { id: "K04", nama: "Kandidat 4", foto: "./assets/kandidat4.jpg",
    visi: "Meningkatkan rasa kepemilikan dan kebersamaan antar taruna.",
    misi: "• Aktivasi komunitas\n• Event rutin\n• Kolaborasi lintas kelas" },
  { id: "K05", nama: "Kandidat 5", foto: "./assets/kandidat5.jpg",
    visi: "Menciptakan iklim aspirasi yang tertata, aman, dan berdampak.",
    misi: "• Kanal aspirasi terstruktur\n• Tindak lanjut transparan\n• Timeline penyelesaian" },
  { id: "K06", nama: "Kandidat 6", foto: "./assets/kandidat6.jpg",
    visi: "Menjadi jembatan solusi antara taruna dan pihak terkait.",
    misi: "• Mediasi yang adil\n• Koordinasi cepat\n• Dokumentasi keputusan" },
  { id: "K07", nama: "Kandidat 7", foto: "./assets/kandidat7.jpg",
    visi: "Menguatkan prestasi taruna melalui dukungan program dan fasilitas.",
    misi: "• Pembinaan lomba\n• Akses informasi lomba\n• Pendampingan administrasi" },
];

const elList = document.getElementById("candidateList");
const elNotice = document.getElementById("voteNotice");
const elSearch = document.getElementById("search");
const elSort = document.getElementById("sort");

// voter info
const vNama = document.getElementById("vNama");
const vNit = document.getElementById("vNit");
const vDob = document.getElementById("vDob");
const vStatus = document.getElementById("vStatus");
const voteBadge = document.getElementById("voteBadge");

// user dropdown
const userBtn = document.getElementById("userBtn");
const userDropdown = document.getElementById("userDropdown");
const userNameEl = document.getElementById("userName");
const profileLink = document.getElementById("profileLink");
const profileLabel = document.getElementById("profileLabel");
const btnLogout = document.getElementById("btnLogout");
const userMenu = document.getElementById("userMenu");

// modal
const backdrop = document.getElementById("modalBackdrop");
const btnCancel = document.getElementById("btnCancel");
const btnConfirm = document.getElementById("btnConfirm");
const modalBody = document.getElementById("modalBody");

let currentUser = null;
let serverVoteStatus = null; // {has_voted, vote:{kandidat_id, created_at}}
let pendingPick = null;

function showNotice(type, msg) {
  if (!elNotice) return;
  elNotice.className = "notice " + (type || "");
  elNotice.textContent = msg;
  elNotice.style.display = "block";
}
function hideNotice() {
  if (!elNotice) return;
  elNotice.style.display = "none";
  elNotice.textContent = "";
  elNotice.className = "notice";
}

function openDropdown() {
  if (!userDropdown) return;
  userDropdown.style.display = "flex";
  userDropdown.setAttribute("aria-hidden", "false");
}
function closeDropdown() {
  if (!userDropdown) return;
  userDropdown.style.display = "none";
  userDropdown.setAttribute("aria-hidden", "true");
}
function isDropdownOpen() {
  return userDropdown?.style.display === "flex";
}

userBtn?.addEventListener("click", (e) => {
  e.preventDefault();
  isDropdownOpen() ? closeDropdown() : openDropdown();
});
document.addEventListener("click", (e) => {
  if (!userMenu) return;
  if (!userMenu.contains(e.target)) closeDropdown();
});
closeDropdown();

function setGuestUI() {
  userNameEl && (userNameEl.textContent = "Guest");
  if (profileLink) profileLink.href = "./login.html";
  if (profileLabel) profileLabel.textContent = "Login";
  if (btnLogout) btnLogout.style.display = "none";

  vNama && (vNama.textContent = "Guest");
  vNit && (vNit.textContent = "—");
  vDob && (vDob.textContent = "—");
  vStatus && (vStatus.textContent = "Belum login");
  setBadge(null);
}

function setUserUI(u) {
  userNameEl && (userNameEl.textContent = u?.nama || "User");
  if (profileLink) profileLink.href = "./profil.html";
  if (profileLabel) profileLabel.textContent = "Profil";
  if (btnLogout) btnLogout.style.display = "flex";

  vNama && (vNama.textContent = u?.nama || "—");
  vNit && (vNit.textContent = u?.nit || "—");
  vDob && (vDob.textContent = u?.tanggal_lahir || "—");
}

function setBadge(status) {
  if (!voteBadge) return;
  voteBadge.classList.remove("good", "bad");
  if (status === "voted") {
    voteBadge.textContent = "SUDAH MEMILIH";
    voteBadge.classList.add("good");
  } else if (status === "not_voted") {
    voteBadge.textContent = "BELUM MEMILIH";
    voteBadge.classList.add("bad");
  } else {
    voteBadge.textContent = "—";
  }
}

async function safeJson(res) {
  const txt = await res.text();
  try { return JSON.parse(txt); } catch { return { ok:false, _raw: txt }; }
}

async function loadUser() {
  try {
    const res = await fetch("./api/auth/me.php", { credentials: "same-origin", headers: { Accept: "application/json" } });
    const data = await safeJson(res);
    if (!res.ok || !data.ok) {
      setGuestUI();
      return null;
    }
    currentUser = data.user;
    setUserUI(currentUser);
    return currentUser;
  } catch {
    setGuestUI();
    return null;
  }
}

async function fetchVoteStatus() {
  if (!currentUser?.nit) return null;
  try {
    const res = await fetch("./vote_top4/status.php", { credentials: "same-origin" });
    const data = await safeJson(res);
    if (!res.ok || !data.ok) return null;
    serverVoteStatus = data;
    return serverVoteStatus;
  } catch {
    return null;
  }
}

function getVoteKandidatId() {
  return serverVoteStatus?.vote?.kandidat_id || null;
}

function hasVoted() {
  return Boolean(serverVoteStatus?.has_voted);
}

function openModal(text) {
  if (modalBody) modalBody.textContent = text;
  backdrop?.classList.add("show");
  backdrop?.setAttribute("aria-hidden", "false");
}
function closeModal() {
  backdrop?.classList.remove("show");
  backdrop?.setAttribute("aria-hidden", "true");
  pendingPick = null;
}

btnCancel?.addEventListener("click", closeModal);
backdrop?.addEventListener("click", (e) => {
  if (e.target === backdrop) closeModal();
});

function placeholderImgSvg(name) {
  const initials = String(name || "K").trim().slice(0, 2).toUpperCase();
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
  <defs>
    <linearGradient id="g" x1="0" x2="1">
      <stop offset="0" stop-color="#60a5fa" stop-opacity="0.35"/>
      <stop offset="1" stop-color="#22c55e" stop-opacity="0.25"/>
    </linearGradient>
  </defs>
  <rect width="200" height="200" rx="32" fill="url(#g)"/>
  <text x="50%" y="54%" text-anchor="middle" font-size="72" font-family="Arial" fill="rgba(255,255,255,0.85)">${initials}</text>
</svg>`;
  return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
}

function renderList(list) {
  if (!elList) return;
  elList.innerHTML = "";

  const votedId = getVoteKandidatId();

  list.forEach((c) => {
    const card = document.createElement("div");
    card.className = "candidate" + (votedId === c.id ? " picked" : "");

    const cover = document.createElement("div");
    cover.className = "cover";

    const img = document.createElement("img");
    img.alt = c.nama;
    img.src = c.foto || placeholderImgSvg(c.nama);
    img.onerror = () => { img.src = placeholderImgSvg(c.nama); };

    cover.appendChild(img);

    const body = document.createElement("div");
    body.className = "body";

    const name = document.createElement("div");
    name.className = "name";
    name.textContent = c.nama;

    const meta = document.createElement("div");
    meta.className = "meta";
    meta.textContent = `${c.id} • Top 4`;

    const vm = document.createElement("div");
    vm.className = "vm";
    vm.textContent = `Visi: ${c.visi}\nMisi: ${c.misi}`;

    const actions = document.createElement("div");
    actions.className = "actions";

    const btn = document.createElement("button");
    btn.className = "btn primary";
    btn.type = "button";
    btn.textContent = hasVoted() ? "Terkunci" : "Pilih";
    btn.disabled = !currentUser || hasVoted();

    btn.addEventListener("click", () => {
      hideNotice();
      if (!currentUser) {
        showNotice("err", "Silakan login terlebih dahulu.");
        window.location.href = "./login.html";
        return;
      }
      if (hasVoted()) {
        showNotice("err", "Kamu sudah memilih. Tidak bisa mengubah pilihan.");
        return;
      }
      pendingPick = c;
      openModal(`Kamu yakin memilih ${c.nama} (${c.id})? Pilihan tidak bisa diubah.`);
    });

    const pill = document.createElement("span");
    pill.className = "pill";
    pill.textContent = votedId === c.id ? "Pilihanmu" : " ";

    actions.appendChild(pill);
    actions.appendChild(btn);

    body.appendChild(name);
    body.appendChild(meta);
    body.appendChild(vm);
    body.appendChild(actions);

    card.appendChild(cover);
    card.appendChild(body);

    elList.appendChild(card);
  });
}

async function submitVote(kandidat_id) {
  const res = await fetch("./vote_top4/submit.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify({ kandidat_id }),
  });
  const out = await safeJson(res);
  return { res, out };
}

btnConfirm?.addEventListener("click", async () => {
  if (!pendingPick) return;

  btnConfirm.disabled = true;
  btnConfirm.textContent = "Memproses...";

  try {
    const { res, out } = await submitVote(pendingPick.id);
    if (!res.ok || !out.ok) {
      showNotice("err", out.message || "Gagal menyimpan vote.");
      return;
    }

    await fetchVoteStatus();
    setBadge("voted");
    vStatus && (vStatus.textContent = "Sudah memilih");
    showNotice("ok", "Vote berhasil disimpan. Terima kasih!");
    renderList(applyFilterSort());
    closeModal();
  } catch (err) {
    showNotice("err", err?.message || "Terjadi kesalahan jaringan.");
  } finally {
    btnConfirm.disabled = false;
    btnConfirm.textContent = "Ya, Pilih";
  }
});

btnLogout?.addEventListener("click", async () => {
  btnLogout.disabled = true;
  try {
    await fetch("./api/auth/logout.php", { method: "POST", credentials: "same-origin" });
  } catch {
    // ignore
  } finally {
    btnLogout.disabled = false;
    closeDropdown();
    setGuestUI();
    serverVoteStatus = null;
    renderList(applyFilterSort());
    window.location.href = "./login.html";
  }
});

function applyFilterSort() {
  const q = (elSearch?.value || "").trim().toLowerCase();
  const sort = elSort?.value || "name_asc";

  let list = candidates.slice();

  if (q) {
    list = list.filter((c) => c.nama.toLowerCase().includes(q) || c.id.toLowerCase().includes(q) || c.visi.toLowerCase().includes(q) || c.misi.toLowerCase().includes(q));
  }

  const cmpStr = (a,b) => a.localeCompare(b, "id", { sensitivity: "base" });

  list.sort((a,b) => {
    if (sort === "name_desc") return cmpStr(b.nama, a.nama);
    if (sort === "id_asc") return cmpStr(a.id, b.id);
    if (sort === "id_desc") return cmpStr(b.id, a.id);
    return cmpStr(a.nama, b.nama);
  });

  return list;
}

elSearch?.addEventListener("input", () => renderList(applyFilterSort()));
elSort?.addEventListener("change", () => renderList(applyFilterSort()));

(async function init() {
  hideNotice();
  setGuestUI();

  const u = await loadUser();
  if (!u) {
    // tetap render kandidat tapi disabled
    renderList(applyFilterSort());
    return;
  }

  await fetchVoteStatus();
  if (hasVoted()) {
    setBadge("voted");
    vStatus && (vStatus.textContent = "Sudah memilih");
  } else {
    setBadge("not_voted");
    vStatus && (vStatus.textContent = "Belum memilih");
  }

  renderList(applyFilterSort());
})();
