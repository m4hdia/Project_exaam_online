
<!-- templates/base.html -->
<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Examens - Exam4U</title>
    
    <link rel="stylesheet" href="/static/css/tailwind.css">
    
    <style>
        /* Custom styles for navigation */
        @media screen and (max-width: 1023px) {
            .desktop-nav {
                display: none;
            }
            .mobile-menu-button {
                display: block;
            }
        }

        @media screen and (min-width: 1024px) {
            .desktop-nav {
                display: flex;
            }
            .mobile-menu-button {
                display: none;
            }
            .mobile-menu {
                display: none !important;
            }
        }

        .nav-link {
            color: var(--text-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(59, 130, 246, 0.1);
            color: rgb(37, 99, 235);
        }

        .nav-link.active {
            color: rgb(37, 99, 235);
        }
    </style>
</head>
<body class="min-h-screen theme-transition bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
    <!-- Navigation -->
    <nav class="fixed w-full z-50 theme-transition bg-white/90 dark:bg-gray-800/90 backdrop-blur-lg border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                
                    <a href="/dashboard/student/" class="text-2xl font-bold tracking-tight bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent hover:opacity-80 transition-opacity duration-300">
                        Exam4U
                    </a>
                

                <!-- Navigation Links (Desktop) -->
                <div class="desktop-nav items-center space-x-8">
                    
                </div>

                <!-- Right Side -->
                <div class="flex items-center">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-400 transition-all duration-300" aria-label="Toggle theme">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    <!-- Auth Buttons (Desktop) -->
                    <div class="desktop-nav items-center space-x-4 ml-6">
                        
                            <a href="/auth/logout/" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-400">
                                Se déconnecter
                            </a>
                        
                    </div>

                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-button" class="mobile-menu-button p-2 ml-4 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-400" aria-label="Toggle mobile menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path class="mobile-menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path class="mobile-menu-close hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="lg:hidden hidden theme-transition bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="container mx-auto px-4 py-4 space-y-4">
                

                
                    <!-- User Profile Section -->
                    <div class="flex items-center space-x-3 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center">
                            <span class="text-white text-lg font-semibold">D</span>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">MEHDI AZOU</h2>
                            <p class="text-xs bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent font-semibold">
                                
                                    Étudiant
                                
                            </p>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <div class="space-y-2">
                        
                            <!-- Student Navigation -->
                            <a href="/dashboard/student/" 
                               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300 text-gray-600 dark:text-gray-400">
                                <div class="w-5 h-5 mr-3 text-gray-600 dark:text-gray-400">
                                    






















<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
</svg>
























































 
                                </div>
                                <span>Tableau de bord</span>
                            </a>
                            <a href="/courses/student/" 
                               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300 text-gray-600 dark:text-gray-400">
                                <div class="w-5 h-5 mr-3 text-gray-600 dark:text-gray-400">
                                    


<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
</svg>












































































 
                                </div>
                                <span>Mes Cours</span>
                            </a>
                            <a href="/exams/student/list/" 
                               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300 text-blue-600 dark:text-blue-400">
                                <div class="w-5 h-5 mr-3 text-gray-600 dark:text-gray-400">
                                    










<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
</svg>




































































 
                                </div>
                                <span>Examens</span>
                            </a>
                            <a href="/student/quizzes/" 
                               class="flex items-center px-3 py-2 text-sm font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300 text-gray-600 dark:text-gray-400">
                                <div class="w-5 h-5 mr-3 text-gray-600 dark:text-gray-400">
                                    














































































<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
</svg>
 
                                </div>
                                <span>Quiz</span>
                            </a>
                        
                    </div>
                
                
                <!-- Auth Buttons -->
                <div class="flex flex-col space-y-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    
                        <a href="/auth/logout/" class="w-full text-center px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300">
                            Se déconnecter
                        </a>
                    
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-24">
        
<div class="flex min-h-screen bg-white dark:bg-gray-900">
    <!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden" aria-hidden="true"></div>

<!-- Student Sidebar -->
<div class="student-sidebar hidden md:flex md:flex-col w-64 min-h-screen bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border-r border-gray-200 dark:border-gray-700 relative">
    <div class="absolute inset-0 bg-gradient-to-b from-blue-500/5 to-purple-500/5 pointer-events-none"></div>
    
    <!-- Profile Section -->
    <div class="relative p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center shadow-lg transform hover:scale-105 transition-transform duration-300">
                <span class="text-white text-lg font-semibold">D</span>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">MEHDI AZOU</h2>
                <p class="text-xs bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent font-semibold">Étudiant</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="relative flex-1 px-3 py-4">
        <div class="space-y-1">
            <!-- Dashboard -->
            <a href="/dashboard/student/" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-300 text-gray-600 dark:text-gray-400 hover:bg-gradient-to-r hover:from-blue-600/5 hover:to-purple-600/5 hover:text-blue-600 dark:hover:text-blue-400">
                <div class="w-5 h-5 mr-3">
                    






















<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
</svg>
























































 
                </div>
                <span>Tableau de bord</span>
            </a>

            <!-- My Courses -->
            <a href="/courses/student/"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-300 text-gray-600 dark:text-gray-400 hover:bg-gradient-to-r hover:from-blue-600/5 hover:to-purple-600/5 hover:text-blue-600 dark:hover:text-blue-400">
                <div class="w-5 h-5 mr-3">
                    


<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
</svg>












































































 
                </div>
                <span>Mes Cours</span>
            </a>

            <!-- Exams -->
            <a href="/exams/student/list/"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-300 bg-gradient-to-r from-blue-600/10 to-purple-600/10 text-blue-600 dark:text-blue-400">
                <div class="w-5 h-5 mr-3">
                    










<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
</svg>




































































 
                </div>
                <span>Examens</span>
            </a>

            <!-- Quizzes -->
            <a href="/student/quizzes/"
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-xl transition-all duration-300 text-gray-600 dark:text-gray-400 hover:bg-gradient-to-r hover:from-blue-600/5 hover:to-purple-600/5 hover:text-blue-600 dark:hover:text-blue-400">
                <div class="w-5 h-5 mr-3">
                    














































































<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
</svg>
 
                </div>
                <span>Quiz</span>
            </a>
        </div>
    </nav>
</div>

    <div class="flex-1 p-4 md:p-8 space-y-6 md:space-y-8">
        <!-- Title Section -->
        <div class="relative overflow-hidden bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-gradient-to-br from-blue-500/20 to-purple-500/20 dark:from-blue-400/10 dark:to-purple-400/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-gradient-to-tr from-purple-500/20 to-pink-500/20 dark:from-purple-400/10 dark:to-pink-400/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
            <div class="relative z-10 p-6 md:p-8">
                <div class="flex items-center space-x-4">
                    <div class="p-3 rounded-full bg-gradient-to-br from-blue-500/10 to-purple-500/10 dark:from-blue-400/5 dark:to-purple-400/5">
                        
















































<svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
</svg>






























 
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent">Mes Examens</h1>
                        <p class="mt-2 text-base md:text-lg text-gray-600 dark:text-gray-400">Parcourez et passez vos examens</p>
                    </div>
                </div>
            </div>
        </div>

        

        <!-- Exams List Section -->
        <div class="space-y-6">
            <!-- Exam Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                
                    <div class="group bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-300">
                        <div class="p-4 md:p-6 space-y-4">
                            <!-- Exam Title -->
                            <div class="flex items-center space-x-3">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-blue-500/10 to-purple-500/10 dark:from-blue-400/5 dark:to-purple-400/5">
                                    
























































<svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
</svg>






















 
                                </div>
                                <h3 class="text-lg font-semibold bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent">Laravel (Contrôle 1)</h3>
                            </div>

                            <!-- Exam Info -->
                            <div class="grid grid-cols-1 gap-3">
                                <div class="flex items-center p-2 space-x-3 bg-gray-50/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-lg hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors">
                                    <div class="p-2 rounded-lg bg-gradient-to-br from-blue-500/10 to-purple-500/10 dark:from-blue-400/5 dark:to-purple-400/5">
                                        


























































<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
</svg>




















 
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Durée: 120 minutes</span>
                                </div>
                                <div class="flex items-center p-2 space-x-3 bg-gray-50/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-lg hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors">
                                    <div class="p-2 rounded-lg bg-gradient-to-br from-blue-500/10 to-purple-500/10 dark:from-blue-400/5 dark:to-purple-400/5">
                                        






















































<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
</svg>
























 
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400 truncate">Développement back-end</span>
                                </div>
                            </div>

                            <!-- Take Exam Button -->
                            <div class="pt-4 mt-2 border-t border-gray-200 dark:border-gray-700">
                                <a href="/exams/student/1/rules/"
                                   class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 space-x-2">
                                    <div class="p-2 rounded-lg bg-white/10 transform group-hover:scale-110 transition-transform">
                                        














































<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
</svg>
































 
                                    </div>
                                    <span class="font-medium">Commencer l'examen</span>
                                </a>
                            </div>
                        </div>
                    </div>
                
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

.animate-pulse {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.delay-1000 {
    animation-delay: 1s;
}
</style>

    </main>

    <!-- Footer -->
    <footer class="theme-transition bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="flex items-center space-x-2">
                    <span class="font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Exam4U</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">© 2024 Tous droits réservés</span>
                </div>
                <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-8">
                    <a href="#" class="nav-link text-sm text-center md:text-left">Politique de confidentialité</a>
                    <a href="#" class="nav-link text-sm text-center md:text-left">Conditions d'utilisation</a>
                    <a href="#" class="nav-link text-sm text-center md:text-left">Aide</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme Toggle Logic
            const themeToggle = document.getElementById('theme-toggle');
            const html = document.documentElement;
            
            // Check system preference and saved theme
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
            const savedTheme = localStorage.getItem('theme');
            
            // Set initial theme
            if (savedTheme) {
                setTheme(savedTheme);
            } else {
                setTheme(prefersDark.matches ? 'dark' : 'light');
            }

            // Listen for system theme changes
            prefersDark.addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });

            // Handle theme toggle click
            themeToggle.addEventListener('click', function() {
                const isDark = html.classList.contains('dark');
                const newTheme = isDark ? 'light' : 'dark';
                setTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });

            function setTheme(theme) {
                const isDark = theme === 'dark';
                html.classList.toggle('dark', isDark);
                
                // Update icon
                const sunPath = "M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z";
                const moonPath = "M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z";
                
                const iconPath = themeToggle.querySelector('path');
                iconPath.setAttribute('d', isDark ? moonPath : sunPath);
                
                // Update aria-label
                themeToggle.setAttribute('aria-label', isDark ? 'Activer le mode clair' : 'Activer le mode sombre');
            }

            // Mobile Menu Toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const menuIcon = mobileMenuButton.querySelector('.mobile-menu-icon');
            const closeIcon = mobileMenuButton.querySelector('.mobile-menu-close');

            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
                menuIcon.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
            });

            // Close mobile menu when clicking on a link
            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                });
            });

            // Close mobile menu on window resize (if screen becomes larger)
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) { // md breakpoint
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                }
            });
        });
    </script>

    
</body>
</html>