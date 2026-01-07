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

        /* =======================
         * GENERATE QR
         * ======================= */
        $qrPng = QrCode::format('png')
            ->size($size)
            ->margin(1)
            ->generate($text);

        $qrImage = imagecreatefromstring($qrPng);
        if (!$qrImage) {
            abort(500, 'Gagal membuat QR');
        }

        $qrWidth  = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        /* =======================
         * LOAD LOGO
         * ======================= */
        $logoPath = public_path('assets/logo-telkomaterial.png');
        if (!file_exists($logoPath)) {
            abort(500, 'Logo Telkomaterial tidak ditemukan');
        }

        $logo = imagecreatefrompng($logoPath);
        imagesavealpha($logo, true);
        $logo = imagecropauto($logo, IMG_CROP_TRANSPARENT);

        $logoWidth  = imagesx($logo);
        $logoHeight = imagesy($logo);

        $logoTargetWidth  = (int) ($qrWidth * 1);
        $logoTargetHeight = (int) (
            ($logoHeight / $logoWidth) * $logoTargetWidth
        );

        /* =======================
         * POSISI (TIDAK DIUBAH)
         * ======================= */
        $logoPadding = 2;

        $opticalOffset = (int) ($logoTargetWidth * 0.03);
        $logoX = (int)(($qrWidth - $logoTargetWidth) / 2) + $opticalOffset;
        $logoY = -20;

        $qrY = $logoTargetHeight + $logoPadding - 70;

        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textX = (int)(($qrWidth - $textWidth) / 2);
        $textY = $qrY + $qrHeight + 5;

        /* =======================
         * HITUNG CANVAS (AUTO FIT)
         * ======================= */
        $bottomPadding = 25;
        $canvasHeight = $textY + imagefontheight($font) + $bottomPadding;

        $canvas = imagecreatetruecolor($qrWidth, $canvasHeight);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        /* =======================
         * DRAW LOGO
         * ======================= */
        imagecopyresampled(
            $canvas,
            $logo,
            $logoX,
            $logoY,
            0,
            0,
            $logoTargetWidth,
            $logoTargetHeight,
            $logoWidth,
            $logoHeight
        );

        /* =======================
         * DRAW QR
         * ======================= */
        imagecopy(
            $canvas,
            $qrImage,
            0,
            $qrY,
            0,
            0,
            $qrWidth,
            $qrHeight
        );

        /* =======================
         * DRAW TEXT
         * ======================= */
        $black = imagecolorallocate($canvas, 0, 0, 0);
        imagestring($canvas, $font, max(0, $textX), $textY, $text, $black);

        /* =======================
         * OUTPUT
         * ======================= */
        ob_start();
        imagepng($canvas);
        $output = ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($logo);
        imagedestroy($canvas);

        return response($output)
            ->header('Content-Type', 'image/png');
    }
}
