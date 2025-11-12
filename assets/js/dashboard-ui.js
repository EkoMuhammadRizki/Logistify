// Dashboard UI helpers: client-side search/filter and low-stock highlighting
(function(){
  function normalize(str){ return (str||'').toString().toLowerCase(); }

  function filterTable(){
    var inputEl = document.getElementById('searchInput');
    var q = normalize(inputEl ? inputEl.value : '');
    var tbody = document.getElementById('data-table');
    if (!tbody) return;
    Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function(tr){
      var hay = '';
      tr.querySelectorAll('[data-search="true"]').forEach(function(cell){ hay += ' ' + normalize(cell.textContent); });
      var match = !q || hay.indexOf(q) !== -1;
      tr.style.display = match ? '' : 'none';
    });
  }

  function applyLowStockHighlight(){
    var tbody = document.getElementById('data-table');
    if (!tbody) return;
    Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function(tr){
      var stokCell = tr.querySelector('[data-col="stok"]');
      if (!stokCell) return;
      var val = parseInt(stokCell.textContent || '0', 10) || 0;
      if (val > 0 && val <= 5) {
        tr.classList.add('row-menipis');
        var badge = tr.querySelector('.badge-menipis');
        if (!badge){
          var el = document.createElement('span');
          el.className = 'badge-menipis ms-1';
          el.textContent = 'Menipis';
          stokCell.appendChild(el);
        }
      }
    });
  }

  function start(){
    var input = document.getElementById('searchInput');
    if (input){ input.addEventListener('input', filterTable); input.addEventListener('keyup', filterTable); }
    filterTable();
    applyLowStockHighlight();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else { start(); }

  // Expose filter for external calls (e.g., when switching sections)
  window.logiFilterTable = filterTable;
})();

// Grafik Aktivitas Stok (Chart.js) — dinamis via AJAX
(function(){
  var chartEl = document.getElementById('stockChart');
  if (!chartEl || typeof Chart === 'undefined') { return; }

  var stockChart = null;
  function render(data){
    var labels = data.labels || ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    var masuk = data.masuk || Array(12).fill(0);
    var keluar = data.keluar || Array(12).fill(0);
    var cfg = {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Barang Masuk',
            data: masuk,
            backgroundColor: 'rgba(40, 167, 69, 0.6)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
          },
          {
            label: 'Barang Keluar',
            data: keluar,
            backgroundColor: 'rgba(30, 126, 52, 0.9)',
            borderColor: 'rgba(30, 126, 52, 1)',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 6, right: 8, bottom: 28, left: 8 } },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Jumlah barang' } },
          x: {
            title: { display: true, text: 'Bulan' },
            ticks: {
              autoSkip: true,
              maxRotation: 60,
              minRotation: 0,
              padding: 8,
              font: { size: 12 }
            }
          }
        },
        plugins: {
          legend: { labels: { color: '#e9ecef' } },
          title: { display: true, text: 'Aktivitas Stok ' + (data.year || ''), color: '#e9ecef' }
        }
      }
    };
    // Hancurkan chart placeholder jika ada, lalu render chart dinamis
    if (!stockChart) {
      var existing = (typeof Chart.getChart === 'function') ? Chart.getChart(chartEl) : null;
      if (existing) { existing.destroy(); }
      stockChart = new Chart(chartEl.getContext('2d'), cfg);
    } else {
      stockChart.data.labels = labels;
      stockChart.data.datasets[0].data = masuk;
      stockChart.data.datasets[1].data = keluar;
      stockChart.update();
    }
  }

  function fetchData(){
    fetch('stats_aktivitas.php')
      .then(function(res){ return res.json(); })
      .then(function(json){ if (json && json.status === 'success') render(json); })
      .catch(function(){ /* diamkan bila gagal */ });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fetchData);
  } else { fetchData(); }

  // Ekspor untuk refresh setelah transaksi masuk/keluar
  window.refreshStockChart = fetchData;
})();

// Grafik Stok Minimum (Chart.js) — top-5 stok terendah
(function(){
  var chartEl = document.getElementById('minStockChart');
  if (!chartEl || typeof Chart === 'undefined') { return; }

  var minChart = null;
  function render(data){
    var labels = data.labels || [];
    var values = data.values || [];
    var cfg = {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Stok Saat Ini',
          data: values,
          backgroundColor: 'rgba(40, 167, 69, 0.7)',
          borderColor: 'rgba(40, 167, 69, 1)',
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { beginAtZero: true, title: { display: true, text: 'Stok' } },
          y: { title: { display: true, text: 'Barang' } }
        },
        plugins: {
          legend: { labels: { color: '#e9ecef' } },
          title: { display: true, text: 'Stok Terendah (Top-5)', color: '#e9ecef' }
        }
      }
    };
    if (!minChart) {
      var existing = (typeof Chart.getChart === 'function') ? Chart.getChart(chartEl) : null;
      if (existing) { existing.destroy(); }
      minChart = new Chart(chartEl.getContext('2d'), cfg);
    } else {
      minChart.data.labels = labels;
      minChart.data.datasets[0].data = values;
      minChart.update();
    }
  }

  function fetchData(){
    fetch('stats_min_stok.php')
      .then(function(res){ return res.json(); })
      .then(function(json){ if (json && json.status === 'success') render(json); })
      .catch(function(){ /* diamkan bila gagal */ });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fetchData);
  } else { fetchData(); }

  // Ekspor untuk refresh setelah transaksi masuk/keluar
  window.refreshMinStockChart = fetchData;
})();

