@foreach($scripts as $script)
@include('cookie-consent::components.managed-script', ['script' => $script, 'enabled' => $enabled])
@endforeach
