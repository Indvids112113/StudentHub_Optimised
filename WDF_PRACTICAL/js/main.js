document.addEventListener("DOMContentLoaded", function () {
    initTheme();
    initRoleAccess();
    renderDynamicUserData();
    highlightActivePage();
    initHamburgerMenu();
    initNotificationBanner();
    initModal();
    initSlider();
    initDashboardTabs();
    initFormValidation();
    
    if (document.getElementById("eventsContainer")) initEventsPage();
    if (document.getElementById("studentsTableBody")) initAdminPage();
    if (document.getElementById("faqContainer")) initFAQPage();
});

/* ==========================================
   UTILITY: CSV PARSER
========================================== */
async function fetchAndParseCSV(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) return [];
        const text = await response.text();
        const lines = text.trim().split('\n');
        const headers = lines[0].split(',').map(h => h.trim());
        const result = [];
        
        for (let i = 1; i < lines.length; i++) {
            const currentline = lines[i].split(',');
            if (currentline.length === headers.length) {
                const obj = {};
                for (let j = 0; j < headers.length; j++) {
                    obj[headers[j]] = currentline[j].trim();
                }
                result.push(obj);
            }
        }
        return result;
    } catch (error) {
        console.error("Error reading CSV:", error);
        return [];
    }
}

/* ==========================================
   DYNAMIC USER DATA RENDERING
========================================== */
function renderDynamicUserData() {
    const currentUser = JSON.parse(localStorage.getItem("studenthub_current_user"));
    if (!currentUser) return;

    const profileName = document.getElementById("profileName");
    const profileId = document.getElementById("profileId");
    const profileMajor = document.getElementById("profileMajor");
    
    if (profileName) profileName.textContent = currentUser.name;
    if (profileId) profileId.textContent = currentUser.id || currentUser.studentId;
    if (profileMajor) profileMajor.textContent = currentUser.course + " (Year " + currentUser.year + ")";

    const dashboardWelcome = document.getElementById("dashboardWelcome");
    if (dashboardWelcome) {
        dashboardWelcome.textContent = "Welcome, " + currentUser.name;
    }
}

/* ==========================================
   AUTHENTICATION & ROLE LOGIC
========================================== */
function initRoleAccess() {
    const currentUserRole = localStorage.getItem("studenthub_role") || "guest";
    const authBadge = document.getElementById("authBadge");
    const logoutBtn = document.getElementById("logoutBtn");

    if (authBadge) authBadge.textContent = "Role: " + currentUserRole.toUpperCase();

    const studentLinks = document.querySelectorAll(".student-only");
    const adminLinks = document.querySelectorAll(".admin-only");
    const guestLinks = document.querySelectorAll(".guest-only");

    if (currentUserRole === "student") {
        studentLinks.forEach(el => el.style.display = "");
        adminLinks.forEach(el => el.style.display = "none");
        guestLinks.forEach(el => el.style.display = "none");
        if (logoutBtn) logoutBtn.style.display = "inline-block";
    } else if (currentUserRole === "admin") {
        studentLinks.forEach(el => el.style.display = "none");
        adminLinks.forEach(el => el.style.display = "");
        guestLinks.forEach(el => el.style.display = "none");
        if (logoutBtn) logoutBtn.style.display = "inline-block";
    } else {
        studentLinks.forEach(el => el.style.display = "none");
        adminLinks.forEach(el => el.style.display = "none");
        guestLinks.forEach(el => el.style.display = "");
        if (logoutBtn) logoutBtn.style.display = "none";
    }

    const currentPath = window.location.pathname.toLowerCase();
    if (currentPath.includes("dashboard.html") && currentUserRole !== "student") {
        window.location.href = "login.php";
    }
    if (currentPath.includes("admin.html") && currentUserRole !== "admin") {
        window.location.href = "login.php";
    }

    if (logoutBtn) {
        logoutBtn.addEventListener("click", function () {
            localStorage.removeItem("studenthub_role");
            localStorage.removeItem("studenthub_current_user");
            window.location.href = "login.php";
        });
    }
}

