<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Create Run</flux:heading>
        <flux:subheading>Set up a new delivery run for your driver</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-6">
            <flux:heading size="lg">Run Details</flux:heading>

            <flux:input
                wire:model="name"
                label="Run Name"
                placeholder="e.g. Monday Morning Deliveries"
                description="A friendly name to identify this run"
            />

            <flux:field>
                <flux:label>Driver PIN</flux:label>
                <flux:description>Four-digit code the driver must enter to access this run</flux:description>
                <flux:input.group>
                    <flux:input wire:model="pin" maxlength="4" class="font-mono" inputmode="numeric" />
                    <flux:button type="button" wire:click="regeneratePin" icon="arrow-path">Regenerate</flux:button>
                </flux:input.group>
                <flux:error name="pin" />
            </flux:field>
        </flux:card>

        <flux:card class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Deliveries</flux:heading>
                    <flux:subheading>Add the stops for this run in order</flux:subheading>
                </div>
                <flux:button type="button" wire:click="addDelivery" icon="plus" size="sm">Add Stop</flux:button>
            </div>

            <div wire:sort="sortDelivery" class="space-y-4">
                @foreach($deliveries as $index => $delivery)
                    <div wire:key="delivery-{{ $index }}" wire:sort:item="{{ $index }}" class="flex items-start gap-4 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <div wire:sort:handle class="flex items-center justify-center w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 shrink-0 cursor-grab active:cursor-grabbing" title="Drag to reorder">
                            <flux:icon.bars-3 class="w-4 h-4 text-zinc-500" />
                        </div>

                        <div class="flex-1 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model="deliveries.{{ $index }}.email"
                                type="email"
                                label="Email"
                                placeholder="customer@example.com"
                            />
                            <flux:input
                                wire:model="deliveries.{{ $index }}.name"
                                label="Name (optional)"
                                placeholder="Customer name or address"
                            />
                        </div>

                        @if(count($deliveries) > 1)
                            <flux:button
                                type="button"
                                wire:click="removeDelivery({{ $index }})"
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                class="text-red-500 hover:text-red-600"
                            />
                        @endif
                    </div>
                @endforeach
            </div>

            @error('deliveries')
                <flux:text color="red">{{ $message }}</flux:text>
            @enderror
        </flux:card>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">Create Run</flux:button>
            <flux:button type="button" variant="ghost" :href="route('runs.index')" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
