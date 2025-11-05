// file_manager.js — render uploaded files as visual cards, handle upload validation and delete
(function () {
  const allowedExt = ['pdf','doc','docx','xls','xlsx','csv','png','jpg','jpeg','gif','bmp','webp'];

  function fmtSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
  }

  function extOf(name) {
    return (name.split('.').pop() || '').toLowerCase();
  }

  function categoryOf(name) {
    const e = extOf(name);
    if (['xls','xlsx','csv'].includes(e)) return 'sheets';
    if (['pdf'].includes(e)) return 'pdfs';
    if (['doc','docx'].includes(e)) return 'docs';
    if (['png','jpg','jpeg','gif','bmp','webp'].includes(e)) return 'images';
    return 'other';
  }

  async function loadFiles() {
    const listEl = document.getElementById('fileList');
    if (!listEl) return;
    listEl.innerHTML = '<div class="empty">Loading files…</div>';

    try {
      const res = await fetch('../list_files.php', { cache: 'no-store' });
      if (res.status === 403) {
        listEl.innerHTML = '<div class="empty">You must be logged in to see uploaded files.</div>';
        return;
      }
      const files = await res.json();

      // prepare categories counts
      const cats = { all: files.length, sheets: 0, pdfs: 0, docs: 0, images: 0, other: 0 };
      files.forEach(f => { const c = categoryOf(f.name); if (cats[c] !== undefined) cats[c]++; });

      // render folder bar
      const folderBar = document.createElement('div');
      folderBar.className = 'folder-bar';
      const buttons = [
        ['all', 'All'],
        ['sheets','Excel/Sheets'],
        ['pdfs','PDFs'],
        ['docs','Docs'],
        ['images','Images']
      ];
      buttons.forEach(([key,label]) => {
        const btn = document.createElement('button');
        btn.className = 'folder-pill' + (key === 'all' ? ' active' : '');
        btn.textContent = `${label} (${cats[key]||0})`;
        btn.dataset.cat = key;
        btn.addEventListener('click', () => {
          folderBar.querySelectorAll('.folder-pill').forEach(p=>p.classList.remove('active'));
          btn.classList.add('active');
          renderGrid(files, key);
        });
        folderBar.appendChild(btn);
      });

      // attach
      listEl.innerHTML = '';
      listEl.appendChild(folderBar);

      // view containers: grid and table
      const grid = document.createElement('div');
      grid.className = 'file-grid';
      const tableWrap = document.createElement('div');
      tableWrap.className = 'file-table-wrap';
      const table = document.createElement('table');
      table.className = 'file-table';
      table.innerHTML = '<thead><tr><th>Name</th><th class="col-category">Type</th><th class="col-size">Size</th><th class="col-actions">Actions</th></tr></thead><tbody></tbody>';
      tableWrap.appendChild(table);

      listEl.appendChild(grid);
      listEl.appendChild(tableWrap);

      // default view
      let currentFilter = 'all';
      const gridBtn = document.getElementById('gridViewBtn');
      const tableBtn = document.getElementById('tableViewBtn');
      function setView(isTable) {
        if (isTable) {
          tableBtn && tableBtn.classList.add('active');
          gridBtn && gridBtn.classList.remove('active');
          grid.style.display = 'none';
          tableWrap.style.display = '';
          renderTable(files, currentFilter);
        } else {
          gridBtn && gridBtn.classList.add('active');
          tableBtn && tableBtn.classList.remove('active');
          grid.style.display = '';
          tableWrap.style.display = 'none';
          renderGrid(files, currentFilter);
        }
      }

      if (gridBtn) gridBtn.addEventListener('click', () => setView(false));
      if (tableBtn) tableBtn.addEventListener('click', () => setView(true));

      // start with grid
      setView(false);

      function renderGrid(filesArr, filter) {
        grid.innerHTML = '';
        const filtered = filesArr.filter(f => {
          if (!filter || filter === 'all') return true;
          return categoryOf(f.name) === filter;
        });
        if (!filtered.length) {
          grid.innerHTML = '<div class="empty">No files in this category.</div>';
          return;
        }

  filtered.forEach(f => {
          const card = document.createElement('div');
          card.className = 'file-card';

          const thumb = document.createElement('div');
          thumb.className = 'file-thumb';
          const ext = extOf(f.name);
          const cat = categoryOf(f.name);
          // build url relative to html/ folder -> prepend ../
          const url = (f.url && !f.url.startsWith('http') ? '../' + f.url : f.url);

          if (cat === 'images') {
            const img = document.createElement('img');
            img.src = url;
            img.alt = f.name;
            thumb.appendChild(img);
          } else {
            const icon = document.createElement('div');
            icon.className = 'icon';
            icon.style.fontSize = '1.6rem';
            let fa = 'fa-file';
            if (cat === 'sheets') fa = 'fa-file-excel';
            if (cat === 'pdfs') fa = 'fa-file-pdf';
            if (cat === 'docs') fa = 'fa-file-word';
            icon.innerHTML = `<i class="fas ${fa}" aria-hidden="true"></i>`;
            thumb.appendChild(icon);
          }

          const meta = document.createElement('div');
          meta.className = 'file-meta';
          const left = document.createElement('div');
          left.style.flex = '1';
          const nameEl = document.createElement('div');
          nameEl.className = 'file-name';
          nameEl.textContent = f.name;
          const sub = document.createElement('div');
          sub.className = 'file-sub';
          sub.textContent = fmtSize(f.size) + ' • ' + ext.toUpperCase();
          left.appendChild(nameEl);
          left.appendChild(sub);

          const actions = document.createElement('div');
          actions.className = 'file-actions';
          const aDown = document.createElement('a');
          aDown.href = url;
          aDown.setAttribute('download', f.name);
          aDown.className = 'btn-download';
          aDown.innerHTML = '<i class="fas fa-download" aria-hidden="true"></i> Download';
          const btnDel = document.createElement('button');
          btnDel.className = 'btn-delete';
          btnDel.type = 'button';
          btnDel.innerHTML = '<i class="fas fa-trash-alt" aria-hidden="true"></i> Delete';
          btnDel.addEventListener('click', async () => {
            if (!confirm('Delete "' + f.name + '" ?')) return;
            try {
              const res = await fetch('../delete_file.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'name=' + encodeURIComponent(f.name) });
              if (res.ok) {
                loadFiles();
              } else if (res.status === 403) {
                alert('You are not authorized. Please login.');
              } else {
                alert('Delete failed');
              }
            } catch (err) {
              console.error(err);
              alert('Delete failed');
            }
          });

          actions.appendChild(aDown);
          actions.appendChild(btnDel);

          meta.appendChild(left);
          meta.appendChild(actions);

          card.appendChild(thumb);
          card.appendChild(meta);
          grid.appendChild(card);
        });
      }

      function renderTable(filesArr, filter) {
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        const filtered = filesArr.filter(f => {
          if (!filter || filter === 'all') return true;
          return categoryOf(f.name) === filter;
        });
        if (!filtered.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="empty">No files in this category.</td></tr>';
          return;
        }

        filtered.forEach(f => {
          const tr = document.createElement('tr');
          const ext = extOf(f.name);
          const cat = categoryOf(f.name);
          const url = (f.url && !f.url.startsWith('http') ? '../' + f.url : f.url);

          const tdName = document.createElement('td');
          tdName.textContent = f.name;

          const tdCat = document.createElement('td');
          tdCat.className = 'col-category';
          tdCat.textContent = cat === 'sheets' ? 'Sheets' : (cat === 'pdfs' ? 'PDF' : (cat === 'docs' ? 'Doc' : (cat === 'images' ? 'Image' : 'Other')));

          const tdSize = document.createElement('td');
          tdSize.className = 'col-size';
          tdSize.textContent = fmtSize(f.size);

          const tdActions = document.createElement('td');
          tdActions.className = 'col-actions';
          const aDown = document.createElement('a');
          aDown.href = url; aDown.setAttribute('download', f.name); aDown.className = 'btn-download'; aDown.innerHTML = '<i class="fas fa-download" aria-hidden="true"></i> Download';
          const btnDel = document.createElement('button'); btnDel.className = 'btn-delete'; btnDel.type = 'button'; btnDel.innerHTML = '<i class="fas fa-trash-alt" aria-hidden="true"></i> Delete';
          btnDel.addEventListener('click', async () => {
            if (!confirm('Delete "' + f.name + '" ?')) return;
            try {
              const res = await fetch('../delete_file.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'name=' + encodeURIComponent(f.name) });
              if (res.ok) { loadFiles(); } else if (res.status === 403) { alert('You are not authorized. Please login.'); } else { alert('Delete failed'); }
            } catch (err) { console.error(err); alert('Delete failed'); }
          });
          tdActions.appendChild(aDown); tdActions.appendChild(btnDel);

          tr.appendChild(tdName); tr.appendChild(tdCat); tr.appendChild(tdSize); tr.appendChild(tdActions);
          tbody.appendChild(tr);
        });
      }

    } catch (err) {
      console.error(err);
      const listEl = document.getElementById('fileList');
      if (listEl) listEl.innerHTML = '<div class="empty">Failed to load files.</div>';
    }
  }

  // handle upload with client-side validation and AJAX
  function initUpload() {
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    if (!form || !fileInput) return;

    fileInput.addEventListener('change', function () {
      const f = this.files && this.files[0];
      if (!f) return;
      const e = extOf(f.name);
      if (!allowedExt.includes(e)) {
        alert('File type not allowed. Allowed: pdf, doc, docx, xls, xlsx, csv, images');
        this.value = '';
      }
    });

    form.addEventListener('submit', async function (ev) {
      ev.preventDefault();
      const f = fileInput.files && fileInput.files[0];
      if (!f) { alert('Choose a file first'); return; }
      const e = extOf(f.name);
      if (!allowedExt.includes(e)) { alert('File type not allowed'); return; }

      const fd = new FormData();
      fd.append('file', f);

      try {
        const res = await fetch('../upload.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        if (res.status === 403) {
          alert('You must be logged in to upload files.');
          return;
        }
        // upload.php redirects back; regardless, refresh list
        fileInput.value = '';
        loadFiles();
      } catch (err) {
        console.error(err);
        alert('Upload failed');
      }
    });
  }

  // init when loaded into page
  function init() {
    initUpload();
    loadFiles();
  }

  // run init on DOM ready
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

  // also expose loadFiles so SPA loader can re-call if needed
  window.fmLoadFiles = loadFiles;
})();
