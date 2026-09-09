<template id="cc-template">
    <div class="cc-banner cc-layout-{{ $settings->layout }} cc-pos-{{ $settings->position }}" role="dialog" aria-modal="false" aria-labelledby="cc-title" aria-describedby="cc-body" data-cc="banner" hidden>
        <div class="cc-banner__inner">
            <div class="cc-banner__text">
                <p class="cc-title" id="cc-title">{{ $texts['title'] ?? '' }}</p>
                <p class="cc-body" id="cc-body">{{ $texts['body'] ?? '' }}@if($settings->policyUrl) <a class="cc-link" href="{{ $settings->policyUrl }}">{{ $texts['policy_link'] ?? '' }}</a>@endif</p>
            </div>
            <div class="cc-banner__actions">
                <button type="button" class="cc-btn cc-btn--ghost" data-cc="manage">{{ $texts['manage'] ?? '' }}</button>
                @if($settings->showRejectButton)
                <button type="button" class="cc-btn cc-btn--secondary" data-cc="reject">{{ $texts['reject_all'] ?? '' }}</button>
                @endif
                <button type="button" class="cc-btn cc-btn--primary" data-cc="accept">{{ $texts['accept_all'] ?? '' }}</button>
            </div>
        </div>
    </div>
    <div class="cc-modal" role="dialog" aria-modal="true" aria-labelledby="cc-modal-title" data-cc="modal" hidden>
        <div class="cc-modal__backdrop" data-cc="backdrop"></div>
        <div class="cc-modal__panel">
            <div class="cc-modal__header">
                <p class="cc-title" id="cc-modal-title">{{ $texts['manage_title'] ?? ($texts['manage'] ?? '') }}</p>
                <button type="button" class="cc-close" data-cc="close" aria-label="{{ $texts['close'] ?? 'Close' }}">&times;</button>
            </div>
            <p class="cc-body">{{ $texts['manage_body'] ?? '' }}</p>
            <ul class="cc-categories">
                @foreach($categories as $category)
                <li class="cc-category">
                    <label class="cc-category__row">
                        <span class="cc-category__text">
                            <span class="cc-category__label">{{ $category['label'] }}</span>
                            <span class="cc-category__description">{{ $category['description'] }}</span>
                        </span>
                        <input type="checkbox" role="switch" class="cc-switch" data-cc-category="{{ $category['key'] }}" @if($category['required']) checked disabled @endif>
                    </label>
                </li>
                @endforeach
            </ul>
            <div class="cc-modal__actions">
                @if($settings->showRejectButton)
                <button type="button" class="cc-btn cc-btn--secondary" data-cc="reject">{{ $texts['reject_all'] ?? '' }}</button>
                @endif
                <button type="button" class="cc-btn cc-btn--secondary" data-cc="save">{{ $texts['save'] ?? '' }}</button>
                <button type="button" class="cc-btn cc-btn--primary" data-cc="accept">{{ $texts['accept_all'] ?? '' }}</button>
            </div>
            @if($settings->policyUrl)
            <p class="cc-modal__footer"><a class="cc-link" href="{{ $settings->policyUrl }}">{{ $texts['policy_link'] ?? '' }}</a></p>
            @endif
        </div>
    </div>
    <div class="cc-placeholder" data-cc="placeholder" hidden>
        <p class="cc-placeholder__title">{{ $texts['placeholder_title'] ?? '' }}</p>
        <p class="cc-placeholder__body">{{ $texts['placeholder_body'] ?? '' }}</p>
        <button type="button" class="cc-btn cc-btn--primary" data-cc="placeholder-accept">{{ $texts['placeholder_button'] ?? '' }}</button>
    </div>
</template>