/* ==========================================
   UI & COMPONENT LOGIC
========================================== */
function initTheme() {
    const savedTheme = localStorage.getItem("studenthub_theme") || "light";
    if (savedTheme === "dark") document.body.classList.add("dark-mode");
    const toggleBtn = document.getElementById("themeToggleBtn");
    if (toggleBtn) {
        toggleBtn.textContent = savedTheme === "dark" ? "☀️ Light" : "🌙 Dark";
        toggleBtn.addEventListener("click", function () {
            document.body.classList.toggle("dark-mode");
            const isDark = document.body.classList.contains("dark-mode");
            localStorage.setItem("studenthub_theme", isDark ? "dark" : "light");
            toggleBtn.textContent = isDark ? "☀️ Light" : "🌙 Dark";
        });
    }
}

function highlightActivePage() {
    const currentPath = window.location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll("nav a").forEach(link => {
        if (link.getAttribute("href") === currentPath) link.classList.add("active");
        else link.classList.remove("active");
    });
}

function initHamburgerMenu() {
    const hamburgerBtn = document.getElementById("hamburgerBtn");
    const navMenu = document.getElementById("navMenu");
    if (hamburgerBtn && navMenu) {
        hamburgerBtn.addEventListener("click", () => navMenu.classList.toggle("nav-active"));
    }
}

function initNotificationBanner() {
    const banner = document.getElementById("notificationBanner");
    const closeBtn = document.getElementById("closeBannerBtn");
    if (banner && closeBtn) closeBtn.addEventListener("click", () => banner.style.display = "none");
}

function initModal() {
    const openModalBtn = document.getElementById("openModalBtn");
    const modal = document.getElementById("infoModal");
    if (openModalBtn && modal) openModalBtn.addEventListener("click", () => modal.classList.add("open"));
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal || e.target.id === "closeModalBtn") modal.classList.remove("open");
        });
    }
}

function initSlider() {
    const slides = document.querySelectorAll(".slide");
    let currentSlide = 0;
    if (slides.length > 0) {
        const showSlide = (i) => {
            slides.forEach(s => s.classList.remove("active-slide"));
            slides[i].classList.add("active-slide");
        };
        document.getElementById("prevSlide").addEventListener("click", () => {
            currentSlide = (currentSlide === 0) ? slides.length - 1 : currentSlide - 1;
            showSlide(currentSlide);
        });
        document.getElementById("nextSlide").addEventListener("click", () => {
            currentSlide = (currentSlide === slides.length - 1) ? 0 : currentSlide + 1;
            showSlide(currentSlide);
        });
    }
}

function initDashboardTabs() {
    const sidebarLinks = document.querySelectorAll(".sidebar-link");
    const sections = document.querySelectorAll(".dashboard-section");
    if (sidebarLinks.length === 0 || sections.length === 0) return;

    sidebarLinks.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const targetSection = document.getElementById(this.getAttribute("href").substring(1));
            if (!targetSection) return;
            const isVisible = targetSection.style.display === "block";
            sections.forEach(sec => sec.style.display = "none");
            sidebarLinks.forEach(l => l.style.borderLeftColor = "transparent");
            if (!isVisible) {
                targetSection.style.display = "block";
                this.style.borderLeftColor = "var(--primary-color)";
            }
        });
    });
}

function initFormValidation() {
    const form = document.getElementById("registerForm");
    if (!form) return;

    const pw = document.getElementById("password");
    if (pw) {
        pw.addEventListener("input", () => {
            const val = pw.value;
            let str = 0;
            if (val.length >= 8) str++;
            if (/[A-Z]/.test(val)) str++;
            if (/[0-9]/.test(val)) str++;
            if (/[^a-zA-Z0-9]/.test(val)) str++;
            
            const bar = document.getElementById("strengthBar");
            const txt = document.getElementById("strengthText");
            if(val.length === 0) { bar.style.width="0%"; txt.textContent=""; }
            else if(str <= 1) { bar.style.width="33%"; bar.style.backgroundColor="#ef4444"; txt.textContent="Weak"; txt.style.color="#ef4444"; }
            else if(str <= 3) { bar.style.width="66%"; bar.style.backgroundColor="#f59e0b"; txt.textContent="Medium"; txt.style.color="#f59e0b"; }
            else { bar.style.width="100%"; bar.style.backgroundColor="#10b981"; txt.textContent="Strong"; txt.style.color="#10b981"; }
        });
    }

    form.addEventListener("submit", (e) => {
        let isValid = true;
        const err = (id, msg) => { document.getElementById(id+"Error").textContent = msg; isValid = false; };
        form.querySelectorAll(".error-msg").forEach(s => s.textContent = "");

        const name = document.getElementById("fullName").value.trim();
        const studentId = document.getElementById("studentId").value.trim();
        const email = document.getElementById("email").value.trim();
        const pass = document.getElementById("password").value;
        const course = document.getElementById("course").value;
        const year = document.getElementById("year").value;

        if (!/^[a-zA-Z\s]{3,}$/.test(name)) err("fullName", "Invalid name.");
        if (studentId === "") err("studentId", "Required.");
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) err("email", "Invalid email.");
        if (!/^[0-9]{10}$/.test(document.getElementById("mobile").value.trim())) err("mobile", "10-digit number required.");
        if (!document.querySelector('input[name="gender"]:checked')) err("gender", "Select gender.");
        if (course === "") err("course", "Select course.");
        if (year === "") err("year", "Select year.");
        if (pass.length < 8) err("password", "Min 8 chars.");
        if (pass !== document.getElementById("confirmPassword").value) err("confirmPassword", "Mismatch.");
        if (!document.getElementById("terms").checked) err("terms", "Must accept.");

        if (!isValid) {
            e.preventDefault(); 
        }
    });
}

