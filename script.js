// script.js - Complete Frontend Logic
const API_BASE_URL = 'http://localhost/SWC3633-Project'; // Update this if your folder name is different
const API_KEY = 'RahasiaSangatAman123!';
let currentUser = null; 

function showMessage(msg, isSuccess = true) {
    const box = document.getElementById('messageBox');
    box.textContent = msg;
    box.className = `alert ${isSuccess ? 'success' : 'error'}`;
    setTimeout(() => { box.className = 'alert'; }, 4000); // Reverts display block to none
}

async function apiFetch(endpoint, method = 'GET', body = null) {
    const headers = {
        'Content-Type': 'application/json',
        'X-API-KEY': API_KEY
    };
    const options = { method, headers };
    if (body) options.body = JSON.stringify(body);

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'API request failed');
        return data;
    } catch (error) {
        showMessage(error.message, false);
        return null;
    }
}

// --- UI & AUTH LOGIC ---

function switchTab(event, tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.sidebar li').forEach(li => li.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    } else if (event) {
        const targetElement = document.getElementById(event);
        if (targetElement) targetElement.classList.add('active');
    }
}

function buildSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarList = document.getElementById('sidebarList');
    sidebar.style.display = 'flex'; 

    if (currentUser.role.toLowerCase() === 'student') {
        sidebarList.innerHTML = `
            <li id="nav-student-exams" onclick="switchTab(event, 'student-exams')">My Exams</li>
            <li onclick="switchTab(event, 'student-results')">My Results</li>
            <li onclick="switchTab(event, 'student-slip')">Entrance Slip</li>
        `;
        switchTab({currentTarget: document.getElementById('nav-student-exams')}, 'student-exams');
    } else {
        sidebarList.innerHTML = `
            <li id="nav-admin-dashboard" onclick="switchTab(event, 'admin-dashboard')">Dashboard</li>
            <li onclick="switchTab(event, 'admin-courses')">Courses</li>
            <li onclick="switchTab(event, 'admin-exams')">Exams</li>
            <li onclick="switchTab(event, 'admin-results')">Results</li>
            <li onclick="switchTab(event, 'admin-students')">Students</li>
        `;
        switchTab({currentTarget: document.getElementById('nav-admin-dashboard')}, 'admin-dashboard');
    }
}

