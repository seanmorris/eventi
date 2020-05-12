import { Tag     }  from 'curvature/base/Tag';
import { RuleSet }  from 'curvature/base/RuleSet';
import { Router   } from 'curvature/base/Router';
import { HomeView } from './home/HomeView';

document.addEventListener('DOMContentLoaded', () => {
	const view = new HomeView;
	RuleSet.add('body', view);
	RuleSet.apply();
	Router.listen(view);
});
