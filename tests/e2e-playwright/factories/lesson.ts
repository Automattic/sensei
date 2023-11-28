export const lessonActionsBlock = `<!-- wp:sensei-lms/lesson-actions -->
	<div class="wp-block-sensei-lms-lesson-actions">
	<div class="sensei-buttons-container">
		<!-- wp:sensei-lms/button-view-quiz {"inContainer":true} -->
	<div class="wp-block-sensei-lms-button-view-quiz is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-view-quiz__wrapper">
	<div class="wp-block-sensei-lms-button-view-quiz is-style-default wp-block-sensei-button wp-block-button has-text-align-left">
	<button class="wp-block-button__link">Take Quiz</button>
	</div>
	</div>
	<!-- /wp:sensei-lms/button-view-quiz -->

	<!-- wp:sensei-lms/button-complete-lesson {"inContainer":true} -->
	<div class="wp-block-sensei-lms-button-complete-lesson is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-complete-lesson__wrapper">
	<div class="wp-block-sensei-lms-button-complete-lesson is-style-default wp-block-sensei-button wp-block-button has-text-align-left">
	<button class="wp-block-button__link sensei-stop-double-submission">Complete Lesson</button>
	</div>
	</div>
	<!-- /wp:sensei-lms/button-complete-lesson -->

	<!-- wp:sensei-lms/button-next-lesson {"inContainer":true} -->
	<div class="wp-block-sensei-lms-button-next-lesson is-style-default sensei-buttons-container__button-block wp-block-sensei-lms-button-next-lesson__wrapper">
	<div class="wp-block-sensei-lms-button-next-lesson is-style-default wp-block-sensei-button wp-block-button has-text-align-left">
	<button class="wp-block-button__link">Next Lesson</button>
	</div>
	</div>
	<!-- /wp:sensei-lms/button-next-lesson -->

	<!-- wp:sensei-lms/button-reset-lesson {"inContainer":true} -->
	<div class="wp-block-sensei-lms-button-reset-lesson is-style-outline sensei-buttons-container__button-block wp-block-sensei-lms-button-reset-lesson__wrapper">
	<div class="wp-block-sensei-lms-button-reset-lesson is-style-outline wp-block-sensei-button wp-block-button has-text-align-left">
	<button class="wp-block-button__link sensei-stop-double-submission">Reset Lesson</button>
	</div>
	</div>
	<!-- /wp:sensei-lms/button-reset-lesson -->
	</div>
	</div>
	<!-- /wp:sensei-lms/lesson-actions -->`;

export const quizBlock = `
<!-- wp:sensei-lms/quiz {"options":{"passRequired":false,"quizPassmark":0,"autoGrade":false,"allowRetakes":false,"showQuestions":null,"randomQuestionOrder":false,"failedIndicateIncorrect":true,"failedShowCorrectAnswers":true,"failedShowAnswerFeedback":true,"buttonTextColor":null,"buttonBackgroundColor":null,"pagination":{"paginationNumber":null,"showProgressBar":false,"progressBarRadius":6,"progressBarHeight":12,"progressBarColor":null,"progressBarBackground":null}},"isPostTemplate":true} /-->`;

export const text =
	'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi condimentum, ipsum sed varius fermentum, dui orci finibus erat, in fringilla ligula massa in ante. Nam pharetra bibendum nulla, nec efficitur ex aliquam in. Duis mauris metus, hendrerit a ullamcorper et, lobortis sed justo. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vivamus quis velit sed tellus ultricies fringilla in nec libero. Integer accumsan a nunc quis finibus. Aliquam ut vehicula nulla, a accumsan lacus. Integer ut metus ut est viverra finibus. Vivamus quis nulla venenatis, tempus risus nec, faucibus mi. Proin a dui in tellus pretium volutpat et eu libero. Nullam nec elit non lectus congue aliquam ut vel felis. Fusce hendrerit condimentum sem a blandit. Integer rhoncus ante massa, eget rhoncus tellus ultrices sit amet. Integer eget sem non ante ultrices lobortis. Quisque nibh nulla, lacinia in tortor sit amet, tincidunt porta est. Sed facilisis bibendum luctus.';

export const paragraph = `<!-- wp:paragraph {"placeholder":"Write lesson content..."} -->
<p>${ text }</p>
<!-- /wp:paragraph -->`;

export const lessonSimple = (): string => `
	${ paragraph }
	${ lessonActionsBlock }
`;

export const lessonNoLessonActions = (): string => `
	${ paragraph }
`;

export const lessonWithQuiz = (): string => `
	${ paragraph }
	${ lessonActionsBlock }
	${ quizBlock }
`;
