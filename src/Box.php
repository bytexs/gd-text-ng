<?php

namespace GDTextNg {

    use GDTextNg\Struct\Point;
    use GDTextNg\Struct\Rectangle;
    use InvalidArgumentException;
    use function explode;
    use function imagefilledrectangle;
    use function imagettfbbox;
    use function imagettftext;
    use function in_array;
    use function is_countable;
    use function preg_split;
    use function random_int;
    use function round;

    class Box {

        protected int        $strokeSize      = 0;
        protected Color      $strokeColor;
        protected int        $fontSize        = 12;
        protected Color      $fontColor;
        protected string     $alignX          = 'left';
        protected string     $alignY          = 'top';
        protected int        $textWrapping    = TextWrapping::WRAP_WITH_OVERFLOW;
        protected float      $lineHeight      = 1.25;
        protected float      $baseline        = 0.2;
        protected string     $fontFace;
        protected bool       $debug           = false;
        protected bool|array $textShadow      = false;
        protected bool|Color $backgroundColor = false;
        protected Rectangle  $box;

        /**
         * @param resource $im
         */
        public function __construct(protected $im) {
            $this->fontColor   = new Color(0, 0, 0);
            $this->strokeColor = new Color(0, 0, 0);
            $this->box         = new Rectangle(0, 0, 100, 100);
        }

        /**
         * @param Color $color Font color
         */
        public function setFontColor(Color $color): void {
            $this->fontColor = $color;
        }

        /**
         * @param string $path Path to the font file
         */
        public function setFontFace(string $path): void {
            $this->fontFace = $path;
        }

        /**
         * @param int $v Font size in *pixels*
         */
        public function setFontSize(int $v): void {
            $this->fontSize = $v;
        }

        /**
         * @param Color $color Stroke color
         */
        public function setStrokeColor(Color $color): void {
            $this->strokeColor = $color;
        }

        /**
         * @param int $v Stroke size in *pixels*
         */
        public function setStrokeSize(int $v): void {
            $this->strokeSize = $v;
        }

        /**
         * @param Color $color  Shadow color
         * @param int   $xShift Relative shadow position in pixels. Positive values move shadow to right, negative to left.
         * @param int   $yShift Relative shadow position in pixels. Positive values move shadow to bottom, negative to up.
         */
        public function setTextShadow(Color $color, int $xShift, int $yShift): void {
            $this->textShadow = [
                'color'  => $color,
                'offset' => new Point($xShift, $yShift),
            ];
        }

        /**
         * @param Color $color Font color
         */
        public function setBackgroundColor(Color $color): void {
            $this->backgroundColor = $color;
        }

        /**
         * Allows to customize spacing between lines.
         *
         * @param float $v Height of the single text line, in percents, proportionally to font size
         */
        public function setLineHeight(float $v): void {
            $this->lineHeight = $v;
        }

        /**
         * @param float $v Position of baseline, in percents, proportionally to line height measuring from the bottom.
         */
        public function setBaseline(float $v): void {
            $this->baseline = $v;
        }

        /**
         * Sets text alignment inside textbox
         *
         * @param string $x Horizontal alignment. Allowed values are: left, center, right.
         * @param string $y Vertical alignment. Allowed values are: top, center, bottom.
         */
        public function setTextAlign(string $x = 'left', string $y = 'top'): void {
            $xAllowed = [
                'left',
                'right',
                'center',
            ];
            $yAllowed = [
                'top',
                'bottom',
                'center',
            ];
            if (!in_array($x, $xAllowed)) {
                throw new InvalidArgumentException('Invalid horizontal alignment value was specified.');
            }
            if (!in_array($y, $yAllowed)) {
                throw new InvalidArgumentException('Invalid vertical alignment value was specified.');
            }
            $this->alignX = $x;
            $this->alignY = $y;
        }

        /**
         * Sets textbox position and dimensions
         *
         * @param int $x      Distance in pixels from left edge of image.
         * @param int $y      Distance in pixels from top edge of image.
         * @param int $width  Width of textbox in pixels.
         * @param int $height Height of textbox in pixels.
         */
        public function setBox(int $x, int $y, int $width, int $height): void {
            $this->box = new Rectangle($x, $y, $width, $height);
        }

        /**
         * Enables debug mode. Whole textbox and individual lines will be filled with random colors.
         */
        public function enableDebug(): void {
            $this->debug = true;
        }

        public function setTextWrapping(int $textWrapping): void {
            $this->textWrapping = $textWrapping;
        }

