// js/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    // Initialize dashboard
    initDashboard();
    
    // Event listeners
    setupEventListeners();
    
    // Load user data
    loadUserData();
});

function initDashboard() {
    // Setup collapsible sections
    document.querySelector('.task-header').addEventListener('click', () => {
        const content = document.querySelector('.task-content');
        const toggle = document.querySelector('.task-toggle');
        
        content.classList.toggle('hidden');
        toggle.innerHTML = content.classList.contains('hidden') ? 
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />' :
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />';
    });
    
    // Setup logout
    document.getElementById('logoutBtn').addEventListener('click', logout);
}

function setupEventListeners() {
    // Task completion buttons
    document.querySelectorAll('.task-btn').forEach(button => {
        button.addEventListener('click', () => {
            const taskType = button.dataset.taskType;
            const points = parseInt(button.dataset.points);
            
            button.disabled = true;
            button.innerHTML = 'Processing...';
            
            completeTask(taskType, points).then(result => {
                if (result.success) {
                    // Update UI with new points
                    updatePointsDisplay(result.total_points);
                    
                    // Show success message
                    showNotification(`Task completed! Earned ${result.points_earned} points`, 'success');
                    
                    // Reset button
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = button.textContent;
                    }, 2000);
                }
            }).catch(error => {
                showNotification(error.message || 'Task verification failed', 'error');
                button.disabled = false;
                button.innerHTML = button.textContent;
            });
        });
    });
}

function loadUserData() {
    fetch('/api/user.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                window.location.href = 'auth.html';
                return;
            }
            
            // Update UI with user data
            updateUserInterface(data);
            
            // Load activity
            loadActivity();
        })
        .catch(() => {
            window.location.href = 'auth.html';
        });
}

function completeTask(taskType, points) {
    return fetch('/api/tasks.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_type: taskType, points: points })
    }).then(response => response.json());
}

function updatePointsDisplay(totalPoints) {
    const formattedPoints = totalPoints.toLocaleString();
    const value = (totalPoints / 1000 * 0.01).toFixed(4);
    
    document.getElementById('totalPoints').textContent = formattedPoints;
    document.getElementById('pointsValue').textContent = value;
    document.getElementById('currentValue').textContent = value;
    
    // Update crypto balance
    const cryptoBalance = (totalPoints / 1000 * 0.01 * 0.95).toFixed(6);
    document.getElementById('cryptoBalance').textContent = cryptoBalance;
    
    // Update interest
    const interest = (cryptoBalance * 0.005).toFixed(6);
    document.getElementById('dailyInterest').textContent = interest;
}

function loadActivity() {
    fetch('/api/activity.php')
        .then(response => response.json())
        .then(activities => {
            const activityList = document.getElementById('activityList');
            activityList.innerHTML = '';
            
            activities.slice(0, 5).forEach(activity => {
                const item = document.createElement('div');
                item.className = 'flex items-start p-3 bg-black/20 rounded-lg';
                item.innerHTML = `
                    <div class="mr-3 mt-1">
                        <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
                    </div>
                    <div>
                        <p class="font-medium">${activity.description}</p>
                        <p class="text-sm text-gray-400">${formatDate(activity.timestamp)}</p>
                    </div>
                `;
                activityList.appendChild(item);
            });
        });
}

function showNotification(message, type) {
    // Implementation would show a toast notification
    console.log(`${type.toUpperCase()}: ${message}`);
}

function logout() {
    fetch('/api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    }).then(() => {
        document.cookie = 'faucet_token=; Max-Age=0; path=/';
        window.location.href = 'index.html';
    });
}

function formatDate(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
