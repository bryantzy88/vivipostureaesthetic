// 登录处理
document.querySelector('.login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    try {
        const response = await fetch('auth/login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            window.location.href = 'dashboard.html';
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('登录失败:', error);
    }
});

// 加载预约数据
async function loadAppointments() {
    try {
        const response = await fetch('api/appointments.php');
        const data = await response.json();
        
        if (data.success) {
            renderAppointments(data.data);
        }
    } catch (error) {
        console.error('获取预约数据失败:', error);
    }
}

// 渲染预约表格
function renderAppointments(appointments) {
    const tbody = document.querySelector('.admin-table tbody');
    tbody.innerHTML = appointments.map(appointment => `
        <tr>
            <td>${appointment.appointment_time}</td>
            <td>${appointment.name}</td>
            <td>${appointment.phone}</td>
            <td>${appointment.service}</td>
            <td>
                <select onchange="updateStatus(${appointment.id}, this.value)">
                    <option value="pending" ${appointment.status === 'pending' ? 'selected' : ''}>待处理</option>
                    <option value="confirmed" ${appointment.status === 'confirmed' ? 'selected' : ''}>已确认</option>
                    <option value="completed" ${appointment.status === 'completed' ? 'selected' : ''}>已完成</option>
                    <option value="cancelled" ${appointment.status === 'cancelled' ? 'selected' : ''}>已取消</option>
                </select>
            </td>
            <td>
                <button onclick="viewDetails(${appointment.id})">查看</button>
                <button onclick="deleteAppointment(${appointment.id})">删除</button>
            </td>
        </tr>
    `).join('');
}

// 更新预约状态
async function updateStatus(id, status) {
    try {
        const response = await fetch('api/appointments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id, status })
        });
        
        const data = await response.json();
        if (data.success) {
            loadAppointments();
        } else {
            alert('更新失败');
        }
    } catch (error) {
        console.error('更新状态失败:', error);
    }
}

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.admin-dashboard')) {
        loadAppointments();
    }
}); 