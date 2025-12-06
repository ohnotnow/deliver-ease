<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DeliverEase - Keep Your Customers in the Loop</title>
    <meta name="description" content="Simple delivery notifications for small businesses. Let your customers know when their delivery is on the way.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --color-cream: #FFFBF5;
            --color-cream-dark: #FDF5E8;
            --color-navy: #1E3A5F;
            --color-navy-light: #2C4A6E;
            --color-blue: #4A90C2;
            --color-blue-light: #6BA8D4;
            --color-yellow: #F5B041;
            --color-yellow-light: #F7C56A;
            --color-green: #5DAE60;
            --color-green-light: #7BC47E;
        }

        * {
            font-family: 'Outfit', sans-serif;
        }

        /* Light mode */
        .bg-cream { background-color: var(--color-cream); }
        .bg-cream-dark { background-color: var(--color-cream-dark); }
        .text-navy { color: var(--color-navy); }
        .text-navy-light { color: var(--color-navy-light); }
        .bg-blue { background-color: var(--color-blue); }
        .bg-yellow { background-color: var(--color-yellow); }
        .bg-green { background-color: var(--color-green); }
        .text-blue { color: var(--color-blue); }
        .text-yellow { color: var(--color-yellow); }
        .text-green { color: var(--color-green); }
        .border-blue { border-color: var(--color-blue); }

        /* Hover states */
        .hover\:bg-blue-light:hover { background-color: var(--color-blue-light); }
        .hover\:bg-yellow-light:hover { background-color: var(--color-yellow-light); }

        /* Dark mode overrides */
        .dark .bg-cream { background-color: #1a1f2e; }
        .dark .bg-cream-dark { background-color: #141824; }
        .dark .text-navy { color: #E8EEF4; }
        .dark .text-navy-light { color: #B8C9D9; }
        .dark .text-blue { color: var(--color-blue-light); }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animation-delay-200 { animation-delay: 0.2s; opacity: 0; }
        .animation-delay-400 { animation-delay: 0.4s; opacity: 0; }
        .animation-delay-600 { animation-delay: 0.6s; opacity: 0; }

        /* Card hover effect */
        .step-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(30, 58, 95, 0.15);
        }
        .dark .step-card:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        /* Decorative blob */
        .blob {
            border-radius: 42% 58% 70% 30% / 45% 45% 55% 55%;
            animation: blob-morph 8s ease-in-out infinite;
        }

        @keyframes blob-morph {
            0%, 100% { border-radius: 42% 58% 70% 30% / 45% 45% 55% 55%; }
            50% { border-radius: 58% 42% 30% 70% / 55% 55% 45% 45%; }
        }
    </style>
</head>
<body class="bg-cream text-navy antialiased min-h-screen">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-cream/80 dark:bg-[#1a1f2e]/80 backdrop-blur-md border-b border-navy/5 dark:border-white/5">
        <div class="max-w-6xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-blue rounded-xl flex items-center justify-center shadow-lg shadow-blue/20 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight">DeliverEase</span>
                </a>

                <!-- Right side -->
                <div class="flex items-center gap-4">
                    <!-- Dark mode toggle -->
                    <button @click="darkMode = !darkMode" class="p-2 rounded-lg hover:bg-navy/5 dark:hover:bg-white/5 transition-colors cursor-pointer" aria-label="Toggle dark mode">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-medium hover:text-blue transition-colors">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium hover:text-blue transition-colors">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2.5 bg-blue text-white text-sm font-medium rounded-xl hover:bg-blue-light transition-colors shadow-lg shadow-blue/20 cursor-pointer">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-6 relative overflow-hidden">
        <!-- Decorative background elements -->
        <div class="absolute top-20 left-10 w-72 h-72 bg-yellow/20 blob blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue/20 blob blur-3xl" style="animation-delay: -4s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-green/10 blob blur-3xl" style="animation-delay: -2s;"></div>

        <div class="max-w-6xl mx-auto relative">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Text content -->
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6 animate-fade-in-up">
                        Your customers shouldn't have to wait in
                        <span class="text-blue relative">
                            'just in case'
                            <svg class="absolute -bottom-2 left-0 w-full h-3 text-yellow" viewBox="0 0 200 12" preserveAspectRatio="none">
                                <path d="M0,8 Q50,0 100,8 T200,8" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </h1>

                    <p class="text-lg text-navy-light dark:text-gray-300 mb-8 max-w-xl mx-auto lg:mx-0 animate-fade-in-up animation-delay-200">
                        Small businesses doing their own deliveries can't offer Amazon-style tracking.
                        Your customers don't know when to expect their delivery, so they wait at home all day.
                        <strong class="text-navy dark:text-white">DeliverEase changes that.</strong>
                    </p>

                    <p class="text-lg text-navy-light dark:text-gray-300 mb-10 max-w-xl mx-auto lg:mx-0 animate-fade-in-up animation-delay-400">
                        Send automatic <span class="font-semibold text-green">"heads up"</span> notifications so customers know exactly when to be ready.
                        No app downloads. No complex setup. Just happy customers.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start animate-fade-in-up animation-delay-600">
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-8 py-4 bg-blue text-white font-semibold rounded-2xl hover:bg-blue-light transition-all shadow-xl shadow-blue/25 hover:shadow-blue/35 hover:-translate-y-0.5 cursor-pointer">
                                Start Free Today
                            </a>
                        @endif
                        <a href="#how-it-works" class="px-8 py-4 bg-cream-dark dark:bg-white/10 font-semibold rounded-2xl hover:bg-yellow/20 dark:hover:bg-white/20 transition-all border-2 border-navy/10 dark:border-white/10 cursor-pointer">
                            See How It Works
                        </a>
                    </div>
                </div>

                <!-- Hero image -->
                <div class="relative animate-fade-in-up animation-delay-400">
                    <div class="relative z-10">
                        <picture>
                            <source media="(max-width: 640px)" srcset="/images/splash-mobile.jpg">
                            <img
                                src="/images/splash.jpg"
                                alt="DeliverEase - Friendly delivery notifications for local businesses"
                                class="w-full rounded-3xl shadow-2xl shadow-navy/20 dark:shadow-black/40 animate-float"
                            >
                        </picture>
                    </div>
                    <!-- Decorative frame -->
                    <div class="absolute -inset-4 bg-gradient-to-br from-yellow/30 via-blue/20 to-green/30 rounded-[2rem] -z-10 blur-sm"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-24 px-6 bg-cream-dark dark:bg-[#141824] relative">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1.5 bg-blue/10 text-blue font-medium rounded-full text-sm mb-4">
                    Simple as 1-2-3
                </span>
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">How It Works</h2>
                <p class="text-navy-light dark:text-gray-300 max-w-2xl mx-auto">
                    Get your delivery notifications up and running in minutes, not hours.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="step-card bg-cream dark:bg-[#1a1f2e] rounded-3xl p-8 relative">
                    <div class="w-14 h-14 bg-yellow rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-yellow/25">
                        <span class="text-2xl font-bold text-navy">1</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Create a Run</h3>
                    <p class="text-navy-light dark:text-gray-300">
                        List your delivery stops for the day. Just paste in customer emails and optionally add names or addresses.
                    </p>
                    <!-- Decorative icon -->
                    <div class="absolute top-6 right-6 text-yellow/30">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step-card bg-cream dark:bg-[#1a1f2e] rounded-3xl p-8 relative">
                    <div class="w-14 h-14 bg-blue rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue/25">
                        <span class="text-2xl font-bold text-white">2</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Share with Your Driver</h3>
                    <p class="text-navy-light dark:text-gray-300">
                        Give your driver a simple link and PIN. They get a mobile-friendly checklist to work through.
                    </p>
                    <!-- Decorative icon -->
                    <div class="absolute top-6 right-6 text-blue/30">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step-card bg-cream dark:bg-[#1a1f2e] rounded-3xl p-8 relative">
                    <div class="w-14 h-14 bg-green rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-green/25">
                        <span class="text-2xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Customers Get Notified</h3>
                    <p class="text-navy-light dark:text-gray-300">
                        As your driver progresses, customers automatically receive "you're next" alerts. No more waiting all day!
                    </p>
                    <!-- Decorative icon -->
                    <div class="absolute top-6 right-6 text-green/30">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-24 px-6 bg-cream dark:bg-[#1a1f2e]">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="inline-block px-4 py-1.5 bg-green/10 text-green font-medium rounded-full text-sm mb-4">
                        Why DeliverEase?
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-bold mb-6">
                        Built for small businesses,<br>
                        <span class="text-blue">loved by customers</span>
                    </h2>

                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-yellow/20 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">No more missed deliveries</h3>
                                <p class="text-navy-light dark:text-gray-300 text-sm">
                                    Customers know when to expect you, so they'll actually be home.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-blue/20 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Zero setup for customers</h3>
                                <p class="text-navy-light dark:text-gray-300 text-sm">
                                    No apps to download, no accounts to create. Just email notifications.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-green/20 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Professional, personal touch</h3>
                                <p class="text-navy-light dark:text-gray-300 text-sm">
                                    Stand out from the competition with proactive communication.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual element -->
                <div class="relative">
                    <div class="bg-cream-dark dark:bg-[#141824] rounded-3xl p-8 shadow-xl">
                        <!-- Mock notification -->
                        <div class="bg-cream dark:bg-[#1a1f2e] rounded-2xl p-6 mb-4 border border-navy/5 dark:border-white/5">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-green rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm text-navy-light dark:text-gray-400">New email from Local Bakery</p>
                                    <p class="font-bold mt-1">You're next for delivery!</p>
                                    <p class="text-sm text-navy-light dark:text-gray-300 mt-2">
                                        Hi there! Your fresh bread order is on its way. The driver is just one stop away...
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-cream dark:bg-[#1a1f2e] rounded-2xl p-6 border border-navy/5 dark:border-white/5 opacity-60">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-blue rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm text-navy-light dark:text-gray-400">Earlier today</p>
                                    <p class="font-bold mt-1">One stop away!</p>
                                    <p class="text-sm text-navy-light dark:text-gray-300 mt-2">
                                        Your delivery is nearly there...
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-yellow/30 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-blue/20 rounded-full blur-2xl"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="py-24 px-6 bg-cream-dark dark:bg-[#141824] relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-br from-yellow/20 via-blue/10 to-green/20 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-3xl mx-auto text-center relative">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6">
                Ready to delight<br>your customers?
            </h2>
            <p class="text-lg text-navy-light dark:text-gray-300 mb-10 max-w-xl mx-auto">
                Join local businesses who've stopped playing delivery guessing games. Your customers will thank you.
            </p>

            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-10 py-5 bg-blue text-white font-semibold text-lg rounded-2xl hover:bg-blue-light transition-all shadow-xl shadow-blue/25 hover:shadow-blue/35 hover:-translate-y-1 cursor-pointer">
                    Get Started Free
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            @endif
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 bg-cream dark:bg-[#1a1f2e] border-t border-navy/5 dark:border-white/5">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                        </svg>
                    </div>
                    <span class="font-bold">DeliverEase</span>
                </a>

                <p class="text-sm text-navy-light dark:text-gray-400">
                    Made with <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
</svg>
</span> for local businesses.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
