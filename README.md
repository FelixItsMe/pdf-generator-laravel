# PDF Generator Laravel

A production-ready Laravel 11 infrastructure for generating PDFs from uploaded images with queue-based processing, S3/CDN storage, and a Vue 3 frontend component.

---

## Features

- **Image Upload & Optimization** — batch upload, auto-resize & compress via Intervention Image, store on S3 with optional CloudFront CDN
- **Queue-based PDF Generation** — async jobs with retry logic, timeout handling, and failure notifications
- **Dynamic Templates** — custom HTML/CSS layouts with `{{variable}}` placeholders
- **Status Polling** — REST endpoint to check job progress
- **Email Notification** — notifies user when PDF is ready
- **Scheduled Cleanup** — auto-delete old PDFs on a schedule
- **Vue 3 Component** — drag-and-drop image upload, real-time progress, one-click download

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| PDF Engine | barryvdh/laravel-dompdf |
| Image Processing | intervention/image-laravel |
| Queue | Redis (or Database) |
| Storage | AWS S3 + CloudFront CDN |
| Frontend | Vue 3 / Inertia.js |

---

## Quick Start

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and fill in:

```env
DB_DATABASE=pdf_generator
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
CLOUDFRONT_URL=https://dXXXXXX.cloudfront.net

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-user
MAIL_PASSWORD=your-pass
```

### 3. Run migrations & seed templates

```bash
php artisan migrate
php artisan db:seed
```

### 4. Start queue workers

```bash
# PDF generation queue
php artisan queue:work redis --queue=pdfs --tries=3

# (Optional) separate worker for image processing
php artisan queue:work redis --queue=images
```

### 5. Start the scheduler (for PDF cleanup)

```bash
php artisan schedule:run  # or add to cron: * * * * * php artisan schedule:run
```

---

## API Endpoints

### Upload Images

```
POST /api/images
Content-Type: multipart/form-data

images[] = <file>   (1–20 images, max 10 MB each, JPEG/PNG/GIF/WebP)
```

**Response 201:**

```json
{
  "message": "3 image(s) uploaded successfully.",
  "data": [
    {
      "id": 1,
      "original_filename": "photo.jpg",
      "url": "https://dXXX.cloudfront.net/images/2024/06/uuid.jpg",
      "width": 1200,
      "height": 800,
      "size": 245760
    }
  ]
}
```

---

### Generate PDF

```
POST /api/pdfs
Content-Type: application/json

{
  "template_id": 1,         // optional
  "image_ids": [1, 2, 3],   // IDs from upload step
  "variables": {
    "title": "My Photo Book",
    "subtitle": "Summer 2024"
  }
}
```

**Response 202:**

```json
{
  "message": "PDF generation queued.",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "pending"
  }
}
```

---

### Check Job Status

```
GET /api/pdfs/{uuid}
```

**Response 200:**

```json
{
  "data": {
    "uuid": "550e8400-...",
    "status": "completed",
    "pdf_url": "https://dXXX.cloudfront.net/pdfs/2024/06/1/uuid.pdf",
    "pdf_size": 512000,
    "completed_at": "2024-06-15T10:30:00+00:00"
  }
}
```

Possible `status` values: `pending` → `processing` → `completed` / `failed`

---

### List User's PDFs

```
GET /api/pdfs
```

Returns paginated list of the authenticated user's PDF jobs.

---

### Delete an Image

```
DELETE /api/images/{id}
```

---

## Database Schema

### `uploaded_images`

| Column | Type | Description |
|---|---|---|
| id | bigint | Primary key |
| user_id | bigint | FK → users (nullable) |
| original_filename | string | Original file name |
| disk | string | Storage disk (s3/local) |
| path | string | Storage path |
| cdn_url | string | CDN URL |
| size | int | File size in bytes |
| width / height | smallint | Image dimensions |
| mime_type | string | MIME type |

### `pdf_templates`

