<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        @livewireStyles
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
<div class="flex min-h-screen">
    <div class="flex-1 flex justify-center items-center">
        <div class="w-80 max-w-80 space-y-6">
            <img src="/images/splash-mobile.jpg" alt="DeliverEase" class="rounded-lg lg:hidden" />

            <flux:heading class="text-center hidden lg:block" size="xl">DeliverEase</flux:heading>

            <flux:heading class="text-center" size="lg">Welcome back</flux:heading>

            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-6">
                @csrf

                <flux:input
                    name="email"
                    label="Email"
                    type="email"
                    placeholder="email@example.com"
                    value="{{ old('email') }}"
                    :invalid="$errors->has('email')"
                />

                @error('email')
                    <flux:text color="red" class="-mt-4">{{ $message }}</flux:text>
                @enderror

                <flux:input
                    name="password"
                    label="Password"
                    type="password"
                    placeholder="Your password"
                    :invalid="$errors->has('password')"
                />

                <flux:checkbox name="remember" label="Remember me for 30 days" />

                <flux:button type="submit" variant="primary" class="w-full">Log in</flux:button>
            </form>
        </div>
    </div>

    <div class="flex-1 p-4 max-lg:hidden">
        <div class="relative rounded-lg h-full w-full overflow-hidden flex flex-col items-start justify-end p-16">
            <img src="/images/splash.jpg" alt="" class="absolute inset-0 w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
            <div class="relative text-white">
                <div class="mb-6 text-3xl xl:text-4xl font-semibold">
                    Keep your customers informed about their deliveries
                </div>

                <div class="text-lg text-white/80">
                    DeliverEase helps small businesses notify customers when their delivery is on the way.
                </div>
            </div>
        </div>
    </div>
</div>
        @fluxScripts
        @stack('scripts')
    </body>
</html>
