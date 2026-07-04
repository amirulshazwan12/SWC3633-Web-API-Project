const express = require('express');
const jwt = require('jsonwebtoken');
const QRCode = require('qrcode');

const app = express();
app.use(express.json()); // Membolehkan API membaca data JSON

const SECRET_KEY = "kunci_rahsia_universiti_kita"; // Kunci untuk JWT

// --- DATA SIMULASI (Mock Database) ---
const users = [
    { id: 1, email: "admin@univ.edu", password: "123", role: "Administrator" },
    { id: 2, email: "pensyarah@univ.edu", password: "123", role: "Lecturer" },
    { id: 3, email: "pelajar@univ.edu", password: "123", role: "Student" }
];

// ==========================================
// ADVANCED FEATURE 1 & 2: MIDDLEWARE & JWT AUTH
// ==========================================
const authMiddleware = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1]; // Ambil token selepas perkataan 'Bearer'

    if (!token) {
        return res.status(401).json({ success: false, message: "Akses disekat! Sila sertakan token JWT." });
    }

    try {
        const verified = jwt.verify(token, SECRET_KEY);
        req.user = verified; // Masukkan data user (id, role) ke dalam request
        next(); // Lepas ke proses seterusnya
    } catch (err) {
        res.status(403).json({ success: false, message: "Token tidak sah atau tamat tempoh." });
    }
};

// ==========================================
// ADVANCED FEATURE 3: ROLE-BASED ACCESS CONTROL (RBAC)
// ==========================================
const checkRole = (allowedRoles) => {
    return (req, res, next) => {
        if (!allowedRoles.includes(req.user.role)) {
            return res.status(403).json({ 
                success: false, 
                message: `Akses ditolak! Anda adalah ${req.user.role}, bahagian ini hanya untuk ${allowedRoles.join(' atau ')}.` 
            });
        }
        next();
    };
};

// ==========================================
// ROUTES (API ENDPOINTS)
// ==========================================

// 1. ROUTE LOGIN (Menghasilkan JWT Token)
app.post('/api/login', (req, res) => {
    const { email, password } = req.body;
    const user = users.find(u => u.email === email && u.password === password);

    if (!user) {
        return res.status(400).json({ success: false, message: "Email atau password salah." });
    }

    // Jana token JWT mengandungi ID dan Role pengguna
    const token = jwt.sign({ id: user.id, role: user.role }, SECRET_KEY, { expiresIn: '1h' });
    
    res.json({ success: true, message: "Login Berjaya!", token: token });
});

// 2. ROUTE UNTUK PELAJAR (Student & Semua Boleh Akses)
app.get('/api/pelajar/profil', authMiddleware, checkRole(['Student', 'Lecturer', 'Administrator']), (req, res) => {
    res.json({ success: true, message: "Selamat datang ke Dashboard Pelajar/Staf." });
});

// 3. ROUTE UNTUK PENSYARAH & ADMIN (Kemasukan Markah)
app.post('/api/pensyarah/markah', authMiddleware, checkRole(['Lecturer', 'Administrator']), (req, res) => {
    res.json({ success: true, message: "Markah peperiksaan berjaya dimasukkan oleh " + req.user.role });
});

// 4. ROUTE UNTUK ADMIN SAHAJA (Urus Sistem)
app.get('/api/admin/tetapan', authMiddleware, checkRole(['Administrator']), (req, res) => {
    res.json({ success: true, message: "Selamat datang Admin. Ini halaman tetapan sistem utama." });
});

// ==========================================
// THIRD-PARTY INTEGRATION: QR CODE GENERATOR API
// ==========================================
// Pelajar boleh jana QR Code untuk Slip Peperiksaan mereka
app.get('/api/pelajar/slip-qr', authMiddleware, checkRole(['Student']), async (req, res, next) => {
    try {
        const dataSlip Peperiksaan = `PELAJAR_ID_${req.user.id}_SLIP_PEPERIKSAAN_SAH_2026`;
        
        // Panggil Third-Party API / Library QR Code untuk hasilkan gambar base64
        const qrCodeImage = await QRCode.toDataURL(dataSlipPeperiksaan);
        
        res.json({ 
            success: true, 
            message: "QR Code slip peperiksaan anda sedia didownload.", 
            qr_code_base64: qrCodeImage 
        });
    } catch (err) {
        next(err); // Hantar ke Error Handling Middleware jika gagal
    }
});

// Route sengaja dibuat error untuk test Error Handling Middleware
app.get('/api/test-error', (req, res, next) => {
    const err = new Error("Alamak! Berlaku ralat teknikal dalam database simulasi.");
    next(err);
});

// ==========================================
// ADVANCED FEATURE 4: ERROR HANDLING MIDDLEWARE
// ==========================================
app.use((err, req, res, next) => {
    console.error(err.stack); // Papar log ralat di terminal backend
    res.status(500).json({
        success: false,
        message: "Sesuatu ralat dalaman berlaku pada server!",
        error_details: err.message
    });
});

// Jalankan Server pada port 3000
app.listen(3000, () => {
    console.log("Sistem API Akademik berjalan di http://localhost:3000");
});