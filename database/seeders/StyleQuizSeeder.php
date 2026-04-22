<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StyleQuizQuestion;
use App\Models\StyleQuizOption;

class StyleQuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [
                'question_text' => 'How much do you like to cover your body?',
                'options' => [
                    'A. I like to be fully covered (arms, legs, chest)',
                    'B. I like to cover up but still look stylish',
                    'C. I\'m okay showing some skin if it looks nice',
                    'D. I don\'t mind anything — I wear what feels right in the moment',
                ],
            ],
            [
                'question_text' => 'What colors do you wear most?',
                'options' => [
                    'A. Neutrals colors (black, white, beige, cream)',
                    'B. Earthy colors (brown, olive, camel)',
                    'C. Light colors (pink, mint, baby blue)',
                    'D. Bold colors (red, green, blue)',
                    'E. I don\'t know',
                ],
            ],
            [
                'question_text' => 'Which colors look best on your skin tone?',
                'options' => [
                    'A. Warm tones (orange, gold, rust)',
                    'B. Cool tones (blue, gray, silver)',
                    'C. Any color — I make it work!',
                    'D. I don\'t know',
                ],
            ],
            [
                'question_text' => 'Which one describes your body shape best?',
                'options' => [
                    'A. Hourglass (shoulders, waist, hips are similar widths)',
                    'B. Pear (hips wider than shoulders)',
                    'C. Apple (upper body bigger than lower body, comfort matters)',
                    'D. Rectangle (shoulders, waist, and hips are straight)',
                    'E. I don\'t know',
                ],
            ],
            [
                'question_text' => 'Choose 1 words that sound like your style:',
                'options' => [
                    'A. Elegant',
                    'B. Edgy',
                    'C. Creative',
                    'D. Minimalist',
                    'E. Relaxed',
                ],
            ],
            [
                'question_text' => 'What kind of fabric or material do you like most?',
                'options' => [
                    'A. Cotton or knits (soft and breathable)',
                    'B. Silk or velvet (smooth and shiny)',
                    'C. Wool or denim (warm and strong)',
                    'D. Synthetic (polyester, nylon, spandex)',
                    'E. Light fabrics (chiffon and linen for hot days)',
                    'F. Future fabrics',
                ],
            ],
            [
                'question_text' => 'What matters most when you dress?',
                'options' => [
                    'A. Feeling comfortable',
                    'B. Looking elegant',
                    'C. Creating a bold look',
                    'D. Dressing for work',
                    'E. Looking unique and different',
                ],
            ],
            [
                'question_text' => 'How do you wear accessories?',
                'options' => [
                    'A. Just one major piece (watch, big ring)',
                    'B. A few statement pieces (earrings, scarf, glasses)',
                    'C. I don\'t wear accessories much',
                ],
            ],
            [
                'question_text' => 'What kind of outfits do you admire most?',
                'options' => [
                    'A. Classic, elegant outfits',
                    'B. Loose, elegant, minimal clothes',
                    'C. Bright and creative use of outfits',
                    'D. Soft and textured dresses',
                    'E. Sporty and neat outfits',
                    'F. Fantastical or fun style (e.g. light coats)',
                ],
            ],
            [
                'question_text' => 'Which city feels like your style?',
                'options' => [
                    'A. Milan — simple and elegant',
                    'B. Marrakech — rich colors and modern textures',
                    'C. New York — crisp, strong, and modern clothes',
                    'D. Tokyo — fun, creative, and unique',
                    'E. Paris — modern, creative with real confidence',
                    'F. London',
                ],
            ],
        ];

        foreach ($questions as $q) {
            $question = StyleQuizQuestion::create([
                'question_text' => $q['question_text'],
                'status' => 0,
            ]);
            foreach ($q['options'] as $option) {
                StyleQuizOption::create([
                    'question_id' => $question->id,
                    'option_text' => $option,
                ]);
            }
        }
    }
}
