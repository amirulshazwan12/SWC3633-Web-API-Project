// app_student1.js - Architecture Controller Logic Framework for Member 1
let activeEntity = 'exams';
let isEditingMode = false;

const ENGINES = {
    exams: { url: 'exams.php', id: 'exam_id' },
    courses: { url: 'courses.php', id: 'course_code' },
    results: { url: 'results.php', id: 'result_id' },
    users: { url: 'users.php', id: 'user_id' }
};

function switchEntity(target) {
    activeEntity = target;
    isEditingMode = false;
    document.querySelectorAll('.tab-btn').forEach((b, i) => b.classList.toggle('active', b.innerText.toLowerCase().includes(target)));
    document.querySelectorAll('.form-panel').forEach(form => form.classList.remove('active'));
    
    const panelId = 'form' + target.charAt(0).toUpperCase() + target.slice(1);
    document.getElementById(panelId).classList.add('active');
    document.getElementById('formTitle').innerText = `Ingest ${target.toUpperCase()} Structural Unit`;
    document.getElementById('viewTitle').innerText = `${target.toUpperCase()} System Register`;
    document.getElementById('passWrapper').style.display = "block";
    document.getElementById('co_code').disabled = false;
    
    resetFormEcosystem();
    synchronizeStream();
}

async function synchronizeStream() {
    const key = document.getElementById('authKey').value;
    const body = document.getElementById('tableBody');
    const header = document.getElementById('tableHeader');
    body.innerHTML = '<tr><td colspan="6">Interrogating REST pipelines...</td></tr>';

    try {
        const res = await fetch(ENGINES[activeEntity].url, { headers: { 'X-API-KEY': key } });
        const json = await res.json();
        document.getElementById('logPanel').innerText = `[GET] pipeline active payload metadata:\n` + JSON.stringify(json, null, 2);

        if (!json.success || !json.data) {
            body.innerHTML = `<tr><td colspan="6" style="color:var(--danger)">No context signatures exist inside target engine matrix.</td></tr>`;
            return;
        }

        renderStructuralLayouts(json.data, header, body);
    } catch (e) { document.getElementById('logPanel').innerText = "REST Handshake Exception: " + e.message; }
}

function renderStructuralLayouts(data, hRef, bRef) {
    bRef.innerHTML = '';
    if (data.length === 0) { hRef.innerHTML = ''; bRef.innerHTML = '<tr><td>Node Array is empty.</td></tr>'; return; }

    if (activeEntity === 'exams') {
        hRef.innerHTML = `<tr><th>ID</th><th>Course Ref</th><th>Date Timestamp</th><th>Location Venue</th><th>Actions Pipeline</th></tr>`;
        data.forEach(item => {
            bRef.innerHTML += `<tr><td>${item.exam_id}</td><td><strong>${item.course_code}</strong></td><td>${item.exam_date} @ ${item.start_time}</td><td>${item.venue}</td>
            <td><button style="background:var(--warning); color:black; border:none; padding:4px 8px; border-radius:3px;" onclick="bindModificationContext(${JSON.stringify(item).replace(/"/g, '&quot;')})">Edit</button>
            <button class="btn-danger" onclick="executePurgeContext('${item.exam_id}')">Purge</button></td></tr>`;
        });
    } else if (activeEntity === 'courses') {
        hRef.innerHTML = `<tr><th>Index Reference Code</th><th>Nomenclature Label</th><th>Weights</th><th>Actions Pipeline</th></tr>`;
        data.forEach(item => {
            bRef.innerHTML += `<tr><td><strong>${item.course_code}</strong></td><td>${item.course_name}</td><td>${item.credits} Units</td>
            <td><button style="background:var(--warning); color:black; border:none; padding:4px 8px; border-radius:3px;" onclick="bindModificationContext(${JSON.stringify(item).replace(/"/g, '&quot;')})">Edit</button>
            <button class="btn-danger" onclick="executePurgeContext('${item.course_code}')">Purge</button></td></tr>`;
        });
    } else if (activeEntity === 'results') {
        hRef.innerHTML = `<tr><th>Evaluation Ledger ID</th><th>Student Matrix ID</th><th>Course Ref</th><th>Grade</th><th>Actions Pipeline</th></tr>`;
        data.forEach(item => {
            bRef.innerHTML += `<tr><td>${item.result_id}</td><td>${item.student_id}</td><td>${item.course_code}</td><td><strong>${item.grade}</strong></td>
            <td><button style="background:var(--warning); color:black; border:none; padding:4px 8px; border-radius:3px;" onclick="bindModificationContext(${JSON.stringify(item).replace(/"/g, '&quot;')})">Edit</button>
            <button class="btn-danger" onclick="executePurgeContext('${item.result_id}')">Purge</button></td></tr>`;
        });
    } else if (activeEntity === 'users') {
        hRef.innerHTML = `<tr><th>Context ID</th><th>User Tag</th><th>Communication Endpoint</th><th>Privilege Role</th><th>Actions Pipeline</th></tr>`;
        data.forEach(item => {
            bRef.innerHTML += `<tr><td>${item.user_id}</td><td><strong>${item.username}</strong></td><td>${item.email}</td><td>${item.role}</td>
            <td><button style="background:var(--warning); color:black; border:none; padding:4px 8px; border-radius:3px;" onclick="bindModificationContext(${JSON.stringify(item).replace(/"/g, '&quot;')})">Edit</button>
            <button class="btn-danger" onclick="executePurgeContext('${item.user_id}')">Purge</button></td></tr>`;
        });
    }
}

