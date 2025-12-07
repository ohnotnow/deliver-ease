<div class="space-y-6">
    @if(!Auth::user()->business_id)
        <flux:callout variant="danger" icon="exclamation-triangle" title="Setup Required">
            <flux:text>You need to create a business profile to start using DeliverEase.</flux:text>
            <div class="mt-4">
                <flux:modal.trigger name="business-modal">
                    <flux:button variant="primary">Create Business</flux:button>
                </flux:modal.trigger>
            </div>
        </flux:callout>
    @endif

    <flux:fieldset :disabled="!Auth::user()->business_id" class="space-y-6">
        <div>
            <flux:heading size="xl" level="1">Dashboard</flux:heading>
            <flux:subheading>Overview of your delivery runs</flux:subheading>
        </div>

        {{-- Stats cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <flux:card>
                <flux:subheading>Active Runs</flux:subheading>
                <flux:heading size="xl">{{ $this->activeRunsCount }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:subheading>Pending</flux:subheading>
                <flux:heading size="xl">{{ $this->pendingRunsCount }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:subheading>Completed Today</flux:subheading>
                <flux:heading size="xl">{{ $this->completedTodayCount }}</flux:heading>
            </flux:card>
        </div>

        {{-- Recent runs --}}
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Recent Runs</flux:heading>
                <flux:button variant="subtle" size="sm" :href="route('runs.index')" wire:navigate>View all</flux:button>
            </div>

            @if($this->recentRuns->isEmpty())
                <flux:text variant="subtle">No runs yet. Create your first delivery run to get started.</flux:text>
                <flux:button variant="primary" :href="route('runs.create')" wire:navigate icon="plus">Create Run</flux:button>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Deliveries</flux:table.column>
                        <flux:table.column>Created</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($this->recentRuns as $run)
                            <flux:table.row :key="$run->id">
                                <flux:table.cell variant="strong">
                                    <flux:link :href="route('runs.show', $run)" wire:navigate>{{ $run->name }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($run->isPending())
                                        <flux:badge color="zinc" size="sm" inset="top bottom">Pending</flux:badge>
                                    @elseif($run->isStarted())
                                        <flux:badge color="blue" size="sm" inset="top bottom">In Progress</flux:badge>
                                    @else
                                        <flux:badge color="green" size="sm" inset="top bottom">Completed</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $run->completedDeliveriesCount() }} / {{ $run->deliveries->count() }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $run->created_at->format('M j, g:i A') }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    </flux:fieldset>

    <flux:modal name="business-modal" class="md:w-96" flyout>
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ Auth::user()->business_id ? 'Edit Business' : 'Create Business' }}</flux:heading>
                <flux:subheading>Enter the name of your business.</flux:subheading>
            </div>

            <flux:input wire:model="businessName" label="Business Name" placeholder="e.g. My Local Bakery" />

            <div class="flex">
                <flux:spacer />
                <flux:button variant="primary" wire:click="saveBusiness">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>