        /**
         * Draws the text on the picture.
         *
         * @param string $text Text to draw. May contain newline characters.
         */
        public function draw(string $text): void {
            if (!isset($this->fontFace)) {
                throw new InvalidArgumentException('No path to font file has been specified.');
            }
            $lines = match ($this->textWrapping) {
                TextWrapping::NO_WRAP => [$text],
                default               => $this->wrapTextWithOverflow($text),
            };
            if ($this->debug) {
                // Marks whole textbox area with color
                $this->drawFilledRectangle($this->box, new Color(random_int(180, 255), random_int(180, 255), random_int(180, 255), 80));
            }
            $lineHeightPx = $this->lineHeight * $this->fontSize;
            $textHeight   = count($lines) * $lineHeightPx;
            $yAlign       = match ($this->alignY) {
                VerticalAlignment::CENTER => ($this->box->getHeight() / 2) - ($textHeight / 2),
                VerticalAlignment::BOTTOM => $this->box->getHeight() - $textHeight,
                default                   => 0,
            };
            $n            = 0;
            foreach ($lines as $line) {
                $box    = $this->calculateBox($line);
                $xAlign = match ($this->alignX) {
                    HorizontalAlignment::CENTER => ($this->box->getWidth() - $box->getWidth()) / 2,
                    HorizontalAlignment::RIGHT  => $this->box->getWidth() - $box->getWidth(),
                    default                     => 0,
                };
                $yShift = $lineHeightPx * (1 - $this->baseline);
                // current line X and Y position
                $xMOD = $this->box->getX() + $xAlign;
                $yMOD = $this->box->getY() + $yAlign + $yShift + ($n * $lineHeightPx);
                if ($line && $this->backgroundColor) {
                    // Marks whole textbox area with given background-color
                    $backgroundHeight = $this->fontSize;
                    $this->drawFilledRectangle(new Rectangle(round($xMOD), round($this->box->getY() + $yAlign + ($n * $lineHeightPx) + ($lineHeightPx - $backgroundHeight) + (1 - $this->lineHeight) * 13 * (1 / 50 * $this->fontSize)), round($box->getWidth()), $backgroundHeight), $this->backgroundColor);
                }
                if ($this->debug) {
                    // Marks current line with color
                    $this->drawFilledRectangle(new Rectangle(round($xMOD), round($this->box->getY() + $yAlign + ($n * $lineHeightPx)), round($box->getWidth()), round($lineHeightPx)), new Color(random_int(1, 180), random_int(1, 180), random_int(1, 180)));
                }
                if ($this->textShadow !== false) {
                    $this->drawInternal(new Point(round($xMOD + $this->textShadow['offset']->getX()), round($yMOD + $this->textShadow['offset']->getY())), $this->textShadow['color'], $line);
                }
                $this->strokeText(round($xMOD), round($yMOD), $line);
                $this->drawInternal(new Point(round($xMOD), round($yMOD)), $this->fontColor, $line);
                $n++;
            }
        }

        /**
         * Splits overflowing text into array of strings.
         *
         * @return string[]
         */
        protected function wrapTextWithOverflow(string $text): array {
            $lines = [];
            // Split text explicitly into lines by \n, \r\n and \r
            $explicitLines = preg_split('/\n|\r\n?/', $text);
            foreach ($explicitLines as $line) {
                // Check every line if it needs to be wrapped
                $words      = explode(" ", (string)$line);
                $line       = $words[0];
                $wordsCount = is_countable($words) ? count($words) : 0;
                for ($i = 1; $i < $wordsCount; $i++) {
                    $box = $this->calculateBox($line . " " . $words[$i]);
                    if ($box->getWidth() >= $this->box->getWidth()) {
                        $lines[] = $line;
                        $line    = $words[$i];
                    } else {
                        $line .= " " . $words[$i];
                    }
                }
                $lines[] = $line;
            }
            return $lines;
        }

        protected function drawFilledRectangle(Rectangle $rect, Color $color): void {
            imagefilledrectangle($this->im, $rect->getLeft(), $rect->getTop(), $rect->getRight(), $rect->getBottom(), $color->getIndex($this->im));
        }

        /**
         * Returns the bounding box of a text.
         */
        protected function calculateBox(string $text): Rectangle {
            $bounds = imagettfbbox($this->getFontSizeInPoints(), 0, $this->fontFace, $text);
            $xLeft  = round($bounds[0]); // (lower|upper) left corner, X position
            $xRight = round($bounds[2]); // (lower|upper) right corner, X position
            $yLower = round($bounds[1]); // lower (left|right) corner, Y position
            $yUpper = round($bounds[5]); // upper (left|right) corner, Y position
            return new Rectangle($xLeft, $yUpper, $xRight - $xLeft, $yLower - $yUpper);
        }

        protected function drawInternal(Point $position, Color $color, $text): void {
            imagettftext($this->im, $this->getFontSizeInPoints(), 0, // no rotation
                         $position->getX(), $position->getY(), $color->getIndex($this->im), $this->fontFace, $text);
        }

        protected function strokeText(int $x, int $y, string $text): void {
            $size = $this->strokeSize;
            if ($size <= 0) {
                return;
            }
            for ($c1 = $x - $size; $c1 <= $x + $size; $c1++) {
                for ($c2 = $y - $size; $c2 <= $y + $size; $c2++) {
                    $this->drawInternal(new Point($c1, $c2), $this->strokeColor, $text);
                }
            }
        }

        protected function getFontSizeInPoints(): float {
            return 0.75 * $this->fontSize;
        }
    }
}