import { H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { render } from '@wordpress/element';
import { useSenseiColorTheme } from '../react-hooks/use-sensei-color-theme';

/**
 * Sensei export page.
 */
const SenseiExportPage = () => {
	useSenseiColorTheme();

	return (
		<div className="sensei-page-export">
			<section className="sensei-data-port-step">
				<header className="sensei-data-port-step__header">
					<H>
						{ __( 'Export content to a CSV file', 'sensei-lms' ) }
					</H>
					<p>
						{ __(
							'This tool enables you to generate and download one or more CSV files containing a list of all courses, ' +
								'lessons and quizzes, or questions. Separate CSV files are generated for each content type.',
							'sensei-lms'
						) }
					</p>
				</header>
			</section>
		</div>
	);
};

render( <SenseiExportPage />, document.getElementById( 'sensei-export-page' ) );
