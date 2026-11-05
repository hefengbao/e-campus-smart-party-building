@php
    $user = filament()->auth()->user();
    $phone = $user?->phone ?: $user?->member?->phone;
@endphp

@if ($user)
    <div
        class="fi-user-personal-info"
        style="padding: 0.25rem 1rem 0.75rem; min-width: 15rem; font-size: 0.8125rem; line-height: 1.5;"
    >
        @if (filled($user->email))
            <div style="color: #6b7280; overflow-wrap: anywhere;">
                <span style="font-weight: 500; color: #374151;">邮箱</span>
                <span>{{ $user->email }}</span>
            </div>
        @endif

        @if (filled($phone))
            <div style="margin-top: 0.25rem; color: #6b7280;">
                <span style="font-weight: 500; color: #374151;">联系电话</span>
                <span>{{ $phone }}</span>
            </div>
        @endif
    </div>
@endif
