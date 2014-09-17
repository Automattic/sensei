<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

/**
* Filter the translation string before it is displayed. Bit of a hack if nothing else exists
*
* Original code from http://blog.ftwr.co.uk/archives/2010/01/02/mangling-strings-for-fun-and-profit/
* 
* @param $translation The current translation
* @param $text The text being translated
* @param $context The context for the translation
* @param $domain The domain for the translation
* @return string The translated / filtered text.
*/
function imperial_sensei_filter_gettext( $translation, $text, $domain_context, $domain = false ) {
	if ( !$domain ) {
		$domain = $domain_context;
	}
	else {
		$context = $domain_context;
	}
	$translations = get_translations_for_domain( $domain );

	if ( 'woothemes-sensei' == $domain_context || 'sensei_modules' == $domain_context ) {
		if ( 'Please sign up for %1$sthe course%2$s before taking this quiz' == $text ) {
			return $translations->translate( 'Please click here to %1$sstart the course%2$s before taking this quiz', $domain_context );
		}
		if ( 'Congratulations! You have passed this lesson.' == $text ) {
			return $translations->translate( 'Activity marked as complete.', $domain_context );
		}
		if ( 'Congratulations! You have passed this lesson\'s quiz achieving %d%%' == $text ) {
			return $translations->translate( 'Activity marked as complete. You passed the quiz achieving %d%%', $domain_context );
		}
		if ( 'Congratulations! You have passed this quiz achieving %d%%' == $text ) {
			return $translations->translate( 'Activity marked as complete. You passed the quiz achieving %d%%', $domain_context );
		}
		if ( 'Sign Up' == $text ) {
			return $translations->translate( 'Start', $domain_context );
		}
		$orig_text = $text;
		if ( false !== stristr($text, 'module') ) {
			$text = $translations->translate( str_replace( array('Module', 'module'), array('Session', 'session'), $text ) );
		}
		if ( false !== stristr($text, 'lesson') ) {
			$text = $translations->translate( str_replace( array('Lessons', 'lessons', 'Lesson\'s', 'lesson\'s', 'Lesson', 'lesson'), array('Activities', 'activities', 'Activities', 'activities', 'Activity', 'activity'), $text ) );
		}
		if ( $text != $orig_text ) {
			$translation = $text;
		}
	}

	return $translation;
}
add_filter( 'gettext', 'imperial_sensei_filter_gettext', 10, 4);
add_filter( 'gettext_with_context', 'imperial_sensei_filter_gettext', 10, 4);
