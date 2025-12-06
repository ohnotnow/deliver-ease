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
                            {{ $run->completedDeliveriesCount() }} / {{ $run->deliveries->count() }} deliveries
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $run->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="route('runs.show', $run)" wire:navigate>View</flux:menu.item>
                                    @if($run->isPending())
                                        <flux:menu.item icon="pencil" :href="route('runs.edit', $run)" wire:navigate>Edit</flux:menu.item>
                                    @endif
                                    <flux:modal.trigger :name="'share-'.$run->id">
                                        <flux:menu.item icon="share">Share with driver</flux:menu.item>
                                    </flux:modal.trigger>

                                    @unless($run->isStarted())
                                        <flux:menu.separator />
                                        <flux:modal.trigger :name="'delete-'.$run->id">
                                            <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
                                        </flux:modal.trigger>
                                    @endunless
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>

                    <flux:modal :name="'share-'.$run->id" class="md:w-96" flyout>
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

                    @unless($run->isStarted())
                        <flux:modal :name="'delete-'.$run->id" class="max-w-md" flyout>
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete this run?</flux:heading>
                                    <flux:subheading>This will permanently delete "{{ $run->name }}" and all its deliveries. This action cannot be undone.</flux:subheading>
                                </div>

                                <div class="flex gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button wire:click="delete({{ $run->id }})" variant="danger" class="flex-1">Delete Run</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endunless
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
