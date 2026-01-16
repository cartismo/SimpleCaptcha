<?php

namespace Modules\SimpleCaptcha\Services;

use App\Models\InstalledModule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SimpleCaptchaService
{
    protected ?array $settings = null;

    protected const SESSION_KEY = 'simple_captcha';

    /**
     * Get module settings
     */
    public function getSettings(): array
    {
        if ($this->settings === null) {
            $module = InstalledModule::where('slug', 'simple-captcha')->first();
            $this->settings = $module?->settings ?? config('simplecaptcha.defaults', []);
        }

        return $this->settings;
    }

    /**
     * Check if SimpleCaptcha is enabled
     */
    public function isEnabled(): bool
    {
        return $this->getSettings()['enabled'] ?? false;
    }

    /**
     * Check if a specific form should be protected
     */
    public function isFormProtected(string $formName): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $protectedForms = $this->getSettings()['protected_forms'] ?? [];
        return $protectedForms[$formName] ?? false;
    }

    /**
     * Generate a new captcha challenge
     */
    public function generate(): array
    {
        $settings = $this->getSettings();
        $type = $settings['type'] ?? 'math';

        if ($type === 'math') {
            return $this->generateMathCaptcha();
        }

        return $this->generateImageCaptcha();
    }

    /**
     * Generate math captcha
     */
    protected function generateMathCaptcha(): array
    {
        $difficulty = $this->getSettings()['difficulty'] ?? 'easy';

        switch ($difficulty) {
            case 'hard':
                $num1 = rand(10, 99);
                $num2 = rand(10, 99);
                $operators = ['+', '-', '*'];
                break;
            case 'medium':
                $num1 = rand(5, 20);
                $num2 = rand(5, 20);
                $operators = ['+', '-'];
                break;
            default: // easy
                $num1 = rand(1, 10);
                $num2 = rand(1, 10);
                $operators = ['+'];
        }

        $operator = $operators[array_rand($operators)];

        // Ensure subtraction doesn't result in negative
        if ($operator === '-' && $num2 > $num1) {
            [$num1, $num2] = [$num2, $num1];
        }

        $answer = match ($operator) {
            '+' => $num1 + $num2,
            '-' => $num1 - $num2,
            '*' => $num1 * $num2,
            default => $num1 + $num2,
        };

        $id = Str::random(32);
        $expiry = time() + ($this->getSettings()['expiry'] ?? 300);

        // Store in session
        Session::put(self::SESSION_KEY . '.' . $id, [
            'answer' => (string) $answer,
            'expiry' => $expiry,
        ]);

        return [
            'id' => $id,
            'type' => 'math',
            'question' => "{$num1} {$operator} {$num2} = ?",
            'image' => null,
        ];
    }

    /**
     * Generate image captcha
     */
    protected function generateImageCaptcha(): array
    {
        $settings = $this->getSettings();
        $length = $settings['length'] ?? 5;
        $caseSensitive = $settings['case_sensitive'] ?? false;

        // Generate random string
        $characters = $caseSensitive
            ? 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'
            : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        $text = '';
        for ($i = 0; $i < $length; $i++) {
            $text .= $characters[rand(0, strlen($characters) - 1)];
        }

        $id = Str::random(32);
        $expiry = time() + ($settings['expiry'] ?? 300);

        // Store in session
        Session::put(self::SESSION_KEY . '.' . $id, [
            'answer' => $caseSensitive ? $text : strtoupper($text),
            'expiry' => $expiry,
            'case_sensitive' => $caseSensitive,
        ]);

        // Generate image
        $image = $this->createCaptchaImage($text);

        return [
            'id' => $id,
            'type' => 'image',
            'question' => 'Enter the characters shown',
            'image' => $image,
        ];
    }

    /**
     * Create captcha image as base64
     */
    protected function createCaptchaImage(string $text): string
    {
        $width = 180;
        $height = 60;

        $image = imagecreatetruecolor($width, $height);

        // Colors
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 50, 50, 50);
        $noiseColor = imagecolorallocate($image, 150, 150, 150);
        $lineColor = imagecolorallocate($image, 200, 200, 200);

        // Fill background
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Add noise dots
        for ($i = 0; $i < 100; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
        }

        // Add lines
        for ($i = 0; $i < 5; $i++) {
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }

        // Add text
        $fontSize = 5; // Built-in font
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);

        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;

        // Draw each character with slight position variation
        for ($i = 0; $i < strlen($text); $i++) {
            $charX = $x + ($i * imagefontwidth($fontSize)) + rand(-2, 2);
            $charY = $y + rand(-5, 5);
            imagestring($image, $fontSize, $charX, $charY, $text[$i], $textColor);
        }

        // Capture image
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    /**
     * Verify captcha response
     */
    public function verify(string $id, string $answer): bool
    {
        if (!$this->isEnabled()) {
            return true; // Skip verification if not enabled
        }

        $stored = Session::get(self::SESSION_KEY . '.' . $id);

        if (!$stored) {
            return false;
        }

        // Remove from session (one-time use)
        Session::forget(self::SESSION_KEY . '.' . $id);

        // Check expiry
        if (time() > $stored['expiry']) {
            return false;
        }

        // Compare answer
        $caseSensitive = $stored['case_sensitive'] ?? false;

        if ($caseSensitive) {
            return $answer === $stored['answer'];
        }

        return strtoupper(trim($answer)) === strtoupper($stored['answer']);
    }

    /**
     * Get frontend configuration
     */
    public function getFrontendConfig(): array
    {
        if (!$this->isEnabled()) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'type' => $this->getSettings()['type'] ?? 'math',
        ];
    }
}