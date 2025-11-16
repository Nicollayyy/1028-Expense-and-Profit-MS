document.addEventListener('DOMContentLoaded', function () {
  // Profile dropdown toggle
  const profileToggle = document.getElementById('profileToggle');
  const profileDropdown = document.getElementById('profileDropdown');
  const adminProfile = document.getElementById('adminProfile');

  if (profileToggle && profileDropdown && adminProfile) {
    profileToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      profileDropdown.classList.toggle('show');
      profileToggle.setAttribute('aria-expanded', profileDropdown.classList.contains('show'));
    });

    document.addEventListener('click', function (e) {
      if (!adminProfile.contains(e.target)) {
        profileDropdown.classList.remove('show');
        profileToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Sidebar active state: mark active item from URL to avoid changing on click
  (function markActiveByUrl() {
    const navItems = document.querySelectorAll('.sidebar .nav-item');
    if (!navItems.length) return;
  // Use filename without extension for matching so links to profit.php
  // match profit.html and vice-versa.
  const currentFileFull = window.location.pathname.split('/').pop().toLowerCase();
  const currentFile = currentFileFull.replace(/\.[^.]+$/, '');
    navItems.forEach((item) => {
      const href = item.getAttribute('href') || '';
      if (!href || href === '#' || href.startsWith('javascript:')) {
        item.classList.remove('active');
        return;
      }
      try {
        const resolved = new URL(href, window.location.href);
  const targetFull = resolved.pathname.split('/').pop().toLowerCase();
  const target = targetFull.replace(/\.[^.]+$/, '');
  if (target === currentFile) item.classList.add('active'); else item.classList.remove('active');
      } catch (e) {
        // ignore malformed hrefs
      }
    });
  })();

  // No-op setActive for compatibility with any leftover inline handlers
  window.setActive = function () { /* noop: active state is set from URL */ };

  // Charts: only initialize when Chart is available and canvas exists
  if (typeof Chart !== 'undefined') {
    // Pie / Doughnut
    const pieEl = document.getElementById('pieChart');
    if (pieEl && pieEl.getContext) {
      const pieCtx = pieEl.getContext('2d');
      // eslint-disable-next-line no-undef
      new Chart(pieCtx, {
        type: 'doughnut',
        data: {
          labels: ['Inventory', 'Utilities', 'Salaries', 'Marketing', 'Other'],
          datasets: [
            {
              data: [30, 20, 25, 15, 10],
              /* brown-based palette for the doughnut slices */
              backgroundColor: ['#4A3728', '#6B4C3B', '#8B6C58', '#A58873', '#C8B79F'],
              borderWidth: 2,
              borderColor: '#fff'
            }
          ]
        },
        options: {
          // keep charts animated and responsive
          responsive: true,
          maintainAspectRatio: true,
          animation: { duration: 1000 },
          plugins: {
            legend: { position: 'bottom', labels: { color: '#302014' } }
          }
        }
      });
    }

    // Additional analytics charts (if present) - fetch data from backend
    async function fetchJson(url) {
      try {
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Network response was not ok');
        return await res.json();
      } catch (e) {
        console.error('Fetch error', url, e);
        return null;
      }
    }

    (async function () {
      const analytics = await fetchJson('/get_analytics.php');
      if (!analytics || !analytics.success) return;
      const data = analytics.data || {};

      // helper to create chart only when element exists
      function makeLineChart(elId, labels, values, color, opts = {}) {
        const el = document.getElementById(elId);
        if (!el || !el.getContext) return null;
        const ctx = el.getContext('2d');
        return new Chart(ctx, Object.assign({
          type: 'line',
          data: { labels: labels, datasets: [{ label: 'Sales (₱)', data: values, borderColor: color, backgroundColor: 'rgba(124,74,62,0.08)', tension: 0.3, fill: true }] },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, labels: { color: '#302014' } } }, scales: { y: { beginAtZero: true } }, animation: { duration: 800 } }
        }, opts));
      }

      function makeBarChart(elId, labels, values, color, opts = {}) {
        const el = document.getElementById(elId);
        if (!el || !el.getContext) return null;
        const ctx = el.getContext('2d');
        return new Chart(ctx, Object.assign({
          type: 'bar',
          data: { labels: labels, datasets: [{ label: 'Expenses (₱)', data: values, backgroundColor: color }] },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { mode: 'index' } }, scales: { y: { beginAtZero: true } }, animation: { duration: 900 } }
        }, opts));
      }

      // Daily
      if (data.daily) makeLineChart('salesDailyChart', data.daily.labels, data.daily.data, '#7C4A3E');
      // Weekly
      if (data.weekly) makeLineChart('salesWeeklyChart', data.weekly.labels, data.weekly.data, '#A56A55');
      // Monthly
      if (data.monthly) {
        // convert YYYY-MM to short month names for display
        const mLabels = data.monthly.labels.map(l => {
          const parts = l.split('-');
          if (parts.length === 2) return new Date(parts[0], parseInt(parts[1],10)-1).toLocaleString('en-US', { month: 'short' });
          return l;
        });
        makeLineChart('salesMonthlyChart', mLabels, data.monthly.data, '#8B6C58');
        // expenses monthly
        if (data.expenses_monthly) {
          const eLabels = data.expenses_monthly.labels.map(l => {
            const parts = l.split('-');
            if (parts.length === 2) return new Date(parts[0], parseInt(parts[1],10)-1).toLocaleString('en-US', { month: 'short' });
            return l;
          });
          makeBarChart('expensesMonthlyChart', eLabels, data.expenses_monthly.data, '#C8B79F');
        }
      }
      // Yearly
      if (data.yearly) makeLineChart('salesYearlyChart', data.yearly.labels, data.yearly.data, '#4A3728');

      // Bar chart (weekly days) - use last 7 daily values and show weekday labels
      if (data.daily && data.daily.labels && data.daily.data) {
        const labels = data.daily.labels.map(d => new Date(d).toLocaleString('en-US', { weekday: 'short' }));
        makeBarChart('barChart', labels, data.daily.data, '#7C4A3E', { options: { scales: { y: { beginAtZero: true } } } });
      }
    })();
  } else {
    // Chart.js not loaded — log a helpful message for debugging
    // (visible in the browser console)
    // eslint-disable-next-line no-console
    console.warn('Chart.js not detected: charts will not render. Make sure Chart.js is included before script.js');
  }
});
