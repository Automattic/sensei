<?php

interface Sensei_Lesson_Progress_Interface {
	public function start( array $metadata ): void;
	public function restart( array $metadata ): void;
	public function complete( array $metadata ): void;
	public function get_metadata(): array;
}
