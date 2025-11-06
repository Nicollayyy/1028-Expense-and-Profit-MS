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

    // Additional analytics charts (if present)
    // Sales: Daily (last 7 days)
    const salesDailyEl = document.getElementById('salesDailyChart');
    if (salesDailyEl && salesDailyEl.getContext) {
      const ctx = salesDailyEl.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Day -6','Day -5','Day -4','Day -3','Day -2','Yesterday','Today'],
          datasets: [
            { label: 'Sales (₱)', data: [420, 380, 500, 460, 520, 610, 700], borderColor: '#7C4A3E', backgroundColor: 'rgba(124,74,62,0.08)', tension: 0.3, fill: true }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, labels: { color: '#302014' } } }, scales: { y: { beginAtZero: true } }, animation: { duration: 800 } }
      });
    }

    // Sales: Weekly (last 8 weeks)
    const salesWeeklyEl = document.getElementById('salesWeeklyChart');
    if (salesWeeklyEl && salesWeeklyEl.getContext) {
      const ctx = salesWeeklyEl.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Wk1','Wk2','Wk3','Wk4','Wk5','Wk6','Wk7','Wk8'],
          datasets: [
            { label: 'Sales (₱)', data: [2800, 3000, 3200, 3100, 3500, 3600, 3800, 4000], borderColor: '#A56A55', backgroundColor: 'rgba(165,106,85,0.06)', tension: 0.3, fill: true }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, labels: { color: '#302014' } } }, scales: { y: { beginAtZero: true } }, animation: { duration: 800 } }
      });
    }

    // Sales: Monthly (last 12 months)
    const salesMonthlyEl = document.getElementById('salesMonthlyChart');
    if (salesMonthlyEl && salesMonthlyEl.getContext) {
      const ctx = salesMonthlyEl.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
          datasets: [
            { label: 'Sales (₱)', data: [12000, 13500, 14200, 15000, 16200, 15800, 17000, 17500, 16800, 18000, 19000, 20500], borderColor: '#8B6C58', backgroundColor: 'rgba(139,108,88,0.06)', tension: 0.25, fill: true }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, labels: { color: '#302014' } } }, scales: { y: { beginAtZero: true } }, animation: { duration: 900 } }
      });
    }

    // Sales: Yearly (last 5 years)
    const salesYearlyEl = document.getElementById('salesYearlyChart');
    if (salesYearlyEl && salesYearlyEl.getContext) {
      const ctx = salesYearlyEl.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['2021','2022','2023','2024','2025'],
          datasets: [
            { label: 'Sales (₱)', data: [120000, 135000, 148000, 165000, 190000], borderColor: '#4A3728', backgroundColor: 'rgba(74,55,40,0.06)', tension: 0.25, fill: true }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, labels: { color: '#302014' } } }, scales: { y: { beginAtZero: true } }, animation: { duration: 900 } }
      });
    }

    // Expenses: Monthly Comparison (12 months)
    const expensesMonthlyEl = document.getElementById('expensesMonthlyChart');
    if (expensesMonthlyEl && expensesMonthlyEl.getContext) {
      const ctx = expensesMonthlyEl.getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
          datasets: [
            { label: 'Expenses (₱)', data: [4000, 3500, 4200, 3800, 3900, 4100, 4500, 4300, 4400, 4600, 4700, 4900], backgroundColor: '#C8B79F' }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { mode: 'index' } }, scales: { y: { beginAtZero: true } }, animation: { duration: 900 } }
      });
    }

    // Bar
    const barEl = document.getElementById('barChart');
    if (barEl && barEl.getContext) {
      const barCtx = barEl.getContext('2d');
      // eslint-disable-next-line no-undef
      new Chart(barCtx, {
        type: 'bar',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [
            {
              label: 'Sales',
              data: [400, 520, 480, 600, 550, 620, 700],
              /* single brown tone for bars */
              backgroundColor: '#7C4A3E',
              borderRadius: 5,
              borderSkipped: false
            }
          ]
        },
        options: {
          // keep charts animated and responsive
          responsive: true,
          maintainAspectRatio: true,
          animation: { duration: 1000 },
          plugins: { legend: { display: true, position: 'top', labels: { color: '#302014' } } },
          scales: { y: { beginAtZero: true, max: 800 } }
        }
      });
    }
  } else {
    // Chart.js not loaded — log a helpful message for debugging
    // (visible in the browser console)
    // eslint-disable-next-line no-console
    console.warn('Chart.js not detected: charts will not render. Make sure Chart.js is included before script.js');
  }
});