/* ==========================================
   FETCH API & DATA LOGIC
========================================== */
async function fetchData(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error("Network response was not ok.");
        return await response.json();
    } catch (error) {
        console.error("Fetch error:", error);
        return null;
    }
}

function renderPagination(totalItems, itemsPerPage, currentPage, containerId, callback) {
    const container = document.getElementById(containerId);
    container.innerHTML = "";
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.textContent = i;
        btn.className = `page-btn ${i === currentPage ? "active-page" : ""}`;
        btn.addEventListener("click", () => callback(i));
        container.appendChild(btn);
    }
}

/* --- Events Page Logic --- */
async function initEventsPage() {
    const container = document.getElementById("eventsContainer");
    const loader = document.getElementById("eventsLoader");
    const errorMsg = document.getElementById("eventsError");
    
    let allEvents = [];
    let currentPage = 1;
    const itemsPerPage = 6;

    const data = await fetchAndParseCSV("data/events.csv");
    loader.style.display = "none";
    
    if (!data || data.length === 0) {
        errorMsg.style.display = "block";
        errorMsg.textContent = "Failed to load events. Please try again later.";
        return;
    }
    
    allEvents = data;
    renderEvents();

    document.getElementById("searchEvents").addEventListener("input", () => { currentPage = 1; renderEvents(); });
    document.getElementById("filterCategoryEvents").addEventListener("change", () => { currentPage = 1; renderEvents(); });
    document.getElementById("sortEvents").addEventListener("change", () => { currentPage = 1; renderEvents(); });

    function renderEvents() {
        const searchTerm = document.getElementById("searchEvents").value.toLowerCase();
        const filterCat = document.getElementById("filterCategoryEvents").value;
        const sortOpt = document.getElementById("sortEvents").value;

        let filtered = allEvents.filter(ev => {
            const title = ev.title ? ev.title.toLowerCase() : '';
            const desc = ev.description ? ev.description.toLowerCase() : '';
            const matchesSearch = title.includes(searchTerm) || desc.includes(searchTerm);
            const matchesCat = (filterCat === "All") || (ev.category === filterCat);
            return matchesSearch && matchesCat;
        });

        filtered.sort((a, b) => {
            if (sortOpt === "dateAsc") return new Date(a.date) - new Date(b.date);
            if (sortOpt === "dateDesc") return new Date(b.date) - new Date(a.date);
            if (sortOpt === "titleAsc" && a.title && b.title) return a.title.localeCompare(b.title);
        });

        const start = (currentPage - 1) * itemsPerPage;
        const paginated = filtered.slice(start, start + itemsPerPage);

        container.innerHTML = "";
        if (paginated.length === 0) {
            container.innerHTML = `<div class="empty-state">No events found matching your criteria.</div>`;
        } else {
            paginated.forEach(ev => {
                const article = document.createElement("article");
                article.innerHTML = `
                    <h3>${ev.title}</h3>
                    <p><strong>Date:</strong> ${ev.date}</p>
                    <p><strong>Location:</strong> ${ev.location}</p>
                    <p><strong>Category:</strong> <span class="auth-badge" style="color:var(--text-dark); border:1px solid #ccc;">${ev.category}</span></p>
                    <p style="margin-top: 1rem;">${ev.description}</p>
                `;
                container.appendChild(article);
            });
        }
        
        renderPagination(filtered.length, itemsPerPage, currentPage, "eventsPagination", (newPage) => {
            currentPage = newPage;
            renderEvents();
        });
    }
}

