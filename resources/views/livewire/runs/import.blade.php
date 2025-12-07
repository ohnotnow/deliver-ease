<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Import Deliveries</flux:heading>
        <flux:subheading>Upload a spreadsheet (.xlsx) to bulk import deliveries for "{{ $run->name }}".</flux:subheading>
    </div>

    <flux:card class="space-y-6">
        <flux:heading size="lg">Upload File</flux:heading>
        <flux:text>Expected format: Column A = Email, Column B = Name (Optional).</flux:text>
        
        <div class="flex items-center gap-4">
            <flux:input type="file" wire:model="file" accept=".xlsx,.xls" />
            <div wire:loading wire:target="file">
                <flux:text class="italic">Processing file...</flux:text>
            </div>
        </div>

        @error('file')
            <flux:text color="red">{{ $message }}</flux:text>
        @enderror
    </flux:card>

    @if(!empty($previewRows))
        <flux:card class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Preview</flux:heading>
                <div class="flex gap-2">
                    <flux:button variant="ghost" :href="route('runs.edit', $run)" wire:navigate>Cancel</flux:button>
                    @if(collect($previewRows)->where('isValid', true)->count() > 0)
                        <flux:button variant="primary" wire:click="import">Import {{ collect($previewRows)->where('isValid', true)->count() }} Deliveries</flux:button>
                    @endif
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($previewRows as $index => $row)
                        <flux:table.row :key="$index">
                            <flux:table.cell :class="$row['isValid'] ? '' : 'text-red-500'">{{ $row['email'] }}</flux:table.cell>
                            <flux:table.cell>{{ $row['name'] }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row['isValid'])
                                    <flux:badge color="green" size="sm" inset="top bottom">Valid</flux:badge>
                                @else
                                    <div class="space-y-1">
                                        <flux:badge color="red" size="sm" inset="top bottom">Invalid</flux:badge>
                                        @foreach($row['errors'] as $error)
                                            <div class="text-xs text-red-500">{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @else
        <div class="flex justify-end">
            <flux:button variant="ghost" :href="route('runs.edit', $run)" wire:navigate>Cancel</flux:button>
        </div>
    @endif
</div>