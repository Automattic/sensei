<?php

list() = 5;
list(, ,) = 5;
list(/*comment*/) = 5;
list( /*comment*/ /*another comment*/ ) = 5;
list(,(),) = 5;


/*
 * The below list assignments are all valid.
 */
list( $item, $anotherItem ) = $infoArray;
list($drink, , $power) = $infoArray;
list(, , $power) = $infoArray;
list($a[0], $a[1], $a[2]) = $infoArray;
list( ${$drink} ) = $infoArray;

// Don't trigger on unfinished code during live code review.
list(
