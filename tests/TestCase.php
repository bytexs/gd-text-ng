<?php
declare(strict_types = 1);

namespace GDTextNg\Tests {

    use function file_get_contents;
    use function imagecreatefromstring;
    use function imagepng;
    use function ob_end_clean;
    use function ob_get_contents;
    use function ob_start;
    use function sha1;
    use function sha1_file;

    class TestCase
        extends \PHPUnit\Framework\TestCase {

        /**
         * @param $name
         *
         * @return resource
         */
        protected function openImageResource(string $name) {
            return imagecreatefromstring(file_get_contents(__DIR__ . '/images/' . $name));
        }

        /**
         * @param resource $im
         */
        protected function assertImageEquals(string $name, $im) {
            ob_start();
            imagepng($im);
            $sha1 = sha1(ob_get_contents());
            ob_end_clean();
            $this->assertEquals($this->sha1ImageResource($name), $sha1);
        }

        /**
         * @param $name
         */
        protected function sha1ImageResource($name): string {
            return sha1_file(__DIR__ . '/images/' . $name);
        }
    }
}