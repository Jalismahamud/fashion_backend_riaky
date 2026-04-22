<?php

namespace App\Helper;

use getID3;
use Illuminate\Support\Str;

class Helper
{
    public static function uploadImage($file, $folder)
    {
        if (! $file->isValid()) {
            return null;
        }

        $uniqueId  = uniqid();
        $extension = $file->getClientOriginalExtension();
        $imageName = Str::slug(time() . '-' . $uniqueId) . '.' . $extension;
        $path      = public_path('uploads/' . $folder);

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $file->move($path, $imageName);

        return 'uploads/' . $folder . '/' . $imageName;
    }

    public static function deleteImage($imageUrl)
    {
        if (! $imageUrl) {
            return false;
        }
        $filePath = public_path($imageUrl);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    public static function deleteImageFromLink($imageLink)
    {
        if (!$imageLink) {
            return false;
        }

        $parsedUrl = parse_url($imageLink, PHP_URL_PATH);
        $filePath = public_path(ltrim($parsedUrl, '/'));
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    public static function deleteAvatar($filePath)
    {
        if (! $filePath) {
            return false;
        }

        $relativePath = str_replace(asset('/'), '', $filePath);
        $fullPath     = public_path($relativePath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
            return true;
        }

        return false;
    }



    public static function getVideoDurationFormatted($relativePath)
    {
        $absolutePath = storage_path('app/public/' . $relativePath);

        $getID3 = new getID3();
        $info = $getID3->analyze($absolutePath);

        if (!isset($info['playtime_seconds'])) {
            return null;
        }

        return gmdate("H:i:s", (int)$info['playtime_seconds']);
    }

    public static function formatNumberShort($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'k';
        } else {
            return (string) $number;
        }
    }

    public static function getStyleProfile($userId)
    {
        $categories = [
            'A' => [
                'keywords' => ['blazer', 'neutral', 'cotton', 'silk', 'high neck', 'minimal', 'classic', 'structure', 'elegant', 'modest', 'timeless', 'clean', 'flowy skirt'],
                'description' => 'Elegant Minimalist / Parisian Classic',
                'details' => 'Likes full coverage, wears neutrals, prioritizes elegance, structure, or simplicity, loves cotton, silk, or clean accessories. Style is polished, modest, timeless, and clean.',
                'style_keywords' => 'Blazers, flowy skirts, high necks, neutral tones.'
            ],
            'B' => [
                'keywords' => ['maxi', 'ruffle', 'cardigan', 'pastel', 'soft', 'modest', 'romantic', 'feminine', 'earthy', 'gentle', 'layered', 'comfortable', 'elegant'],
                'description' => 'Soft Modest / Romantic Feminine',
                'details' => 'Loves modesty with light fabrics, earthy or soft colors, chooses comfort and femininity, admires gentle, layered looks. Style is delicate, comfortable, and elegant.',
                'style_keywords' => 'Maxi dresses, ruffles, loose cardigans, pastels.'
            ],
            'C' => [
                'keywords' => ['fitted', 'color block', 'playful', 'trend', 'confident', 'cool', 'bold', 'pastel', 'modern', 'expressive', 'skin show'],
                'description' => 'Trend-Aware Feminine / Confident Cool',
                'details' => 'Okay with light skin show, wears bold or pastel colors, chooses confidence, trends, and expression. Style is feminine but bold, modern, expressive.',
                'style_keywords' => 'Fitted tops, color blocking, playful accessorizing.'
            ],
            'D' => [
                'keywords' => ['wide pant', 'oversized', 'layered', 'scarf', 'leather', 'denim', 'street', 'creative', 'edge', 'city', 'tokyo', 'nyc', 'fun', 'fashion-forward', 'unique'],
                'description' => 'Bold & Street-Chic / Creative Edge',
                'details' => 'Oversized or mix styles, bold colors or leather/denim, chooses uniqueness, loves city fashion like Tokyo or NYC. Style is fun, layered, expressive, fashion-forward.',
                'style_keywords' => 'Wide pants, oversized shirts, layered scarves.'
            ],
            'E/F' => [
                'keywords' => ['mix', 'hybrid', 'boho', 'classy', 'explore', 'capsule', 'modest trendy'],
                'description' => 'Mixed Style or Still Exploring',
                'details' => 'Mix of modesty and boldness, likes trying everything, may need to define core preferences. Try hybrid styles like “Modest Trendy” or “Boho Classy”.',
                'style_keywords' => 'Build capsule outfits with a mix of fabrics and styles until they feel “right”.'
            ],
        ];

        $scores = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E/F' => 0];
        $answerDetails = [];
        $answers = \App\Models\StyleQuizAnswer::where('user_id', $userId)
            ->with(['question', 'option'])
            ->get();
        foreach ($answers as $a) {
            $answerText = strtolower($a->option ? $a->option->option_text : $a->text_answer);
            $answerDetails[] = [
                'question' => $a->question->question_text,
                'answer' => $answerText,
            ];
            foreach ($categories as $key => $cat) {
                foreach ($cat['keywords'] as $kw) {
                    if (strpos($answerText, strtolower($kw)) !== false) {
                        $scores[$key]++;
                    }
                }
            }
        }

        $maxScore = max($scores);
        $dominant = array_keys($scores, $maxScore);
        $dominantStyle = count($dominant) === 1 ? $dominant[0] : 'E/F';
        $cat = $categories[$dominantStyle];

        $data = [
            'type' => $cat['description'],
            'details' => $cat['details'],
            'keywords' => $cat['style_keywords'],
            'score_breakdown' => $scores,
        ];

        return $data;
    }
}
