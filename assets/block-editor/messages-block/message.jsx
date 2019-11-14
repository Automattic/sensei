export default function( { message } ) {
	return (
		<div>
			<h3>{ message.post_title }</h3>
			{ message.post_content }
			<hr />
		</div>
	);
}
