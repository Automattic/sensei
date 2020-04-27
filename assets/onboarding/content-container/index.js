import { useQueryStringRouter } from '../query-string-router';

const ContentContainer = () => {
	const { currentContainer } = useQueryStringRouter();

	return (
		<div className="sensei-onboarding__container">{ currentContainer }</div>
	);
};

export default ContentContainer;
