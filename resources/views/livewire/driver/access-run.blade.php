<div class="min-h-screen flex items-center justify-center p-4">
    <flux:card class="w-full max-w-sm space-y-6">
        <div class="text-center">
            <flux:heading size="xl" level="1">{{ $run->name }}</flux:heading>
            <flux:subheading>Enter the PIN to access this delivery run</flux:subheading>
        </div>

        <form wire:submit="submit" class="space-y-6">
            <div class="space-y-2">
                <flux:otp wire:model="pin" length="4" submit="auto" class="mx-auto" />
                @if($invalidPin)
                    <flux:error class="text-center">Incorrect PIN. Please try again.</flux:error>
                @endif
            </div>
        </form>

        <flux:text variant="subtle" class="text-center text-sm">
            Contact your dispatcher if you don't have the PIN.
        </flux:text>
    </flux:card>
</div>
