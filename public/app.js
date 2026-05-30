// Konfigurasi Axios dengan baseURL /api/v1 dan Authorization header dari localStorage
const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Tambahkan interceptor untuk menambahkan token ke setiap request
api.interceptors.request.use(function (config) {
    const token = localStorage.getItem('authToken');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, function (error) {
    return Promise.reject(error);
});

// Handle response 401 Unauthorized
api.interceptors.response.use(
    function (response) {
        return response;
    },
    function (error) {
        if (error.response && error.response.status === 401) {
            // Token expired atau invalid
            localStorage.removeItem('authToken');
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        }
        return Promise.reject(error);
    }
);

// State Management
let containersData = [];
let currentUser = null;

// Load data saat halaman pertama kali dibuka
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    loadUserInfo();
    loadContainers();
    setupFormHandler();
    setupLogoutHandler();
});

// ========== Authentication Check ==========
function checkAuthentication() {
    const token = localStorage.getItem('authToken');
    
    if (!token) {
        window.location.href = 'login.html';
        return false;
    }
    
    return true;
}

// ========== Load User Info ==========
function loadUserInfo() {
    const userJson = localStorage.getItem('user');
    if (userJson) {
        currentUser = JSON.parse(userJson);

        const nameEl = document.getElementById('userNameDisplay');
        const roleEl = document.getElementById('userRoleDisplay');
        const addBtn = document.getElementById('addContainerBtn');

        if (nameEl) nameEl.textContent = currentUser.name;
        if (roleEl) roleEl.textContent = currentUser.role.toUpperCase();

        // Tampilkan tombol Add hanya untuk admin
        if (addBtn && currentUser.role === 'admin') {
            addBtn.style.display = 'inline-block';
        }
    }
}

// ========== Setup Logout Handler ==========
function setupLogoutHandler() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function() {
            try {
                await api.post('/logout');
            } catch (error) {
                console.log('Error logging out:', error);
            } finally {
                localStorage.removeItem('authToken');
                localStorage.removeItem('user');
                window.location.href = 'login.html';
            }
        });
    }
}

// Setup Form Submit Handler
function setupFormHandler() {
    const form = document.getElementById('containerForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await createContainer();
        });
    }
}

// ========== CRUD Operations menggunakan API Gateway v1 ==========

// READ - Load All Containers
async function loadContainers() {
    const grid = document.getElementById('containersGrid');
    if (grid) grid.innerHTML = '<div class="loading-text">Memuat data...</div>';

    try {
        const response = await api.get('/gateway/containers');
        
        if (response.data.success) {
            containersData = response.data.data;
            displayContainers(containersData);
            calculateTotalWeight(containersData);
        }
    } catch (error) {
        console.error('Error loading containers:', error);
        if (error.response && error.response.status === 403) {
            alert('Anda tidak memiliki akses untuk melihat containers');
        } else if (error.response && error.response.status !== 401) {
            if (grid) grid.innerHTML = '<div class="loading-text">Gagal memuat data container.</div>';
        }
    }
}

// Display Containers in Grid
function displayContainers(containers) {
    const grid = document.getElementById('containersGrid');
    if (!grid) return;
    
    if (containers.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border: 1px solid #1a1a1a;">
                <h3 style="color: #95a5a6; text-transform: uppercase;">Tidak ada container yang ditemukan</h3>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = containers.map(container => `
        <div class="container-card">
            <div class="card-header">
                <div class="container-id">${container.container_id}</div>
                <span class="status-badge status-${container.status.toLowerCase()}">${container.status}</span>
            </div>
            
            <div class="card-info">
                <div class="info-row">
                    <span class="info-label">Tipe Limbah:</span>
                    <span class="info-value">${container.waste_type}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Berat:</span>
                    <span class="info-value">${container.weight_kg} kg</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jumlah Log:</span>
                    <span class="info-value">${container.tracking_logs ? container.tracking_logs.length : 0} entri</span>
                </div>
            </div>
            
            <div class="card-actions">
                <button class="btn btn-outline" onclick="viewLogs(${container.id})">
                    LOGS
                </button>
                ${currentUser && currentUser.role === 'admin' && container.status === 'Active' ? `
                    <button class="btn btn-outline" onclick="archiveContainer(${container.id})">
                        ARCHIVE
                    </button>
                ` : ''}
                ${currentUser && currentUser.role === 'admin' ? `
                    <button class="btn btn-danger" onclick="deleteContainer(${container.id})">
                        HAPUS
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Calculate Total Weight
function calculateTotalWeight(containers) {
    const totalWeight = containers.reduce((sum, container) => sum + container.weight_kg, 0);
    const el = document.getElementById('totalWeight');
    if (el) el.textContent = totalWeight.toLocaleString('id-ID');
}

// CREATE - Add Container
async function createContainer() {
    clearErrors();
    
    if (!currentUser || currentUser.role !== 'admin') {
        alert('Hanya admin yang bisa membuat container baru');
        return;
    }
    
    const data = {
        container_id: document.getElementById('container_id').value.trim(),
        waste_type: document.getElementById('waste_type').value,
        weight_kg: parseFloat(document.getElementById('weight_kg').value),
        status: document.getElementById('status').value
    };
    
    try {
        const response = await api.post('/gateway/containers', data);
        
        if (response.data.success) {
            alert('Container berhasil ditambahkan!');
            document.getElementById('containerForm').reset();
            document.getElementById('formContainer').style.display = 'none';
            loadContainers();
        }
    } catch (error) {
        if (error.response && error.response.status === 403) {
            alert('Hanya admin yang bisa membuat container');
        } else if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors;
            displayErrors(errors);
        } else {
            alert('Gagal menambahkan container: ' + (error.response?.data?.message || error.message));
        }
    }
}

// Display Validation Errors
function displayErrors(errors) {
    Object.keys(errors).forEach(field => {
        const errorElement = document.getElementById(`error_${field}`);
        if (errorElement) {
            errorElement.textContent = errors[field][0];
            errorElement.classList.add('show');
        }
    });
}

// Clear All Error Messages
function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
        element.classList.remove('show');
    });
}

