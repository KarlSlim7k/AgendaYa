@php
    $viteEntries = $entries ?? ['resources/css/app.css', 'resources/js/app.js'];
    $manifestPath = public_path('build/manifest.json');
    $manifestAvailable = is_file($manifestPath);
@endphp

@if ($manifestAvailable)
    @vite($viteEntries)
@else
    @php
        logger()->warning('Vite manifest omitido durante render de vista.', [
            'manifest_path' => $manifestPath,
            'public_path' => public_path(),
            'request_uri' => request()?->getRequestUri(),
        ]);
    @endphp
@endif
