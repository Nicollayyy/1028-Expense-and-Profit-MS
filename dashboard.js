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

// Pie Chart
const pieCtx = document.getElementById("pieChart").getContext("2d")
new Chart(pieCtx, {
  type: "doughnut",
  data: {
    labels: ["Inventory", "Utilities", "Salaries", "Marketing", "Other"],
    datasets: [
      {
        data: [30, 20, 25, 15, 10],
        backgroundColor: ["#FF6B6B", "#FF9B9B", "#FFE5E5", "#4A3728", "#8B6F47"],
        borderWidth: 2,
        borderColor: "#fff",
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        position: "bottom",
      },
    },
  },
})

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
