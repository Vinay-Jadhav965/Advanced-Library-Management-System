<?php
// Simple barcode generator class
class BarcodeGenerator {
    
    /**
     * Generate barcode image using GD library
     */
    public function generateBarcode($text, $width = 200, $height = 50) {
        // Create image
        $image = imagecreate($width, $height);
        
        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Generate simple barcode pattern (Code 39 style)
        $barcode = $this->generateCode39($text);
        
        // Draw barcode
        $barWidth = $width / strlen($barcode);
        $x = 0;
        
        for ($i = 0; $i < strlen($barcode); $i++) {
            if ($barcode[$i] == '1') {
                imagefilledrectangle($image, $x, 10, $x + $barWidth - 1, $height - 15, $black);
            }
            $x += $barWidth;
        }
        
        // Add text below barcode
        $textWidth = imagefontwidth(2) * strlen($text);
        $textX = ($width - $textWidth) / 2;
        imagestring($image, 2, $textX, $height - 12, $text, $black);
        
        return $image;
    }
    
    /**
     * Save barcode as PNG
     */
    public function saveBarcode($text, $filename, $width = 200, $height = 50) {
        $image = $this->generateBarcode($text, $width, $height);
        imagepng($image, $filename);
        imagedestroy($image);
    }
    
    /**
     * Output barcode as PNG
     */
    public function outputBarcode($text, $width = 200, $height = 50) {
        $image = $this->generateBarcode($text, $width, $height);
        
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
    
    /**
     * Generate HTML barcode (for printing)
     */
    public function getBarcodeHTML($text, $width = 2, $height = 50) {
        $barcode = $this->generateCode39($text);
        $html = '<div style="font-family: monospace; font-size: ' . $height . 'px; line-height: 1;">';
        
        for ($i = 0; $i < strlen($barcode); $i++) {
            if ($barcode[$i] == '1') {
                $html .= '<span style="background: black; color: black;">' . str_repeat('&#9608;', $width) . '</span>';
            } else {
                $html .= '<span style="color: white;">' . str_repeat('&#9608;', $width) . '</span>';
            }
        }
        
        $html .= '</div>';
        $html .= '<div style="text-align: center; font-family: monospace; font-size: 12px;">' . htmlspecialchars($text) . '</div>';
        
        return $html;
    }
    
    /**
     * Simple Code 39 pattern generator
     */
    private function generateCode39($text) {
        // Code 39 encoding patterns (simplified)
        $patterns = [
            '0' => '101001101101',
            '1' => '110100101011',
            '2' => '101100101011',
            '3' => '110110010101',
            '4' => '101001101011',
            '5' => '110100110101',
            '6' => '101100110101',
            '7' => '101001011011',
            '8' => '110100101101',
            '9' => '101100101101',
            'A' => '110101001011',
            'B' => '101101001011',
            'C' => '110110100101',
            'D' => '101011001011',
            'E' => '110101100101',
            'F' => '101101100101',
            'G' => '101010011011',
            'H' => '110101001101',
            'I' => '101101001101',
            'J' => '101011001101',
            'K' => '110101010011',
            'L' => '101101010011',
            'M' => '110110101001',
            'N' => '101011010011',
            'O' => '110101101001',
            'P' => '101101101001',
            'Q' => '101010110011',
            'R' => '110101011001',
            'S' => '101101011001',
            'T' => '101011011001',
            'U' => '110010101011',
            'V' => '100110101011',
            'W' => '110011010101',
            'X' => '100101101011',
            'Y' => '110010110101',
            'Z' => '100110110101',
            '-' => '100101011011',
            '.' => '110010101101',
            ' ' => '100110101101',
            '$' => '100100100101',
            '/' => '100100101001',
            '+' => '100101001001',
            '%' => '101001001001',
            '*' => '100101101101' // Start/Stop character
        ];
        
        $text = strtoupper($text);
        $barcode = '*'; // Start character
        
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (isset($patterns[$char])) {
                $barcode .= $patterns[$char] . '0'; // Add inter-character gap
            }
        }
        
        $barcode .= '*'; // Stop character
        return $barcode;
    }
}
?>
