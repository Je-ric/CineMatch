<div>
    <div class="min-h-[80px] p-4 bg-slate-800 rounded-lg border-2 border-slate-600 flex flex-wrap gap-2">
        @forelse($people as $person)
            <span class="person-badge">
                <i class="bx {{ $role === 'Director' ? 'bx-user-voice' : 'bx-user' }}"></i>
                {{ $person->name }}
                <button type="button" wire:click="removePerson({{ $person->id }})" class="remove-person">
                    <i class="bx bx-x"></i>
                </button>
            </span>
        @empty
            <div class="empty-state">No {{ strtolower($role) }}s added yet</div>
        @endforelse
    </div>

    <div>
        <input type="text"
                wire:model.defer="searchName"
                wire:keydown.enter.prevent="addPerson"
                placeholder="Type {{ strtolower($role) }} name..."
                class="w-full px-4 py-3 bg-slate-600 border-2 border-slate-500 rounded-lg text-white placeholder-slate-300 focus:border-purple-400 focus:ring-0 focus:outline-none transition-all duration-300" />
    </div>
</div>
