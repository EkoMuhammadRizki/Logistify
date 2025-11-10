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
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Jumlah barang' } },
          x: { title: { display: true, text: 'Bulan' } }
        },
        plugins: {
          legend: { labels: { color: '#e9ecef' } },
          title: { display: true, text: 'Aktivitas Stok ' + (data.year || ''), color: '#e9ecef' }
        }
      }
    };
    if (!stockChart) { stockChart = new Chart(chartEl.getContext('2d'), cfg); }
    else {
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