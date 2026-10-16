@props(['key'])

@php
    $slot = \App\Models\AdSlot::findActiveByKey($key);
@endphp

@if ($slot)
    <div {{ $attributes->merge(['class' => 'my-6 flex justify-center items-center overflow-hidden rounded-lg border border-stone-warm bg-surface p-2 text-center']) }}>
        @if (!empty($slot->ad_code))
            {!! $slot->ad_code !!}
        @elseif (!empty($slot->image_url))
            <a href="{{ $slot->target_url ?? '#' }}" target="_blank" rel="noopener noreferrer nofollow" class="block w-full">
                <img src="{{ $slot->image_url }}" alt="{{ $slot->name }}" class="max-h-32 w-auto mx-auto object-contain rounded" />
            </a>
        @endif
    </div>
@endif
