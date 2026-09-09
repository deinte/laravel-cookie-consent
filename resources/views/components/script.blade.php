@php
    $flags = trim(($async ? 'async ' : '').($defer ? 'defer ' : ''));
    $extra = trim($flags.' '.$attributes->toHtml());
    $extra = $extra === '' ? '' : ' '.$extra;
    $gated = $enabled && $category !== \Deinte\CookieConsent\Enums\ConsentCategory::Necessary;
@endphp
@if($src)
@if($gated)
<script type="text/plain" data-cookieconsent="{{ $category->value }}" data-src="{{ $src }}"{!! $extra !!}></script>
@else
<script src="{{ $src }}"{!! $extra !!}></script>
@endif
@else
@if($gated)
<script type="text/plain" data-cookieconsent="{{ $category->value }}"{!! $extra !!}>{{ $slot }}</script>
@else
<script{!! $extra !!}>{{ $slot }}</script>
@endif
@endif
