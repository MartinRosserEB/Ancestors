import $ from 'jquery';
import whatInput from 'what-input';

window.$ = $;

import Foundation from 'foundation-sites';
// If you want to pick and choose which modules to include, comment out the above and uncomment
// the line below
//import './lib/foundation-explicit-pieces';

$(document).ready(function() {
    $(document).foundation();
});

import dTree from 'd3-dtree';

window.dTree = dTree;

require('../scss/app.scss');
