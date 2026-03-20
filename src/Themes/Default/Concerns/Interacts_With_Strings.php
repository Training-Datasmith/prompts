<?php

declare (strict_types=1);
namespace Laravel\Prompts\Themes\Default\Concerns;

trait Interacts_With_Strings
{
    /**
     * Get the length of the longest line.
     *
     * @param  array<string>  $lines
     */
    protected function longest(array $lines, int $padding = 0): int
    {
        return max($this->min_width, count($lines) > 0 ? max(array_map(fn($line): int => mb_strwidth($this->strip_escape_sequences($line)) + $padding, $lines)) : null);
    }
    /**
     * Pad text ignoring ANSI escape sequences.
     */
    protected function pad(string $text, int $length, string $char = ' '): string
    {
        $right_padding = str_repeat($char, max(0, $length - mb_strwidth($this->strip_escape_sequences($text))));
        return "{$text}{$right_padding}";
    }
    /**
     * Strip ANSI escape sequences from the given text.
     */
    protected function strip_escape_sequences(string $text): string
    {
        // Strip ANSI escape sequences.
        $text = preg_replace("/\x1b[^m]*m/", '', $text);
        // Strip Symfony named style tags.
        $text = preg_replace("/<(info|comment|question|error)>(.*?)<\\/\\1>/", '$2', (string) $text);
        // Strip Symfony inline style tags.
        return preg_replace("/<(?:(?:[fb]g|options)=[a-z,;]+)+>(.*?)<\\/>/i", '$1', (string) $text);
    }
    /**
     * Multi-byte version of wordwrap.
     *
     * @param  non-empty-string  $break
     */
    protected function mb_wordwrap(string $string, int $width = 75, string $break = "\n", bool $cut_long_words = false): string
    {
        $lines = explode($break, $string);
        $result = [];
        foreach ($lines as $original_line) {
            if (mb_strwidth($original_line) <= $width) {
                $result[] = $original_line;
                continue;
            }
            $words = explode(' ', $original_line);
            $line = null;
            $line_width = 0;
            if ($cut_long_words) {
                foreach ($words as $index => $word) {
                    $characters = mb_str_split($word);
                    $strings = [];
                    $str = '';
                    foreach ($characters as $character) {
                        $tmp = $str . $character;
                        if (mb_strwidth($tmp) > $width) {
                            $strings[] = $str;
                            $str = $character;
                        } else {
                            $str = $tmp;
                        }
                    }
                    if ($str !== '') {
                        $strings[] = $str;
                    }
                    $words[$index] = implode(' ', $strings);
                }
                $words = explode(' ', implode(' ', $words));
            }
            foreach ($words as $word) {
                $tmp = $line === null ? $word : $line . ' ' . $word;
                // Look for zero-width joiner characters (combined emojis)
                preg_match('/\p{Cf}/u', $word, $joiner_matches);
                $word_width = count($joiner_matches) > 0 ? 2 : mb_strwidth($word);
                $line_width += $word_width;
                if ($line !== null) {
                    // Space between words
                    $line_width += 1;
                }
                if ($line_width <= $width) {
                    $line = $tmp;
                } else {
                    $result[] = $line;
                    $line = $word;
                    $line_width = $word_width;
                }
            }
            if ($line !== '') {
                $result[] = $line;
            }
            $line = null;
        }
        return implode($break, $result);
    }
    /**
     * Word wrap text while preserving ANSI escape sequences.
     *
     * @return array<int, string>
     */
    protected function ansi_wordwrap(string $text, int $width): array
    {
        // Parse segments and build character array with codes
        $segments = $this->parse_ansi_text($text);
        $plain_text = $this->strip_escape_sequences($text);
        $chars = [];
        foreach ($segments as $segment) {
            $segment_chars = mb_str_split((string) $segment['text']);
            foreach ($segment_chars as $char) {
                $chars[] = ['char' => $char, 'codes' => $segment['codes']];
            }
        }
        // Word wrap the plain text
        $wrapped_lines = $this->mb_wordwrap($plain_text, $width, "\n", false);
        $plain_lines = explode("\n", $wrapped_lines);
        // Rebuild each wrapped line with ANSI codes
        $result = [];
        $char_index = 0;
        foreach ($plain_lines as $plain_line) {
            $line = '';
            $last_codes = '';
            $line_chars = mb_str_split($plain_line);
            foreach ($line_chars as $line_char) {
                // Find matching character in original (handling spaces removed by wordwrap)
                while ($char_index < count($chars) && $chars[$char_index]['char'] !== $line_char) {
                    // Skip spaces that wordwrap removed
                    if ($chars[$char_index]['char'] === ' ') {
                        $char_index++;
                    } else {
                        break;
                    }
                }
                if ($char_index < count($chars)) {
                    $codes = $chars[$char_index]['codes'];
                    if ($codes !== $last_codes) {
                        if ($last_codes !== '') {
                            $line .= "\x1b[0m";
                        }
                        if ($codes !== '') {
                            $line .= $codes;
                        }
                        $last_codes = $codes;
                    }
                    $line .= $line_char;
                    $char_index++;
                } else {
                    $line .= $line_char;
                }
            }
            // Close any open ANSI codes
            if ($last_codes !== '' && !str_ends_with($line, "\x1b[0m")) {
                $line .= "\x1b[0m";
            }
            $result[] = $line;
        }
        return $result;
    }
    /**
     * Parse text into segments with their associated ANSI codes.
     *
     * @return array<int, array{text: string, codes: string}>
     */
    protected function parse_ansi_text(string $text): array
    {
        $segments = [];
        $current_codes = '';
        $current_text = '';
        $i = 0;
        $text_length = strlen($text);
        while ($i < $text_length) {
            if ($text[$i] === "\x1b" && $i + 1 < $text_length && $text[$i + 1] === '[') {
                // Save current segment if it has text
                if ($current_text !== '') {
                    $segments[] = ['text' => $current_text, 'codes' => $current_codes];
                    $current_text = '';
                }
                // Extract ANSI escape sequence
                $escape_sequence = '';
                while ($i < $text_length) {
                    $escape_sequence .= $text[$i];
                    $i++;
                    if (preg_match('/^\e\[[0-9;]*m$/', $escape_sequence)) {
                        // Update current codes
                        if ($escape_sequence === "\x1b[0m") {
                            $current_codes = '';
                        } else {
                            $current_codes = $escape_sequence;
                        }
                        break;
                    }
                }
                continue;
            }
            $current_text .= $text[$i];
            $i++;
        }
        // Add final segment
        if ($current_text !== '') {
            $segments[] = ['text' => $current_text, 'codes' => $current_codes];
        }
        return $segments;
    }
}