/**
 * Internal dependencies
 */
import { onTimeupdate } from './youtube-adapter';

describe( 'youtube-adapter', () => {
	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'reports the full duration once within a second of the end even if the ENDED state never fires', () => {
		jest.useFakeTimers();

		const duration = 100;
		const w = { YT: { PlayerState: { ENDED: 0 } } };
		const player = {
			getPlayerState: () => 1, // PLAYING; the ENDED state (0) never arrives.
			getCurrentTime: () => duration - 0.5,
			getDuration: () => duration,
			addEventListener: () => {},
			removeEventListener: () => {},
		};
		const callback = jest.fn();

		const unsubscribe = onTimeupdate( player, callback, w );
		jest.advanceTimersByTime( 250 );

		expect( callback ).toHaveBeenCalledWith( duration );

		unsubscribe();
	} );
} );
