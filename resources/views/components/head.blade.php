@if($enabled)
<script{{ $nonce ? ' nonce="'.$nonce.'"' : '' }} id="cc-config">window.CookieConsentConfig={!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!};</script>
@if($styles !== '')
<style id="cc-css">{!! $styles !!}</style>
@endif
@if($runtime !== null && $runtime !== '')
<script{{ $nonce ? ' nonce="'.$nonce.'"' : '' }} id="cc-runtime">{!! $runtime !!}</script>
@elseif($runtimeUrl !== null)
<script src="{{ $runtimeUrl }}"{{ $nonce ? ' nonce="'.$nonce.'"' : '' }} id="cc-runtime"></script>
@endif
@include('cookie-consent::components.template', ['texts' => $texts, 'categories' => $categories, 'settings' => $settings])
@endif
@foreach($scripts as $script)
@include('cookie-consent::components.managed-script', ['script' => $script, 'enabled' => $enabled])
@endforeach
