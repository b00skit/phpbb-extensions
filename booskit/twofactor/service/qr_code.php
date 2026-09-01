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

    // Capacity & Error Correction table for Versions 1..40, Error Correction Level 'M' (15%)
    // [version => ['blocks' => [[num_blocks, total_words, data_words, ec_words], ...], 'align' => [...]]]
    protected static $version_table = [
        1 => ['blocks' => [[1, 26, 16, 10]], 'align' => []],
        2 => ['blocks' => [[1, 44, 28, 16]], 'align' => [6, 18]],
        3 => ['blocks' => [[1, 70, 44, 26]], 'align' => [6, 22]],
        4 => ['blocks' => [[2, 50, 32, 18]], 'align' => [6, 26]],
        5 => ['blocks' => [[2, 67, 43, 24]], 'align' => [6, 30]],
        6 => ['blocks' => [[4, 43, 27, 16]], 'align' => [6, 34]],
        7 => ['blocks' => [[4, 49, 31, 18]], 'align' => [6, 22, 38]],
        8 => ['blocks' => [[2, 60, 38, 22], [2, 61, 39, 22]], 'align' => [6, 24, 42]],
        9 => ['blocks' => [[3, 58, 36, 22], [2, 59, 37, 22]], 'align' => [6, 26, 46]],
        10 => ['blocks' => [[4, 69, 43, 26], [1, 70, 44, 26]], 'align' => [6, 28, 50]],
        11 => ['blocks' => [[1, 80, 50, 30], [4, 81, 51, 30]], 'align' => [6, 30, 54]],
        12 => ['blocks' => [[6, 58, 36, 22], [2, 59, 37, 22]], 'align' => [6, 32, 58]],
        13 => ['blocks' => [[8, 59, 37, 22], [1, 60, 38, 22]], 'align' => [6, 34, 62]],
        14 => ['blocks' => [[4, 64, 40, 24], [5, 65, 41, 24]], 'align' => [6, 26, 46, 66]],
        15 => ['blocks' => [[5, 65, 41, 24], [5, 66, 42, 24]], 'align' => [6, 26, 48, 70]],
        16 => ['blocks' => [[7, 73, 45, 28], [3, 74, 46, 28]], 'align' => [6, 26, 50, 74]],
        17 => ['blocks' => [[10, 74, 46, 28], [1, 75, 47, 28]], 'align' => [6, 30, 54, 78]],
        18 => ['blocks' => [[9, 69, 43, 26], [4, 70, 44, 26]], 'align' => [6, 30, 56, 82]],
        19 => ['blocks' => [[3, 70, 44, 26], [11, 71, 45, 26]], 'align' => [6, 30, 58, 86]],
        20 => ['blocks' => [[3, 67, 41, 26], [13, 68, 42, 26]], 'align' => [6, 34, 62, 90]],
        21 => ['blocks' => [[17, 68, 42, 26]], 'align' => [6, 28, 50, 72, 94]],
        22 => ['blocks' => [[17, 74, 46, 28]], 'align' => [6, 26, 50, 74, 98]],
        23 => ['blocks' => [[4, 75, 47, 28], [14, 76, 48, 28]], 'align' => [6, 30, 54, 78, 102]],
        24 => ['blocks' => [[6, 73, 45, 28], [14, 74, 46, 28]], 'align' => [6, 28, 54, 80, 106]],
        25 => ['blocks' => [[8, 75, 47, 28], [13, 76, 48, 28]], 'align' => [6, 32, 58, 84, 110]],
        26 => ['blocks' => [[19, 74, 46, 28], [4, 75, 47, 28]], 'align' => [6, 30, 58, 86, 114]],
        27 => ['blocks' => [[22, 73, 45, 28], [3, 74, 46, 28]], 'align' => [6, 34, 62, 90, 118]],
        28 => ['blocks' => [[3, 73, 45, 28], [23, 74, 46, 28]], 'align' => [6, 26, 50, 74, 98, 122]],
        29 => ['blocks' => [[21, 73, 45, 28], [7, 74, 46, 28]], 'align' => [6, 30, 54, 78, 102, 126]],
        30 => ['blocks' => [[19, 75, 47, 28], [10, 76, 48, 28]], 'align' => [6, 26, 52, 78, 104, 130]],
        31 => ['blocks' => [[2, 74, 46, 28], [29, 75, 47, 28]], 'align' => [6, 30, 56, 82, 108, 134]],
        32 => ['blocks' => [[10, 74, 46, 28], [23, 75, 47, 28]], 'align' => [6, 34, 60, 86, 112, 138]],
        33 => ['blocks' => [[14, 74, 46, 28], [21, 75, 47, 28]], 'align' => [6, 30, 58, 86, 114, 142]],
        34 => ['blocks' => [[14, 74, 46, 28], [23, 75, 47, 28]], 'align' => [6, 34, 62, 90, 118, 146]],
        35 => ['blocks' => [[12, 75, 47, 28], [26, 76, 48, 28]], 'align' => [6, 30, 54, 78, 102, 126, 150]],
        36 => ['blocks' => [[6, 75, 47, 28], [34, 76, 48, 28]], 'align' => [6, 24, 50, 76, 102, 128, 154]],
        37 => ['blocks' => [[29, 74, 46, 28], [14, 75, 47, 28]], 'align' => [6, 28, 54, 80, 106, 132, 158]],
        38 => ['blocks' => [[13, 74, 46, 28], [32, 75, 47, 28]], 'align' => [6, 32, 58, 84, 110, 136, 162]],
        39 => ['blocks' => [[40, 75, 47, 28], [7, 76, 48, 28]], 'align' => [6, 26, 54, 82, 110, 138, 166]],
        40 => ['blocks' => [[18, 75, 47, 28], [31, 76, 48, 28]], 'align' => [6, 30, 58, 86, 114, 142, 170]],
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
     * @param int $margin Quiet zone in modules (default: 4 per ISO/IEC 18004)
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
               '<path fill="#000000" d="' . trim($path_data) . '" />' .
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
     *
     * @param string $text
     * @return array|null 2D binary matrix
     */
    public function encode_matrix($text)
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
            // Byte mode overhead: 4 bits mode + (8 or 16 bits count indicator)
            $count_bits = ($ver <= 9) ? 8 : 16;
            $max_bytes = floor(($cap_data_words * 8 - 4 - $count_bits) / 8);
            if ($data_len <= $max_bytes) {
                $version = $ver;
                break;
            }
        }

        if ($version === null) {
            $version = 40;
        }

        $ver_info = self::$version_table[$version];
        $total_data_capacity = 0;
        foreach ($ver_info['blocks'] as $b) {
            $total_data_capacity += $b[0] * $b[2];
        }

        // 1. Build data bitstream
        $bits = '0100'; // Byte mode indicator
        $count_bits = ($version <= 9) ? 8 : 16;
        $bits .= str_pad(decbin($data_len), $count_bits, '0', STR_PAD_LEFT);
        foreach ($data_bytes as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        // 2. Terminator (up to 4 zeroes)
        $max_data_bits = $total_data_capacity * 8;
        $bits .= substr('0000', 0, max(0, min(4, $max_data_bits - strlen($bits))));

        // 3. Pad to multiple of 8 bits
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
        }

        // 4. Pad bytes (0xEC, 0x11)
        $pad_bytes = ['11101100', '00010001'];
        $pad_idx = 0;
        while (strlen($bits) < $max_data_bits) {
            $bits .= $pad_bytes[$pad_idx % 2];
            $pad_idx++;
        }

        // 5. Convert bit stream to codewords
        $data_words = [];
        $chunks = str_split($bits, 8);
        foreach ($chunks as $chunk) {
            $data_words[] = bindec($chunk);
        }

        // 6. Divide data into blocks and calculate Reed-Solomon EC for each block
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

        // 7. Interleave data codewords
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

        // 8. Interleave EC codewords
        $max_ec_len = count($blocks_ec[0]);
        for ($i = 0; $i < $max_ec_len; $i++) {
            foreach ($blocks_ec as $bec) {
                if ($i < count($bec)) {
                    $final_codewords[] = $bec[$i];
                }
            }
        }

        // 9. Build matrix grid
        $size = 17 + 4 * $version;
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // Function patterns: Finder
        $this->place_finder_pattern($matrix, $reserved, 0, 0);
        $this->place_finder_pattern($matrix, $reserved, 0, $size - 7);
        $this->place_finder_pattern($matrix, $reserved, $size - 7, 0);

        // Function patterns: Alignment
        if (!empty($ver_info['align'])) {
            $this->place_alignment_patterns($matrix, $reserved, $ver_info['align']);
        }

        // Function patterns: Timing
        $this->place_timing_patterns($matrix, $reserved, $size);

        // Reserve format info and version info areas
        $this->reserve_format_areas($reserved, $size);
        if ($version >= 7) {
            $this->reserve_version_areas($reserved, $size);
        }

        // Dark module (row = size - 8, col = 8)
        $matrix[$size - 8][8] = 1;
        $reserved[$size - 8][8] = true;

        // Place data bits into matrix
        $this->place_data_bits($matrix, $reserved, $final_codewords, $size);

        // Evaluate masks 0..7 and choose mask with lowest penalty score
        $best_mask = 0;
        $best_score = PHP_INT_MAX;
        $best_matrix = null;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate_matrix = $matrix;
            $this->apply_mask($candidate_matrix, $reserved, $mask, $size);
            $this->place_format_info($candidate_matrix, $mask, $size);
            if ($version >= 7) {
                $this->place_version_info($candidate_matrix, $version, $size);
            }
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
        // Generate Reed-Solomon generator polynomial (highest degree first)
        $gen = [1];
        for ($i = 0; $i < $ec_count; $i++) {
            $root = self::$gf_exp[$i];
            $new_gen = array_fill(0, count($gen) + 1, 0);
            for ($j = 0; $j < count($gen); $j++) {
                $new_gen[$j] ^= $gen[$j];
                $new_gen[$j + 1] ^= $this->gf_mul($gen[$j], $root);
            }
            $gen = $new_gen;
        }

        // Polynomial division
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
                if ($r >= 0 && $r <= 6 && ($c === 0 || $c === 6)) {
                    $matrix[$mr][$mc] = 1;
                } elseif ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6)) {
                    $matrix[$mr][$mc] = 1;
                } elseif ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4) {
                    $matrix[$mr][$mc] = 1;
                } else {
                    $matrix[$mr][$mc] = 0;
                }
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
                // Skip if already occupied by finder pattern
                if ($matrix[$r][$c] !== null) {
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

    protected function place_timing_patterns(&$matrix, &$reserved, $size)
    {
        for ($i = 8; $i < $size - 8; $i++) {
            if ($matrix[6][$i] === null) {
                $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
                $reserved[6][$i] = true;
            }
            if ($matrix[$i][6] === null) {
                $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
                $reserved[$i][6] = true;
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

    protected function reserve_version_areas(&$reserved, $size)
    {
        for ($r = 0; $r < 6; $r++) {
            for ($c = $size - 11; $c < $size - 8; $c++) {
                $reserved[$r][$c] = true;
            }
        }
        for ($r = $size - 11; $r < $size - 8; $r++) {
            for ($c = 0; $c < 6; $c++) {
                $reserved[$r][$c] = true;
            }
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
                $col--; // Skip vertical timing pattern column
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
        // Error Correction Level M = 00 in QR spec
        $ec_bits = 0b00;
        $data = ($ec_bits << 3) | $mask;

        // 10 error correction bits with generator poly 0x537 (BCH 15,5)
        $d = $data << 10;
        for ($i = 4; $i >= 0; $i--) {
            if ($d & (1 << ($i + 10))) {
                $d ^= (0x537 << $i);
            }
        }
        $format_info = (($data << 10) | $d) ^ 0x5412; // Mask with 0x5412

        for ($i = 0; $i < 15; $i++) {
            $bit = ($format_info >> $i) & 1;

            // Vertical (around top-left & bottom-left)
            if ($i < 6) {
                $matrix[$i][8] = $bit;
            } elseif ($i < 8) {
                $matrix[$i + 1][8] = $bit;
            } else {
                $matrix[$size - 15 + $i][8] = $bit;
            }

            // Horizontal (around bottom-left & top-right & top-left)
            if ($i < 8) {
                $matrix[8][$size - $i - 1] = $bit;
            } elseif ($i < 9) {
                $matrix[8][15 - $i] = $bit;
            } else {
                $matrix[8][14 - $i] = $bit;
            }
        }
    }

    protected function place_version_info(&$matrix, $version, $size)
    {
        // 12 error correction bits with generator poly 0x1F25 (BCH 18,6)
        $d = $version << 12;
        for ($i = 5; $i >= 0; $i--) {
            if ($d & (1 << ($i + 12))) {
                $d ^= (0x1F25 << $i);
            }
        }
        $version_info = ($version << 12) | $d;

        for ($i = 0; $i < 18; $i++) {
            $bit = ($version_info >> $i) & 1;
            $row_idx = (int)floor($i / 3);
            $col_idx = ($i % 3) + $size - 11;

            // Top-right
            $matrix[$row_idx][$col_idx] = $bit;

            // Bottom-left
            $matrix[$col_idx][$row_idx] = $bit;
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

        // Rule 3: 1:1:3:1:1 patterns (finder-like patterns)
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size - 6; $c++) {
                if ($matrix[$r][$c] === 1 &&
                    $matrix[$r][$c + 1] === 0 &&
                    $matrix[$r][$c + 2] === 1 &&
                    $matrix[$r][$c + 3] === 1 &&
                    $matrix[$r][$c + 4] === 1 &&
                    $matrix[$r][$c + 5] === 0 &&
                    $matrix[$r][$c + 6] === 1) {
                    if ($c >= 4 &&
                        $matrix[$r][$c - 1] === 0 &&
                        $matrix[$r][$c - 2] === 0 &&
                        $matrix[$r][$c - 3] === 0 &&
                        $matrix[$r][$c - 4] === 0) {
                        $penalty += 40;
                    }
                    if ($c + 10 < $size &&
                        $matrix[$r][$c + 7] === 0 &&
                        $matrix[$r][$c + 8] === 0 &&
                        $matrix[$r][$c + 9] === 0 &&
                        $matrix[$r][$c + 10] === 0) {
                        $penalty += 40;
                    }
                }
            }
        }

        for ($c = 0; $c < $size; $c++) {
            for ($r = 0; $r < $size - 6; $r++) {
                if ($matrix[$r][$c] === 1 &&
                    $matrix[$r + 1][$c] === 0 &&
                    $matrix[$r + 2][$c] === 1 &&
                    $matrix[$r + 3][$c] === 1 &&
                    $matrix[$r + 4][$c] === 1 &&
                    $matrix[$r + 5][$c] === 0 &&
                    $matrix[$r + 6][$c] === 1) {
                    if ($r >= 4 &&
                        $matrix[$r - 1][$c] === 0 &&
                        $matrix[$r - 2][$c] === 0 &&
                        $matrix[$r - 3][$c] === 0 &&
                        $matrix[$r - 4][$c] === 0) {
                        $penalty += 40;
                    }
                    if ($r + 10 < $size &&
                        $matrix[$r + 7][$c] === 0 &&
                        $matrix[$r + 8][$c] === 0 &&
                        $matrix[$r + 9][$c] === 0 &&
                        $matrix[$r + 10][$c] === 0) {
                        $penalty += 40;
                    }
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
