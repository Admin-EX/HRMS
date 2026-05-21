// Analytics Dashboard JavaScript with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data
    loadDashboardStats();
    loadEducationalAttainment();
    loadCivilServiceEligibility();
    loadTurnoverData();
    loadDepartmentData();
    loadLeaveDistribution();
    
    // Initialize modal functionality
    initializeModal();
});

// ============= AJAX DATA LOADING FUNCTIONS =============

// Load main dashboard statistics
function loadDashboardStats() {
    // Load each stat card
    fetch('../../backendPHP/get_analytics.php?type=stat&id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCard(1, data.data);
            }
        })
        .catch(error => console.error('Error loading total employees:', error));

    fetch('../../backendPHP/get_analytics.php?type=stat&id=2')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCard(2, data.data);
            }
        })
        .catch(error => console.error('Error loading pending leave:', error));

    fetch('../../backendPHP/get_analytics.php?type=stat&id=3')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCard(3, data.data);
            }
        })
        .catch(error => console.error('Error loading masters data:', error));

    fetch('../../backendPHP/get_analytics.php?type=stat&id=4')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCard(4, data.data);
            }
        })
        .catch(error => console.error('Error loading civil service data:', error));

    fetch('../../backendPHP/get_analytics.php?type=stat&id=5')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCard(5, data.data);
            }
        })
        .catch(error => console.error('Error loading doctorate data:', error));
}

// Update stat card with data
function updateStatCard(cardId, data) {
    const statCard = document.querySelector(`.stat[data-id="${cardId}"]`);
    if (statCard && data.details && data.details.length > 0) {
        const mainValue = data.details[0].value;
        const h3 = statCard.querySelector('h3');
        if (h3) {
            h3.textContent = mainValue;
        }
    }
}

// Load educational attainment data
function loadEducationalAttainment() {
    fetch('../../backendPHP/get_analytics.php?type=educational_attainment')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateEducationalAttainment(data.data);
            }
        })
        .catch(error => console.error('Error loading educational attainment:', error));
}

function updateEducationalAttainment(data) {
    // Update progress bars
    if (data.doctorate) {
        updateProgressBar('doctorate', data.doctorate.count, data.doctorate.percentage);
    }
    if (data.taking_doctorate) {
        updateProgressBar('taking-doctorate', data.taking_doctorate.count, data.taking_doctorate.percentage);
    }
    if (data.masters) {
        updateProgressBar('masters', data.masters.count, data.masters.percentage);
    }
    if (data.taking_masters) {
        updateProgressBar('taking-masters', data.taking_masters.count, data.taking_masters.percentage);
    }
    if (data.bachelors) {
        updateProgressBar('bachelors', data.bachelors.count, data.bachelors.percentage);
    }
    
    // Update sample size
    if (data.total) {
        const sampleBadge = document.querySelector('.sample-badge');
        if (sampleBadge) {
            sampleBadge.textContent = `SAMPLE SIZE: N=${data.total} TEACHING PERSONNEL`;
        }
    }
}

function updateProgressBar(className, count, percentage) {
    const progressItem = document.querySelector(`.progress-bar-fill.${className}`);
    if (progressItem) {
        progressItem.style.width = percentage + '%';
        progressItem.textContent = percentage + '%';
        
        const valueSpan = progressItem.closest('.progress-item').querySelector('.progress-value');
        if (valueSpan) {
            valueSpan.textContent = `${count} (${percentage}%)`;
        }
    }
}

// Load civil service eligibility data
function loadCivilServiceEligibility() {
    fetch('../../backendPHP/get_analytics.php?type=civil_service_eligibility')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateCivilServiceData(data.data);
            }
        })
        .catch(error => console.error('Error loading civil service data:', error));
}

function updateCivilServiceData(data) {
    // Update eligibility cards
    if (data.professional) {
        updateEligibilityCard('professional', data.professional.count, data.professional.percentage);
    }
    if (data.subprofessional) {
        updateEligibilityCard('subprofessional', data.subprofessional.count, data.subprofessional.percentage);
    }
    if (data.no_eligibility) {
        updateEligibilityCard('no-eligibility', data.no_eligibility.count, data.no_eligibility.percentage);
    }
    
    // Update circular progress
    if (data.overall_rate) {
        updateCircularProgress(data.overall_rate);
    }
    
    // Update sample size for NTP
    if (data.total) {
        const ntpBadge = document.querySelectorAll('.sample-badge')[1];
        if (ntpBadge) {
            ntpBadge.textContent = `SAMPLE SIZE: N=${data.total} NON-TEACHING PERSONNEL`;
        }
    }
}

