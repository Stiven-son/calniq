<x-filament-panels::page>
@livewire('notifications')
    {{-- Profile --}}
    <form wire:submit="saveProfile">
        {{ $this->profileForm }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Save Profile
            </x-filament::button>
        </div>
    </form>

    {{-- Booking Settings --}}
    <form wire:submit="saveBooking">
        {{ $this->bookingForm }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Save Booking Settings
            </x-filament::button>
        </div>
    </form>

    {{-- Branding --}}
    <form wire:submit="saveBranding">
        {{ $this->brandingForm }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Save Branding
            </x-filament::button>
        </div>
    </form>

    {{-- Email Notifications --}}
    <form wire:submit="saveEmail">
        {{ $this->emailForm }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Save Email Settings
            </x-filament::button>
        </div>
    </form>

    {{-- Password --}}
    <form wire:submit="savePassword">
        {{ $this->passwordForm }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" color="danger">
                Change Password
            </x-filament::button>
        </div>
    </form>

    {{-- Danger Zone --}}
    <form wire:submit="deleteProject">
        {{ $this->dangerForm }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" color="danger">
                Delete This Project
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
