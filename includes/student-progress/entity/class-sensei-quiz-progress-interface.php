<?php

interface Sensei_Quiz_Progress_Interface {
	public function start( array $metadata ): void;
	public function pass( array $metadata ): void;
	public function grade( array $metadata ): void;
	public function ungrade( array $metadata ): void;
	public function fail( array $metadata ): void;
	public function get_metadata(): array;
}
