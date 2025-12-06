<div class="min-h-screen p-4 pb-24">
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ $run->name }}</flux:heading>
        <flux:text variant="subtle">
            {{ $run->completedDeliveriesCount() }} of {{ $this->deliveries->count() }} deliveries completed
        </flux:text>
    </div>

    {{-- Start button for pending runs --}}
    @if($run->isPending())
        <flux:card class="mb-6 text-center space-y-4">
            <flux:heading size="lg">Ready to start?</flux:heading>
            <flux:text variant="subtle">
                Tap the button below to begin your delivery run. The first two customers will be notified.
            </flux:text>
            <flux:button wire:click="startRun" variant="primary" class="w-full py-4 text-lg">
                Start Deliveries
            </flux:button>
        </flux:card>
    @endif

    {{-- Completed message --}}
    @if($run->isCompleted())
        <flux:callout icon="check-circle" color="green" class="mb-6">
            <flux:callout.heading>All done!</flux:callout.heading>
            <flux:callout.text>You've completed all deliveries. Great work!</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Deliveries list --}}
    <div wire:sort="sortDelivery" class="space-y-3">
        @foreach($this->deliveries as $delivery)
            <div
                wire:key="delivery-{{ $delivery->id }}"
                wire:sort:item="{{ $delivery->id }}"
                class="rounded-xl p-4 {{ $delivery->isCompleted() ? 'bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800' : ($delivery->isNotified() ? 'bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800' : 'bg-zinc-100 dark:bg-zinc-800 border-2 border-transparent') }}"
            >
                <div class="flex items-center gap-4">
                    {{-- Position indicator / drag handle --}}
                    @if($delivery->isCompleted())
                        <div class="flex items-center justify-center w-12 h-12 rounded-full shrink-0 bg-green-500 text-white">
                            <flux:icon.check class="w-6 h-6" />
                        </div>
                    @else
                        <div wire:sort:handle class="flex items-center justify-center w-12 h-12 rounded-full shrink-0 cursor-grab active:cursor-grabbing {{ $delivery->isNotified() ? 'bg-blue-500 text-white' : 'bg-zinc-300 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300' }}">
                            <flux:icon.bars-3 class="w-6 h-6" />
                        </div>
                    @endif

                    {{-- Customer info --}}
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-lg truncate">{{ $delivery->display_name }}</div>
                        @if($delivery->name)
                            <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $delivery->email }}</div>
                        @endif
                        @if($delivery->isNotified() && !$delivery->isCompleted())
                            <flux:badge color="blue" size="sm" class="mt-1">Customer notified</flux:badge>
                        @endif
                    </div>

                    {{-- Action button --}}
                    @if($delivery->isNotified() && !$delivery->isCompleted())
                        <flux:button
                            wire:click="completeDelivery({{ $delivery->id }})"
                            variant="primary"
                            class="shrink-0 py-3 px-6 text-base"
                        >
                            Complete
                        </flux:button>
                    @elseif($delivery->isCompleted())
                        <flux:icon.check-circle class="w-8 h-8 text-green-500 shrink-0" />
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Progress footer (fixed at bottom on mobile) --}}
    @if($run->isStarted())
        <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 p-4 safe-area-pb">
            <div class="flex items-center justify-between mb-2">
                <flux:text variant="strong">Progress</flux:text>
                <flux:text>{{ $run->completedDeliveriesCount() }} / {{ $this->deliveries->count() }}</flux:text>
            </div>
            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                <div
                    class="h-2 rounded-full transition-all duration-500 {{ $run->isCompleted() ? 'bg-green-500' : 'bg-blue-500' }}"
                    style="width: {{ $this->progressPercentage }}%"
                ></div>
            </div>
        </div>
    @endif
</div>
