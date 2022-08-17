<?php

namespace GDTextNg\Struct {

    class Point {

        /**
         * Point constructor.
         */
        public function __construct(private readonly int $x, private readonly int $y) {}

        public function getX(): int {
            return $this->x;
        }

        public function getY(): int {
            return $this->y;
        }
    }
}