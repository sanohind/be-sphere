<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Be-Sphere Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Be-Sphere</h1>
                        <span class="ml-2 text-sm text-gray-500">SSO Portal</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900" id="user-name">Loading...</p>
                            <p class="text-xs text-gray-500" id="user-role">Loading...</p>
                        </div>
                        <button onclick="logout()" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome to Be-Sphere</h2>
                <p class="text-gray-600">Select a project to access your applications</p>
            </div>

            <!-- Projects Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="projects-grid">
                <!-- Projects will be loaded here -->
            </div>
        </main>
    </div>

    <script>
        let userData = null;
        let projects = [];

        // Load dashboard data
        async function loadDashboard() {
            try {
                const response = await fetch('/api/dashboard', {
                    headers: {
                        'Authorization': 'Bearer ' + getToken(),
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load dashboard');
                }

                const data = await response.json();
                userData = data.data.user;
                projects = data.data.projects;

                updateUserInfo();
                renderProjects();
            } catch (error) {
                console.error('Error loading dashboard:', error);
                alert('Failed to load dashboard. Please try again.');
            }
        }

        // Update user info in header
        function updateUserInfo() {
            document.getElementById('user-name').textContent = userData.name;
            document.getElementById('user-role').textContent = userData.role.name;
        }

        // Render projects grid
        function renderProjects() {
            const grid = document.getElementById('projects-grid');
            
            if (projects.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-900 mb-2">No Projects Available</h3>
                        <p class="text-gray-500">You don't have access to any projects yet.</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = projects.map(project => `
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer" onclick="accessProject('${project.id}')">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-${project.color}-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-${project.icon} text-${project.color}-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">${project.name}</h3>
                                <p class="text-sm text-gray-500">${project.description}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-2">
                                ${project.permissions.map(permission => `
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        ${permission}
                                    </span>
                                `).join('')}
                            </div>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Access project
        async function accessProject(projectId) {
            try {
                const response = await fetch(`/api/dashboard/project/${projectId}/url`, {
                    headers: {
                        'Authorization': 'Bearer ' + getToken(),
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to get project URL');
                }

                const data = await response.json();
                window.open(data.data.url, '_blank');
            } catch (error) {
                console.error('Error accessing project:', error);
                alert('Failed to access project. Please try again.');
            }
        }

        // Logout
        async function logout() {
            try {
                await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + getToken(),
                        'Accept': 'application/json',
                    }
                });
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                localStorage.removeItem('token');
                window.location.href = '/login';
            }
        }

        // Get token from localStorage
        function getToken() {
            return localStorage.getItem('token');
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            if (!getToken()) {
                window.location.href = '/login';
                return;
            }
            loadDashboard();
        });
    </script>
</body>
</html>