/* --- Admin Page Logic --- */
async function initAdminPage() {
    const tbody = document.getElementById("studentsTableBody");
    const table = document.getElementById("studentsTable");
    const loader = document.getElementById("studentsLoader");
    
    let allStudents = [];
    let currentPage = 1;
    const itemsPerPage = 5;

    const data = await fetchData("data/students.json");
    loader.style.display = "none";
    table.style.display = "table";
    
    if (!data) return;
    allStudents = data;
    renderStudents();

    document.getElementById("searchStudents").addEventListener("input", () => { currentPage = 1; renderStudents(); });
    document.getElementById("filterStatusStudents").addEventListener("change", () => { currentPage = 1; renderStudents(); });

    function renderStudents() {
        const search = document.getElementById("searchStudents").value.toLowerCase();
        const filter = document.getElementById("filterStatusStudents").value;

        let filtered = allStudents.filter(st => {
            const matchesSearch = st.name.toLowerCase().includes(search) || st.id.toLowerCase().includes(search);
            const matchesFilter = (filter === "All") || (st.status === filter);
            return matchesSearch && matchesFilter;
        });

        const start = (currentPage - 1) * itemsPerPage;
        const paginated = filtered.slice(start, start + itemsPerPage);

        tbody.innerHTML = "";
        paginated.forEach(st => {
            const tr = document.createElement("tr");
            const statusClass = st.status === "Active" ? "status-active" : (st.status === "Pending" ? "status-pending" : "status-suspended");
            tr.innerHTML = `
                <td>${st.id}</td>
                <td>${st.name}</td>
                <td>${st.course}</td>
                <td>Year ${st.year}</td>
                <td><span class="status-badge ${statusClass}">${st.status}</span></td>
            `;
            tbody.appendChild(tr);
        });

        renderPagination(filtered.length, itemsPerPage, currentPage, "studentsPagination", (newPage) => {
            currentPage = newPage;
            renderStudents();
        });
    }
}

/* --- FAQ Page Logic --- */
async function initFAQPage() {
    const container = document.getElementById("faqContainer");
    const loader = document.getElementById("faqLoader");
    
    let allFaqs = [];
    const data = await fetchData("data/faqs.json");
    loader.style.display = "none";
    
    if (!data) return;
    allFaqs = data;
    renderFaqs();

    document.getElementById("searchFaq").addEventListener("input", renderFaqs);
    document.getElementById("filterCategoryFaq").addEventListener("change", renderFaqs);

    function renderFaqs() {
        const search = document.getElementById("searchFaq").value.toLowerCase();
        const filter = document.getElementById("filterCategoryFaq").value;

        let filtered = allFaqs.filter(f => {
            const matchesSearch = f.question.toLowerCase().includes(search) || f.answer.toLowerCase().includes(search);
            const matchesCat = (filter === "All") || (f.category === filter);
            return matchesSearch && matchesCat;
        });

        container.innerHTML = "";
        if(filtered.length === 0) {
            container.innerHTML = `<div class="empty-state">No FAQs found.</div>`;
            return;
        }

        filtered.forEach(f => {
            const item = document.createElement("div");
            item.className = "accordion-item";
            item.innerHTML = `
                <button class="accordion-header">
                    <span>${f.question} <small style="color:var(--secondary-color); font-weight:normal;">(${f.category})</small></span>
                    <span>&#9662;</span>
                </button>
                <div class="accordion-body">
                    <p>${f.answer}</p>
                </div>
            `;
            container.appendChild(item);
        });

        const headers = document.querySelectorAll(".accordion-header");
        headers.forEach(header => {
            header.addEventListener("click", function () {
                const body = this.nextElementSibling;
                const isOpen = body.classList.contains("open");
                document.querySelectorAll(".accordion-body").forEach(b => b.classList.remove("open"));
                if (!isOpen) body.classList.add("open");
            });
        });
    }
}