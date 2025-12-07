<div class="space-y-6" wire:poll.5s>
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl" level="1">{{ $run->name }}</flux:heading>
                @if($run->isPending())
                    <flux:badge color="zinc">Pending</flux:badge>
                @elseif($run->isStarted())
                    <flux:badge color="blue">In Progress</flux:badge>
                @else
                    <flux:badge color="green">Completed</flux:badge>
                @endif
            </div>
            <flux:subheading>Created {{ $run->created_at->format('M j, Y \a\t g:i A') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('runs.export', $run)" icon="arrow-down-tray">Export</flux:button>
            <flux:modal.trigger name="share-run">
                <flux:button icon="share">Share with Driver</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Progress --}}
    <flux:card class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Progress</flux:heading>
            <flux:text variant="strong">{{ $run->completedDeliveriesCount() }} / {{ $this->deliveries->count() }} deliveries</flux:text>
        </div>

        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
            <div
                class="h-3 rounded-full transition-all duration-500 {{ $run->isCompleted() ? 'bg-green-500' : 'bg-blue-500' }}"
                style="width: {{ $this->progressPercentage }}%"
            ></div>
        </div>

        @if($run->isStarted() && $run->currentDelivery())
            <flux:callout icon="truck" color="blue">
                <flux:callout.heading>Currently delivering to</flux:callout.heading>
                <flux:callout.text>{{ $run->currentDelivery()->display_name }}</flux:callout.text>
            </flux:callout>
        @elseif($run->isCompleted())
            <flux:callout icon="check-circle" color="green">
                <flux:callout.heading>Run completed</flux:callout.heading>
                <flux:callout.text>All deliveries have been completed. Finished at {{ $run->completed_at->format('g:i A') }}</flux:callout.text>
            </flux:callout>
        @endif
    </flux:card>

    {{-- Deliveries list --}}
    <flux:card class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Deliveries</flux:heading>
            @if($run->isPending())
                <flux:button variant="subtle" :href="route('runs.edit', $run)" wire:navigate icon="pencil">Edit</flux:button>
            @endif
        </div>

        <div class="space-y-2">
            @foreach($this->deliveries as $delivery)
                <div wire:key="delivery-{{ $delivery->id }}" class="flex items-center gap-4 p-4 rounded-lg {{ $delivery->isCompleted() ? 'bg-green-50 dark:bg-green-900/20' : ($delivery->isNotified() ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-zinc-50 dark:bg-zinc-800') }}">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full shrink-0 {{ $delivery->isCompleted() ? 'bg-green-500 text-white' : ($delivery->isNotified() ? 'bg-blue-500 text-white' : 'bg-zinc-200 dark:bg-zinc-700') }}">
                        @if($delivery->isCompleted())
                            <flux:icon.check variant="mini" />
                        @else
                            <flux:text variant="strong">{{ $delivery->position }}</flux:text>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <flux:text variant="strong">{{ $delivery->display_name }}</flux:text>
                        @if($delivery->name)
                            <flux:text variant="subtle" class="text-sm truncate">{{ $delivery->email }}</flux:text>
                        @endif
                    </div>

                    <div>
                        @if($delivery->isCompleted())
                            <flux:badge color="green" size="sm">Completed</flux:badge>
                        @elseif($delivery->isNotified())
                            <flux:badge color="blue" size="sm">Notified</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Pending</flux:badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </flux:card>

    {{-- Share modal --}}
    <flux:modal name="share-run" class="md:w-96" flyout>
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Share with Driver</flux:heading>
                <flux:subheading>Send this link to your driver. They'll need the PIN to access the run.</flux:subheading>
            </div>

            <flux:input label="Driver Link" icon="link" :value="$run->getDriverUrl()" readonly copyable />
            <flux:input label="PIN" icon="key" :value="$run->pin" readonly copyable />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button>Done</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
