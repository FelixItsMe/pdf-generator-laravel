<?php

namespace Database\Seeders;

use App\Models\PdfTemplate;
use Illuminate\Database\Seeder;

class PdfTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'         => 'Simple Photo Book',
                'slug'         => 'simple-photo-book',
                'description'  => 'A clean photo-book layout with title and images.',
                'paper_size'   => 'A4',
                'orientation'  => 'portrait',
                'variables'    => ['title', 'subtitle', 'author'],
                'html_content' => <<<'HTML'
<!DOCTYPE html>
<html>
<head>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; padding: 40px; }
  h1   { font-size: 28px; text-align: center; margin-bottom: 8px; }
  h2   { font-size: 16px; text-align: center; color: #666; margin-bottom: 4px; }
  p.author { text-align: center; font-style: italic; color: #999; margin-bottom: 32px; }
  .images img { max-width: 100%; height: auto; margin: 10px 0; display: block; }
</style>
</head>
<body>
  <h1>{{title}}</h1>
  <h2>{{subtitle}}</h2>
  <p class="author">by {{author}}</p>
  <div class="images">{{images}}</div>
</body>
</html>
HTML,
            ],
            [
                'name'         => 'Invoice',
                'slug'         => 'invoice',
                'description'  => 'A professional invoice template.',
                'paper_size'   => 'A4',
                'orientation'  => 'portrait',
                'variables'    => ['invoice_number', 'client_name', 'due_date', 'total_amount'],
                'html_content' => <<<'HTML'
<!DOCTYPE html>
<html>
<head>
<style>
  body { font-family: DejaVu Sans, sans-serif; padding: 40px; font-size: 13px; }
  h1   { font-size: 24px; margin-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 24px; }
  th, td { padding: 8px 12px; border: 1px solid #ddd; }
  th { background: #f3f4f6; text-align: left; }
  .total { font-weight: bold; font-size: 14px; }
</style>
</head>
<body>
  <h1>INVOICE</h1>
  <p><strong>Invoice #:</strong> {{invoice_number}}</p>
  <p><strong>Client:</strong> {{client_name}}</p>
  <p><strong>Due Date:</strong> {{due_date}}</p>
  <div class="images">{{images}}</div>
  <table>
    <tr><th>Description</th><th>Amount</th></tr>
    <tr><td>Services rendered</td><td>{{total_amount}}</td></tr>
    <tr class="total"><td>Total</td><td>{{total_amount}}</td></tr>
  </table>
</body>
</html>
HTML,
            ],
        ];

        foreach ($templates as $data) {
            PdfTemplate::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
