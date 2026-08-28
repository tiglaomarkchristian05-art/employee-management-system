/**
 * Moses Group of Companies - Overseas Manpower & Recruitment Agency Chart.js Visualizations
 */

document.addEventListener('DOMContentLoaded', function () {
    const textColor = '#223344';
    const gridColor = '#E2EEF4';

    // 1. Executive Dashboard Overseas Deployment Trend Chart
    const trainingTrendCtx = document.getElementById('trainingTrendChart');
    if (trainingTrendCtx) {
        new Chart(trainingTrendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                datasets: [{
                    label: 'Overseas Deployment Rate (%)',
                    data: [82, 88, 91, 85, 94, 96, 92, 98],
                    borderColor: '#2B7A9E',
                    backgroundColor: 'rgba(43, 122, 158, 0.12)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'PDOS & Trade Assessment Pass Rate',
                    data: [78, 82, 85, 80, 89, 91, 88, 94],
                    borderColor: '#3498DB',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: textColor, font: { family: 'Plus Jakarta Sans', weight: '600' } } } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: { ticks: { color: textColor }, grid: { color: gridColor } }
                }
            }
        });
    }

    // 2. Candidate Skills Distribution Radar Chart
    const skillsRadarCtx = document.getElementById('skillsRadarChart');
    if (skillsRadarCtx) {
        new Chart(skillsRadarCtx, {
            type: 'radar',
            data: {
                labels: ['Patient Nursing', '6G Pipe Welding', 'DMW Compliance', 'Language Skills', 'Heavy Machinery', 'Hospitality'],
                datasets: [{
                    label: 'Current Candidate Competency',
                    data: [85, 75, 90, 70, 65, 80],
                    backgroundColor: 'rgba(81, 69, 229, 0.16)',
                    borderColor: '#5145E5',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#5145E5',
                    pointBorderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5
                }, {
                    label: 'Foreign Principal Target Benchmark',
                    data: [90, 85, 95, 85, 80, 85],
                    backgroundColor: 'rgba(16, 185, 129, 0.10)',
                    borderColor: '#10B981',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#10B981',
                    pointBorderWidth: 2,
                    pointRadius: 3.5,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 4, right: 12, bottom: 8, left: 12 } },
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        labels: {
                            color: '#334155',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 14,
                            font: { family: 'Plus Jakarta Sans', size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#111827',
                        titleColor: '#FFFFFF',
                        bodyColor: '#E2E8F0',
                        padding: 11,
                        cornerRadius: 9,
                        displayColors: true
                    }
                },
                scales: {
                    r: {
                        min: 60,
                        max: 100,
                        beginAtZero: false,
                        angleLines: { color: '#E2E8F0', lineWidth: 1 },
                        grid: { color: '#E2E8F0', circular: false },
                        pointLabels: { color: '#475569', padding: 12, font: { family: 'Plus Jakarta Sans', size: 10, weight: '600' } },
                        ticks: { stepSize: 10, showLabelBackdrop: false, color: '#94A3B8', font: { size: 8 } }
                    }
                }
            }
        });
    }

    // 3. Overseas Country Destinations Doughnut Chart
    const govContribCtx = document.getElementById('govContribChart');
    if (govContribCtx) {
        new Chart(govContribCtx, {
            type: 'doughnut',
            data: {
                labels: ['UAE / Dubai', 'Saudi Arabia', 'Japan', 'Canada & Others'],
                datasets: [{
                    data: [450, 380, 320, 270],
                    backgroundColor: ['#2B7A9E', '#2EAF6B', '#F4A62A', '#E74C3C']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { color: textColor, font: { family: 'Plus Jakarta Sans', weight: '600' } } } }
            }
        });
    }

    // 4. Overseas Benefits Utilization Bar Chart
    const benefitsBarCtx = document.getElementById('benefitsBarChart');
    if (benefitsBarCtx) {
        new Chart(benefitsBarCtx, {
            type: 'bar',
            data: {
                labels: ['Compulsory Insurance', 'OWWA Fund', 'Deployment Allowance', 'Trade Test Support', 'Performance Bonus'],
                datasets: [{
                    label: 'Enrolled Candidates',
                    data: [142, 98, 165, 85, 120],
                    backgroundColor: '#3F8FB5',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: textColor, font: { family: 'Plus Jakarta Sans', weight: '600' } } } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { display: false } },
                    y: { ticks: { color: textColor }, grid: { color: gridColor } }
                }
            }
        });
    }

    // 5. Monthly Deployment & Repatriation Rate Chart
    const separationCtx = document.getElementById('separationChart');
    if (separationCtx) {
        new Chart(separationCtx, {
            type: 'bar',
            data: {
                labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                datasets: [{
                    label: 'Contract Expirations & Returns',
                    data: [3, 2, 1, 4],
                    backgroundColor: '#F4A62A',
                    borderRadius: 4
                }, {
                    label: 'Successful Repatriations',
                    data: [1, 0, 1, 1],
                    backgroundColor: '#2EAF6B',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: textColor, font: { family: 'Plus Jakarta Sans', weight: '600' } } } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { display: false } },
                    y: { ticks: { color: textColor }, grid: { color: gridColor } }
                }
            }
        });
    }
});
