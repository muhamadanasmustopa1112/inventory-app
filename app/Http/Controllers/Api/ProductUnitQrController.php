<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProductUnitQrController extends Controller
{
    public function show(ProductUnit $productUnit)
    {
        $text = $productUnit->qr_value; 
        $size = 300;

        // 1) Generate QR PNG pakai simple-qrcode (ini sudah pasti jalan, karena kode kamu sebelumnya OK)
        $qrPng = QrCode::format('png')
            ->size($size)
            ->margin(1)
            ->generate($text);

        // 2) Ubah string PNG jadi resource gambar GD
        $qrImage = imagecreatefromstring($qrPng);
        if ($qrImage === false) {
            abort(500, 'Gagal membaca gambar QR');
        }

        $qrWidth  = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        // 3) Tambah tinggi untuk area teks
        $padding       = 40; // tinggi area teks
        $canvasHeight  = $qrHeight + $padding;

        // 4) Canvas baru (background putih)
        $canvas = imagecreatetruecolor($qrWidth, $canvasHeight);
        $white  = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        // 5) Tempel QR di bagian atas
        imagecopy($canvas, $qrImage, 0, 0, 0, 0, $qrWidth, $qrHeight);

        // 6) Tulis teks di bawah QR (font default bawaan GD)
        $black     = imagecolorallocate($canvas, 0, 0, 0);
        $font      = 5; // built-in font 1–5
        $textWidth = imagefontwidth($font) * strlen($text);
        $x         = (int) (($qrWidth - $textWidth) / 2); // center
        $y         = $qrHeight + 10; // sedikit di bawah QR

        imagestring($canvas, $font, max(0, $x), $y, $text, $black);

        // 7) Output lagi sebagai PNG
        ob_start();
        imagepng($canvas);
        $output = ob_get_clean();

        // 8) Bersihkan resource
        imagedestroy($qrImage);
        imagedestroy($canvas);

        return response($output)->header('Content-Type', 'image/png');
    }
}
