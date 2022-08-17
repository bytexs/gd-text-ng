<?php

namespace GDTextNg\Tests {

    use GDTextNg\Box;
    use GDTextNg\Color;
    use GDTextNg\HorizontalAlignment;
    use GDTextNg\VerticalAlignment;
    use function imagealphablending;
    use function imagesavealpha;
    use function imagesx;

    final class TextAlignmentTest
        extends TestCase {

        public function testAlignment() {
            $xList = [
                HorizontalAlignment::LEFT,
                HorizontalAlignment::CENTER,
                HorizontalAlignment::RIGHT,
            ];
            $yList = [
                VerticalAlignment::TOP,
                VerticalAlignment::CENTER,
                VerticalAlignment::BOTTOM,
            ];
            foreach ($yList as $y) {
                foreach ($xList as $x) {
                    $im  = $this->openImageResource('owl_png24.png');
                    $box = $this->mockBox($im);
                    $box->setTextAlign($x, $y);
                    $box->draw("Owls are birds from the order Strigiformes, which includes about 200 species.");
                    $this->assertImageEquals("test_align_{$y}_{$x}.png", $im);
                }
            }
        }

        protected function mockBox($im) {
            imagealphablending($im, true);
            imagesavealpha($im, true);
            $box = new Box($im);
            $box->setFontFace(__DIR__ . '/LinLibertine_R.ttf'); // http://www.dafont.com/franchise.font
            $box->setFontColor(new Color(255, 75, 140));
            $box->setFontSize(16);
            $box->setBackgroundColor(new Color(0, 0, 0));
            $box->setBox(0, 10, imagesx($im), 150);
            return $box;
        }
    }
}