// Grafik Barang Keluar Terbanyak (Chart.js) — top-5 sepanjang tahun
(function(){
  var chartEl = document.getElementById('topKeluarChart');
  if (!chartEl || typeof Chart === 'undefined') { return; }

  var topKeluarChart = null;
  function render(data){
    var labels = data.labels || [];
    var values = data.values || [];
    var year = data.year || new Date().getFullYear();
    var cfg = {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Total Keluar',
          data: values,
          backgroundColor: 'rgba(40, 167, 69, 0.7)',
          borderColor: 'rgba(40, 167, 69, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 6, right: 8, bottom: 28, left: 8 } },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Jumlah Keluar' } },
          x: {
            title: { display: true, text: 'Barang' },
            ticks: {
              autoSkip: true,
              maxRotation: 60,
              minRotation: 0,
              padding: 8,
              font: { size: 12 }
            }
          }
        },
        plugins: {
          legend: { labels: { color: '#e9ecef' } },
          title: { display: true, text: 'Barang Keluar Terbanyak (Top-5) Tahun ' + year, color: '#e9ecef' }
        }
      }
    };
    if (!topKeluarChart) {
      var existing = (typeof Chart.getChart === 'function') ? Chart.getChart(chartEl) : null;
      if (existing) { existing.destroy(); }
      topKeluarChart = new Chart(chartEl.getContext('2d'), cfg);
    } else {
      topKeluarChart.data.labels = labels;
      topKeluarChart.data.datasets[0].data = values;
      topKeluarChart.update();
    }
  }

  function fetchData(){
    var year = new Date().getFullYear();
    fetch('stats_keluar_top.php?year=' + encodeURIComponent(year) + '&limit=5')
      .then(function(res){ return res.json(); })
      .then(function(json){ if (json && json.status === 'success') render(json); })
      .catch(function(){});
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fetchData);
  } else { fetchData(); }

  window.refreshTopKeluarChart = fetchData;
})();

// Ringkasan Stok (Total, Menipis, Habis) — auto-refresh via AJAX
(function(){
  function formatID(n){ try { return new Intl.NumberFormat('id-ID').format(n || 0); } catch(e){ return (n||0).toString(); } }
  var lastHabisCount = null;
  function getStoredHabis(){
    try {
      var s = sessionStorage.getItem('logistify.habisCount');
      if (s === null || s === undefined) return null;
      var v = parseInt(s, 10);
      return isNaN(v) ? null : v;
    } catch(e){ return null; }
  }
  function setStoredHabis(n){
    try { sessionStorage.setItem('logistify.habisCount', String(n || 0)); } catch(e){}
  }

  function render(data){
    var elTotal = document.getElementById('summaryTotalQty');
    var elMenipis = document.getElementById('summaryMenipisCount');
    var elHabis = document.getElementById('summaryHabisCount');
    if (elTotal) { elTotal.textContent = formatID(data.total_qty || 0); }
    if (elMenipis) { elMenipis.textContent = formatID(data.menipis_count || 0); }
    var currentHabis = data.habis_count || 0;
    if (elHabis) { elHabis.textContent = formatID(currentHabis); }
    // Tampilkan notifikasi di Dashboard satu kali per sesi, dan saat jumlah bertambah
    try {
      if (typeof Swal !== 'undefined') {
        var prevStored = getStoredHabis();
        var shouldNotify = currentHabis > 0 && (prevStored === null || currentHabis > prevStored);
        if (shouldNotify) {
          Swal.fire({
            title: 'Notifikasi Stok Habis',
            text: 'Stok Habis sekarang: ' + formatID(currentHabis),
            icon: 'warning',
            confirmButtonText: 'Mengerti',
            customClass: { confirmButton: 'btn btn-success' },
            buttonsStyling: false
          });
        }
        setStoredHabis(currentHabis);
      }
    } catch(e){}
    lastHabisCount = currentHabis;
  }

  function fetchSummary(){
    fetch('stats_summary.php')
      .then(function(res){ return res.json(); })
      .then(function(json){ if (json && json.status === 'success') { render(json); } })
      .catch(function(){ /* diamkan bila gagal */ });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fetchSummary);
  } else { fetchSummary(); }

  // Ekspor untuk dipanggil setelah transaksi masuk/keluar
  window.refreshSummaryStats = fetchSummary;
  // Dengarkan perubahan localStorage dari halaman lain (mis. Data Barang)
  // Ketika tombol Hapus diklik, halaman tersebut meng-set key 'logistify.notifyHabis'.
  // Dashboard yang terbuka akan menangkap event ini, lalu me-refresh ringkasan & notifikasi.
  window.addEventListener('storage', function(ev){
    try {
      if (ev && ev.key === 'logistify.notifyHabis') {
        fetchSummary();
      }
    } catch(e){}
  });
})();