// UPDATE - Archive Container
async function archiveContainer(id) {
    if (!confirm('Apakah Anda yakin ingin mengarsipkan container ini?')) {
        return;
    }
    
    try {
        const response = await api.patch(`/gateway/containers/${id}`, {
            status: 'Archived'
        });
        
        if (response.data.success) {
            alert('Container berhasil diarsipkan!');
            loadContainers();
        }
    } catch (error) {
        if (error.response && error.response.status === 403) {
            alert('Hanya admin yang bisa archive container');
        } else {
            alert('Gagal mengarsipkan container');
        }
    }
}

// DELETE - Delete Container
async function deleteContainer(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus container ini?\n\nTindakan ini tidak dapat dibatalkan!')) {
        return;
    }
    
    try {
        const response = await api.delete(`/gateway/containers/${id}`);
        
        if (response.data.success) {
            alert('Container berhasil dihapus!');
            loadContainers();
        }
    } catch (error) {
        if (error.response && error.response.status === 403) {
            alert('Hanya admin yang bisa menghapus container');
        } else {
            alert('Gagal menghapus container');
        }
    }
}

// Search & Filter
async function searchContainers() {
    const type = document.getElementById('searchType').value;
    const minWeight = document.getElementById('searchMinWeight').value;
    
    if (!type && !minWeight) {
        loadContainers();
        return;
    }
    
    try {
        const params = {};
        if (type) params.type = type;
        if (minWeight) params.min_weight = minWeight;
        
        const response = await api.get('/gateway/containers', { params });
        
        if (response.data.success) {
            displayContainers(response.data.data);
            calculateTotalWeight(response.data.data);
        }
    } catch (error) {
        alert('Gagal melakukan pencarian');
    }
}

// VIEW - Get Tracking Logs
async function viewLogs(id) {
    try {
        const response = await api.get(`/gateway/containers/${id}/logs`);
        
        if (response.data.success) {
            displayLogsModal(response.data.container_id, response.data.logs);
        }
    } catch (error) {
        alert('Gagal memuat tracking logs');
    }
}

// Display Logs in Modal
function displayLogsModal(containerId, logs) {
    const modal = document.getElementById('logsModal');
    const modalTitle = document.getElementById('modalTitle');
    const logsContent = document.getElementById('logsContent');
    
    modalTitle.textContent = `Tracking Logs - ${containerId}`;
    
    if (!logs || logs.length === 0) {
        logsContent.innerHTML = '<p style="text-align:center; color:#666; padding: 20px; text-transform: uppercase; font-size: 0.85em;">Belum ada log tracking.</p>';
    } else {
        logsContent.innerHTML = logs.map(log => `
            <div class="log-entry">
                <div class="log-time">${new Date(log.timestamp).toLocaleString('id-ID')}</div>
                <div class="log-location">${log.location}</div>
                <div class="log-desc">${log.description}</div>
            </div>
        `).join('');
    }
    
    modal.style.display = 'block';
}

// Close Modal
function closeModal() {
    document.getElementById('logsModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logsModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}