<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Incidents - AEGIZ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php outputJsConfig(); ?>
    <style>
        .incidents-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .incidents-filters {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-dropdown {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            color: #374151;
            position: relative;
        }

        .filter-dropdown select {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 0.875rem;
            color: #374151;
            outline: none;
        }

        .filter-search {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 200px;
        }

        .filter-search input {
            border: none;
            outline: none;
            font-size: 0.875rem;
            flex: 1;
        }

        .incident-icon {
            width: 24px;
            height: 24px;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .incident-name-cell {
            display: flex;
            align-items: center;
        }

        .reported-by-cell {
            display: flex;
            flex-direction: column;
        }

        .reported-by-name {
            font-weight: 600;
            color: #111827;
            font-size: 0.875rem;
        }

        .reported-by-role {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.125rem;
        }

        .created-date-cell {
            display: flex;
            flex-direction: column;
        }

        .created-date {
            font-weight: 500;
            color: #111827;
            font-size: 0.875rem;
        }

        .created-time {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.125rem;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sort-icon {
            margin-left: 0.5rem;
            cursor: pointer;
            opacity: 0.5;
        }

        .sort-icon:hover {
            opacity: 1;
        }

        .sort-icon.active {
            opacity: 1;
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <h1 class="logo">AEGIZ</h1>
            <span class="page-title">safety incidents</span>
        </div>
        <div class="header-center">
            <div class="search-bar">
                <svg class="search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M7.333 12.667A5.333 5.333 0 1 0 7.333 2a5.333 5.333 0 0 0 0 10.667ZM14 14l-2.9-2.9" stroke="currentColor" stroke-width="1.333" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input type="text" placeholder="Search Returns, Branches" />
                <span class="shortcut-hint">ctrl+/</span>
            </div>
        </div>
        <div class="header-right">
            <div class="notification-icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z" fill="currentColor"/>
                </svg>
                <span class="badge">1</span>
            </div>
            <div class="company-selector">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M3 3h14v14H3V3zm1 1v12h12V4H4z" fill="currentColor"/>
                </svg>
                <span>EUSS</span>
                <select>
                    <option>All branches</option>
                </select>
            </div>
            <div class="user-profile">
                <div class="avatar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                    DM
                </div>
                <div class="user-info">
                    <span class="user-name">Dany Madona</span>
                    <span class="user-role">COO</span>
                </div>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" class="sidebar-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="9 22 9 12 15 12 15 22" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <a href="incidents.php" class="sidebar-icon active">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <rect x="1" y="3" width="22" height="18" rx="2" ry="2" stroke="white" stroke-width="2"/>
                    <path d="M1 9h22" stroke="white" stroke-width="2"/>
                </svg>
            </a>
            <div class="sidebar-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="sidebar-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="incidents-header">
                <div class="incidents-filters">
                    <div class="filter-dropdown">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M3 3h14v14H3V3zm1 1v12h12V4H4z" fill="currentColor"/>
                        </svg>
                        <select id="incidentFilter">
                            <option value="">All Incidents</option>
                            <option value="In progress">In progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="filter-search">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M7.333 12.667A5.333 5.333 0 1 0 7.333 2a5.333 5.333 0 0 0 0 10.667ZM14 14l-2.9-2.9" stroke="currentColor" stroke-width="1.333" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search Audit" />
                    </div>
                    <div class="filter-dropdown">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M2 4h12M2 8h12M2 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <select id="dateFilter">
                            <option value="">This Month</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="openNewIncidentModal()">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="margin-right: 0.5rem;">
                        <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Add Incident
                </button>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <div style="display: flex; align-items: center;">
                                    INCIDENT NAME
                                    <svg class="sort-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" onclick="sortTable('incident_name')">
                                        <path d="M3 4.5L6 1.5L9 4.5M3 7.5L6 10.5L9 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </th>
                            <th>AREA</th>
                            <th>DESCRIPTION</th>
                            <th>REPORTED BY</th>
                            <th>CREATED DATE</th>
                            <th>STATUS</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="incidentsTableBody">
                        <tr>
                            <td colspan="7" class="loading-state">
                                <div class="spinner"></div>
                                <p>Loading incidents...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <div class="pagination-info">
                    <span>Rows per page:</span>
                    <select id="rowsPerPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="pagination-controls">
                    <button id="prevPage" class="pagination-btn" disabled>&lt;</button>
                    <span id="pageInfo">1 / 1</span>
                    <button id="nextPage" class="pagination-btn">&gt;</button>
                </div>
            </div>
        </main>
    </div>

    <!-- New Incident Modal -->
    <div id="newIncidentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" style="margin-right: 0.5rem; color: #3b82f6;">
                        <path d="M10 3v14M3 10h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Report New Incident
                </h2>
                <button class="modal-close" onclick="closeNewIncidentModal()">&times;</button>
            </div>
            <form id="newIncidentForm">
                <div class="form-group">
                    <label>Incident Title*</label>
                    <input type="text" name="incident_name" required placeholder="Fire Safety" />
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Area</label>
                        <select name="area">
                            <option value="">Select Area</option>
                            <option value="Fire">Fire</option>
                            <option value="Safety">Safety</option>
                            <option value="Security">Security</option>
                            <option value="Health">Health</option>
                            <option value="Environment">Environment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Plant</label>
                        <input type="text" name="plant_name" placeholder="Plant Name" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="incident_date_time" />
                    </div>
                    <div class="form-group">
                        <label>Shift</label>
                        <select name="shift">
                            <option value="">Select Shift</option>
                            <option value="Day Shift">Day Shift</option>
                            <option value="Night Shift">Night Shift</option>
                            <option value="Morning Shift">Morning Shift</option>
                            <option value="Evening Shift">Evening Shift</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Whats this audit about?"></textarea>
                </div>
                <div class="form-actions" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <button type="button" class="btn btn-secondary" onclick="closeNewIncidentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Ensure API_BASE_URL is set before incidents.js loads
        // Wait for window.API_CONFIG to be available from outputJsConfig()
        if (typeof window.API_CONFIG !== 'undefined' && window.API_CONFIG.baseUrl) {
            var API_BASE_URL = window.API_CONFIG.baseUrl;
        } else if (typeof API_BASE_URL === 'undefined') {
            var API_BASE_URL = 'http://localhost:8000/api';
        }
        console.log('incidents.php - API_BASE_URL:', API_BASE_URL);
        console.log('incidents.php - window.API_CONFIG:', window.API_CONFIG);
        
        // Make sure API_BASE_URL is available globally
        window.API_BASE_URL = API_BASE_URL;
    </script>
    <script src="assets/js/incidents.js"></script>
</body>
</html>

