<?php

namespace App\Filament\Pages;

use App\Models\GlobalCategory;
use App\Models\GlobalService;
use App\Models\ProjectCategory;
use App\Models\ProjectService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ServiceSetupWizard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Service Setup';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Service Setup';
    protected static ?string $slug = 'service-setup';

    protected static string $view = 'filament.pages.service-setup-wizard';
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('service_setup');
    }

    // ─── Livewire State ────────────────────────────────────
    public int $step = 1;
    public array $categories = [];
    public array $selectedCategoryIds = [];
    public array $services = [];           // id => service data
    public array $selectedServices = [];   // id => bool
    public array $customPrices = [];       // id => string price
    public int $createdCount = 0;

    public function mount(): void
    {
        $this->loadCategories();
    }

    protected function loadCategories(): void
    {
        $project = Filament::getTenant();

        // Get already linked category IDs for this project
        $existingCategoryIds = ProjectCategory::where('project_id', $project->id)
            ->pluck('global_category_id')
            ->toArray();

        $this->categories = GlobalCategory::active()
            ->withCount(['globalServices' => fn($q) => $q->active()])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'icon_url' => $cat->icon_full_url,
                'services_count' => $cat->global_services_count,
                'already_added' => in_array($cat->id, $existingCategoryIds),
            ])
            ->toArray();
    }

    // ─── Step 1 → Step 2 ─────────────────────────────────
    public function goToStep2(): void
    {
        if (empty($this->selectedCategoryIds)) {
            Notification::make()
                ->title('Please select at least one category')
                ->warning()
                ->send();
            return;
        }

        // Load services for selected categories
        $templates = GlobalService::active()
            ->whereIn('global_category_id', $this->selectedCategoryIds)
            ->with('globalCategory')
            ->orderBy('global_category_id')
            ->orderBy('sort_order')
            ->get();

        $project = Filament::getTenant();

        // Check which services are already added
        $existingServiceIds = ProjectService::where('project_id', $project->id)
            ->pluck('global_service_id')
            ->toArray();

        $this->services = [];
        $this->selectedServices = [];
        $this->customPrices = [];

        foreach ($templates as $svc) {
            $id = $svc->id;
            $alreadyAdded = in_array($id, $existingServiceIds);

            $this->services[$id] = [
                'id' => $id,
                'category_name' => $svc->globalCategory->name,
                'category_id' => $svc->global_category_id,
                'name' => $svc->name,
                'description' => $svc->description,
                'default_price' => $svc->default_price,
                'price_type' => $svc->price_type,
                'price_unit' => $svc->price_unit,
                'image_url' => $svc->image_full_url,
                'already_added' => $alreadyAdded,
            ];

            // Pre-select only new services
            $this->selectedServices[$id] = !$alreadyAdded;
            $this->customPrices[$id] = (string) $svc->default_price;
        }

        $this->step = 2;
    }

    // ─── Back to Step 1 ──────────────────────────────────
    public function goToStep1(): void
    {
        $this->step = 1;
    }

    // ─── Toggle single service ───────────────────────────
    public function toggleService(int $id): void
    {
        if (isset($this->selectedServices[$id]) && !($this->services[$id]['already_added'] ?? false)) {
            $this->selectedServices[$id] = !$this->selectedServices[$id];
        }
    }

    // ─── Toggle all in category ──────────────────────────
    public function toggleCategory(int $categoryId): void
    {
        $ids = collect($this->services)
            ->where('category_id', $categoryId)
            ->where('already_added', false)
            ->pluck('id')
            ->toArray();

        $allSelected = collect($ids)->every(fn($id) => $this->selectedServices[$id] ?? false);

        foreach ($ids as $id) {
            $this->selectedServices[$id] = !$allSelected;
        }
    }

    // ─── Count new services to add ───────────────────────
    public function getNewSelectedCountProperty(): int
    {
        return collect($this->selectedServices)
            ->filter(fn($selected, $id) => $selected && !($this->services[$id]['already_added'] ?? false))
            ->count();
    }

    // ─── Step 2 → Create ─────────────────────────────────
    public function createServices(): void
    {
        $project = Filament::getTenant();

        $newIds = collect($this->selectedServices)
            ->filter(fn($selected, $id) => $selected && !($this->services[$id]['already_added'] ?? false))
            ->keys()
            ->toArray();

        if (empty($newIds)) {
            Notification::make()
                ->title('No new services to add')
                ->warning()
                ->send();
            return;
        }

        // Ensure project_categories exist
        $categoryIds = collect($newIds)
            ->map(fn($id) => $this->services[$id]['category_id'])
            ->unique()
            ->toArray();

        $maxCatSort = ProjectCategory::where('project_id', $project->id)->max('sort_order') ?? -1;

        foreach ($categoryIds as $catId) {
            ProjectCategory::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'global_category_id' => $catId,
                ],
                [
                    'sort_order' => ++$maxCatSort,
                    'is_active' => true,
                ]
            );
        }

        // Create project_services
        $maxSvcSort = ProjectService::where('project_id', $project->id)->max('sort_order') ?? -1;

        foreach ($newIds as $globalServiceId) {
            $svc = $this->services[$globalServiceId];
            $customPrice = (float) ($this->customPrices[$globalServiceId] ?? 0);
            $defaultPrice = (float) $svc['default_price'];

            ProjectService::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'global_service_id' => $globalServiceId,
                ],
                [
                    // Only store custom_price if different from default
                    'custom_price' => abs($customPrice - $defaultPrice) > 0.001 ? $customPrice : null,
                    'sort_order' => ++$maxSvcSort,
                    'is_active' => true,
                ]
            );
        }

        $this->createdCount = count($newIds);
        $this->step = 3;

        Notification::make()
            ->title("Added {$this->createdCount} services!")
            ->success()
            ->send();
    }

    // ─── Grouped services for Step 2 view ────────────────
    public function getGroupedServicesProperty(): array
    {
        $grouped = [];
        foreach ($this->services as $svc) {
            $grouped[$svc['category_name']][] = $svc;
        }
        return $grouped;
    }

    // ─── Grouped with category IDs ───────────────────────
    public function getGroupedWithIdsProperty(): array
    {
        $grouped = [];
        foreach ($this->services as $svc) {
            $key = $svc['category_id'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'name' => $svc['category_name'],
                    'id' => $svc['category_id'],
                    'services' => [],
                ];
            }
            $grouped[$key]['services'][] = $svc;
        }
        return $grouped;
    }
}
