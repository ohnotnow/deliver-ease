<x-layouts.app>
<div class="flex min-h-screen">
    <div class="flex-1 flex justify-center items-center">
        <div class="w-80 max-w-80 space-y-6">
            <div class="flex justify-center">
                <flux:heading size="xl">DeliverEase</flux:heading>
            </div>

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
        <div class="relative rounded-lg h-full w-full bg-gradient-to-br from-blue-600 to-indigo-800 flex flex-col items-start justify-end p-16">
            <div class="text-white">
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
</x-layouts.app>