| Column | Type | Description |
|---|---|---|
| id | bigint | Primary key |
| name / slug | string | Template identifier |
| html_content | longtext | HTML with `{{variable}}` placeholders |
| variables | json | Expected variable keys |
| paper_size | string | A4, Letter, etc. |
| orientation | string | portrait / landscape |

### `pdf_jobs`

| Column | Type | Description |
|---|---|---|
| id | bigint | Primary key |
| uuid | uuid | Public job identifier |
| user_id | bigint | FK → users |
| pdf_template_id | bigint | FK → pdf_templates |
| status | string | pending / processing / completed / failed |
| image_ids | json | Array of UploadedImage IDs |
| variables | json | Dynamic text values |
| pdf_path / pdf_url | string | Final PDF location |
| pdf_size | int | PDF file size in bytes |
| error_message | text | Last error (if failed) |
| completed_at | timestamp | Completion time |

---

## Custom Templates

1. Create a template via seeder or direct DB insert:

```php
PdfTemplate::create([
    'name'         => 'My Template',
    'slug'         => 'my-template',
    'paper_size'   => 'A4',
    'orientation'  => 'portrait',
    'variables'    => ['title', 'author'],
    'html_content' => '<html><body><h1>{{title}}</h1>{{images}}</body></html>',
]);
```

2. Use `{{variable_name}}` for dynamic text and `{{images}}` to inject uploaded images.

3. Pass the `template_id` in the PDF generation request.

---

## Vue 3 Component

```vue
<script setup>
import PdfGenerator from '@/Components/PdfGenerator.vue'
</script>

<template>
  <PdfGenerator
    api-base="/api"
    :templates="$page.props.templates"
  />
</template>
```

Props:

| Prop | Type | Default | Description |
|---|---|---|---|
| apiBase | String | `/api` | Base URL for API calls |
| templates | Array | `[]` | List of available templates `{id, name}` |

---

## Artisan Commands

```bash
# Delete PDFs older than 30 days (default)
php artisan pdf:cleanup

# Custom retention period
php artisan pdf:cleanup --days=7
```

---

## Configuration (`config/pdf.php`)

| Key | Default | Description |
|---|---|---|
| `paper_size` | `A4` | Default paper size |
| `orientation` | `portrait` | Default orientation |
| `queue` | `pdfs` | Queue name for PDF jobs |
| `image.max_width` | `1920` | Max image width after resize |
| `image.max_height` | `1920` | Max image height after resize |
| `image.quality` | `80` | JPEG quality (1–100) |
| `cloudfront_url` | `null` | CloudFront distribution URL |
| `cleanup_days` | `30` | Days before PDFs are auto-deleted |

---

## Architecture

```
Browser
  │
  ├─ POST /api/images ─────────────────────────────────────────────────────────┐
  │   ImageUploadController                                                      │
  │   └─ ImageService::uploadBatch()                                             │
  │       ├─ Resize & compress (Intervention Image)                              │
  │       ├─ Upload to S3                                                        │
  │       └─ UploadedImage model saved                                           │
  │                                                                              │
  ├─ POST /api/pdfs ─────────────────────────────────────────────────────────── │
  │   PdfGenerationController                                                    │
  │   └─ PdfJob created (status=pending)                                        │
  │   └─ GeneratePdfJob dispatched → Redis queue                                │
  │                                                                              │
  └─ GET /api/pdfs/{uuid} ──── poll until status=completed/failed               │
                                                                                 │
Queue Worker                                                                     │
  └─ GeneratePdfJob::handle()                                                   │
      ├─ PdfService::generate()                                                  │
      │   ├─ Render HTML (Blade template or custom HTML)                         │
      │   ├─ Embed images as base64 data URIs                                    │
      │   └─ DOMPDF renders to PDF binary                                        │
      ├─ StorageService::storePdf() → S3                                         │
      ├─ PdfJob updated (status=completed, pdf_url=...)                          │
      └─ PdfReadyMail sent to user                                               │
```

---

## License

MIT
