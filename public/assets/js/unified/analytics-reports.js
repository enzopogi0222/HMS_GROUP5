const AnalyticsManager = (function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute("content") || "/";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute("content") || "";
    
    let charts = {};
    
    function getElement(id) {
        return document.getElementById(id);
    }
    
    function formatDate(date) {
        const d = new Date(date);
        const pad = (n) => String(n).padStart(2, "0");
        return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
    }
    
    function getDateRange() {
        const dateRangeSelect = getElement("dateRange");
        const range = dateRangeSelect ? dateRangeSelect.value : "month";
        const today = new Date();
        const endDate = formatDate(today);
        let startDate;
        
        switch(range) {
            case "today":
                startDate = endDate;
                break;
            case "week":
                startDate = formatDate(new Date(today.getTime() - 6 * 86400000));
                break;
            case "month":
                startDate = formatDate(new Date(today.getTime() - 29 * 86400000));
                break;
            case "quarter":
                startDate = formatDate(new Date(today.getTime() - 89 * 86400000));
                break;
            case "year":
                startDate = formatDate(new Date(today.getTime() - 364 * 86400000));
                break;
            case "custom":
                startDate = getElement("startDate")?.value || endDate;
                const customEnd = getElement("endDate")?.value || endDate;
                return { start: startDate, end: customEnd };
            default:
                startDate = endDate;
        }
        
        return { start: startDate, end: endDate };
    }
    
    async function loadAnalyticsData() {
        const dateRange = getDateRange();
        
        try {
            const url = baseUrl.replace(/\/$/, "") + "/analytics/api?start_date=" + 
                       encodeURIComponent(dateRange.start) + "&end_date=" + 
                       encodeURIComponent(dateRange.end);
            
            const response = await fetch(url, {
                headers: {
                    "Accept": "application/json"
                }
            });
            
            const data = await response.json();
            
            if (data && data.success) {
                window.analyticsData = data.data || {};
                renderCharts();
                showAnalyticsNotification("Data updated", "success");
            } else {
                showAnalyticsNotification("Failed to load analytics", "error");
            }
        } catch (error) {
            console.error("Error loading analytics:", error);
            showAnalyticsNotification("Error loading analytics", "error");
        }
    }
    
    function createChart(canvasId, config) {
        const canvas = getElement(canvasId);
        if (!canvas) {
            console.warn("Chart canvas not found:", canvasId);
            return;
        }
        
        try {
            // Destroy existing chart if it exists
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }
            
            charts[canvasId] = new Chart(canvas, config);
        } catch (error) {
            console.error("Error creating chart", canvasId, ":", error);
        }
    }
    
    function renderAdminCharts(data) {
        if (!data || typeof data !== 'object') {
            console.warn("No data available for charts");
            return;
        }
        
        // Appointment Trends Chart
        const dailyAppointments = (data.appointment_analytics || {}).daily_appointments || [];
        if (dailyAppointments.length > 0) {
            createChart("appointmentTrendsChart", {
                type: "line",
                data: {
                    labels: dailyAppointments.map(item => item.date || item.date),
                    datasets: [{
                        label: "Appointments",
                        data: dailyAppointments.map(item => Number(item.count) || 0),
                        borderColor: "#6366f1",
                        backgroundColor: "rgba(99, 102, 241, 0.2)",
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            display: false,
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
        
        // Patient Type Chart
        const patientsByType = data.patient_analytics && data.patient_analytics.patients_by_type || [];
        if (patientsByType.length > 0) {
            createChart("patientTypeChart", {
            type: "doughnut",
            data: {
                labels: patientsByType.map(item => item.patient_type || "Unknown"),
                datasets: [{
                    data: patientsByType.map(item => Number(item.count) || 0),
                    backgroundColor: [
                        "#1d4ed8",
                        "#0ea5e9",
                        "#22c55e",
                        "#f59e0b",
                        "#ef4444",
                        "#8b5cf6"
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: "bottom"
                    }
                }
            });
        }
        
        // Appointment Status Chart
        const appointmentsByStatus = (data.appointment_analytics || {}).appointments_by_status || [];
        if (appointmentsByStatus.length > 0) {
            createChart("appointmentStatusChart", {
                type: "bar",
                data: {
                    labels: appointmentsByStatus.map(item => item.status || "Unknown"),
                    datasets: [{
                        label: "Count",
                        data: appointmentsByStatus.map(item => Number(item.count) || 0),
                        backgroundColor: "#a78bfa"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            display: false,
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Revenue Trend Chart
        const revenueByMonth = (data.financial_analytics || {}).revenue_by_month || [];
        if (revenueByMonth.length > 0) {
            // Prepare expense data by month (simplified - you may need to adjust based on your data structure)
            const expenseMonths = revenueByMonth.map(item => item.month);
            const expenseData = expenseMonths.map(() => {
                // This is a placeholder - adjust based on your actual expense data structure
                return (data.financial_analytics || {}).total_expenses / (revenueByMonth.length || 1);
            });

            createChart("revenueTrendChart", {
                type: "line",
                data: {
                    labels: revenueByMonth.map(item => {
                        // Format month label (e.g., "2024-01" -> "Jan 2024")
                        if (item.month) {
                            const [year, month] = item.month.split("-");
                            const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                            return monthNames[parseInt(month) - 1] + " " + year;
                        }
                        return item.month || "Unknown";
                    }),
                    datasets: [
                        {
                            label: "Revenue",
                            data: revenueByMonth.map(item => Number(item.revenue) || 0),
                            borderColor: "#10b981",
                            backgroundColor: "rgba(16, 185, 129, 0.1)",
                            tension: 0.4,
                            fill: true,
                            yAxisID: "y"
                        },
                        {
                            label: "Expenses",
                            data: expenseData,
                            borderColor: "#ef4444",
                            backgroundColor: "rgba(239, 68, 68, 0.1)",
                            tension: 0.4,
                            fill: true,
                            yAxisID: "y"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: "index",
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: "top",
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || "";
                                    if (label) {
                                        label += ": ";
                                    }
                                    if (context.parsed.y !== null) {
                                        label += "₱" + Number(context.parsed.y).toLocaleString("en-US", {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return "₱" + Number(value).toLocaleString();
                                }
                            },
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Expenses by Category Chart
        const expensesByCategory = (data.financial_analytics || {}).expenses_by_category || [];
        if (expensesByCategory.length > 0) {
            createChart("expensesChart", {
                type: "doughnut",
                data: {
                    labels: expensesByCategory.map(item => item.category || "Unknown"),
                datasets: [{
                    data: expensesByCategory.map(item => Number(item.total) || 0),
                    backgroundColor: [
                        "#ef4444",
                        "#f59e0b",
                        "#eab308",
                        "#84cc16",
                        "#22c55e",
                        "#10b981",
                        "#14b8a6",
                        "#06b6d4",
                        "#3b82f6",
                        "#6366f1",
                        "#8b5cf6",
                        "#a855f7"
                    ],
                    borderWidth: 2,
                    borderColor: "#ffffff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: "bottom",
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || "";
                                if (label) {
                                    label += ": ";
                                }
                                if (context.parsed !== null) {
                                    label += "₱" + Number(context.parsed).toLocaleString("en-US", {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                                return label;
                            }
                        }
                    }
                }
            });
        }

        // Payment Methods Chart
        const revenueByPaymentMethod = (data.financial_analytics || {}).revenue_by_payment_method || [];
        if (revenueByPaymentMethod.length > 0) {
            createChart("paymentMethodsChart", {
                type: "pie",
                data: {
                    labels: revenueByPaymentMethod.map(item => item.payment_method || "Unknown"),
                    datasets: [{
                        data: revenueByPaymentMethod.map(item => Number(item.total) || 0),
                        backgroundColor: [
                            "#10b981",
                            "#3b82f6",
                            "#8b5cf6",
                            "#f59e0b",
                            "#ef4444",
                            "#06b6d4"
                        ],
                        borderWidth: 2,
                        borderColor: "#ffffff"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: "bottom",
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || "";
                                    if (label) {
                                        label += ": ";
                                    }
                                    if (context.parsed !== null) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        label += "₱" + Number(context.parsed).toLocaleString("en-US", {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }) + " (" + percentage + "%)";
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // Hide chart container if no data
            const paymentChartContainer = getElement("paymentMethodsChart");
            if (paymentChartContainer && paymentChartContainer.closest(".chart-container")) {
                paymentChartContainer.closest(".chart-container").style.display = "none";
            }
        }

        // Patient Age Distribution Chart
        const patientsByAge = (data.patient_analytics || {}).patients_by_age || [];
        if (patientsByAge.length > 0) {
            createChart("patientAgeChart", {
                type: "bar",
                data: {
                    labels: patientsByAge.map(item => item.age_group || "Unknown"),
                    datasets: [{
                        label: "Patients",
                        data: patientsByAge.map(item => Number(item.count) || 0),
                        backgroundColor: "#3b82f6",
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Appointment Types Chart
        const appointmentsByType = (data.appointment_analytics || {}).appointments_by_type || [];
        if (appointmentsByType.length > 0) {
            createChart("appointmentTypeChart", {
                type: "doughnut",
                data: {
                    labels: appointmentsByType.map(item => item.appointment_type || "Unknown"),
                    datasets: [{
                        data: appointmentsByType.map(item => Number(item.count) || 0),
                        backgroundColor: [
                            "#6366f1",
                            "#8b5cf6",
                            "#a855f7",
                            "#d946ef",
                            "#ec4899",
                            "#f43f5e"
                        ],
                        borderWidth: 2,
                        borderColor: "#ffffff"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: "bottom",
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Staff by Role Chart
        const staffByRole = (data.staff_analytics || {}).staff_by_role || [];
        if (staffByRole.length > 0) {
            createChart("staffRoleChart", {
                type: "pie",
                data: {
                    labels: staffByRole.map(item => {
                        const role = (item.role || "Unknown").replace(/_/g, " ");
                        return role.charAt(0).toUpperCase() + role.slice(1);
                    }),
                    datasets: [{
                        data: staffByRole.map(item => Number(item.count) || 0),
                        backgroundColor: [
                            "#f59e0b",
                            "#3b82f6",
                            "#ef4444",
                            "#10b981",
                            "#8b5cf6",
                            "#06b6d4",
                            "#f472b6",
                            "#84cc16"
                        ],
                        borderWidth: 2,
                        borderColor: "#ffffff"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: "bottom",
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || "";
                                    if (label) {
                                        label += ": ";
                                    }
                                    if (context.parsed !== null) {
                                        label += context.parsed + " staff";
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Lab Tests by Category Chart
        const labOrdersByCategory = (data.lab_analytics || {}).orders_by_category || [];
        if (labOrdersByCategory.length > 0) {
            createChart("labCategoryChart", {
                type: "bar",
                data: {
                    labels: labOrdersByCategory.map(item => item.category || "Unknown"),
                    datasets: [{
                        label: "Orders",
                        data: labOrdersByCategory.map(item => Number(item.count) || 0),
                        backgroundColor: "#06b6d4",
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Prescription Status Chart
        const prescriptionsByStatus = (data.prescription_analytics || {}).prescriptions_by_status || [];
        if (prescriptionsByStatus.length > 0) {
            createChart("prescriptionStatusChart", {
                type: "doughnut",
                data: {
                    labels: prescriptionsByStatus.map(item => (item.status || "Unknown").charAt(0).toUpperCase() + (item.status || "Unknown").slice(1)),
                    datasets: [{
                        data: prescriptionsByStatus.map(item => Number(item.count) || 0),
                        backgroundColor: [
                            "#10b981",
                            "#f59e0b",
                            "#ef4444",
                            "#6366f1",
                            "#8b5cf6"
                        ],
                        borderWidth: 2,
                        borderColor: "#ffffff"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: "bottom",
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Resources by Category Chart
        const resourcesByCategory = (data.resource_analytics || {}).resources_by_category || [];
        if (resourcesByCategory.length > 0) {
            createChart("resourceCategoryChart", {
                type: "bar",
                data: {
                    labels: resourcesByCategory.map(item => item.category || "Unknown"),
                    datasets: [{
                        label: "Resources",
                        data: resourcesByCategory.map(item => Number(item.count) || 0),
                        backgroundColor: "#f59e0b",
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Room Type Chart
        const roomsByType = (data.room_analytics || {}).rooms_by_type || [];
        if (roomsByType.length > 0) {
            createChart("roomTypeChart", {
                type: "bar",
                data: {
                    labels: roomsByType.map(item => item.room_type || "Unknown"),
                    datasets: [{
                        label: "Rooms",
                        data: roomsByType.map(item => Number(item.count) || 0),
                        backgroundColor: "#8b5cf6",
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Peak Hours Chart
        const peakHours = (data.appointment_analytics || {}).peak_hours || [];
        if (peakHours.length > 0) {
            createChart("peakHoursChart", {
                type: "bar",
                data: {
                    labels: peakHours.map(item => {
                        const hour = Number(item.hour) || 0;
                        return hour + ":00";
                    }),
                    datasets: [{
                        label: "Appointments",
                        data: peakHours.map(item => Number(item.count) || 0),
                        backgroundColor: "#6366f1",
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }
    
    function renderDoctorCharts(data) {
        const myAppointments = data.my_appointments || {};
        
        createChart("doctorAppointmentsChart", {
            type: "bar",
            data: {
                labels: ["Total", "Completed"],
                datasets: [{
                    label: "Appointments",
                    data: [
                        Number(myAppointments.total_appointments) || 0,
                        Number(myAppointments.completed_appointments) || 0
                    ],
                    backgroundColor: ["#6366f1", "#10b981"]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        display: false,
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        const myPatients = data.my_patients || {};
        createChart("patientGrowthChart", {
            type: "line",
            data: {
                labels: ["-5", "-4", "-3", "-2", "-1", "Now"],
                datasets: [{
                    label: "Patients",
                    data: [0, 0, 0, 0, 0, Number(myPatients.total_patients) || 0],
                    borderColor: "#f59e0b",
                    backgroundColor: "rgba(245, 158, 11, 0.2)",
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        display: false,
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    function renderCharts() {
        const data = window.analyticsData || {};
        
        // Wait for Chart.js to be available
        if (typeof Chart === 'undefined') {
            console.warn("Chart.js is not loaded yet, retrying...");
            setTimeout(renderCharts, 100);
            return;
        }
        
        // Check if we have any data
        if (!data || Object.keys(data).length === 0) {
            console.warn("No analytics data available for charts");
            return;
        }
        
        try {
            if (userRole === "admin" || userRole === "accountant" || userRole === "it_staff") {
                renderAdminCharts(data);
            } else if (userRole === "doctor") {
                renderDoctorCharts(data);
            } else if (userRole === "nurse") {
                // Add nurse charts if needed
                console.log("Nurse charts not yet implemented");
            } else if (userRole === "receptionist") {
                // Add receptionist charts if needed
                console.log("Receptionist charts not yet implemented");
            }
        } catch (error) {
            console.error("Error rendering charts:", error);
            console.error("Error details:", error.stack);
        }
    }
    
    function downloadFile(filename, content) {
        const element = document.createElement("a");
        element.setAttribute("href", "data:application/json;charset=utf-8," + encodeURIComponent(content));
        element.setAttribute("download", filename);
        element.style.display = "none";
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }
    
    async function exportData() {
        const data = window.analyticsData || {};
        downloadFile("analytics-" + Date.now() + ".json", JSON.stringify(data, null, 2));
    }
    
    async function generateReport() {
        const reportModal = getElement("reportModal");
        const reportType = getElement("reportType");
        
        if (!reportType || !reportType.value) {
            showAnalyticsNotification("Please select a report type", "error");
            return;
        }
        
        const reportDateRange = getElement("reportDateRange");
        const reportFormat = getElement("reportFormat");
        
        const today = new Date();
        const endDate = formatDate(today);
        let startDate;
        const range = reportDateRange ? reportDateRange.value : "month";
        
        switch(range) {
            case "week":
                startDate = formatDate(new Date(today.getTime() - 6 * 86400000));
                break;
            case "month":
                startDate = formatDate(new Date(today.getTime() - 29 * 86400000));
                break;
            case "quarter":
                startDate = formatDate(new Date(today.getTime() - 89 * 86400000));
                break;
            case "year":
                startDate = formatDate(new Date(today.getTime() - 364 * 86400000));
                break;
            default:
                startDate = endDate;
        }
        
        const payload = {
            report_type: reportType.value,
            filters: {
                start_date: startDate,
                end_date: endDate
            }
        };
        
        try {
            const response = await fetch(baseUrl.replace(/\/$/, "") + "/analytics/report/generate", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();
            
            if (result && result.success) {
                showAnalyticsNotification("Report generated successfully", "success");
                
                const filename = "report-" + payload.report_type + "-" + Date.now() + ".json";
                if (result.report) {
                    downloadFile(filename, JSON.stringify(result.report, null, 2));
                }
                
                if (reportModal) {
                    reportModal.style.display = "none";
                }
            } else {
                showAnalyticsNotification(result.message || "Failed to generate report", "error");
            }
        } catch (error) {
            console.error("Error generating report:", error);
            showAnalyticsNotification("Error generating report", "error");
        }
    }
    
    function initDateRangeHandler() {
        const dateRangeSelect = getElement("dateRange");
        const customDateGroup = getElement("customDateGroup");
        const customDateGroup2 = getElement("customDateGroup2");
        
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener("change", function() {
                const isCustom = this.value === "custom";
                if (customDateGroup) {
                    customDateGroup.style.display = isCustom ? "block" : "none";
                }
                if (customDateGroup2) {
                    customDateGroup2.style.display = isCustom ? "block" : "none";
                }
            });
        }
    }
    
    // Initialize on DOM ready
    document.addEventListener("DOMContentLoaded", function() {
        initDateRangeHandler();
        
        // Check if we have data already (from PHP)
        if (!window.analyticsData || Object.keys(window.analyticsData || {}).length === 0) {
            // No data yet, load it
            loadAnalyticsData();
        } else {
            // We have data, render charts when Chart.js is ready
            if (typeof Chart !== 'undefined') {
                renderCharts();
            } else {
                // Retry after a short delay
                setTimeout(function() {
                    if (typeof Chart !== 'undefined') {
                        renderCharts();
                    } else {
                        console.error("Chart.js failed to load");
                    }
                }, 500);
            }
        }
    });
    
    // Public API
    return {
        openReportModal: function() {
            const modal = getElement("reportModal");
            if (modal) {
                modal.style.display = "block";
            }
        },
        
        closeReportModal: function() {
            const modal = getElement("reportModal");
            if (modal) {
                modal.style.display = "none";
            }
        },
        
        applyFilters: function() {
            loadAnalyticsData();
        },
        
        refreshData: function() {
            loadAnalyticsData();
        },
        
        renderCharts: function() {
            renderCharts();
        },
        
        exportData: exportData,
        
        generateReport: generateReport
    };
})();

// Make AnalyticsManager globally available
window.AnalyticsManager = AnalyticsManager;
