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