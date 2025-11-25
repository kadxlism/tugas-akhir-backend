@php
  $manifest = json_decode(file_get_contents(public_path('build/.vite/manifest.json')), true);
  $main = $manifest['src/main.tsx'];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>React in Laravel</title>

  {{-- Load CSS kalau ada --}}
  @if(isset($main['css']))
    @foreach($main['css'] as $cssFile)
      <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
    @endforeach
  @endif

  {{-- Load JS --}}
  <script type="module" src="{{ asset('build/' . $main['file']) }}" defer></script>
</head>
<body>
  <div id="root"></div>
</body>
</html>