async function handleTransactionIngestion(e) {
    e.preventDefault();
    const key = document.getElementById('authKey').value;
    let payload = {}, url = ENGINES[activeEntity].url, method = isEditingMode ? 'PUT' : 'POST';

    if (activeEntity === 'exams') {
        const id = document.getElementById('exam_id').value;
        if(id) url += `?id=${id}`;
        payload = { course_code: document.getElementById('ex_code').value, exam_date: document.getElementById('ex_date').value, start_time: document.getElementById('ex_time').value, venue: document.getElementById('ex_venue').value };
    } else if (activeEntity === 'courses') {
        const code = document.getElementById('co_code').value;
        if(isEditingMode) url += `?code=${code}`;
        payload = { course_code: code, course_name: document.getElementById('co_name').value, credits: document.getElementById('co_credits').value };
    } else if (activeEntity === 'results') {
        const id = document.getElementById('result_id').value;
        if(id) url += `?id=${id}`;
        payload = { student_id: document.getElementById('res_stud').value, course_code: document.getElementById('res_code').value, grade: document.getElementById('res_grade').value };
    } else if (activeEntity === 'users') {
        const id = document.getElementById('user_id').value;
        if(id) url += `?id=${id}`;
        payload = { username: document.getElementById('usr_name').value, email: document.getElementById('usr_email').value, role: document.getElementById('usr_role').value };
        if(!isEditingMode) payload.password = document.getElementById('usr_pass').value;
    }

    try {
        const res = await fetch(url, { method: method, headers: { 'Content-Type': 'application/json', 'X-API-KEY': key }, body: JSON.stringify(payload) });
        const result = await res.json();
        document.getElementById('logPanel').innerText = `[${method}] structural response pipeline loop out:\n` + JSON.stringify(result, null, 2);
        if (result.success) { alert(result.message || "Ecosystem record commit processing completed."); resetFormEcosystem(); synchronizeStream(); }
    } catch (err) { alert("Operational Failure: " + err.message); }
}

function bindModificationContext(obj) {
    isEditingMode = true;
    document.getElementById('abortEditBtn').style.display = "block";
    
    if (activeEntity === 'exams') {
        document.getElementById('exam_id').value = obj.exam_id;
        document.getElementById('ex_code').value = obj.course_code;
        document.getElementById('ex_date').value = obj.exam_date;
        document.getElementById('ex_time').value = obj.start_time;
        document.getElementById('ex_venue').value = obj.venue;
    } else if (activeEntity === 'courses') {
        document.getElementById('co_code').value = obj.course_code;
        document.getElementById('co_code').disabled = true;
        document.getElementById('co_name').value = obj.course_name;
        document.getElementById('co_credits').value = obj.credits;
    } else if (activeEntity === 'results') {
        document.getElementById('result_id').value = obj.result_id;
        document.getElementById('res_stud').value = obj.student_id;
        document.getElementById('res_code').value = obj.course_code;
        document.getElementById('res_grade').value = obj.grade;
    } else if (activeEntity === 'users') {
        document.getElementById('user_id').value = obj.user_id;
        document.getElementById('usr_name').value = obj.username;
        document.getElementById('usr_email').value = obj.email;
        document.getElementById('usr_role').value = obj.role;
        document.getElementById('passWrapper').style.display = "none";
    }
}

async function executePurgeContext(id) {
    if (!confirm(`Purge entry identity structural index identifier token instance ${id}?`)) return;
    const key = document.getElementById('authKey').value;
    const paramKey = activeEntity === 'courses' ? 'code' : 'id';
    
    try {
        const res = await fetch(`${ENGINES[activeEntity].url}?${paramKey}=${id}`, { method: 'DELETE', headers: { 'X-API-KEY': key } });
        const result = await res.json();
        document.getElementById('logPanel').innerText = `[DELETE] dynamic frame trace metrics logs:\n` + JSON.stringify(result, null, 2);
        if (result.success) synchronizeStream();
    } catch (e) { alert("Pipeline execution block exception logic triggered."); }
}

function resetFormEcosystem() {
    isEditingMode = false;
    document.getElementById('abortEditBtn').style.display = "none";
    document.querySelectorAll('form').forEach(f => f.reset());
    document.querySelectorAll('form input[type="hidden"]').forEach(h => h.value = "");
    document.getElementById('co_code').disabled = false;
    document.getElementById('passWrapper').style.display = "block";
}

document.querySelectorAll('form').forEach(form => form.addEventListener('submit', handleTransactionIngestion));
document.getElementById('abortEditBtn').addEventListener('click', resetFormEcosystem);
document.getElementById('syncStreamBtn').addEventListener('click', synchronizeStream);
window.onload = () => switchEntity('exams');