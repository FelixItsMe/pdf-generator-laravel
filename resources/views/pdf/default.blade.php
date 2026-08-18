<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; padding: 30px; }
        h1  { font-size: 22px; margin-bottom: 16px; color: #1a1a2e; }
        .variable-row { margin-bottom: 8px; }
        .variable-row strong { display: inline-block; min-width: 120px; }
        .image-section { margin-top: 24px; }
        .image-section img { max-width: 100%; height: auto; margin-bottom: 12px; display: block; border: 1px solid #ddd; }
        .page-number { position: fixed; bottom: 20px; right: 20px; font-size: 10px; color: #999; }
    </style>
</head>
<body>

    <h1>{{ $variables['title'] ?? 'Generated Document' }}</h1>

    @if(!empty($variables))
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        @foreach($variables as $key => $value)
            @if($key !== 'title')
            <tr>
                <td style="padding:4px 8px;border-bottom:1px solid #eee;font-weight:bold;width:35%;">
                    {{ ucwords(str_replace('_', ' ', $key)) }}
                </td>
                <td style="padding:4px 8px;border-bottom:1px solid #eee;">
                    {{ $value }}
                </td>
            </tr>
            @endif
        @endforeach
    </table>
    @endif

    @if(!empty($images))
    <div class="image-section">
        @foreach($images as $image)
        <img src="{{ $image->public_url }}" alt="{{ $image->original_filename }}" />
        @endforeach
    </div>
    @endif

    <div class="page-number">Page <span class="pagenum"></span></div>

</body>
</html>
