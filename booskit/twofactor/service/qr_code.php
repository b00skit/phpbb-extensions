<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\service;

/**
 * Self-contained pure PHP QR Code generator (SVG output)
 * Zero external dependencies.
 */
class qr_code
{
    // Reed-Solomon Galois Field tables
    protected static $gf_exp = [];
    protected static $gf_log = [];
    protected static $tables_initialized = false;

    // Capacity & Error Correction table for Version 1..10, Error Correction Level 'M' (15%)
    // [version => ['ec_blocks' => [[num_blocks, total_words, ec_words], ...], 'align' => [...]]]
    protected static $version_table = [
        1 => ['total_ec' => 10, 'blocks' => [[1, 26, 16, 10]], 'align' => []],
        2 => ['total_ec' => 16, 'blocks' => [[1, 44, 28, 16]], 'align' => [6, 18]],
        3 => ['total_ec' => 26, 'blocks' => [[1, 70, 44, 26]], 'align' => [6, 22]],
        4 => ['total_ec' => 36, 'blocks' => [[2, 50, 32, 18]], 'align' => [6, 26]],
        5 => ['total_ec' => 48, 'blocks' => [[2, 67, 43, 24]], 'align' => [6, 30]],
        6 => ['total_ec' => 64, 'blocks' => [[4, 43, 27, 16]], 'align' => [6, 34]],
        7 => ['total_ec' => 72, 'blocks' => [[4, 49, 31, 18]], 'align' => [6, 22, 38]],
        8 => ['total_ec' => 88, 'blocks' => [[2, 60, 38, 22], [2, 61, 39, 22]], 'align' => [6, 24, 42]],
        9 => ['total_ec' => 110, 'blocks' => [[3, 58, 36, 22], [2, 59, 37, 22]], 'align' => [6, 26, 46]],
        10 => ['total_ec' => 130, 'blocks' => [[4, 69, 43, 26], [1, 70, 44, 26]], 'align' => [6, 28, 50]],
    ];

    public function __construct()
    {
        $this->init_gf_tables();
    }

    protected function init_gf_tables()
    {
        if (self::$tables_initialized) {
            return;
        }

        self::$gf_exp = array_fill(0, 512, 0);
        self::$gf_log = array_fill(0, 256, 0);

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gf_exp[$i] = $x;
            self::$gf_log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D; // Primitive polynomial 285
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$gf_exp[$i] = self::$gf_exp[$i - 255];
        }

