// DEMUSTAR - Aspirasi Page JavaScript

// ===== Global Variables =====
let currentUser = null;
let allAspirations = [];
let currentFilter = 'all';
let currentSearch = '';

// ===== DOM Elements =====
const sliderTrack = document.getElementById('sliderTrack');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const sliderDots = document.getElementById('sliderDots');
const emptyState = document.getElementById('emptyState');
const filterBtns = document.querySelectorAll('.filter-btn');
const searchInput = document.getElementById('searchAspirasi');
const statPending = document.getElementById('statPending');
const statProses = document.getElementById('statProses');
const statSelesai = document.getElementById('statSelesai');

// ===== Helper Functions =====
async function safeJson(res) {
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch {
    return { ok: false, _raw: text };
  }
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getStatusBadge(status) {
  const statusMap = {
    'proses': { label: 'Diproses', class: 'status-proses' },
    'selesai': { label: 'Selesai', class: 'status-selesai' }
  };
  const s = statusMap[status] || { label: status, class: '' };
  return `<span class="card-status ${s.class}">${s.label}</span>`;
}

// ===== Load User Session =====
async function loadUser() {
  try {
    const res = await fetch('./api/auth/me.php', { credentials: 'same-origin' });
    const data = await safeJson