const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { withAPIData } = wp.components;

import './style.scss';
import { find, get } from 'lodash';

registerBlockType( 'sensei/module', {
	title: 'Module',
	icon: 'list-view',
	category: 'widgets',
	supportHTML: false,
	attributes: {
		moduleId: { type: 'number' },
	},

	edit: withAPIData( props => {
		const moduleId = get( props, [ 'attributes', 'moduleId' ] );

		return {
			module: `/sensei/v1/modules${ moduleId ? '/' + moduleId: '' }`,
			modules: '/sensei/v1/modules',
		};
	} )( props => {
		const moduleId = get( props, [ 'attributes', 'moduleId' ] );
		const module = get( props, [ 'module', 'data' ], [] );
		const modules = get( props, [ 'modules', 'data' ], [] );

		const handleModuleChange = event => props.setAttributes( { moduleId: event.target.value } );

		return (
			<div>
				{ modules &&
				<form>
					<select onChange={ handleModuleChange }>
						<option value="-1" selected={ moduleId === -1 }>{ __( 'Select a Module' ) }</option>
						{ modules.map( ( { id, name } ) => (
							<option
								selected={ moduleId === id }
								value={ id }>
								{ name }
							</option>
						) ) }
					</select>
				</form>
				}

				{ module &&
					<article className="module">
						{ module.name &&
							<header>
								<h2>
									<a href="#" title={ module.name }>
										{ module.name }
									</a>
								</h2>
							</header>
						}

						{ module.description &&
							<section className="entry">
								<p className="module-description">
									{ module.description }
								</p>
							</section>
						}
					</article>
				}
			</div>
		);
	} ),

	save: () => {
		return null;
	}
} );
