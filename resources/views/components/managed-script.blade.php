@php
    $attributeString = collect($script->attributes)
        ->map(fn ($value, $key) => $value === true ? e($key) : e($key).'="'.e((string) $value).'"')
        ->implode(' ');
@endphp
@if(! $enabled || $script->category === \Deinte\CookieConsent\Enums\ConsentCategory::Necessary)
@if($script->isExternal())
<script src="{{ $script->src }}" {!! $attributeString !!}></script>
@else
<script {!! $attributeString !!}>{!! $script->code !!}</script>
@endif
@else
@if($script->isExternal())
<script type="text/plain" data-cookieconsent="{{ $script->category->value }}" data-src="{{ $script->src }}" {!! $attributeString !!}></script>
@else
<script type="text/plain" data-cookieconsent="{{ $script->category->value }}" {!! $attributeString !!}>{!! $script->code !!}</script>
@endif
@endif
