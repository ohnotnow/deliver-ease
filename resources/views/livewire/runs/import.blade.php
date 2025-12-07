<div class="space-y-6">
    <div>
        <flux:heading size="xl" level="1">Import Deliveries</flux:heading>
        <flux:subheading>Upload a spreadsheet (.xlsx) to bulk import deliveries for "{{ $run->name }}".</flux:subheading>
    </div>

    <flux:card class="space-y-6">
        <flux:heading size="lg">Upload File</flux:heading>
        <flux:text>Expected format: Column A = Email, Column B = Name (Optional).</flux:text>

        <flux:file-upload wire:model="file" label="Upload spreadsheet" accept=".xlsx,.xls">
            <flux:file-upload.dropzone heading="Drop file here or click to browse" text="Excel files (.xlsx) up to 10MB" />
        </flux:file-upload>

        <div class="flex flex-col gap-2">
            @if ($file)
                <flux:file-item
                    :heading="$file->getClientOriginalName()"
                    :size="$file->getSize()"
                >
                    <x-slot name="actions">
                        <flux:file-item.remove wire:click="removeFile" aria-label="Remove file" />
                    </x-slot>
                </flux:file-item>
            @endif
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
    @elseif(! $file)
        <div class="flex justify-end">
            <flux:button variant="ghost" :href="route('runs.edit', $run)" wire:navigate>Cancel</flux:button>
        </div>
    @endif
</div>
