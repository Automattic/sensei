<?php

/**
 * Marker exception used by the legacy quiz editor AJAX tests to abort a handler
 * at question-group creation, before its response rendering exits the process.
 */
class Sensei_Lesson_Quiz_Editor_Test_Abort extends Exception {}