function checkAuth() {
    const savedUser = localStorage.getItem('examUser');
    if (savedUser) {
        currentUser = JSON.parse(savedUser);
        loadDashboard();
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const payload = { username: document.getElementById('login_username').value, password: document.getElementById('login_password').value };
    const response = await apiFetch('/login.php', 'POST', payload);
    
    if (response && response.success) {
        currentUser = response.user;
        localStorage.setItem('examUser', JSON.stringify(currentUser));
        document.getElementById('login_username').value = '';
        document.getElementById('login_password').value = '';
        showMessage(response.message);
        loadDashboard();
    }
}

function logout() {
    localStorage.removeItem('examUser');
    currentUser = null;
    
    // Hide all tabs and user info
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('userInfo').style.display = 'none';
    
    // Show the login screen again
    document.getElementById('loginView').style.display = 'block';
    document.getElementById('loginView').classList.add('active');
    
    showMessage("Logged out successfully.");
}

function loadDashboard() {
    // Hide the login screen
    document.getElementById('loginView').style.display = 'none';
    document.getElementById('loginView').classList.remove('active');
    
    // Show the dashboard UI
    document.getElementById('userInfo').style.display = 'flex';
    document.getElementById('welcomeText').innerHTML = `Welcome, <strong>${currentUser.username}</strong> (${currentUser.role})`;
    buildSidebar();

    if (currentUser.role.toLowerCase() === 'student') {
        loadExamsStudent();
        loadResultsStudent();
    } else {
        loadAdminDashboardStats();
        loadExamsAdmin();
        loadCoursesAdmin();
        loadStudentsAdmin();
        loadResultsAdmin(); 
    }
}
// ==========================================
// ADMIN: DASHBOARD STATS
// ==========================================
async function loadAdminDashboardStats() {
    const [students, courses, exams] = await Promise.all([
        apiFetch('/users.php?role=student'),
        apiFetch('/courses.php'),
        apiFetch('/examinations.php')
    ]);

    if (students && students.success) document.getElementById('dash-student-count').innerText = students.data.length;
    if (courses && courses.success) document.getElementById('dash-course-count').innerText = courses.data.length;
    if (exams && exams.success) document.getElementById('dash-exam-count').innerText = exams.data.length;
}

// ==========================================
// ADMIN: COURSES
// ==========================================
async function loadCoursesAdmin() {
    const response = await apiFetch('/courses.php');
    const tbody = document.getElementById('adminCoursesTable');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (response && response.success) {
        response.data.forEach(course => {
            const credits = course.credits || 3;
            tbody.innerHTML += `
                <tr>
                    <td><strong>${course.course_code}</strong></td>
                    <td>${course.course_name}</td>
                    <td>${credits}</td>
                    <td>
                        <button class="btn-edit" onclick="editCourse('${course.course_code}', '${course.course_name}', ${credits})">Edit</button>
                        <button class="btn-delete" onclick="deleteCourse('${course.course_code}')">Delete</button>
                    </td>
                </tr>`;
        });
    }
}

function editCourse(code, name, credits) {
    document.getElementById('course_id_hidden').value = code; // Using the hidden ID!
    document.getElementById('manage_course_code').value = code;
    document.getElementById('manage_course_name').value = name;
    document.getElementById('manage_course_credits').value = credits;
    
    document.getElementById('courseFormTitle').innerText = 'Edit Course';
    document.getElementById('courseSubmitBtn').innerText = 'Update Course';
    window.scrollTo(0,0);
}

async function submitCourse(e) {
    e.preventDefault();
    const oldCode = document.getElementById('course_id_hidden').value;
    const isEdit = oldCode !== '';
    
    const payload = {
        course_code: document.getElementById('manage_course_code').value,
        course_name: document.getElementById('manage_course_name').value,
        credits: parseInt(document.getElementById('manage_course_credits').value)
    };

    const method = isEdit ? 'PUT' : 'POST';
    let url = '/courses.php';
    if (isEdit) url += `?code=${encodeURIComponent(oldCode)}`;

    const response = await apiFetch(url, method, payload);
    if (response && response.success) {
        showMessage(response.message);
        resetCourseForm();
        loadCoursesAdmin();
        loadAdminDashboardStats();
    }
}

function resetCourseForm() {
    document.getElementById('courseForm').reset();
    document.getElementById('course_id_hidden').value = '';
    document.getElementById('courseFormTitle').innerText = 'Add New Course';
    document.getElementById('courseSubmitBtn').innerText = 'Save Course';
}

async function deleteCourse(code) {
    if (!confirm('Are you sure you want to delete this course?')) return;
    const response = await apiFetch(`/courses.php?code=${code}`, 'DELETE');
    if (response && response.success) {
        showMessage(response.message);
        loadCoursesAdmin();
        loadAdminDashboardStats();
    }
}

// ==========================================
// ADMIN: STUDENTS
// ==========================================
async function loadStudentsAdmin() {
    const response = await apiFetch('/users.php?role=student');
    const tbody = document.getElementById('adminStudentsTable');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (response && response.success) {
        response.data.forEach(student => {
            const sId = student.id || student.user_id || '';
            tbody.innerHTML += `
                <tr>
                    <td>${sId}</td>
                    <td><strong>${student.username}</strong></td>
                    <td>${student.email}</td>
                    <td>
                        <button class="btn-edit" onclick="editStudent('${sId}', '${student.username}', '${student.email}')">Edit</button>
                        <button class="btn-delete" onclick="deleteStudent('${sId}', '${student.username}')">Delete</button>
                    </td>
                </tr>`;
        });
    }
}

function editStudent(id, username, email) {
    document.getElementById('student_id_hidden').value = id;
    document.getElementById('student_old_username_hidden').value = username;
    
    document.getElementById('manage_student_username').value = username;
    document.getElementById('manage_student_email').value = email;
    document.getElementById('manage_student_password').value = '';
    
    document.getElementById('studentFormTitle').innerText = 'Edit Student';
    document.getElementById('studentSubmitBtn').innerText = 'Update Student';
    window.scrollTo(0,0);
}

async function submitStudent(e) {
    e.preventDefault();
    const oldId = document.getElementById('student_id_hidden').value;
    const oldUsername = document.getElementById('student_old_username_hidden').value;
    const isEdit = oldId !== '';
    const passwordInput = document.getElementById('manage_student_password').value;
    
    const payload = {
        username: document.getElementById('manage_student_username').value,
        email: document.getElementById('manage_student_email').value,
        role: 'Student'
    };

    if (passwordInput) payload.password = passwordInput;
    if (!isEdit && !passwordInput) { showMessage("Password is required for new students.", false); return; }

    const method = isEdit ? 'PUT' : 'POST';
    let url = '/users.php';
    if (isEdit) url += `?id=${oldId}&username=${encodeURIComponent(oldUsername)}`;

    const response = await apiFetch(url, method, payload);
    if (response && response.success) {
        showMessage(response.message);
        resetStudentForm();
        loadStudentsAdmin();
        loadAdminDashboardStats();
    }
}

function resetStudentForm() {
    document.getElementById('studentForm').reset();
    document.getElementById('student_id_hidden').value = '';
    document.getElementById('student_old_username_hidden').value = '';
    document.getElementById('studentFormTitle').innerText = 'Add New Student';
    document.getElementById('studentSubmitBtn').innerText = 'Save Student';
}

async function deleteStudent(id, username) {
    if (!confirm('Are you sure you want to delete this student record?')) return;
    const response = await apiFetch(`/users.php?id=${id}&username=${username}`, 'DELETE');
    if (response && response.success) { 
        showMessage(response.message); 
        loadStudentsAdmin(); 
        loadAdminDashboardStats();
    }
}

// ==========================================
// ADMIN: EXAMS
// ==========================================
async function loadExamsAdmin() {
    const response = await apiFetch('/examinations.php');
    const tbody = document.getElementById('adminExamsTable');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (response && response.success) {
        response.data.forEach(exam => {
            const eId = exam.id || exam.exam_id || '';
            tbody.innerHTML += `
                <tr>
                    <td>${eId}</td>
                    <td><strong>${exam.course_code}</strong></td>
                    <td>${exam.exam_date}</td>
                    <td>${exam.start_time}</td>
                    <td>${exam.venue}</td>
                    <td>
                        <button class="btn-edit" onclick="editExam('${eId}', '${exam.course_code}', '${exam.exam_date}', '${exam.start_time}', '${exam.venue}')">Edit</button>
                        <button class="btn-delete" onclick="deleteExam('${eId}')">Delete</button>
                    </td>
                </tr>`;
        });
    }
}

function editExam(id, code, date, time, venue) {
    document.getElementById('exam_id_hidden').value = id;
    document.getElementById('course_code').value = code;
    document.getElementById('exam_date').value = date;
    document.getElementById('start_time').value = time;
    document.getElementById('venue').value = venue;
    
    document.getElementById('examFormTitle').innerText = 'Edit Examination';
    document.getElementById('examSubmitBtn').innerText = 'Update Exam';
    window.scrollTo(0,0);
}

async function submitExam(e) {
    e.preventDefault();
    const oldId = document.getElementById('exam_id_hidden').value;
    const isEdit = oldId !== '';
    
    const payload = {
        course_code: document.getElementById('course_code').value,
        exam_date: document.getElementById('exam_date').value,
        start_time: document.getElementById('start_time').value,
        venue: document.getElementById('venue').value
    };

    const method = isEdit ? 'PUT' : 'POST';
    let url = '/examinations.php';
    if (isEdit) url += `?id=${oldId}`;

    const response = await apiFetch(url, method, payload);
    if (response && response.success) {
        showMessage(response.message);
        resetExamForm();
        loadExamsAdmin();
        loadAdminDashboardStats();
    }
}

function resetExamForm() {
    document.getElementById('addExamForm').reset();
    document.getElementById('exam_id_hidden').value = '';
    document.getElementById('examFormTitle').innerText = 'Schedule New Exam';
    document.getElementById('examSubmitBtn').innerText = 'Schedule Exam';
}

async function deleteExam(id) {
    if (!confirm('Cancel this exam?')) return;
    const response = await apiFetch(`/examinations.php?id=${id}`, 'DELETE');
    if (response && response.success) { 
        showMessage(response.message); 
        loadExamsAdmin(); 
        loadAdminDashboardStats();
    }
}

// ==========================================
// ADMIN: RESULTS
// ==========================================
async function loadResultsAdmin() {
    const studentsResponse = await apiFetch('/users.php?role=student');
    let studentMap = {};
    if (studentsResponse && studentsResponse.success) {
        studentsResponse.data.forEach(st => studentMap[st.id || st.user_id] = st.username);
    }

    const examsResponse = await apiFetch('/examinations.php');
    let examMap = {};
    if (examsResponse && examsResponse.success) {
        examsResponse.data.forEach(ex => examMap[ex.id || ex.exam_id] = ex.course_code);
    }

    const response = await apiFetch('/results.php');
    const tbody = document.getElementById('adminResultsTable');
    if (!tbody) return;

    tbody.innerHTML = '';
    
    if (response && response.success) {
        response.data.forEach(result => {
            const rId = result.id || result.result_id || '';
            const studentName = studentMap[result.student_id] || `ID: ${result.student_id}`;
            const courseCode = examMap[result.exam_id] || `ID: ${result.exam_id}`;
            
            tbody.innerHTML += `
                <tr>
                    <td>${rId}</td>
                    <td><strong>${studentName}</strong></td>
                    <td>${courseCode}</td>
                    <td>${result.marks}%</td>
                    <td><strong>${result.grade}</strong></td>
                    <td>
                        <button class="btn-edit" onclick="editResult('${rId}', ${result.student_id}, ${result.exam_id}, ${result.marks})">Edit</button>
                        <button class="btn-delete" onclick="deleteResult('${rId}')">Delete</button>
                    </td>
                </tr>`;
        });
    }
}

function editResult(id, student_id, exam_id, marks) {
    document.getElementById('result_id_hidden').value = id;
    document.getElementById('result_student_id').value = student_id;
    document.getElementById('result_exam_id').value = exam_id;
    document.getElementById('result_marks').value = marks;
    
    document.getElementById('resultFormTitle').innerText = 'Edit Result';
    document.getElementById('resultSubmitBtn').innerText = 'Update Result';
    window.scrollTo(0,0);
}

async function submitResult(e) {
    e.preventDefault();
    const oldId = document.getElementById('result_id_hidden').value;
    const isEdit = oldId !== '';
    const marks = parseInt(document.getElementById('result_marks').value);
    
    const payload = {
        student_id: document.getElementById('result_student_id').value,
        exam_id: document.getElementById('result_exam_id').value,
        marks: marks
    };

    const method = isEdit ? 'PUT' : 'POST';
    let url = '/results.php';
    if (isEdit) url += `?id=${oldId}`;

    const response = await apiFetch(url, method, payload);
    if (response && response.success) {
        showMessage(response.message || 'Result saved successfully!');
        resetResultForm();
        loadResultsAdmin(); 
    }
}

function resetResultForm() {
    document.getElementById('addResultForm').reset();
    document.getElementById('result_id_hidden').value = '';
    document.getElementById('resultFormTitle').innerText = 'Input Student Result';
    document.getElementById('resultSubmitBtn').innerText = 'Submit Result';
}

async function deleteResult(id) {
    if (!confirm('Are you sure you want to delete this result?')) return;
    const response = await apiFetch(`/results.php?id=${id}`, 'DELETE');
    if (response && response.success) { showMessage(response.message); loadResultsAdmin(); }
}

// ==========================================
// STUDENT FUNCTIONS
// ==========================================
async function loadExamsStudent() {
    const response = await apiFetch('/examinations.php');
    const tbody = document.getElementById('studentExamsTable');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (response && response.success) {
        response.data.forEach(exam => {
            tbody.innerHTML += `<tr><td><strong>${exam.course_code}</strong></td><td>${exam.exam_date}</td><td>${exam.start_time}</td><td>${exam.venue}</td></tr>`;
        });
    }
}

async function loadResultsStudent() {
    const examsResponse = await apiFetch('/examinations.php');
    let examMap = {};
    if (examsResponse && examsResponse.success) {
        examsResponse.data.forEach(ex => examMap[ex.id || ex.exam_id] = ex.course_code);
    }

    const response = await apiFetch('/results.php');
    const tbody = document.getElementById('studentResultsTable');
    if (!tbody) return;

    tbody.innerHTML = '';
    
    if (response && response.success) {
        const currentUser_id = currentUser.id || currentUser.user_id;
        const myResults = response.data.filter(r => r.student_id == currentUser_id);
        myResults.forEach(result => {
            const courseCode = examMap[result.exam_id] || `EXM-${result.exam_id}`;
            tbody.innerHTML += `<tr><td><strong>${courseCode}</strong></td><td>${result.marks}%</td><td><strong>${result.grade}</strong></td></tr>`;
        });
    }
}

async function generateSlip() {
    const currentUserId = currentUser.id || currentUser.user_id;
    const response = await apiFetch(`/generate_slip.php?student_id=${currentUserId}`);
    const qrContainer = document.getElementById('qr-container');
    
    if (response && response.success) {
        const qrUrl = response.integrated_api_evidence.qr_code_image_url;
        qrContainer.innerHTML = `<img src="${qrUrl}" alt="QR Entrance Slip" style="margin-top: 15px; border-radius: 8px; border: 2px solid var(--primary);">`;
        showMessage(response.message);
    } else {
        qrContainer.innerHTML = '';
    }
}

window.onload = checkAuth;