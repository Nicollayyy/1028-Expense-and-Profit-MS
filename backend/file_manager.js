document.addEventListener('DOMContentLoaded', function () {
  const uploadForm = document.getElementById('uploadForm');
  const fileInput = document.getElementById('fileInput');
  const fileListEl = document.getElementById('fileList');

  const allowedExt = ['pdf','doc','docx','xls','xlsx','csv','png','jpg','jpeg','gif','bmp','webp'];
  const imageExt = ['png','jpg','jpeg','gif','bmp','webp'];

  function extOf(name) {
    return (name || '').split('.').pop().toLowerCase();
  }

  function categoryForExt(ext) {
    if (imageExt.includes(ext)) return 'Images';
    if (['xls','xlsx','csv'].includes(ext)) return 'Excel';
    if (ext === 'pdf') return 'PDFs';
    if (['doc','docx'].includes(ext)) return 'Docs';
    return 'Other';
  }

  // build file URL relative to this html folder
  function buildFileUrl(rel) {
    if (!rel) return rel;
    // list_files.php returns paths like 'uploads/filename'
    if (rel.startsWith('http') || rel.startsWith('/')) return rel;
    // If current page is under /html/ we need to go up one level
    if (window.location.pathname.indexOf('/html/') !== -1) return '../' + rel;
    return rel;
  }

  // fetch files and render
  async function loadFiles() {
    fileListEl.innerHTML = '<div class="empty">Loading files…</div>';
    try {
      const res = await fetch('../list_files.php', { cache: 'no-store' });
      if (!res.ok) {
        if (res.status === 403) fileListEl.innerHTML = '<div class="empty">Please login to view uploaded files.</div>';
        else fileListEl.innerHTML = '<div class="empty">Error loading files</div>';
        return;
      }
      const files = await res.json();
      renderFilesUI(files || []);
    } catch (err) {
      fileListEl.innerHTML = '<div class="empty">Unable to load files</div>';
      // eslint-disable-next-line no-console
      console.error(err);
    }
  }

  function renderFilesUI(files) {
    // group counts per category
    const groups = {};
    files.forEach(f => {
      const e = extOf(f.name);
      const cat = categoryForExt(e);
      groups[cat] = groups[cat] || [];
      groups[cat].push(f);
    });

    // folder cards
    const folderContainer = document.createElement('div');
    folderContainer.className = 'folders';

    const order = ['All','Images','Excel','PDFs','Docs','Other'];
    order.forEach((k) => {
      const count = k === 'All' ? files.length : (groups[k] ? groups[k].length : 0);
      const card = document.createElement('div');
      card.className = 'folder-card' + (k === 'All' ? ' active' : '');
      card.setAttribute('data-cat', k.toLowerCase());
      card.innerHTML = `<div class="f-icon"><i class="fas fa-folder-open"></i></div><div class="f-body"><div class="f-title">${k}</div><div class="f-count">${count} file${count===1?'':'s'}</div></div>`;
      card.addEventListener('click', function () {
        // toggle active
        document.querySelectorAll('.folder-card').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        const cat = this.getAttribute('data-cat');
        filterFiles(cat === 'all' ? null : cat);
      });
      folderContainer.appendChild(card);
    });

    // files grid
    const grid = document.createElement('div');
    grid.className = 'files-grid';

    files.forEach(f => {
      const e = extOf(f.name);
      const cat = categoryForExt(e);
      const card = document.createElement('div');
      card.className = 'file-card';
      card.setAttribute('data-cat', cat.toLowerCase());
      const fileUrl = buildFileUrl(f.url);

      let thumb = '';
      if (imageExt.includes(e)) {
        thumb = `<div class="file-thumb"><img src="${fileUrl}" alt="${f.name}" loading="lazy"/></div>`;
      } else {
        // icon placeholder
        thumb = `<div class="file-thumb"><i class="fas fa-file fa-3x" style="color:var(--brown-700)"></i></div>`;
      }

      const sizeKb = Math.round((f.size||0)/1024);
      card.innerHTML = `${thumb}<div class="file-name">${f.name}</div><div class="file-meta">${cat} · ${sizeKb} KB</div><div class="file-actions"><a class="btn-download" href="${fileUrl}" target="_blank" rel="noopener">Download</a><button class="btn-delete" data-name="${encodeURIComponent(f.name)}">Delete</button></div>`;

      grid.appendChild(card);
    });

    fileListEl.innerHTML = '';
    fileListEl.appendChild(folderContainer);
    fileListEl.appendChild(grid);

    // wire delete buttons
    fileListEl.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', async function () {
        const raw = decodeURIComponent(this.getAttribute('data-name'));
        if (!confirm('Delete file "' + raw + '"?')) return;
        try {
          const fd = new FormData();
          fd.append('name', raw);
          const res = await fetch('../delete_file.php', { method: 'POST', body: fd });
          if (!res.ok) throw new Error('delete failed');
          const j = await res.json();
          if (j.ok) loadFiles();
        } catch (err) {
          // eslint-disable-next-line no-console
          console.error(err);
          alert('Unable to delete file');
        }
      });
    });
  }

  function filterFiles(cat) {
    const cards = fileListEl.querySelectorAll('.files-grid .file-card');
    cards.forEach(c => {
      if (!cat) c.style.display = '';
      else {
        c.style.display = (c.getAttribute('data-cat') === cat) ? '' : 'none';
      }
    });
  }

  // client-side validation on upload
  if (uploadForm && fileInput) {
    uploadForm.addEventListener('submit', function (e) {
      const f = fileInput.files && fileInput.files[0];
      if (!f) { e.preventDefault(); alert('Please pick a file to upload'); return; }
      const name = f.name || '';
      const ext = extOf(name);
      if (!allowedExt.includes(ext)) {
        e.preventDefault();
        alert('Unsupported file type. Allowed: pdf, doc, docx, xls, xlsx, csv, images');
        return;
      }
      // optional: size limit 10MB
      const max = 10 * 1024 * 1024;
      if (f.size > max) {
        e.preventDefault();
        alert('File too large. Max 10MB');
        return;
      }
      // allow submit to proceed
    });
  }

  // initial load
  loadFiles();
});