        self::$tables_initialized = true;
    }

    /**
     * Generate SVG string for given text/URL
     *
     * @param string $text
     * @param int $size Width and height in px
     * @param int $margin Quiet zone in modules
     * @return string SVG XML
     */
    public function generate_svg($text, $size = 220, $margin = 4)
    {
        $matrix = $this->encode_matrix($text);
        if (!$matrix) {
            return '';
        }

        $module_count = count($matrix);
        $total_size = $module_count + ($margin * 2);

        $path_data = '';
        for ($r = 0; $r < $module_count; $r++) {
            for ($c = 0; $c < $module_count; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = $c + $margin;
                    $y = $r + $margin;
                    $path_data .= "M{$x},{$y}h1v1h-1z ";
                }
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $total_size . ' ' . $total_size . '" width="' . (int)$size . '" height="' . (int)$size . '" shape-rendering="crispEdges">' .
               '<rect width="100%" height="100%" fill="#ffffff" />' .
               '<path fill="#111827" d="' . trim($path_data) . '" />' .
               '</svg>';

        return $svg;
    }

    /**
     * Generate base64 data URI for inline <img> rendering
     *
     * @param string $text
     * @param int $size
     * @return string
     */
    public function generate_data_uri($text, $size = 220)
    {
        $svg = $this->generate_svg($text, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Encode text into 2D QR matrix array
     */
    protected function encode_matrix($text)
    {
        $data_bytes = array_values(unpack('C*', $text));
        $data_len = count($data_bytes);

        // Select lowest version capable of holding this data in Byte Mode (Level M)
        $version = null;
        foreach (self::$version_table as $ver => $info) {
            $cap_data_words = 0;
            foreach ($info['blocks'] as $b) {
                $cap_data_words += $b[0] * $b[2];
            }
            // Byte mode overhead: 4 bits mode + (8 or 16 bits count)
            $count_bits = ($ver <= 9) ? 8 : 16;
            $max_bytes = floor(($cap_data_words * 8 - 4 - $count_bits) / 8);
            if ($data_len <= $max_bytes) {
                $version = $ver;
                break;
            }
        }

        if ($version === null) {
            $version = 10;
        }

        $ver_info = self::$version_table[$version];
        $total_data_capacity = 0;
        foreach ($ver_info['blocks'] as $b) {
            $total_data_capacity += $b[0] * $b[2];
        }

        // Build data bitstream
        $bits = '';
        // 1. Mode indicator: Byte mode (0100)
        $bits .= '0100';
        // 2. Character count
        $count_bits = ($version <= 9) ? 8 : 16;
        $bits .= str_pad(decbin($data_len), $count_bits, '0', STR_PAD_LEFT);
        // 3. Data bytes
        foreach ($data_bytes as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        // 4. Terminator (up to 4 zeroes)
        $max_data_bits = $total_data_capacity * 8;
        $bits .= substr('0000', 0, max(0, min(4, $max_data_bits - strlen($bits))));

        // 5. Pad to multiple of 8
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
        }

        // 6. Pad bytes (0xEC, 0x11)
        $pad_bytes = ['11101100', '00010001'];
        $pad_idx = 0;
        while (strlen($bits) < $max_data_bits) {
            $bits .= $pad_bytes[$pad_idx % 2];
            $pad_idx++;
        }

        // Convert bit stream to codewords
        $data_words = [];
        $chunks = str_split($bits, 8);
        foreach ($chunks as $chunk) {
            $data_words[] = bindec($chunk);
        }

        // Divide data into blocks and calculate Reed-Solomon EC for each block
        $blocks_data = [];
        $blocks_ec = [];
        $data_offset = 0;

        foreach ($ver_info['blocks'] as $group) {
            $num_blocks = $group[0];
            $data_words_per_block = $group[2];
            $ec_words_per_block = $group[3];

            for ($b = 0; $b < $num_blocks; $b++) {
                $block_data = array_slice($data_words, $data_offset, $data_words_per_block);
                $data_offset += $data_words_per_block;

                $block_ec = $this->calc_rs_ecc($block_data, $ec_words_per_block);

                $blocks_data[] = $block_data;
                $blocks_ec[] = $block_ec;
            }
        }

        // Interleave data codewords
        $final_codewords = [];
        $max_data_len = 0;
        foreach ($blocks_data as $bd) {
            $max_data_len = max($max_data_len, count($bd));
        }
        for ($i = 0; $i < $max_data_len; $i++) {
            foreach ($blocks_data as $bd) {
                if ($i < count($bd)) {
                    $final_codewords[] = $bd[$i];
                }
            }
        }

        // Interleave EC codewords
        $max_ec_len = count($blocks_ec[0]);
        for ($i = 0; $i < $max_ec_len; $i++) {
            foreach ($blocks_ec as $bec) {
                if ($i < count($bec)) {
                    $final_codewords[] = $bec[$i];
                }
            }
        }

        // Build matrix
        $size = 17 + 4 * $version;
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // Place function patterns
        $this->place_finder_pattern($matrix, $reserved, 0, 0);
        $this->place_finder_pattern($matrix, $reserved, 0, $size - 7);
        $this->place_finder_pattern($matrix, $reserved, $size - 7, 0);

        $this->place_timing_patterns($matrix, $reserved, $size);

        if (!empty($ver_info['align'])) {
            $this->place_alignment_patterns($matrix, $reserved, $ver_info['align']);
        }

        // Dark module
        $matrix[4 * $version + 9][8] = 1;
        $reserved[4 * $version + 9][8] = true;

        // Reserve format info areas
        $this->reserve_format_areas($reserved, $size);

        // Place data bits into matrix
        $this->place_data_bits($matrix, $reserved, $final_codewords, $size);

        // Apply best mask (Level M = 00, choose mask 0..7 with lowest penalty)
        $best_mask = 0;
        $best_score = PHP_INT_MAX;
        $best_matrix = null;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate_matrix = $matrix;
            $this->apply_mask($candidate_matrix, $reserved, $mask, $size);
            $this->place_format_info($candidate_matrix, $mask, $size); // Level M
            $score = $this->calc_penalty_score($candidate_matrix, $size);
            if ($score < $best_score) {
                $best_score = $score;
                $best_mask = $mask;
                $best_matrix = $candidate_matrix;
            }
        }

        return $best_matrix;
    }

    protected function calc_rs_ecc($data, $ec_count)
    {
        // Generate generator polynomial
        $gen = [1];
        for ($i = 0; $i < $ec_count; $i++) {
            $root = self::$gf_exp[$i];
            $new_gen = array_fill(0, count($gen) + 1, 0);
            for ($j = 0; $j < count($gen); $j++) {
                $new_gen[$j] ^= $this->gf_mul($gen[$j], $root);
                $new_gen[$j + 1] ^= $gen[$j];
            }
            $gen = $new_gen;
        }

        $res = array_merge($data, array_fill(0, $ec_count, 0));
        $data_len = count($data);

        for ($i = 0; $i < $data_len; $i++) {
            $coef = $res[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($gen); $j++) {
                    $res[$i + $j] ^= $this->gf_mul($gen[$j], $coef);
                }
            }
        }

        return array_slice($res, $data_len);
    }

    protected function gf_mul($x, $y)
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }
        return self::$gf_exp[(self::$gf_log[$x] + self::$gf_log[$y]) % 255];
    }

    protected function place_finder_pattern(&$matrix, &$reserved, $row, $col)
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $mr = $row + $r;
                $mc = $col + $c;
                if ($mr < 0 || $mr >= count($matrix) || $mc < 0 || $mc >= count($matrix)) {
                    continue;
                }
                $reserved[$mr][$mc] = true;
                if ($r === -1 || $r === 7 || $c === -1 || $c === 7) {
                    $matrix[$mr][$mc] = 0;
                } elseif ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                    $matrix[$mr][$mc] = 1;
                } else {
                    $matrix[$mr][$mc] = 0;
                }
            }
        }
    }

    protected function place_timing_patterns(&$matrix, &$reserved, $size)
    {
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            if (!$reserved[6][$i]) {
                $matrix[6][$i] = $val;
                $reserved[6][$i] = true;
            }
            if (!$reserved[$i][6]) {
                $matrix[$i][6] = $val;
                $reserved[$i][6] = true;
            }
        }
    }

    protected function place_alignment_patterns(&$matrix, &$reserved, $positions)
    {
        $count = count($positions);
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $r = $positions[$i];
                $c = $positions[$j];
                // Skip if overlapping finder patterns
                if ($reserved[$r][$c]) {
                    continue;
                }
                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $mr = $r + $dr;
                        $mc = $c + $dc;
                        $reserved[$mr][$mc] = true;
                        if (abs($dr) === 2 || abs($dc) === 2 || ($dr === 0 && $dc === 0)) {
                            $matrix[$mr][$mc] = 1;
                        } else {
                            $matrix[$mr][$mc] = 0;
                        }
                    }
                }
            }
        }
    }

    protected function reserve_format_areas(&$reserved, $size)
    {
        for ($i = 0; $i <= 8; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = $size - 8; $i < $size; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
    }

    protected function place_data_bits(&$matrix, &$reserved, $codewords, $size)
    {
        $bits = '';
        foreach ($codewords as $cw) {
            $bits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }
        $bit_idx = 0;
        $bit_len = strlen($bits);

        $col = $size - 1;
        $up = true;

        while ($col > 0) {
            if ($col === 6) {
                $col--; // Skip vertical timing pattern
            }
            $rows = $up ? range($size - 1, 0) : range(0, $size - 1);
            foreach ($rows as $r) {
                foreach ([$col, $col - 1] as $c) {
                    if (!$reserved[$r][$c]) {
                        $matrix[$r][$c] = ($bit_idx < $bit_len) ? (int)$bits[$bit_idx] : 0;
                        $bit_idx++;
                    }
                }
            }
            $up = !$up;
            $col -= 2;
        }
    }

    protected function apply_mask(&$matrix, &$reserved, $mask, $size)
    {
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($reserved[$r][$c]) {
                    continue;
                }
                $invert = false;
                switch ($mask) {
                    case 0: $invert = (($r + $c) % 2 === 0); break;
                    case 1: $invert = ($r % 2 === 0); break;
                    case 2: $invert = ($c % 3 === 0); break;
                    case 3: $invert = (($r + $c) % 3 === 0); break;
                    case 4: $invert = ((floor($r / 2) + floor($c / 3)) % 2 === 0); break;
                    case 5: $invert = ((($r * $c) % 2) + (($r * $c) % 3) === 0); break;
                    case 6: $invert = (((($r * $c) % 2) + (($r * $c) % 3)) % 2 === 0); break;
                    case 7: $invert = (((($r + $c) % 2) + (($r * $c) % 3)) % 2 === 0); break;
                }
                if ($invert) {
                    $matrix[$r][$c] ^= 1;
                }
            }
        }
    }

    protected function place_format_info(&$matrix, $mask, $size)
    {
        // Error Correction Level M = 00 in QR spec (Level L = 01, Level M = 00, Level Q = 11, Level H = 10)
        // Format data: 5 bits (2 bits EC + 3 bits mask)
        $ec_bits = 0b00; // Level M
        $data = ($ec_bits << 3) | $mask;

        // 10 error correction bits with generator poly 0x537 (BCH 15,5)
        $d = $data << 10;
        $poly = 0x537 << 4;
        for ($i = 4; $i >= 0; $i--) {
            if ($d & (1 << ($i + 10))) {
                $d ^= (0x537 << $i);
            }
        }
        $format_info = (($data << 10) | $d) ^ 0x5412; // Mask with 0x5412

        $bits = str_pad(decbin($format_info), 15, '0', STR_PAD_LEFT);

        // Place around top-left
        $coords_tl = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8]
        ];
        for ($i = 0; $i < 15; $i++) {
            $matrix[$coords_tl[$i][0]][$coords_tl[$i][1]] = (int)$bits[$i];
        }

        // Place around bottom-left / top-right
        for ($i = 0; $i < 7; $i++) {
            $matrix[$size - 1 - $i][8] = (int)$bits[$i];
        }
        for ($i = 7; $i < 15; $i++) {
            $matrix[8][$size - 15 + $i] = (int)$bits[$i];
        }
    }

    protected function calc_penalty_score($matrix, $size)
    {
        $penalty = 0;

        // Rule 1: 5+ consecutive same color in row/col
        for ($r = 0; $r < $size; $r++) {
            $count = 1;
            for ($c = 1; $c < $size; $c++) {
                if ($matrix[$r][$c] === $matrix[$r][$c - 1]) {
                    $count++;
                } else {
                    if ($count >= 5) {
                        $penalty += 3 + ($count - 5);
                    }
                    $count = 1;
                }
            }
            if ($count >= 5) {
                $penalty += 3 + ($count - 5);
            }
        }

        for ($c = 0; $c < $size; $c++) {
            $count = 1;
            for ($r = 1; $r < $size; $r++) {
                if ($matrix[$r][$c] === $matrix[$r - 1][$c]) {
                    $count++;
                } else {
                    if ($count >= 5) {
                        $penalty += 3 + ($count - 5);
                    }
                    $count = 1;
                }
            }
            if ($count >= 5) {
                $penalty += 3 + ($count - 5);
            }
        }

        // Rule 2: 2x2 blocks of same color
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $val = $matrix[$r][$c];
                if ($val === $matrix[$r][$c + 1] && $val === $matrix[$r + 1][$c] && $val === $matrix[$r + 1][$c + 1]) {
                    $penalty += 3;
                }
            }
        }

        // Rule 4: Total dark module proportion
        $dark = 0;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $dark++;
                }
            }
        }
        $ratio = ($dark / ($size * $size)) * 100;
        $prev_multiple = floor($ratio / 5) * 5;
        $next_multiple = ceil($ratio / 5) * 5;
        $penalty += min(abs($prev_multiple - 50), abs($next_multiple - 50)) * 2;

        return $penalty;
    }
}
