@php
    $user = filament()->auth()->user();
    if (! $user) {
        return;
    }

    $currentPanel = filament()->getCurrentPanel();
    $currentPanelId = $currentPanel?->getId();

    $panelsConfig = [
        'member' => [
            'name' => '个人工作台',
            'icon' => 'heroicon-m-user',
        ],
        'branch' => [
            'name' => '党支部管理',
            'icon' => 'heroicon-m-building-library',
        ],
        'college' => [
            'name' => '二级党委管理',
            'icon' => 'heroicon-m-academic-cap',
        ],
        'org' => [
            'name' => '党委组织部',
            'icon' => 'heroicon-m-shield-check',
        ],
        'sysadmin' => [
            'name' => '系统管理',
            'icon' => 'heroicon-m-cog-6-tooth',
        ],
    ];

    $allPanels = filament()->getPanels();
    $accessiblePanels = [];

    foreach ($panelsConfig as $id => $meta) {
        if (isset($allPanels[$id]) && $user->canAccessPanel($allPanels[$id])) {
            $accessiblePanels[$id] = [
                'id' => $id,
                'name' => $meta['name'],
                'icon' => $meta['icon'],
                'url' => $allPanels[$id]->getUrl(),
                'is_current' => ($id === $currentPanelId),
            ];
        }
    }

    $currentMeta = $panelsConfig[$currentPanelId] ?? [
        'name' => '切换工作台',
        'icon' => 'heroicon-m-squares-2x2',
    ];
@endphp

<style>
    .fi-panel-switcher-trigger {
        gap: 0.4rem !important;
        padding-left: 0.625rem !important;
        padding-right: 0.625rem !important;
    }

    .fi-panel-switcher-label {
        display: inline-block !important;
    }

    .fi-panel-switcher-chevron {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    @media (max-width: 639px) {
        .fi-panel-switcher-trigger {
            gap: 0 !important;
            width: 2.125rem !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .fi-panel-switcher-label,
        .fi-panel-switcher-chevron {
            display: none !important;
        }
    }
</style>

<div class="fi-panel-switcher" style="display: inline-flex !important; align-items: center !important; flex-shrink: 0 !important; margin-right: 0.5rem !important;">
    <x-filament::dropdown placement="bottom-end" teleport>
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-panel-switcher-trigger hover:!bg-gray-50 dark:!bg-gray-900 dark:!border-white/20 dark:!text-gray-200 dark:hover:!bg-white/5"
                style="
                    display: inline-flex !important;
                    flex-direction: row !important;
                    flex-wrap: nowrap !important;
                    align-items: center !important;
                    justify-content: center !important;
                    white-space: nowrap !important;
                    height: 2.125rem !important;
                    font-size: 0.8125rem !important;
                    font-weight: 500 !important;
                    line-height: 1 !important;
                    border-radius: 0.5rem !important;
                    border: 1px solid #d1d5db !important;
                    background-color: #ffffff !important;
                    color: #374151 !important;
                    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
                    cursor: pointer !important;
                    flex-shrink: 0 !important;
                    user-select: none !important;
                    outline: none !important;
                    transition: all 0.15s ease-in-out !important;
                "
                title="切换工作台 (当前：{{ $currentMeta['name'] }})"
                aria-label="切换工作台：{{ $currentMeta['name'] }}"
            >
                <span style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 1.125rem !important; height: 1.125rem !important; flex-shrink: 0 !important; color: #dc2626 !important;">
                    <x-filament::icon
                        :icon="$currentMeta['icon']"
                        style="width: 100% !important; height: 100% !important; display: block !important;"
                    />
                </span>

                <span class="fi-panel-switcher-label" style="white-space: nowrap !important; line-height: 1 !important; flex-shrink: 0 !important; font-size: 0.8125rem !important;">
                    {{ $currentMeta['name'] }}
                </span>

                <span class="fi-panel-switcher-chevron" style="width: 0.875rem !important; height: 0.875rem !important; flex-shrink: 0 !important; color: #9ca3af !important;">
                    <x-filament::icon
                        icon="heroicon-m-chevron-down"
                        style="width: 100% !important; height: 100% !important; display: block !important;"
                    />
                </span>
            </button>
        </x-slot>

        <x-filament::dropdown.header
            color="gray"
            icon="heroicon-m-arrows-right-left"
        >
            切换身份工作台
        </x-filament::dropdown.header>

        <x-filament::dropdown.list>
            @foreach($accessiblePanels as $panelItem)
                <x-filament::dropdown.list.item
                    :href="$panelItem['url']"
                    :icon="$panelItem['icon']"
                    tag="a"
                    :color="$panelItem['is_current'] ? 'primary' : 'gray'"
                    :badge="$panelItem['is_current'] ? '当前' : null"
                    :badge-color="$panelItem['is_current'] ? 'primary' : 'gray'"
                >
                    {{ $panelItem['name'] }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
