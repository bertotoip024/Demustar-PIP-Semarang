document.addEventListener("DOMContentLoaded", () => {
  const yearEl   = document.getElementById("year");
  const form     = document.getElementById("loginForm");
  const nitInput = document.getElementById("nit");
  const dobInput = document.getElementById("tanggal_lahir");
  const passInput= document.getElementById("password");
  const toggleBtn= document.getElementById("togglePass");
  const statusEl = document.getElementById("loginStatus");
  const btnLogin = document.getElementById("btnLogin");

  if (yearEl) yearEl.textContent = new Date().getFullYear();
  if (!form) return;

  // ===== Helpers =====
  function setStatus(type, msg) {
    if (!statusEl) return;
    statusEl.classList.remove("ok","err","muted");
    if (type) statusEl.classList.add(type);
    statusEl.textContent = msg || "";
  }

  function setLoading(on) {
    if (!btnLogin) return;
    btnLogin.disabled = on;
    btnLogin.textContent = on ? "Memproses..." : "Login";
  }

  // ===== Toggle password =====
  toggleBtn?.addEventListener("click", () => {
    const show = passInput.type === "password";
    passInput.type = show ? "text" : "password";
    toggleBtn.textContent = show ? "🙈" : "👁";
    passInput.focus();
  });

  // ===== Submit =====
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const nit           = (nitInput?.value  || "").trim();
    const tanggal_lahir = (dobInput?.value  || "").trim(); // YYYY-MM-DD dari input[type=date]
    const password      = passInput?.value  || "";

    if (!nit)           { setStatus("err","NIT wajib diisi."); nitInput?.focus(); return; }
    if (!tanggal_lahir) { setStatus("err","Tanggal lahir wajib diisi."); dobInput?.focus(); return; }
    if (!password)      { setStatus("err","Password wajib diisi."); passInput?.focus(); return; }

    setStatus("muted","Memproses login...");
    setLoading(true);

    // URL yang benar — file ada di api/auth/login.php
    const apiUrl = "./api/auth/login.php";
    console.log("[LOGIN] →", apiUrl, { nit, tanggal_lahir });

    try {
      const res = await fetch(apiUrl, {
        method:      "POST",
        headers:     { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ nit, password, tanggal_lahir }),
      });

      const raw = await res.text();
      console.log("[LOGIN] HTTP", res.status, raw.substring(0,300));

      let out = null;
      try { out = JSON.parse(raw); } catch { /* bukan JSON */ }

      if (!out) {
        // PHP error / HTML
        if (raw.includes("Fatal") || raw.includes("Parse error")) {
          setStatus("err","PHP Error — buka http://localhost/demustar/api/auth/login.php untuk detail.");
        } else {
          setStatus("err","Server error: respon bukan JSON. Cek Console (F12).");
        }
        setLoading(false);
        return;
      }

      if (!res.ok || !out.ok) {
        setStatus("err", out.message || `Login gagal (HTTP ${res.status}).`);
        setLoading(false);
        return;
      }

      // Berhasil
      const role = out.data?.role || "user";
      const nama = out.data?.nama || "User";

      sessionStorage.setItem("demustar_welcome", JSON.stringify({ name: nama, role, ts: Date.now() }));

      setStatus("ok", `✓ Selamat datang, ${nama}!`);
      btnLogin.textContent = "Berhasil ✓";

      setTimeout(() => {
        window.location.href = role === "admin" ? "./admin/admin-verifikasi.html" : "./";
      }, 1500);

    } catch (err) {
      console.error("[LOGIN]", err);
      setStatus("err","Tidak bisa terhubung. Pastikan XAMPP Apache aktif dan buka via http://localhost/demustar/");
      setLoading(false);
    }
  });
});
