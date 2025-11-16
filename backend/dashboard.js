import { Chart } from "@/components/ui/chart"
// Profile dropdown toggle
const profileToggle = document.getElementById("profileToggle")
const profileDropdown = document.getElementById("profileDropdown")
const adminProfile = document.getElementById("adminProfile")

profileToggle.addEventListener("click", (e) => {
  e.stopPropagation()
  profileDropdown.classList.toggle("show")
  profileToggle.setAttribute("aria-expanded", profileDropdown.classList.contains("show"))
})

document.addEventListener("click", (e) => {
  if (!adminProfile.contains(e.target)) {
    profileDropdown.classList.remove("show")
    profileToggle.setAttribute("aria-expanded", "false")
  }
})

// Sidebar active state
function setActive(element) {
  const navItems = document.querySelectorAll(".nav-item")
  navItems.forEach((item) => item.classList.remove("active"))
  element.classList.add("active")
}

// Pie Chart - build from actual expense categories instead of hard-coded values
(async function buildPie() {
  const el = document.getElementById("pieChart");
  if (!el || !el.getContext) return;
  const ctx = el.getContext('2d');
  try {
    const res = await fetch('/get_expenses.php?t=' + Date.now(), { credentials: 'same-origin' });
    const json = await res.json();
    if (!json || !json.success || !Array.isArray(json.data)) return;
    const rows = json.data;
    const map = {};
    rows.forEach(r => {
      const cat = (r.category || 'Uncategorized').toString();
      const amt = parseFloat(r.amount) || 0;
      map[cat] = (map[cat] || 0) + amt;
    });
    const labels = Object.keys(map);
    const data = labels.map(l => map[l]);
    const palette = ['#FF6B6B','#FF9B9B','#FFE5E5','#4A3728','#8B6F47','#A58873','#C8B79F'];
    const backgroundColor = labels.map((_,i)=>palette[i%palette.length]);
    new Chart(ctx, { type: 'doughnut', data: { labels, datasets:[{ data, backgroundColor, borderWidth:2, borderColor:'#fff' }] }, options:{ responsive:true, maintainAspectRatio:true, plugins:{ legend:{ position:'bottom' } } } });
  } catch (e) {
    console.error('Failed to build pie chart', e);
  }
})();

// Bar Chart
const barCtx = document.getElementById("barChart").getContext("2d")
new Chart(barCtx, {
  type: "bar",
  data: {
    labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    datasets: [
      {
        label: "Sales",
        data: [400, 520, 480, 600, 550, 620, 700],
        backgroundColor: "#5DADE2",
        borderRadius: 5,
        borderSkipped: false,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: true,
        position: "top",
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        max: 800,
      },
    },
  },
})
