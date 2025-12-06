<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Delivery Runs</flux:heading>
            <flux:subheading>Manage your delivery runs</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('runs.create')" wire:navigate icon="plus">New Run</flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-4">
        <flux:select wire:model.live="status" placeholder="All statuses" class="w-48">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="in_progress">In Progress</flux:select.option>
            <flux:select.option value="completed">Completed</flux:select.option>
        </flux:select>
    </div>

    {{-- Runs table --}}
    @if($this->runs->isEmpty())
        <flux:card class="text-center py-12">
            <flux:heading size="lg">No runs found</flux:heading>
            <flux:text variant="subtle" class="mt-2">
                @if($status)
                    No runs match the selected filter.
                @else
                    Get started by creating your first delivery run.
                @endif
            </flux:text>
            @unless($status)
                <div class="mt-4">
                    <flux:button variant="primary" :href="route('runs.create')" wire:navigate icon="plus">Create Run</flux:button>
                </div>
            @endunless
        </flux:card>
    @else
        <flux:table :paginate="$this->runs">
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Progress</flux:table.column>
                <flux:table.column>Created</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->runs as $run)
                    <flux:table.row :key="$run->id">
                        <flux:table.cell variant="strong">{{ $run->name }}</flux:table.cell>
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
                            {{ $run->completedDeliveriesCount() }} / {{ $run->deliveries->count() }} deliveries
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $run->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:button variant="ghost" size="sm" icon="eye" :href="route('runs.show', $run)" wire:navigate tooltip="View" />
                                @if($run->isPending())
                                    <flux:button variant="ghost" size="sm" icon="pencil" :href="route('runs.edit', $run)" wire:navigate tooltip="Edit" />
                                @endif
                                <flux:modal.trigger :name="'share-'.$run->id">
                                    <flux:button variant="ghost" size="sm" icon="share" tooltip="Share with driver" />
                                </flux:modal.trigger>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>

                    <flux:modal :name="'share-'.$run->id" class="md:w-96">
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
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
