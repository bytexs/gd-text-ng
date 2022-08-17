<?php

namespace GDTextNg\Tests {

    use GDTextNg\Box;
    use GDTextNg\Color;
    use GDTextNg\TextWrapping;
    use function imagealphablending;
    use function imagesavealpha;

    final class TextWrappingTest
        extends TestCase {

        public function testWrapWithOverflow() {
            $im  = $this->openImageResource('owl_png24.png');
            $box = $this->mockBox($im);
            $box->setTextWrapping(TextWrapping::WRAP_WITH_OVERFLOW);
            $box->draw("Owls are birds from the order Strigiformes, which includes about 200 species.");
            $this->assertImageEquals('test_wrap_WrapWithOverflow.png', $im);
        }

        protected function mockBox($im) {
            imagealphablending($im, true);
            imagesavealpha($im, true);
            $box = new Box($im);
            $box->setFontFace(__DIR__ . '/LinLibertine_R.ttf'); // http://www.dafont.com/franchise.font
            $box->setFontColor(new Color(255, 75, 140));
            $box->setFontSize(16);
            $box->setBox(0, 135, imagesx($im), 70);
            $box->setTextAlign('left', 'top');
            return $box;
        }

        public function testNoWrap() {
            $im  = $this->openImageResource('owl_png24.png');
            $box = $this->mockBox($im);
            $box->setTextWrapping(TextWrapping::NO_WRAP);
            $box->draw("Owls are birds from the order Strigiformes, which includes about 200 species.");
            $this->assertImageEquals('test_wrap_NoWrap.png', $im);
        }
    }
}