// DEMUSTAR main script (FULL revised)

// -------------------------------
// Helpers
// -------------------------------
async function safeJson(res) {
  const txt = await res.text();
  try { return JSON.parse(txt); }
  catch { return { ok: false, _raw: txt }; }
}

function setStatus(el, type, msg) {
  if (!el) return;
  el.classList.remove("ok", "err", "muted");
  if (type) el.classList.add(type);
  el.textContent = msg || "";
}

// -------------------------------
// Main
// -------------------------------
document.addEventListener("DOMContentLoaded", () => {

  // ===============================
  // Footer year (optional)
  // ===============================
  const year = document.getElementById("year");
  if (year) year.textContent = new Date().getFullYear();

  // ===============================
  // MOBILE MENU (optional)
  // ===============================
  const hamburger = document.getElementById("hamburger");
  const mobileMenu = document.getElementById("mobileMenu");

  if (hamburger && mobileMenu) {
    hamburger.addEventListener("click", () => {
      mobileMenu.style.display =
        mobileMenu.style.display === "block" ? "none" : "block";
    });

    mobileMenu.querySelectorAll("a").forEach((a) => {
      a.addEventListener("click", () => {
        mobileMenu.style.display = "none";
      });
    });
  }

  // ===============================
  // USER DROPDOWN + SESSION STATE
  // ===============================
  (function initUserDropdown() {
    const userWrap = document.querySelector(".user"); // wrapper .user
    const userBtn = document.getElementById("userBtn");
    const userMenu = document.getElementById("userMenu");
    const userName = document.getElementById("userName");
    const loginLink = document.getElementById("loginLink");
    const profileLink = document.getElementById("profileLink");
    const btnLogout = document.getElementById("btnLogout");

    if (!userWrap || !userBtn || !userMenu || !userName) return;

    // open/close
    const close = () => userWrap.classList.remove("open");
    const toggle = () => userWrap.classList.toggle("open");

    userBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggle();
    });

    document.addEventListener("click", (e) => {
      if (!userWrap.contains(e.target)) close();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") close();
    });

    // set guest/user UI
    function setGuestUI() {
      userName.textContent = "Guest";
      if (loginLink) loginLink.style.display = "";
      if (profileLink) profileLink.style.display = "none";
      if (btnLogout) btnLogout.style.display = "none";
    }

    function setUserUI(user) {
      userName.textContent = user?.nama || "User";
      if (loginLink) loginLink.style.display = "none";
      if (profileLink) profileLink.style.display = "";
      if (btnLogout) btnLogout.style.display = "";
    }

    // fetch session
    (async () => {
      try {
        const res = await fetch("./api/auth/me.php", { credentials: "same-origin" });
        const data = await safeJson(res);
        if (res.ok && data?.ok) setUserUI(data.user);
        else setGuestUI();
      } catch {
        setGuestUI();
      }
    })();

    // logout
    btnLogout?.addEventListener("click", async () => {
      btnLogout.disabled = true;
      try {
        await fetch("./api/auth/logout.php", {
          method: "POST",
          credentials: "same-origin",
        });
      } catch {
        // ignore network errors
      } finally {
        btnLogout.disabled = false;
        close();
        location.reload();
      }
    });
  })();

  // ===============================
  // ASPIRASI FORM
  // ===============================
  (function initAspirasiForm() {
    const aspirasiForm = document.getElementById("aspirasiForm");
    const aspirasiStatus = document.getElementById("aspirasiStatus");

    const anonimCheckbox = document.getElementById("anonimCheckbox");
    const namaInput = document.getElementById("namaInput");
    const kontakInput = document.getElementById("kontakInput");

    if (anonimCheckbox) {
      anonimCheckbox.addEventListener("change", () => {
        const isAnon = anonimCheckbox.checked;
        if (namaInput) {
          namaInput.disabled = isAnon;
          if (isAnon) namaInput.value = "";
        }
        if (kontakInput) {
          kontakInput.disabled = isAnon;
          if (isAnon) kontakInput.value = "";
        }
      });
    }

    if (!aspirasiForm) return;

    aspirasiForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const fd = new FormData(aspirasiForm);
      const payload = {
        nama: String(fd.get("nama") || "").trim(),
        kontak: String(fd.get("kontak") || "").trim(),
        kategori: String(fd.get("kategori") || "").trim(),
        isi: String(fd.get("isi") || "").trim(),
        anonim: Boolean(fd.get("anonim")),
      };

      if (!payload.kategori || !payload.isi) {
        setStatus(aspirasiStatus, "err", "Kategori dan isi aspirasi wajib diisi.");
        return;
      }

      setStatus(aspirasiStatus, "muted", "Mengirim aspirasi...");
      const btn = aspirasiForm.querySelector('button[type="submit"], input[type="submit"]');
      if (btn) btn.disabled = true;

      try {
        const res = await fetch("./api/aspirasi.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify(payload),
        });

        const out = await safeJson(res);

        if (!res.ok || !out?.ok) {
          setStatus(aspirasiStatus, "err", out?.message || "Gagal mengirim aspirasi.");
          return;
        }

        setStatus(aspirasiStatus, "ok", "Aspirasi terkirim. Terima kasih!");
        aspirasiForm.reset();

        // balikkan state anonim ke normal
        if (namaInput) namaInput.disabled = false;
        if (kontakInput) kontakInput.disabled = false;
        if (anonimCheckbox) anonimCheckbox.checked = false;

      } catch (err) {
        setStatus(aspirasiStatus, "err", err?.message || "Terjadi kesalahan jaringan.");
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  })();

  // ===============================
  // PRESTASI FORM (+ tingkat + tanggal)
  // ===============================
  (function initPrestasiForm() {
    const prestasiForm = document.getElementById("prestasiForm");
    const prestasiStatus = document.getElementById("prestasiStatus");
    if (!prestasiForm) return;

    prestasiForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const fd = new FormData(prestasiForm);
      const payload = {
        nama: String(fd.get("nama") || "").trim(),
        kelas: String(fd.get("kelas") || "").trim(),
        nit: String(fd.get("nit") || "").trim(),
        link: String(fd.get("link") || "").trim(),
        deskripsi: String(fd.get("deskripsi") || "").trim(),
        saran: String(fd.get("saran") || "").trim(),

        // NEW fields
        tingkat: String(fd.get("tingkat") || "").trim(),
        tanggal: String(fd.get("tanggal") || "").trim(),
      };

      // required: all except saran
      if (
        !payload.nama ||
        !payload.kelas ||
        !payload.nit ||
        !payload.link ||
        !payload.deskripsi ||
        !payload.tingkat ||
        !payload.tanggal
      ) {
        setStatus(prestasiStatus, "err", "Semua field wajib diisi kecuali saran.");
        return;
      }

      // guard: tingkat must be one of allowed
      const allowed = new Set(["Kota", "Provinsi", "Nasional", "Internasional"]);
      if (!allowed.has(payload.tingkat)) {
        setStatus(prestasiStatus, "err", "Tingkat kejuaraan tidak valid.");
        return;
      }

      setStatus(prestasiStatus, "muted", "Mengirim pengajuan prestasi...");
      const btn = prestasiForm.querySelector('button[type="submit"], input[type="submit"]');
      if (btn) btn.disabled = true;

      try {
        const res = await fetch("./api/prestasi.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify(payload),
        });

        const out = await safeJson(res);

        if (!res.ok || !out?.ok) {
          setStatus(prestasiStatus, "err", out?.message || "Gagal mengirim pengajuan.");
          return;
        }

        setStatus(prestasiStatus, "ok", "Pengajuan prestasi terkirim. Akan kami tindak lanjuti.");
        prestasiForm.reset();

      } catch (err) {
        setStatus(prestasiStatus, "err", err?.message || "Terjadi kesalahan jaringan.");
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  })();

});