function updateEligibilityCard(type, count, percentage) {
    const card = document.querySelector(`.eligibility-card[data-type="${type}"]`);
    if (card) {
        const valueElement = card.querySelector('.eligibility-value');
        const percentageElement = card.querySelector('.eligibility-percentage');
        
        if (valueElement) valueElement.textContent = count;
        if (percentageElement) percentageElement.textContent = percentage + '%';
    }
}

function updateCircularProgress(percentage) {
    const progressValue = document.querySelector('.circular-progress-value');
    if (progressValue) {
        progressValue.textContent = percentage + '%';
    }
    
    // Animate the circular progress (optional - requires CSS animation)
    const progressBg = document.querySelector('.circular-progress-bg');
    if (progressBg) {
        // You can add animation here if needed
        progressBg.style.setProperty('--progress', percentage);
    }
}

// Load turnover data
function loadTurnoverData() {
    const years = [2020, 2021, 2022, 2023, 2024];
    
    years.forEach(year => {
        fetch(`../../backendPHP/get_analytics.php?type=turnover&id=${year}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    updateTurnoverBar(year, data.data);
                }
            })
            .catch(error => console.error(`Error loading turnover for ${year}:`, error));
    });
    
    // Load total turnover stats
    fetch('../../backendPHP/get_analytics.php?type=turnover_totals')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateTurnoverTotals(data.data);
            }
        })
        .catch(error => console.error('Error loading turnover totals:', error));
}

function updateTurnoverBar(year, data) {
    const hiredBar = document.querySelector(`.turnover-bar.hired[data-year="${year}"]`);
    const resignedBar = hiredBar?.nextElementSibling;
    
    if (hiredBar && data.hired) {
        const hiredHeight = Math.min(data.hired, 100); // Max 100px
        hiredBar.style.height = hiredHeight + 'px';
        hiredBar.querySelector('.turnover-value').textContent = data.hired;
    }
    
    if (resignedBar && data.resigned) {
        const resignedHeight = Math.min(data.resigned, 100);
        resignedBar.style.height = resignedHeight + 'px';
        resignedBar.querySelector('.turnover-value').textContent = data.resigned;
    }
}

function updateTurnoverTotals(data) {
    const hiredTotal = document.querySelector('.hired-stat .turnover-stat-value');
    const resignedTotal = document.querySelector('.resigned-stat .turnover-stat-value');
    
    if (hiredTotal && data.total_hired) {
        hiredTotal.textContent = data.total_hired;
    }
    if (resignedTotal && data.total_resigned) {
        resignedTotal.textContent = data.total_resigned;
    }
}

// Load department data
function loadDepartmentData() {
    fetch('../../backendPHP/get_analytics.php?type=departments')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateDepartmentTable(data.data);
            }
        })
        .catch(error => console.error('Error loading department data:', error));
}

function updateDepartmentTable(departments) {
    const tbody = document.querySelector('table tbody');
    if (!tbody || !departments) return;
    
    tbody.innerHTML = ''; // Clear existing rows
    
    departments.forEach(dept => {
        const row = document.createElement('tr');
        row.setAttribute('data-dept', dept.name);
        
        const teachingPercent = dept.total > 0 ? Math.round((dept.teaching / dept.total) * 100) : 0;
        const badgeClass = teachingPercent >= 80 ? 'high-dist' : teachingPercent >= 50 ? 'medium-dist' : 'low-dist';
        
        row.innerHTML = `
            <td><strong>${dept.name}</strong></td>
            <td>${dept.teaching}</td>
            <td>${dept.non_teaching}</td>
            <td><strong>${dept.total}</strong></td>
            <td><span class="distribution-badge ${badgeClass}">${teachingPercent}%</span></td>
        `;
        
        tbody.appendChild(row);
    });
}

// Load leave distribution data
function loadLeaveDistribution() {
    fetch('../../backendPHP/get_analytics.php?type=leave_distribution')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateLeaveDistribution(data.data);
            }
        })
        .catch(error => console.error('Error loading leave distribution:', error));
}

function updateLeaveDistribution(data) {
    const leaveTypes = ['sick', 'vacation', 'emergency', 'maternity', 'paternity'];
    
    leaveTypes.forEach(type => {
        if (data[type]) {
            const bar = document.querySelector(`.graph-bar[data-type="${type}"]`);
            if (bar) {
                const height = Math.min(data[type] * 5, 150); // Scale: 5px per leave
                bar.style.height = height + 'px';
                bar.querySelector('.graph-bar-value').textContent = data[type];
            }
        }
    });
}

// ============= MODAL FUNCTIONALITY =============

function initializeModal() {
    const modal = document.getElementById('analyticsModal');
    const closeButtons = document.querySelectorAll('#closeAnalyticsModal, #closeAnalyticsModalBtn');
    
    // Add click handlers to all interactive elements
    addModalClickHandlers();
    
    // Close modal handlers
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });
    
    // Close on outside click
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Export report handler
    document.getElementById('viewReportBtn')?.addEventListener('click', exportReport);
}

function addModalClickHandlers() {
    // Stat cards
    document.querySelectorAll('.stat[data-id]').forEach(stat => {
        stat.style.cursor = 'pointer';
        stat.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            openModal('stat', id);
        });
    });
    
    // Department rows
    document.querySelectorAll('table tbody tr[data-dept]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function() {
            const dept = this.getAttribute('data-dept');
            openModal('department', dept);
        });
    });
    
    // Turnover bars
    document.querySelectorAll('.turnover-bar.hired[data-year]').forEach(bar => {
        bar.style.cursor = 'pointer';
        bar.addEventListener('click', function() {
            const year = this.getAttribute('data-year');
            openModal('turnover', year);
        });
    });
    
    // Eligibility cards
    document.querySelectorAll('.eligibility-card[data-type]').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            openModal('eligibility', type);
        });
    });
    
    // Leave bars
    document.querySelectorAll('.graph-bar[data-type]').forEach(bar => {
        bar.style.cursor = 'pointer';
        bar.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            openModal('leave', type);
        });
    });
}

function openModal(type, id) {
    const modal = document.getElementById('analyticsModal');
    const detailsContainer = document.getElementById('analyticsDetails');
    const descriptionContainer = document.getElementById('analyticsDescription');
    
    // Show loading state
    detailsContainer.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    descriptionContainer.innerHTML = '';
    modal.style.display = 'flex';
    
    // Fetch data
    fetch(`../../backendPHP/get_analytics.php?type=${type}&id=${encodeURIComponent(id)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                displayModalContent(data.data);
            } else {
                detailsContainer.innerHTML = '<p style="color: red;">Error loading data</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching modal data:', error);
            detailsContainer.innerHTML = '<p style="color: red;">Network error</p>';
        });
}

function displayModalContent(data) {
    const detailsContainer = document.getElementById('analyticsDetails');
    const descriptionContainer = document.getElementById('analyticsDescription');
    
    // Update modal title
    const modalTitle = document.querySelector('.modal-header h3');
    if (modalTitle) {
        modalTitle.innerHTML = `<i class="fas fa-chart-bar"></i> ${data.title}`;
    }
    
    // Display details
    if (data.details && data.details.length > 0) {
        let detailsHTML = '<div class="detail-grid">';
        data.details.forEach(detail => {
            detailsHTML += `
                <div class="detail-item">
                    <div class="detail-label">${detail.label}</div>
                    <div class="detail-value">${detail.value}</div>
                </div>
            `;
        });
        detailsHTML += '</div>';
        detailsContainer.innerHTML = detailsHTML;
    }
    
    // Display description
    if (data.description) {
        descriptionContainer.innerHTML = data.description;
    }
}

function exportReport() {
    alert('Export functionality will be implemented. This will generate a PDF or Excel report of the current analytics data.');
    // TODO: Implement actual export functionality
}

// ============= UTILITY FUNCTIONS =============

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatPercentage(value, total) {
    if (total === 0) return '0%';
    return Math.round((value / total) * 100) + '%';
}

// Auto-refresh data every 5 minutes
setInterval(() => {
    console.log('Refreshing analytics data...');
    loadDashboardStats();
    loadEducationalAttainment();
    loadCivilServiceEligibility();
    loadDepartmentData();
    loadLeaveDistribution();
}, 5 * 60 * 1000);