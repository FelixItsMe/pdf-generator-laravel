<!DOCTYPE html>
<html>
<head><meta charset="UTF-8" /></head>
<body style="font-family:sans-serif;color:#333;padding:20px;">
    <h2>Your PDF is ready! 🎉</h2>
    <p>Hello,</p>
    <p>Your PDF has been generated successfully.</p>
    @if($pdfJob->pdf_url)
    <p>
        <a href="{{ $pdfJob->pdf_url }}"
           style="display:inline-block;padding:10px 20px;background:#4f46e5;color:#fff;border-radius:4px;text-decoration:none;">
            Download PDF
        </a>
    </p>
    @endif
    <p style="color:#999;font-size:12px;">
        Generated at: {{ $pdfJob->completed_at?->format('Y-m-d H:i:s') }}
    </p>
</body>
</html>
